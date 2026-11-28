#!/usr/bin/env python3
"""Mide el desempeño de cada estudiante en los repositorios de grupo del ePortfolio.

Lee el registro de auditoría de la organización (exportado en JSON desde
https://github.com/organizations/<org>/settings/audit-log), la relación de estudiantes y la
planificación de las tareas (planificacion.toml, con las fechas que calcula
planificar_eportfolio.py), y genera:

  desempeno_estudiantes.csv   una fila por estudiante: actividad, aportación y notas
  desempeno_por_tarea.csv     una fila por estudiante y tarea
  eventos_atribuidos.csv      cada evento contado, a quién y cuántos puntos (para revisar)
  informe.html                el informe con los gráficos (un único fichero)

Cómo se puntúa
--------------
1. Cada evento suma puntos a quien lo hace (PR creado, revisión, merge...) y, en el caso de la
   integración de un PR, también a su autor. Los puntos de cada tipo se cambian con
   --pesos-eventos.
2. Cada PR se asigna a una tarea por el código RAx_y de su título (RA5_6 → apartado 5.6); si no
   lo tiene, por la fecha de creación del PR. Los eventos sin PR van por su fecha.
3. Nota de actividad de una tarea = 10 × (puntos del estudiante / puntos de su grupo) × nº de
   miembros del grupo, con un máximo de 10. Quien aporta su parte proporcional (o más) saca 10.
4. Nota de tests de una tarea (si se pasa --tests) = media de las notas de los PR de los que
   el estudiante es autor en esa tarea; sin PR propio, 0. --tests admite tal cual el
   notas_detalle.csv de recoger_notas.py.
5. Nota de la tarea = peso_tests × tests + (1 − peso_tests) × actividad (solo actividad si
   esa tarea no tiene notas de tests). Nota final = media de las tareas ponderada por sus horas.

Uso
---
    python3 desempeno_github.py export-audit-log.json \\
        --estudiantes estudiantes.csv \\
        --planificacion ../planificacion/planificacion.toml \\
        [--tests notas_detalle.csv] [--exclude profe1,profe2] \\
        [--from 2026-11-02] [--to 2026-12-18] [--salida informe_desempeno]

Necesita Python 3.11 o posterior y matplotlib (pip install matplotlib). Usa las funciones de
planificar_eportfolio.py, que debe estar junto a planificacion.toml (o indicarse con
--planificador), para que las fechas coincidan con las de los proyectos de GitHub.
"""

from __future__ import annotations

import argparse
import base64
import csv
import gzip
import html
import importlib.util
import io
import json
import os
import re
import subprocess
import sys
import tomllib
import urllib.error
import urllib.request
from collections import Counter, defaultdict
from dataclasses import dataclass, field
from datetime import date, datetime, timedelta
from pathlib import Path
from zoneinfo import ZoneInfo

ZONA = ZoneInfo('Europe/Madrid')

# Conceptos que se puntúan, en el orden en que aparecen en tablas y gráficos.
CONCEPTOS = {
    'pr_creado':           ('PR creados',               3.0),
    'pr_integrado':        ('PR propios integrados',    3.0),
    'revision':            ('Revisiones enviadas',      2.0),
    'comentario_revision': ('Comentarios de revisión',  1.0),
    'merge_realizado':     ('Merges realizados',        1.0),
    'commit':              ('Commits (API)',            1.0),
    'push':                ('Pushes',                   0.5),
    'solicitud_revision':  ('Solicitudes de revisión',  0.5),
    'pr_cerrado':          ('PR cerrados sin integrar', 0.0),
    'otras':               ('Otras acciones',           0.0),
}

RE_CODIGO = re.compile(r'RA\s*(\d+)(?:[\s_.-]*(\d+))?', re.IGNORECASE)
RE_PR = re.compile(r'github\.com/([^/]+/[^/]+)/pull/(\d+)')


# --------------------------------------------------------------------------------------------
# Datos de entrada
# --------------------------------------------------------------------------------------------

def leer_log(ruta: Path) -> list[dict]:
    """Admite un array JSON o un evento por línea (NDJSON), comprimido en .gz o no."""
    abrir = gzip.open if ruta.suffix == '.gz' else open
    with abrir(ruta, 'rt', encoding='utf-8-sig') as f:
        texto = f.read().strip()
    if not texto:
        return []
    if texto.startswith('['):
        return json.loads(texto)
    eventos = []
    for n, linea in enumerate(texto.splitlines(), 1):
        linea = linea.strip()
        if linea:
            try:
                eventos.append(json.loads(linea))
            except json.JSONDecodeError as e:
                print(f'Aviso: línea {n} del log no es JSON válido ({e}); se ignora.', file=sys.stderr)
    return eventos


def leer_csv_flexible(ruta: Path) -> list[dict[str, str]]:
    """Lee un CSV con separador , o ; y en UTF-8 o en la codificación de Excel en Windows."""
    for codificacion in ('utf-8-sig', 'cp1252'):
        try:
            texto = ruta.read_text(encoding=codificacion)
            break
        except UnicodeDecodeError:
            continue
    try:
        dialecto = csv.Sniffer().sniff(texto.splitlines()[0], delimiters=',;\t')
        separador = dialecto.delimiter
    except (csv.Error, IndexError):
        separador = ','
    lector = csv.DictReader(io.StringIO(texto), delimiter=separador, skipinitialspace=True)
    filas = []
    for fila in lector:
        filas.append({(k or '').strip().lower(): (v or '').strip() for k, v in fila.items()})
    return filas


def columna(fila: dict, *nombres: str) -> str:
    for nombre in nombres:
        if fila.get(nombre):
            return fila[nombre]
    return ''


@dataclass
class Estudiante:
    login: str
    nombre: str
    grupo: str = ''


def leer_estudiantes(ruta: Path) -> dict[str, Estudiante]:
    estudiantes = {}
    for fila in leer_csv_flexible(ruta):
        login = columna(fila, 'usuario_github', 'usuario', 'login', 'github').lstrip('@')
        if login:
            estudiantes[login.lower()] = Estudiante(
                login, columna(fila, 'alumno', 'nombre', 'estudiante') or login,
                columna(fila, 'grupo').lower().removeprefix('g'))
    if not estudiantes:
        sys.exit(f'Error: {ruta} no tiene la columna usuario_github o está vacío.')
    return estudiantes


def leer_tests(ruta: Path) -> dict[str, float]:
    """URL del PR → nota sobre 10. Columnas: pull_request_url (o url, pr) y nota (y, si la nota
    no es sobre 10, maximo). También vale repo (o repositorio) + numero (o pr) en lugar de la URL.

    Lee tal cual el notas_detalle.csv de recoger_notas.py: de sus filas, una por clase de test,
    solo cuentan las de tipo "ejercicio" (las "anterior" son los tests de los issues anteriores).
    Si un PR tiene varias, por haberse corregido varias veces, cuenta la de fecha más reciente."""
    notas: dict[str, tuple[str, float]] = {}
    for fila in leer_csv_flexible(ruta):
        if fila.get('tipo') and fila['tipo'] != 'ejercicio':
            continue
        url = columna(fila, 'pull_request_url', 'url', 'pr_url', 'pr')
        repo = columna(fila, 'repo', 'repositorio')
        numero = columna(fila, 'numero', 'number', 'pr')
        if not RE_PR.search(url) and repo and numero:
            url = f'https://github.com/{repo}/pull/{numero}'
        coincidencia = RE_PR.search(url)
        valor = columna(fila, 'nota', 'calificacion', 'puntuacion', 'score')
        if not coincidencia or not valor:
            continue
        try:
            nota = float(valor.replace(',', '.'))
            maximo = float((columna(fila, 'maximo', 'max') or '10').replace(',', '.'))
        except ValueError:
            continue
        clave = f'{coincidencia.group(1).lower()}#{coincidencia.group(2)}'
        fecha = columna(fila, 'fecha')
        if clave not in notas or fecha >= notas[clave][0]:
            notas[clave] = (fecha, max(0.0, min(10.0, 10 * nota / maximo)))
    return {clave: nota for clave, (_, nota) in notas.items()}


# --------------------------------------------------------------------------------------------
# Las tareas, desde planificacion.toml
# --------------------------------------------------------------------------------------------

@dataclass
class Tarea:
    numero: str
    titulo: str
    horas: int
    planteamiento: date
    limite: date
    codigos: set[str] = field(default_factory=set)
    orden: int = 0

    @property
    def etiqueta(self) -> str:
        return self.numero if not self.numero.startswith('Bloque') else 'B' + self.numero.split()[-1]


def leer_tareas(toml: Path, planificador: Path | None) -> tuple[list[Tarea], list[str]]:
    planificador = planificador or toml.with_name('planificar_eportfolio.py')
    if not planificador.exists():
        sys.exit(f'Error: no encuentro {planificador}. Indica dónde está con --planificador.')
    spec = importlib.util.spec_from_file_location('planificar_eportfolio', planificador)
    modulo = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(modulo)
    with open(toml, 'rb') as f:
        datos = tomllib.load(f)
    tramos, avisos = modulo.planificar(datos, modulo.Calendario(datos['calendario']))
    tareas = []
    for i, t in enumerate(tramos):
        tarea = Tarea(t.numero, t.titulo, t.horas, t.planteamiento, t.limite, orden=i)
        issue = (t.apartado or {}).get('issue', '')
        if m := RE_CODIGO.match(issue):
            tarea.codigos.add(f'{m.group(1)}_{m.group(2)}' if m.group(2) else m.group(1))
        tareas.append(tarea)
    # Los apartados sin issue se reconocen por su número (5.1 → RA5_1), si nadie lo tiene ya.
    usados = {c for t in tareas for c in t.codigos}
    for t in tareas:
        partes = t.numero.split('.')
        if not t.numero.startswith('Bloque') and len(partes) == 2:
            codigo = '_'.join(partes)
            if codigo not in usados:
                t.codigos.add(codigo)
                usados.add(codigo)
        elif t.numero.startswith('Bloque'):
            t.codigos.add(t.numero.split()[-1])   # RA6_x → Bloque 6, si no tiene apartados
    return tareas, avisos


def tarea_por_titulo(titulo: str, tareas: list[Tarea]) -> Tarea | None:
    for m in RE_CODIGO.finditer(titulo or ''):
        bloque, apartado = m.group(1), m.group(2)
        for codigo in ([f'{bloque}_{apartado}'] if apartado else []) + [bloque]:
            for t in tareas:
                if codigo in t.codigos:
                    return t
    return None


def tarea_por_fecha(dia: date, tareas: list[Tarea]) -> Tarea | None:
    """La tarea que estaba planteada ese día: desde su planteamiento hasta el de la siguiente."""
    actual = None
    for t in sorted(tareas, key=lambda t: t.planteamiento):
        if t.planteamiento <= dia:
            actual = t
    return actual


# --------------------------------------------------------------------------------------------
# Los eventos
# --------------------------------------------------------------------------------------------

def instante(valor) -> datetime | None:
    if isinstance(valor, (int, float)):
        return datetime.fromtimestamp(valor / 1000, ZONA)
    if isinstance(valor, str) and valor:
        try:
            return datetime.fromisoformat(valor.replace('Z', '+00:00')).astimezone(ZONA)
        except ValueError:
            return None
    return None


def concepto_de(accion: str) -> str:
    return {
        'pull_request.create': 'pr_creado',
        'pull_request.merge': 'merge_realizado',
        'pull_request.close': 'pr_cerrado',
        'pull_request_review.submit': 'revision',
        'pull_request.create_review_request': 'solicitud_revision',
        'pull_request_review_comment.create': 'comentario_revision',
        'git.push': 'push',
    }.get(accion, 'otras')


@dataclass
class Credito:
    momento: datetime
    login: str
    concepto: str
    repo: str
    grupo: str
    accion: str
    pr: str = ''
    titulo: str = ''
    tarea: Tarea | None = None
    fuera_de_plazo: bool = False


def es_bot(login: str, evento: dict) -> bool:
    return bool(evento.get('actor_is_bot')) or login.endswith('[bot]')


def atribuir(eventos: list[dict], patron: re.Pattern, tareas: list[Tarea]) -> tuple[list[Credito], dict]:
    # 1. Quién es cada id: el log trae "actor" en el original, y además user/reviewer con su id.
    nombres: dict[int, str] = {}
    for e in eventos:
        for clave_id, clave_login in (('actor_id', 'actor'), ('user_id', 'user'), ('reviewer_id', 'reviewer')):
            if e.get(clave_id) and e.get(clave_login):
                nombres.setdefault(e[clave_id], e[clave_login])

    def login_actor(e: dict) -> str:
        return e.get('actor') or nombres.get(e.get('actor_id'), f"id:{e.get('actor_id')}")

    # 2. Cada PR: autor, título, fecha de creación y tarea.
    prs: dict[str, dict] = {}
    for e in eventos:
        url = e.get('pull_request_url') or ''
        m = RE_PR.search(url)
        if not m:
            continue
        pr = prs.setdefault(f'{m.group(1).lower()}#{m.group(2)}', {'url': url})
        pr.setdefault('titulo', e.get('pull_request_title', ''))
        momento = instante(e.get('@timestamp') or e.get('created_at'))
        if e.get('action') == 'pull_request.create':
            pr['autor'] = login_actor(e)
            pr['creado'] = momento
        elif e.get('action', '').startswith('pull_request.') and e.get('user'):
            pr.setdefault('autor', e['user'])
        if momento and (not pr.get('primero') or momento < pr['primero']):
            pr['primero'] = momento
    for pr in prs.values():
        inicio = pr.get('creado') or pr.get('primero')
        pr['tarea'] = tarea_por_titulo(pr['titulo'], tareas) or (
            tarea_por_fecha(inicio.date(), tareas) if inicio else None)

    # 3. Los créditos.
    creditos: list[Credito] = []
    integrados: set[str] = set()
    solicitudes: set[tuple] = set()
    for e in sorted(eventos, key=lambda e: e.get('@timestamp') or e.get('created_at') or 0):
        m_repo = patron.match(e.get('repo') or '')
        momento = instante(e.get('@timestamp') or e.get('created_at'))
        if not m_repo or not momento:
            continue
        grupo = m_repo.group(1) if m_repo.groups() else e['repo']
        accion = e.get('action', '')
        actor = login_actor(e)
        m_pr = RE_PR.search(e.get('pull_request_url') or '')
        clave_pr = f'{m_pr.group(1).lower()}#{m_pr.group(2)}' if m_pr else ''
        pr = prs.get(clave_pr, {})
        base = dict(momento=momento, repo=e['repo'], grupo=grupo, accion=accion,
                    pr=pr.get('url', ''), titulo=pr.get('titulo', ''),
                    tarea=pr.get('tarea') or tarea_por_fecha(momento.date(), tareas))

        if accion == 'pull_request.create_review_request':
            # Una solicitud a varios revisores genera un evento por revisor: se cuenta una vez.
            clave = (e.get('request_id'), clave_pr, actor)
            if clave in solicitudes:
                continue
            solicitudes.add(clave)

        if accion in ('pull_request.merge', 'pull_request.indirect_merge'):
            if accion == 'pull_request.merge' and not es_bot(actor, e):
                creditos.append(Credito(login=actor, concepto='merge_realizado', **base))
            autor = pr.get('autor') or e.get('user')
            if autor and clave_pr not in integrados:
                integrados.add(clave_pr)
                creditos.append(Credito(login=autor, concepto='pr_integrado', **base))
            continue

        if es_bot(actor, e):
            continue
        creditos.append(Credito(login=actor, concepto=concepto_de(accion), **base))

    for c in creditos:
        c.fuera_de_plazo = bool(c.tarea and c.momento.date() > c.tarea.limite)
    return creditos, prs


def token_de_gh() -> str | None:
    try:
        return subprocess.run(['gh', 'auth', 'token'], capture_output=True, text=True,
                              check=True).stdout.strip() or None
    except (OSError, subprocess.CalledProcessError):
        return None


def commits_por_api(prs: dict, patron: re.Pattern, token: str, cache: Path) -> list[tuple]:
    """(momento, login, repo, clave_pr) de cada commit de los PR del log, sin repetir SHA."""
    datos = json.loads(cache.read_text()) if cache.exists() else {}
    resultado, vistos = [], set()
    for clave, pr in prs.items():
        repo, numero = clave.split('#')
        if not patron.match(pr['url'].split('github.com/')[1].rsplit('/pull/')[0]):
            continue
        if clave not in datos:
            commits, pagina = [], 1
            while True:
                url = f'https://api.github.com/repos/{repo}/pulls/{numero}/commits?per_page=100&page={pagina}'
                peticion = urllib.request.Request(url, headers={
                    'Authorization': f'Bearer {token}', 'Accept': 'application/vnd.github+json',
                    'X-GitHub-Api-Version': '2022-11-28'})
                try:
                    with urllib.request.urlopen(peticion, timeout=30) as r:
                        lote = json.load(r)
                except urllib.error.HTTPError as e:
                    if e.code in (401, 403):
                        sys.exit(f'Error: GitHub rechaza el token ({e.code}). Comprueba que tiene '
                                 'permiso de lectura sobre los repositorios de la organización.')
                    print(f'Aviso: no se pudieron leer los commits de {pr["url"]} ({e.code}).', file=sys.stderr)
                    lote = []
                commits += [{'sha': c['sha'], 'login': (c.get('author') or {}).get('login'),
                             'fecha': c['commit']['author']['date']} for c in lote]
                if len(lote) < 100 or pagina == 3:
                    break
                pagina += 1
            datos[clave] = commits
        for c in datos[clave]:
            if c['login'] and (repo, c['sha']) not in vistos:
                vistos.add((repo, c['sha']))
                resultado.append((instante(c['fecha']), c['login'], repo, clave))
    cache.write_text(json.dumps(datos))
    return resultado


# --------------------------------------------------------------------------------------------
# El cálculo
# --------------------------------------------------------------------------------------------

def calcular(creditos, estudiantes, tareas, puntos, notas_tests, prs, peso_tests):
    por_login = {e.login.lower(): e for e in estudiantes.values()}
    # El grupo de cada estudiante: el del CSV o, si no lo trae, donde más actividad tiene.
    actividad_grupo = defaultdict(Counter)
    for c in creditos:
        actividad_grupo[c.login.lower()][c.grupo] += 1
    for clave, est in por_login.items():
        if not est.grupo:
            est.grupo = actividad_grupo[clave].most_common(1)[0][0] if actividad_grupo[clave] else '?'
    miembros = Counter(e.grupo for e in por_login.values())

    tareas_usadas = sorted({c.tarea.orden for c in creditos if c.tarea})
    tareas_usadas = [t for t in tareas if t.orden in tareas_usadas]

    cuentas = defaultdict(Counter)            # login → concepto → nº
    pts = defaultdict(float)                  # (login, tarea) → puntos
    pts_grupo = defaultdict(float)            # (grupo, tarea) → puntos
    tarde = Counter()
    for c in creditos:
        clave = c.login.lower()
        if clave not in por_login:
            continue
        cuentas[clave][c.concepto] += 1
        if c.fuera_de_plazo and c.concepto == 'pr_creado':
            tarde[clave] += 1
        if c.tarea and c.grupo == por_login[clave].grupo:
            p = puntos.get(c.concepto, 0.0)
            pts[clave, c.tarea.orden] += p
            pts_grupo[c.grupo, c.tarea.orden] += p

    # Notas de tests: media de los PR propios de cada tarea.
    tests_est = defaultdict(list)
    tareas_con_tests = defaultdict(set)       # grupo → tareas con alguna nota
    for clave_pr, nota in notas_tests.items():
        pr = prs.get(clave_pr)
        if not pr or not pr.get('tarea') or not pr.get('autor'):
            continue
        autor = pr['autor'].lower()
        if autor in por_login:
            tests_est[autor, pr['tarea'].orden].append(nota)
            tareas_con_tests[por_login[autor].grupo].add(pr['tarea'].orden)

    filas_tarea, filas = [], []
    for clave, est in sorted(por_login.items(), key=lambda kv: (kv[1].grupo, kv[1].nombre.lower())):
        n = miembros[est.grupo]
        suma, horas, detalle = 0.0, 0, {}
        for t in tareas_usadas:
            total = pts_grupo[est.grupo, t.orden]
            propio = pts[clave, t.orden]
            cuota = propio / total if total else 0.0
            nota_act = min(10.0, 10 * cuota * n) if total else 0.0
            lista = tests_est.get((clave, t.orden))
            hay_tests = t.orden in tareas_con_tests[est.grupo]
            nota_tests = (sum(lista) / len(lista) if lista else 0.0) if hay_tests else None
            nota = (peso_tests * nota_tests + (1 - peso_tests) * nota_act) if hay_tests else nota_act
            suma += nota * t.horas
            horas += t.horas
            detalle[t.orden] = nota
            filas_tarea.append({
                'grupo': est.grupo, 'alumno': est.nombre, 'usuario_github': est.login,
                'tarea': t.numero, 'titulo_tarea': t.titulo, 'horas': t.horas,
                'puntos': propio, 'puntos_grupo': total, 'aportacion_pct': 100 * cuota,
                'nota_actividad': nota_act, 'nota_tests': nota_tests, 'nota_tarea': nota})
        fila = {'grupo': est.grupo, 'alumno': est.nombre, 'usuario_github': est.login,
                'miembros_grupo': n}
        for concepto, (nombre, _) in CONCEPTOS.items():
            fila[concepto] = cuentas[clave][concepto]
        fila['pr_fuera_de_plazo'] = tarde[clave]
        fila['puntos'] = sum(pts[clave, t.orden] for t in tareas_usadas)
        total_g = sum(pts_grupo[est.grupo, t.orden] for t in tareas_usadas)
        fila['aportacion_pct'] = 100 * fila['puntos'] / total_g if total_g else 0.0
        notas_a = [f['nota_actividad'] for f in filas_tarea if f['usuario_github'] == est.login]
        notas_t = [f['nota_tests'] for f in filas_tarea if f['usuario_github'] == est.login
                   and f['nota_tests'] is not None]
        horas_a = [t.horas for t in tareas_usadas]
        fila['nota_actividad'] = (sum(a * h for a, h in zip(notas_a, horas_a)) / sum(horas_a)) if horas_a else 0.0
        fila['nota_tests'] = sum(notas_t) / len(notas_t) if notas_t else None
        for t in tareas_usadas:
            fila[f'nota_{t.numero}'] = detalle[t.orden]
        fila['nota_final'] = suma / horas if horas else 0.0
        filas.append(fila)
    return filas, filas_tarea, tareas_usadas


# --------------------------------------------------------------------------------------------
# Salida: CSV
# --------------------------------------------------------------------------------------------

def guardar_csv(ruta: Path, filas: list[dict], separador: str) -> None:
    if not filas:
        return
    decimal = ',' if separador == ';' else '.'

    def formato(v):
        if v is None:
            return ''
        if isinstance(v, float):
            return f'{v:.2f}'.replace('.', decimal)
        return v

    with open(ruta, 'w', encoding='utf-8-sig', newline='') as f:
        escritor = csv.DictWriter(f, fieldnames=list(filas[0]), delimiter=separador)
        escritor.writeheader()
        for fila in filas:
            escritor.writerow({k: formato(v) for k, v in fila.items()})


# --------------------------------------------------------------------------------------------
# Salida: informe HTML con gráficos
# --------------------------------------------------------------------------------------------

# Paleta categórica de referencia (orden fijo) y rampa secuencial azul.
SERIES = ['#257de0', '#f47230', '#00b894', '#f2a600', '#ee7fac', '#008a17', '#6a4ce2', '#ec4647']
TEXTO, TEXTO_2, REJILLA = '#191919', '#666666', '#e6e6e6'


def preparar_matplotlib():
    try:
        import matplotlib
        matplotlib.use('Agg')
        import matplotlib.pyplot as plt
    except ImportError:
        sys.exit('Error: falta matplotlib. Instálalo con: pip install matplotlib')
    plt.rcParams.update({
        'font.size': 9, 'axes.edgecolor': REJILLA, 'axes.labelcolor': TEXTO_2,
        'xtick.color': TEXTO_2, 'ytick.color': TEXTO, 'axes.spines.top': False,
        'axes.spines.right': False, 'axes.grid': True, 'grid.color': REJILLA,
        'grid.linewidth': 0.6, 'axes.axisbelow': True, 'figure.dpi': 110,
        'axes.titlesize': 11, 'axes.titleweight': 'bold', 'axes.titlelocation': 'left',
        'axes.titlecolor': TEXTO})
    return plt


def png(fig) -> str:
    buffer = io.BytesIO()
    fig.savefig(buffer, format='png', bbox_inches='tight', facecolor='white')
    fig.clf()
    return 'data:image/png;base64,' + base64.b64encode(buffer.getvalue()).decode()


def etiqueta_est(f: dict) -> str:
    return f"G{f['grupo']} · {f['alumno']}"


def grafico_notas(plt, filas):
    filas = list(reversed(filas))
    fig, ax = plt.subplots(figsize=(8, 0.28 * len(filas) + 1))
    colores = [SERIES[(int(f['grupo']) - 1) % 8] if str(f['grupo']).isdigit() else TEXTO_2 for f in filas]
    barras = ax.barh([etiqueta_est(f) for f in filas], [f['nota_final'] for f in filas],
                     color=colores, height=0.7, edgecolor='white', linewidth=1)
    for b, f in zip(barras, filas):
        ax.text(b.get_width() + 0.12, b.get_y() + b.get_height() / 2, f"{f['nota_final']:.1f}",
                va='center', fontsize=8, color=TEXTO)
    ax.set_xlim(0, 10.8)
    ax.set_xlabel('Nota final (0–10)')
    ax.grid(axis='y', visible=False)
    ax.set_title('Nota final por estudiante (color = grupo)')
    return png(fig)


def grafico_actividad(plt, filas, puntos):
    conceptos = [c for c in CONCEPTOS if puntos.get(c, 0) > 0 and any(f[c] for f in filas)]
    filas = list(reversed(filas))
    fig, ax = plt.subplots(figsize=(8, 0.28 * len(filas) + 1.4))
    izquierda = [0.0] * len(filas)
    nombres = [etiqueta_est(f) for f in filas]
    for i, c in enumerate(conceptos[:8]):
        valores = [f[c] * puntos[c] for f in filas]
        ax.barh(nombres, valores, left=izquierda, color=SERIES[i], height=0.7,
                edgecolor='white', linewidth=1, label=f'{CONCEPTOS[c][0]} (×{puntos[c]:g})')
        izquierda = [a + b for a, b in zip(izquierda, valores)]
    ax.set_xlabel('Puntos de actividad')
    ax.grid(axis='y', visible=False)
    fig.legend(loc='upper center', bbox_to_anchor=(0.5, 0.0), ncol=3, frameon=False, fontsize=8)
    ax.set_title('Puntos por tipo de aportación')
    return png(fig)


def grafico_mapa(plt, filas, filas_tarea, tareas):
    import numpy as np
    from matplotlib.colors import LinearSegmentedColormap
    mapa = LinearSegmentedColormap.from_list('azul', ['#eef5fd', '#9cc5f2', '#257de0', '#0b3f7a'])
    nombres = [etiqueta_est(f) for f in filas]
    indice = {(f['usuario_github'], f['tarea']): f['nota_actividad'] for f in filas_tarea}
    datos = np.array([[indice.get((f['usuario_github'], t.numero), 0) for t in tareas] for f in filas])
    fig, ax = plt.subplots(figsize=(max(5, 0.7 * len(tareas) + 3), 0.28 * len(filas) + 1.2))
    imagen = ax.imshow(datos, aspect='auto', cmap=mapa, vmin=0, vmax=10)
    ax.set_xticks(range(len(tareas)), [t.etiqueta for t in tareas])
    ax.set_yticks(range(len(filas)), nombres)
    ax.grid(False)
    for s in ax.spines.values():
        s.set_visible(False)
    ax.set_xticks([x - 0.5 for x in range(1, len(tareas))], minor=True)
    ax.set_yticks([y - 0.5 for y in range(1, len(filas))], minor=True)
    ax.grid(which='minor', color='white', linewidth=2)
    ax.tick_params(which='minor', length=0)
    for y in range(len(filas)):
        for x in range(len(tareas)):
            ax.text(x, y, f'{datos[y, x]:.0f}', ha='center', va='center', fontsize=7,
                    color='white' if datos[y, x] >= 5 else TEXTO)
    fig.colorbar(imagen, ax=ax, fraction=0.04, pad=0.02).set_label('Nota de actividad')
    ax.set_title('Nota de actividad por tarea (aportación al repositorio del grupo)')
    return png(fig)


def grafico_diario(plt, creditos, puntos, tareas, por_login):
    import matplotlib.dates as mdates
    serie = defaultdict(Counter)
    for c in creditos:
        if c.login.lower() in por_login:
            serie[c.grupo][c.momento.date()] += puntos.get(c.concepto, 0)
    if not serie:
        return None
    dias = sorted({d for s in serie.values() for d in s})
    inicio, fin = dias[0], dias[-1]
    todos = [inicio + timedelta(days=i) for i in range((fin - inicio).days + 1)]
    grupos = sorted(serie, key=lambda g: (len(g), g))
    fig, ax = plt.subplots(figsize=(9, 3.4))
    for g in grupos:
        color = SERIES[(int(g) - 1) % 8] if g.isdigit() else TEXTO_2
        ax.plot(todos, [serie[g][d] for d in todos], color=color, linewidth=2,
                marker='o', markersize=4, label=f'Grupo {g}')
    for t in tareas:
        if inicio <= t.planteamiento <= fin:
            ax.axvline(t.planteamiento, color=TEXTO_2, linewidth=0.8, linestyle=':')
            ax.text(t.planteamiento, ax.get_ylim()[1], f' {t.etiqueta}', fontsize=7,
                    color=TEXTO_2, va='top')
    ax.xaxis.set_major_locator(mdates.DayLocator(interval=max(1, len(todos) // 12)))
    ax.xaxis.set_major_formatter(mdates.DateFormatter('%d/%m'))
    ax.set_ylabel('Puntos por día')
    fig.legend(frameon=False, fontsize=8, ncol=len(grupos), loc='upper center', bbox_to_anchor=(0.5, 0.0))
    ax.set_title('Actividad diaria por grupo (líneas punteadas: planteamiento de cada tarea)')
    return png(fig)


def tabla_html(filas: list[dict], columnas: list[tuple[str, str]]) -> str:
    def celda(v):
        if v is None:
            return '<td class="n">–</td>'
        if isinstance(v, float):
            return f'<td class="n">{v:.1f}</td>'
        if isinstance(v, int):
            return f'<td class="n">{v}</td>'
        return f'<td>{html.escape(str(v))}</td>'
    cabecera = ''.join(f'<th>{html.escape(t)}</th>' for _, t in columnas)
    cuerpo = ''.join('<tr>' + ''.join(celda(f.get(c)) for c, _ in columnas) + '</tr>' for f in filas)
    return f'<div class="tabla"><table><thead><tr>{cabecera}</tr></thead><tbody>{cuerpo}</tbody></table></div>'


def informe(ruta, filas, filas_tarea, tareas, creditos, puntos, por_login, ajustes, avisos, ajenos):
    plt = preparar_matplotlib()
    graficos = [('Notas', grafico_notas(plt, filas)),
                ('Aportaciones', grafico_actividad(plt, filas, puntos)),
                ('Por tarea', grafico_mapa(plt, filas, filas_tarea, tareas) if tareas else None),
                ('Evolución', grafico_diario(plt, creditos, puntos, tareas, por_login))]
    figuras = ''.join(f'<figure><img src="{src}" alt="{html.escape(t)}"></figure>' for t, src in graficos if src)

    columnas = [('grupo', 'Grupo'), ('alumno', 'Alumno'), ('usuario_github', 'Usuario')]
    columnas += [(c, n) for c, (n, _) in CONCEPTOS.items() if any(f[c] for f in filas)]
    columnas += [('pr_fuera_de_plazo', 'PR fuera de plazo'), ('puntos', 'Puntos'),
                 ('aportacion_pct', '% del grupo'), ('nota_actividad', 'Nota actividad'),
                 ('nota_tests', 'Nota tests')]
    columnas += [(f'nota_{t.numero}', t.etiqueta) for t in tareas] + [('nota_final', 'Nota final')]

    tabla_tareas = tabla_html([{'numero': t.numero, 'titulo': t.titulo, 'horas': t.horas,
                                'planteamiento': f'{t.planteamiento:%d/%m/%Y}',
                                'limite': f'{t.limite:%d/%m/%Y}'} for t in tareas],
                              [('numero', 'Tarea'), ('titulo', 'Título'), ('horas', 'Horas'),
                               ('planteamiento', 'Planteamiento'), ('limite', 'Fecha límite')])
    pesos = ', '.join(f'{CONCEPTOS[c][0].lower()} {p:g}' for c, p in puntos.items() if p)
    lista_ajenos = ''.join(f'<li><code>{html.escape(l)}</code>: {n} eventos</li>' for l, n in ajenos.most_common())
    lista_avisos = ''.join(f'<li>{html.escape(a)}</li>' for a in avisos)

    pagina = f'''<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Desempeño en GitHub · {html.escape(ajustes['org'])}</title>
<style>
  body {{ font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; color: {TEXTO};
         max-width: 1100px; margin: 2rem auto; padding: 0 1.2rem; }}
  h1 {{ font-size: 1.6rem; margin-bottom: .2rem; }} h2 {{ margin-top: 2.2rem; font-size: 1.2rem; }}
  .sub {{ color: {TEXTO_2}; margin-top: 0; }}
  figure {{ margin: 1.5rem 0; }} img {{ max-width: 100%; height: auto; }}
  .tabla {{ overflow-x: auto; }} table {{ border-collapse: collapse; font-size: 13px; }}
  th, td {{ padding: .35rem .6rem; border-bottom: 1px solid {REJILLA}; text-align: left; white-space: nowrap; }}
  th {{ color: {TEXTO_2}; font-weight: 600; position: sticky; top: 0; background: white; }}
  td.n {{ text-align: right; font-variant-numeric: tabular-nums; }}
  .nota {{ background: #f6f8fa; padding: .8rem 1rem; border-radius: 6px; font-size: 14px; }}
  code {{ font-size: 13px; }}
</style></head><body>
<h1>Desempeño de los estudiantes en GitHub</h1>
<p class="sub">{html.escape(ajustes['org'])} · {html.escape(ajustes['periodo'])} ·
generado el {datetime.now(ZONA):%d/%m/%Y %H:%M}</p>

<div class="nota"><strong>Cómo se calcula.</strong> Puntos por evento: {html.escape(pesos)}.
La integración de un PR puntúa a quien lo integra y a su autor. Nota de actividad de una tarea =
10 × (puntos propios / puntos del grupo) × nº de miembros, hasta 10.
{html.escape(ajustes['texto_tests'])} La nota final es la media de las tareas ponderada por sus horas.</div>

{figuras}

<h2>Resumen por estudiante</h2>
{tabla_html(filas, columnas)}

<h2>Tareas evaluadas</h2>
{tabla_tareas}

{'<h2>Cuentas del log que no están en la relación de estudiantes</h2><ul>' + lista_ajenos + '</ul>' if ajenos else ''}
{'<h2>Avisos</h2><ul>' + lista_avisos + '</ul>' if avisos else ''}
</body></html>'''
    ruta.write_text(pagina, encoding='utf-8')


# --------------------------------------------------------------------------------------------
# Programa principal
# --------------------------------------------------------------------------------------------

def fecha_arg(texto: str) -> date:
    try:
        return date.fromisoformat(texto)
    except ValueError:
        raise argparse.ArgumentTypeError(f'"{texto}" no es una fecha AAAA-MM-DD')


def main() -> None:
    p = argparse.ArgumentParser(description=__doc__.split('\n\n')[0],
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('log', type=Path, help='exportación JSON del registro de auditoría (.json, .ndjson o .gz)')
    p.add_argument('--estudiantes', type=Path, required=True, help='CSV con usuario_github, alumno [, grupo]')
    p.add_argument('--planificacion', type=Path, required=True, help='planificacion.toml')
    p.add_argument('--planificador', type=Path, help='planificar_eportfolio.py (por defecto, junto al .toml)')
    p.add_argument('--tests', type=Path, help='CSV con la nota de los tests de cada PR, como el notas_detalle.csv de recoger_notas.py')
    p.add_argument('--peso-tests', type=float, default=0.6, help='peso de los tests en la nota (0–1; por defecto 0.6)')
    p.add_argument('--exclude', action='append', default=[], metavar='USUARIOS',
                   help='cuentas que no se tienen en cuenta (se puede repetir o separar por comas)')
    p.add_argument('--from', dest='desde', type=fecha_arg, help='primer día (AAAA-MM-DD, hora de Madrid)')
    p.add_argument('--to', dest='hasta', type=fecha_arg, help='último día, incluido')
    p.add_argument('--repos', default=r'^2DAW-CarlosIII/eportfolio_g(\d+)$',
                   help='expresión regular de los repositorios de grupo; el grupo 1 captura su número')
    p.add_argument('--pesos-eventos', type=Path, help='TOML con los puntos de cada concepto (p. ej. revision = 3)')
    p.add_argument('--commits', action='store_true',
                   help='cuenta también los commits de cada PR, consultando la API de GitHub')
    p.add_argument('--token', help='token para --commits (por defecto, GITHUB_TOKEN o el de "gh auth token")')
    p.add_argument('--salida', type=Path, default=Path('informe_desempeno'), help='carpeta de resultados')
    p.add_argument('--csv-sep', default=';', choices=[';', ','],
                   help='separador de los CSV: ";" con coma decimal, para Excel en español (por defecto), o ","')
    a = p.parse_args()

    if not 0 <= a.peso_tests <= 1:
        sys.exit('Error: --peso-tests debe estar entre 0 y 1.')
    excluidos = {u.strip().lower().lstrip('@') for grupo in a.exclude for u in grupo.split(',') if u.strip()}
    patron = re.compile(a.repos)
    puntos = {c: p for c, (_, p) in CONCEPTOS.items()}
    if a.pesos_eventos:
        with open(a.pesos_eventos, 'rb') as f:
            for c, v in tomllib.load(f).items():
                if c not in CONCEPTOS:
                    sys.exit(f'Error: concepto desconocido en {a.pesos_eventos}: {c}. '
                             f'Válidos: {", ".join(CONCEPTOS)}')
                puntos[c] = float(v)

    eventos = leer_log(a.log)
    estudiantes = {k: v for k, v in leer_estudiantes(a.estudiantes).items() if k not in excluidos}
    tareas, avisos = leer_tareas(a.planificacion, a.planificador)
    creditos, prs = atribuir(eventos, patron, tareas)

    if a.commits:
        token = a.token or os.environ.get('GITHUB_TOKEN') or token_de_gh()
        if not token:
            sys.exit('Error: --commits necesita un token (--token, GITHUB_TOKEN o gh auth login).')
        a.salida.mkdir(parents=True, exist_ok=True)
        for momento, login, repo, clave in commits_por_api(prs, patron, token, a.salida / 'cache_commits.json'):
            m = patron.match(repo) or patron.match(prs[clave]['url'].split('github.com/')[1].rsplit('/pull/')[0])
            grupo = m.group(1) if m and m.groups() else repo
            creditos.append(Credito(momento, login, 'commit', repo, grupo, 'commit',
                                    prs[clave]['url'], prs[clave]['titulo'], prs[clave].get('tarea'),
                                    bool(prs[clave].get('tarea') and momento.date() > prs[clave]['tarea'].limite)))

    # Periodo y exclusiones.
    creditos = [c for c in creditos if c.login.lower() not in excluidos
                and (not a.desde or c.momento.date() >= a.desde)
                and (not a.hasta or c.momento.date() <= a.hasta)]
    if not creditos:
        sys.exit('No queda ningún evento de los repositorios de grupo en ese periodo.')
    ajenos = Counter(c.login for c in creditos if c.login.lower() not in estudiantes)
    if any(l.startswith('id:') for l in ajenos):
        avisos.append('Algunas cuentas aparecen solo por su id (el log no trae el campo "actor"): '
                      'exporta el log completo para identificarlas.')

    notas_tests = leer_tests(a.tests) if a.tests else {}
    sin_tarea = [k for k in notas_tests if k not in prs]
    if sin_tarea:
        avisos.append(f'{len(sin_tarea)} PR con nota de tests no aparecen en el log del periodo y no se han usado.')

    filas, filas_tarea, tareas_usadas = calcular(creditos, estudiantes, tareas, puntos, notas_tests, prs, a.peso_tests)

    sin_grupo = [f['usuario_github'] for f in filas if f['grupo'] == '?']
    if sin_grupo:
        avisos.append(f'Sin actividad ni grupo conocido: {", ".join(sin_grupo)}. Añade la columna grupo '
                      'al CSV de estudiantes para que cuenten como miembros de su grupo.')
    a.salida.mkdir(parents=True, exist_ok=True)
    guardar_csv(a.salida / 'desempeno_estudiantes.csv', filas, a.csv_sep)
    guardar_csv(a.salida / 'desempeno_por_tarea.csv', filas_tarea, a.csv_sep)
    guardar_csv(a.salida / 'eventos_atribuidos.csv', [{
        'fecha': f'{c.momento:%Y-%m-%d %H:%M:%S}', 'grupo': c.grupo, 'usuario_github': c.login,
        'estudiante': 'sí' if c.login.lower() in estudiantes else 'no', 'accion': c.accion,
        'concepto': c.concepto, 'puntos': puntos.get(c.concepto, 0.0),
        'tarea': c.tarea.numero if c.tarea else '', 'fuera_de_plazo': 'sí' if c.fuera_de_plazo else '',
        'pull_request': c.pr, 'titulo_pr': c.titulo} for c in creditos], a.csv_sep)

    fechas = [c.momento.date() for c in creditos]
    periodo = f'del {(a.desde or min(fechas)):%d/%m/%Y} al {(a.hasta or max(fechas)):%d/%m/%Y}'
    texto_tests = (f'Nota de la tarea = {a.peso_tests:g} × tests (media de los PR propios; 0 sin PR) + '
                   f'{1 - a.peso_tests:g} × actividad; si una tarea no tiene tests, solo actividad.'
                   if notas_tests else 'No se han usado notas de tests: la nota es solo de actividad.')
    org = patron.pattern.strip('^').split('/')[0] if '/' in patron.pattern else 'GitHub'
    informe(a.salida / 'informe.html', filas, filas_tarea, tareas_usadas, creditos, puntos,
            estudiantes, {'org': org, 'periodo': periodo, 'texto_tests': texto_tests}, avisos, ajenos)

    print(f'{len(creditos)} eventos atribuidos, {len(filas)} estudiantes, {len(tareas_usadas)} tareas ({periodo}).')
    for aviso in avisos:
        print(f'Aviso: {aviso}')
    if ajenos:
        print('Cuentas que no están en la relación de estudiantes (usa --exclude o añádelas):',
              ', '.join(sorted(ajenos)))
    print(f'Resultados en {a.salida}/')


if __name__ == '__main__':
    main()
