"""Crea y mantiene los proyectos de GitHub que planifican los ejercicios del ePortfolio.

Lee planificacion.toml (el calendario, los destinos y los bloques con sus apartados y horas),
calcula cuándo se plantea y cuándo termina cada ejercicio, y para cada destino:

1. busca su proyecto en la organización y, si no existe, lo crea (el de referencia) o lo copia
   del de referencia, con sus vistas;
2. lo hace público, le pone la descripción y un README con las fechas, y lo enlaza al repositorio;
3. crea los campos Planteamiento, Fecha límite y Bloque;
4. crea en el repositorio los issues que falten, a partir de los .md de la carpeta de issues,
   y enlaza los sub-issues a su issue principal;
5. añade al proyecto los issues y los borradores (los ejercicios que aún no tienen issue);
6. pone a cada elemento sus fechas y su bloque.

Es idempotente: lo que ya existe se reutiliza, y solo se cambia lo que no coincide. Si cambia
el calendario, basta con volver a ejecutarlo. No borra nada, salvo sus propios borradores
cuando ya no hacen falta (su issue ya existe, o su bloque ya tiene apartados).

Necesita Python 3.11 o posterior y GitHub CLI (gh) identificado con el permiso "project"
(00_instalacionEntorno.md, §12, y README.md de esta carpeta). Uso:

    python3 planificar_eportfolio.py --calendario          # solo las fechas calculadas
    python3 planificar_eportfolio.py --dry-run             # lo que haría, sin tocar GitHub
    python3 planificar_eportfolio.py --destino marcapersonalfp2627
    python3 planificar_eportfolio.py                       # todos los destinos, en orden

Opciones: --datos FICHERO (por defecto, planificacion.toml junto al script), --destino NOMBRE
(se puede repetir), --sin-conexion (con --dry-run, no consulta GitHub) y --csv FICHERO (guarda
las fechas en CSV, en UTF-8 con BOM).
"""

import argparse
import bisect
import csv
import json
import os
import re
import shlex
import shutil
import subprocess
import sys
import tempfile
import tomllib
import unicodedata
from dataclasses import dataclass, field
from datetime import date, timedelta
from pathlib import Path

DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']
MARCA = '<!-- planificacion: {} -->'
RE_MARCA = re.compile(r'<!-- planificacion: (.+?) -->')

CONSULTA_PROYECTO = '''
query($org: String!, $numero: Int!, $despues: String) {
  organization(login: $org) {
    projectV2(number: $numero) {
      id number url public shortDescription readme
      repositories(first: 50) { nodes { nameWithOwner } }
      fields(first: 50) { nodes {
        ... on ProjectV2Field { id name dataType }
        ... on ProjectV2SingleSelectField { id name dataType options { id name } }
        ... on ProjectV2IterationField { id name dataType } } }
      items(first: 100, after: $despues) {
        pageInfo { hasNextPage endCursor }
        nodes {
          id type
          content {
            ... on DraftIssue { id title body }
            ... on Issue { id number url title }
            ... on PullRequest { id number url title } }
          fieldValues(first: 30) { nodes {
            ... on ProjectV2ItemFieldDateValue { date field { ... on ProjectV2FieldCommon { id } } }
            ... on ProjectV2ItemFieldSingleSelectValue { optionId field { ... on ProjectV2FieldCommon { id } } }
          } }
        }
      }
    }
  }
}
'''

ENLAZAR_REPOSITORIO = '''
mutation($proyecto: ID!, $repositorio: ID!) {
  linkProjectV2ToRepository(input: {projectId: $proyecto, repositoryId: $repositorio}) {
    repository { id }
  }
}
'''


def sin_tildes(texto: str) -> str:
    return ''.join(c for c in unicodedata.normalize('NFD', texto) if unicodedata.category(c) != 'Mn')


def limpiar_titulo(titulo: str) -> str:
    """Quita el énfasis de Markdown (_ePortfolio_ → ePortfolio): los títulos de GitHub no lo muestran."""
    return re.sub(r'(?<![\w`])[_*]+([^_*]+?)[_*]+(?![\w`])', r'\1', titulo).strip()


def normalizar_titulo(titulo: str) -> str:
    return ' '.join(re.sub(r'[_*]', '', titulo).casefold().split())


# --------------------------------------------------------------------------------------------
# El calendario y la planificación
# --------------------------------------------------------------------------------------------

class Calendario:
    """Convierte horas de clase en fechas: una entrada por cada hora de clase, desde el inicio."""

    def __init__(self, conf: dict):
        self.inicio: date = conf['inicio']
        self.fin: date = conf['fin_curso']
        if not isinstance(self.inicio, date) or not isinstance(self.fin, date):
            sys.exit('Error: inicio y fin_curso deben ser fechas AAAA-MM-DD, sin comillas.')
        horas_por_dia = {}
        for dia, horas in conf['horas_por_dia'].items():
            nombre = sin_tildes(dia.lower())
            if nombre not in DIAS:
                sys.exit(f'Error: "{dia}" no es un día de la semana (horas_por_dia).')
            horas_por_dia[DIAS.index(nombre)] = int(horas)
        if not any(horas_por_dia.values()):
            sys.exit('Error: horas_por_dia no tiene ninguna hora de clase.')

        self.no_lectivos: dict[date, str] = {}
        for tramo in conf.get('no_lectivos', []):
            dia, hasta = tramo['desde'], tramo.get('hasta', tramo['desde'])
            while dia <= hasta:
                self.no_lectivos[dia] = tramo.get('motivo', '')
                dia += timedelta(days=1)

        # Se sigue contando después del fin de curso, para poder avisar de lo que no cabe.
        self.horas: list[date] = []
        dia = self.inicio
        while dia <= self.fin + timedelta(days=400):
            if dia not in self.no_lectivos:
                self.horas.extend([dia] * horas_por_dia.get(dia.weekday(), 0))
            dia += timedelta(days=1)
        self.horas_curso = bisect.bisect_right(self.horas, self.fin)

    def fecha(self, hora: int) -> date:
        return self.horas[hora]

    def hora_de(self, dia: date) -> int:
        """La primera hora de clase de ese día o, si no hay clase, del siguiente día lectivo."""
        return bisect.bisect_left(self.horas, dia)

    def es_lectivo(self, dia: date) -> bool:
        indice = self.hora_de(dia)
        return indice < len(self.horas) and self.horas[indice] == dia


@dataclass
class Tramo:
    """Un apartado de un bloque, o un bloque entero si no tiene apartados."""
    bloque: dict
    apartado: dict | None
    horas: int
    horas_ejercicio: int
    inicio: int = 0
    planteamiento: date | None = None
    limite: date | None = None
    fecha_real: bool = False

    @property
    def datos(self) -> dict:
        """Donde están sus fechas fijas, si las tiene: el apartado o, si no hay apartados, el bloque."""
        return self.apartado if self.apartado else self.bloque

    @property
    def numero(self) -> str:
        return self.apartado['numero'] if self.apartado else f"Bloque {self.bloque['numero']}"

    @property
    def titulo(self) -> str:
        return self.apartado['titulo'] if self.apartado else self.bloque['titulo']


@dataclass
class Elemento:
    """Un elemento del proyecto: un issue (y su texto) o un borrador."""
    clave: str
    tipo: str                    # 'issue' o 'borrador'
    titulo: str
    cuerpo: str
    tramo: Tramo
    origen: str = ''             # fichero e issue del que sale, para los mensajes
    padre: str | None = None     # clave del issue principal, en los sub-issues

    @property
    def bloque(self) -> str:
        return f"Bloque {self.tramo.bloque['numero']}"


def planificar(datos: dict, calendario: Calendario) -> tuple[list[Tramo], list[str]]:
    avisos = []
    tramos: list[Tramo] = []
    for bloque in datos['bloques']:
        apartados = bloque.get('apartados', [])
        if apartados:
            suma = sum(a['horas'] for a in apartados)
            if suma != bloque['horas']:
                avisos.append(f"El Bloque {bloque['numero']} tiene {bloque['horas']} h, "
                              f"pero sus apartados suman {suma} h: se usan las de los apartados.")
            tramos += [Tramo(bloque, a, a['horas'], a['horas']) for a in apartados]
        else:
            tramos.append(Tramo(bloque, None, bloque['horas'],
                                bloque.get('horas_planificadas', bloque['horas'])))

    hora = 0
    for tramo in tramos:
        real = tramo.datos.get('planteamiento')
        if real:
            if not calendario.es_lectivo(real):
                avisos.append(f'{tramo.numero}: el {real:%d/%m/%Y} no hay clase; '
                              'se toma el siguiente día lectivo.')
            nueva = calendario.hora_de(real)
            if nueva < hora:
                avisos.append(f'{tramo.numero}: su fecha real ({real:%d/%m/%Y}) se solapa con el '
                              'apartado anterior, que debería durar menos.')
            hora, tramo.fecha_real = nueva, True
        tramo.inicio = hora
        hora += tramo.horas

    for i, tramo in enumerate(tramos):
        tramo.planteamiento = calendario.fecha(tramo.inicio)
        # Fecha límite: el día en que empieza el apartado siguiente. Si el bloque tiene margen,
        # el día en que empezaría lo siguiente sin ese margen.
        if tramo.horas_ejercicio < tramo.horas or i + 1 == len(tramos):
            fin = tramo.inicio + tramo.horas_ejercicio
        else:
            fin = tramos[i + 1].inicio
        if fin < calendario.horas_curso:
            tramo.limite = calendario.fecha(fin)
        else:
            # Lo último del curso termina el último día que ocupa.
            tramo.limite = calendario.fecha(max(fin - 1, tramo.inicio))
        real = tramo.datos.get('fecha_limite')
        if real:
            tramo.limite, tramo.fecha_real = real, True
        elif tramo.limite > calendario.fin:
            avisos.append(f'{tramo.numero} termina el {tramo.limite:%d/%m/%Y}, después del fin de '
                          f'curso ({calendario.fin:%d/%m/%Y}).')

    necesarias = hora
    if necesarias > calendario.horas_curso:
        avisos.append(f'Desde el {calendario.inicio:%d/%m/%Y} hasta el fin de curso hay '
                      f'{calendario.horas_curso} h de clase, y la planificación necesita '
                      f'{necesarias} h: faltan {necesarias - calendario.horas_curso} h.')
    return tramos, avisos


def leer_issues(ruta: Path) -> list[tuple[str, str]]:
    """Los issues de un .md: [(título, cuerpo)]. El primero es el principal; los demás, sus sub-issues."""
    texto = ruta.read_text(encoding='utf-8')
    texto = re.sub(r'^<!-- \{% (end)?raw %\} -->[ \t]*\n?', '', texto, flags=re.M)
    partes = re.split(r'^(?=## Issue )', texto, flags=re.M)
    if len(partes) == 1:
        primera, _, cuerpo = texto.partition('\n')
        return [(limpiar_titulo(primera.lstrip('# ')), cuerpo.strip())]
    issues = []
    for parte in partes[1:]:      # partes[0] es el título y la nota para el profesorado
        primera, _, cuerpo = parte.partition('\n')
        cuerpo = re.sub(r'\n-{3,}\s*$', '', cuerpo.strip()).strip()
        issues.append((limpiar_titulo(primera[3:]), cuerpo))
    return issues


def elementos_de(tramos: list[Tramo], carpeta: Path) -> tuple[list[Elemento], list[str]]:
    avisos, elementos = [], []
    for tramo in tramos:
        apartado = tramo.apartado
        if apartado is None:
            bloque = tramo.bloque
            horas = f"{bloque['horas']} h"
            if tramo.horas_ejercicio < tramo.horas:
                horas += f", {tramo.horas_ejercicio} h para los ejercicios y el resto de margen"
            cuerpo = (f"Ejercicios del ePortfolio del Bloque {bloque['numero']} (RA{bloque['numero']}): "
                      f"{bloque['titulo']}. Duración: {horas}.\n\n"
                      'Es un borrador: se sustituirá por los issues del bloque cuando estén redactados.')
            clave = f"borrador:B{bloque['numero']}"
            elementos.append(Elemento(clave, 'borrador', f"Bloque {bloque['numero']}. {bloque['titulo']}",
                                      cuerpo + '\n\n' + MARCA.format(clave), tramo))
            continue

        borrador = f"borrador:{apartado['numero']}"
        fichero = apartado.get('issue')
        if fichero and (carpeta / fichero).is_file():
            issues = leer_issues(carpeta / fichero)
            principal = f'{fichero}#0'
            for n, (titulo, cuerpo) in enumerate(issues):
                clave = f'{fichero}#{n}'
                origen = fichero if len(issues) == 1 else f'{fichero}, {"principal" if n == 0 else f"issue {n}"}'
                elementos.append(Elemento(clave, 'issue', titulo, cuerpo + '\n\n' + MARCA.format(clave), tramo,
                                          origen, padre=principal if n else None))
        elif fichero or apartado.get('borrador'):
            if fichero:
                avisos.append(f"{apartado['numero']}: no existe {carpeta / fichero}; va como borrador.")
            cuerpo = (f"Ejercicio del ePortfolio del apartado {apartado['numero']}. {apartado['titulo']}.\n\n"
                      'Es un borrador: se sustituirá por su issue cuando esté redactado.')
            elementos.append(Elemento(borrador, 'borrador',
                                      f"{apartado['numero']}. {apartado['titulo']} (issue en preparación)",
                                      cuerpo + '\n\n' + MARCA.format(borrador), tramo))
    return elementos, avisos


def readme_del_proyecto(tramos: list[Tramo]) -> str:
    lineas = ['Cuándo se plantea y cuándo termina cada ejercicio del ePortfolio.', '',
              'La **fecha límite** es el día en que empieza el apartado siguiente. La revisión por un '
              'compañero puede terminar algo después, pero la fecha límite no se mueve.', '',
              '| Apartado | Horas | Planteamiento | Fecha límite |', '|---|---:|---|---|']
    for tramo in tramos:
        lineas.append(f'| {tramo.numero}. {tramo.titulo} | {tramo.horas} | '
                      f'{tramo.planteamiento:%d/%m/%Y} | {tramo.limite:%d/%m/%Y} |')
    lineas += ['', '_Generado por `planificar_eportfolio.py` (REA marcapersonalFP). No lo edites a mano: '
               'se sobrescribe en cada ejecución._']
    return '\n'.join(lineas)


def mostrar_calendario(tramos: list[Tramo], elementos: list[Elemento], calendario: Calendario) -> None:
    ejercicios: dict[int, list[Elemento]] = {}
    for el in elementos:
        ejercicios.setdefault(id(el.tramo), []).append(el)
    print(f'Calendario: del {calendario.inicio:%d/%m/%Y} al {calendario.fin:%d/%m/%Y}, '
          f'{calendario.horas_curso} h de clase.\n')
    print(f"{'Apartado':<10} {'Horas':>5}  {'Planteamiento':<13}  {'Fecha límite':<12}  Ejercicio")
    for tramo in tramos:
        lista = ejercicios.get(id(tramo), [])
        if not lista:
            ejercicio = '—'
        elif lista[0].tipo == 'borrador':
            ejercicio = 'borrador'
        else:
            ejercicio = lista[0].clave.split('#')[0] + (f' (+{len(lista) - 1} sub-issues)' if len(lista) > 1 else '')
        marca = ' (fecha fija)' if tramo.fecha_real else ''
        print(f'{tramo.numero:<10} {tramo.horas:>5}  {tramo.planteamiento:%d/%m/%Y}     '
              f'{tramo.limite:%d/%m/%Y}    {ejercicio}{marca}')


def guardar_csv(ruta: str, tramos: list[Tramo]) -> None:
    with open(ruta, 'w', encoding='utf-8-sig', newline='') as fichero:
        escritor = csv.writer(fichero)
        escritor.writerow(['bloque', 'apartado', 'titulo', 'horas', 'planteamiento', 'fecha_limite', 'fecha_fija'])
        for t in tramos:
            escritor.writerow([t.bloque['numero'], t.numero, t.titulo, t.horas, t.planteamiento.isoformat(),
                               t.limite.isoformat(), 'sí' if t.fecha_real else ''])
    print(f'\nFechas guardadas en {ruta}.')


# --------------------------------------------------------------------------------------------
# GitHub
# --------------------------------------------------------------------------------------------

def abreviar(argumento: str) -> str:
    argumento = argumento.replace('\n', ' ')
    return argumento if len(argumento) <= 70 else f'{argumento[:50]}… ({len(argumento)} caracteres)'


class GH:
    """Ejecuta gh. Las consultas se hacen siempre que haya conexión; los cambios, solo fuera del modo de prueba."""

    def __init__(self, prueba: bool, conexion: bool):
        self.prueba, self.conexion = prueba, conexion

    @staticmethod
    def _ejecutar(argumentos: list[str], tolerar: bool = False) -> str | None:
        resultado = subprocess.run(['gh', *argumentos], capture_output=True, text=True, encoding='utf-8')
        if resultado.returncode != 0:
            if tolerar:
                return None
            sys.exit(f'Error al ejecutar: gh {" ".join(map(shlex.quote, map(abreviar, argumentos)))}\n'
                     f'{resultado.stderr.strip()}')
        return resultado.stdout

    def leer(self, *argumentos: str) -> str | None:
        return self._ejecutar(list(argumentos)) if self.conexion else None

    def cambiar(self, *argumentos: str, tolerar: bool = False, mostrar: list[str] | None = None) -> str | None:
        texto = ' '.join(shlex.quote(abreviar(a)) for a in (mostrar or argumentos))
        if self.prueba:
            print(f'    [prueba] gh {texto}')
            return None
        print(f'    gh {texto}')
        return self._ejecutar(list(argumentos), tolerar)

    def graphql(self, consulta: str, **variables) -> dict:
        argumentos = ['api', 'graphql', '-f', f'query={consulta}']
        for nombre, valor in variables.items():
            if valor is not None:
                argumentos += ['-F' if isinstance(valor, int) else '-f', f'{nombre}={valor}']
        return json.loads(self._ejecutar(argumentos))


@dataclass
class Estado:
    """Lo que hay en GitHub de un proyecto."""
    numero: int | None = None
    id: str | None = None
    url: str = ''
    publico: bool | None = None
    descripcion: str | None = None
    readme: str | None = None
    repos: set[str] = field(default_factory=set)
    campos: dict[str, dict] = field(default_factory=dict)     # nombre → {id, tipo, opciones}
    elementos: list[dict] = field(default_factory=list)       # {id, tipo, url, contenido, titulo, cuerpo, valores}


def buscar_proyecto(gh: GH, org: str, titulo: str) -> int | None:
    salida = gh.leer('project', 'list', '--owner', org, '--closed', '--limit', '500', '--format', 'json')
    if salida is None:
        return None
    for proyecto in json.loads(salida).get('projects', []):
        if proyecto['title'] == titulo:
            return proyecto['number']
    return None


def leer_estado(gh: GH, org: str, numero: int | None) -> Estado:
    if numero is None or not gh.conexion:
        return Estado(numero=numero)
    estado, despues = Estado(numero=numero), None
    while True:
        p = gh.graphql(CONSULTA_PROYECTO, org=org, numero=numero, despues=despues)['data']['organization']['projectV2']
        if despues is None:
            estado.id, estado.url, estado.publico = p['id'], p['url'], p['public']
            estado.descripcion, estado.readme = p['shortDescription'] or '', p['readme'] or ''
            estado.repos = {r['nameWithOwner'].lower() for r in p['repositories']['nodes']}
            for campo in p['fields']['nodes']:
                if campo:
                    estado.campos[campo['name']] = {
                        'id': campo['id'], 'tipo': campo['dataType'],
                        'opciones': {o['name']: o['id'] for o in campo.get('options', [])}}
        for item in p['items']['nodes']:
            contenido = item.get('content') or {}
            valores = {}
            for valor in item['fieldValues']['nodes']:
                if valor and valor.get('field'):
                    valores[valor['field']['id']] = valor.get('date') or valor.get('optionId')
            estado.elementos.append({
                'id': item['id'], 'tipo': item['type'], 'contenido': contenido.get('id'),
                'url': contenido.get('url'), 'titulo': contenido.get('title', ''),
                'cuerpo': contenido.get('body', ''), 'valores': valores})
        if not p['items']['pageInfo']['hasNextPage']:
            return estado
        despues = p['items']['pageInfo']['endCursor']


def marca_de(texto: str | None) -> str | None:
    encontrada = RE_MARCA.search(texto or '')
    return encontrada.group(1) if encontrada else None


def json_o_nada(salida: str | None) -> dict | None:
    try:
        return json.loads(salida) if salida else None
    except json.JSONDecodeError:
        return None


class Destino:
    def __init__(self, conf: dict, datos: dict, gh: GH, tramos: list[Tramo], elementos: list[Elemento]):
        self.nombre, self.referencia = conf['nombre'], conf.get('referencia', False)
        self.crear_issues = conf.get('crear_issues', True)
        github = datos['github']
        self.org = github['organizacion']
        self.repo = f"{self.org}/{conf['repo']}"
        self.titulo = github['titulo_proyecto'].format(nombre=self.nombre)
        self.publico = github.get('publico', True)
        self.descripcion = github.get('descripcion', '')
        self.nombres_campos = github['campos']
        self.bloques = [f"Bloque {b['numero']}" for b in datos['bloques']]
        self.gh, self.tramos, self.elementos = gh, tramos, elementos
        self.estado = Estado()

    # -- utilidades ----------------------------------------------------------------------------

    @property
    def numero(self) -> str:
        return str(self.estado.numero) if self.estado.numero is not None else '<N>'

    @property
    def id_proyecto(self) -> str:
        return self.estado.id or '<id del proyecto>'

    def releer(self) -> None:
        if not self.gh.prueba:
            self.estado = leer_estado(self.gh, self.org, self.estado.numero)

    def id_campo(self, clave: str) -> str:
        nombre = self.nombres_campos[clave]
        return self.estado.campos.get(nombre, {}).get('id') or f'<id del campo {nombre}>'

    # -- pasos ---------------------------------------------------------------------------------

    def procesar(self, referencia: int | str | None) -> int | str | None:
        print(f'\n== {self.nombre} ({self.repo}) ==')
        print(f'  Proyecto "{self.titulo}"')
        numero = buscar_proyecto(self.gh, self.org, self.titulo)
        if numero is None:
            self.crear_proyecto(referencia)
        else:
            print(f'    ya existe: número {numero}')
            self.estado = leer_estado(self.gh, self.org, numero)
        self.configurar_proyecto()
        self.crear_campos()
        numeros = self.crear_issues_y_borradores()
        self.enlazar_sub_issues(numeros)
        self.poner_valores()
        if self.estado.url:
            print(f'  Listo: {self.estado.url}')
        # En el modo de prueba, un proyecto que aún no existe no tiene número.
        return self.estado.numero if self.estado.numero is not None else '<N de referencia>'

    def crear_proyecto(self, referencia: int | str | None) -> None:
        if not self.referencia and referencia is not None:
            print(f'    no existe: se copia del proyecto de referencia ({referencia}), con sus vistas')
            salida = self.gh.cambiar('project', 'copy', str(referencia), '--source-owner', self.org,
                                     '--target-owner', self.org, '--title', self.titulo, '--drafts',
                                     '--format', 'json')
        else:
            print('    no existe: se crea')
            salida = self.gh.cambiar('project', 'create', '--owner', self.org, '--title', self.titulo,
                                     '--format', 'json')
        creado = json_o_nada(salida)
        numero = creado.get('number') if creado else None
        if numero is None and not self.gh.prueba:
            numero = buscar_proyecto(self.gh, self.org, self.titulo)
        self.estado = leer_estado(self.gh, self.org, numero) if not self.gh.prueba else Estado(numero=numero)

    def configurar_proyecto(self) -> None:
        e = self.estado
        visibilidad = 'PUBLIC' if self.publico else 'PRIVATE'
        if e.publico is None or e.publico != self.publico:
            print(f'  Visibilidad: {visibilidad}')
            self.gh.cambiar('project', 'edit', self.numero, '--owner', self.org, '--visibility', visibilidad)
        readme = readme_del_proyecto(self.tramos)
        if e.descripcion != self.descripcion or (e.readme or '').strip() != readme.strip():
            print('  Descripción y README con las fechas')
            self.gh.cambiar('project', 'edit', self.numero, '--owner', self.org,
                            '--description', self.descripcion, '--readme', readme)
        if self.repo.lower() not in e.repos:
            print(f'  Enlace con {self.repo}')
            hecho = self.gh.cambiar('project', 'link', self.numero, '--owner', self.org,
                                    '--repo', self.repo.split('/')[1], tolerar=True)
            if hecho is None and not self.gh.prueba:
                # Por si la versión de gh no tiene "project link": la misma operación con GraphQL.
                print('    gh project link ha fallado; se enlaza con la API GraphQL')
                id_repo = self.gh._ejecutar(['api', f'repos/{self.repo}', '--jq', '.node_id']).strip()
                self.gh.graphql(ENLAZAR_REPOSITORIO, proyecto=self.estado.id, repositorio=id_repo)

    def crear_campos(self) -> None:
        creados = False
        for clave in ('planteamiento', 'limite'):
            nombre = self.nombres_campos[clave]
            campo = self.estado.campos.get(nombre)
            if campo is None:
                print(f'  Campo "{nombre}" (fecha)')
                self.gh.cambiar('project', 'field-create', self.numero, '--owner', self.org,
                                '--name', nombre, '--data-type', 'DATE', '--format', 'json')
                creados = True
            elif campo['tipo'] != 'DATE':
                sys.exit(f'Error: el campo "{nombre}" ya existe, pero no es de tipo fecha.')
        nombre = self.nombres_campos['bloque']
        campo = self.estado.campos.get(nombre)
        if campo is None:
            print(f'  Campo "{nombre}" (selección: {", ".join(self.bloques)})')
            self.gh.cambiar('project', 'field-create', self.numero, '--owner', self.org, '--name', nombre,
                            '--data-type', 'SINGLE_SELECT', '--single-select-options', ','.join(self.bloques),
                            '--format', 'json')
            creados = True
        else:
            faltan = [b for b in self.bloques if b not in campo['opciones']]
            if faltan:
                print(f'  Aviso: al campo "{nombre}" le faltan las opciones {", ".join(faltan)}. '
                      'Añádelas a mano en la configuración del proyecto.')
        if creados:
            self.releer()

    def issues_del_repositorio(self) -> list[dict]:
        salida = self.gh.leer('issue', 'list', '-R', self.repo, '--state', 'all', '--limit', '1000',
                              '--json', 'number,title,url,body')
        return json.loads(salida) if salida else []

    def crear_issues_y_borradores(self) -> dict[str, int | None]:
        """Crea lo que falte en el repositorio y en el proyecto. Devuelve el número de cada issue."""
        existentes = self.issues_del_repositorio() if any(el.tipo == 'issue' for el in self.elementos) else []
        en_proyecto_url = {el['url']: el for el in self.estado.elementos if el['url']}
        borradores = {marca_de(el['cuerpo']): el for el in self.estado.elementos if el['tipo'] == 'DRAFT_ISSUE'}
        numeros: dict[str, int | None] = {}
        cambios = False

        print('  Issues y borradores')
        for el in self.elementos:
            if el.tipo == 'issue':
                if not self.crear_issues:
                    print(f'    {el.titulo}: este destino no lleva issues')
                    continue
                issue = next((i for i in existentes if marca_de(i['body']) == el.clave
                              or normalizar_titulo(i['title']) == normalizar_titulo(el.titulo)), None)
                if issue:
                    url, numeros[el.clave] = issue['url'], issue['number']
                    print(f'    #{issue["number"]} {el.titulo}: ya existe')
                else:
                    print(f'    {el.titulo}: se crea ({el.origen})')
                    url = self.crear_issue(el)
                    numeros[el.clave] = int(url.rstrip('/').split('/')[-1]) if url else None
                    if url:
                        existentes.append({'number': numeros[el.clave], 'title': el.titulo, 'url': url, 'body': el.cuerpo})
                if url not in en_proyecto_url:
                    self.gh.cambiar('project', 'item-add', self.numero, '--owner', self.org,
                                    '--url', url or f'<url de "{el.titulo}">', '--format', 'json')
                    cambios = True
            else:
                existente = borradores.get(el.clave)
                if existente is None:
                    print(f'    Borrador "{el.titulo}": se crea')
                    self.gh.cambiar('project', 'item-create', self.numero, '--owner', self.org,
                                    '--title', el.titulo, '--body', el.cuerpo, '--format', 'json')
                    cambios = True
                elif existente['titulo'] != el.titulo or existente['cuerpo'].strip() != el.cuerpo.strip():
                    print(f'    Borrador "{el.titulo}": se actualiza su texto')
                    self.gh.cambiar('project', 'item-edit', '--id', existente['contenido'],
                                    '--title', el.titulo, '--body', el.cuerpo)
                else:
                    print(f'    Borrador "{el.titulo}": ya existe')

        # Los borradores de este script que ya no corresponden a ningún ejercicio (porque su issue
        # ya existe o porque su bloque ya tiene apartados) sobran.
        vigentes = {el.clave for el in self.elementos if el.tipo == 'borrador'}
        for clave, item in borradores.items():
            if clave and clave.startswith('borrador:') and clave not in vigentes:
                print(f'    Borrador "{item["titulo"]}": ya no hace falta, se borra')
                self.gh.cambiar('project', 'item-delete', self.numero, '--owner', self.org, '--id', item['id'])
                cambios = True
        if cambios:
            self.releer()
        return numeros

    def crear_issue(self, el: Elemento) -> str | None:
        if self.gh.prueba:
            self.gh.cambiar('issue', 'create', '-R', self.repo, '--title', el.titulo, '--body-file',
                            f'<texto de {el.origen}>')
            return None
        with tempfile.NamedTemporaryFile('w', encoding='utf-8', suffix='.md', delete=False) as fichero:
            fichero.write(el.cuerpo + '\n')
        try:
            salida = self.gh.cambiar('issue', 'create', '-R', self.repo, '--title', el.titulo,
                                     '--body-file', fichero.name)
        finally:
            os.unlink(fichero.name)
        return salida.strip().splitlines()[-1]

    def enlazar_sub_issues(self, numeros: dict[str, int | None]) -> None:
        hijos = [el for el in self.elementos if el.padre and el.clave in numeros]
        if not hijos:
            return
        print('  Sub-issues')
        actuales: dict[int, set[int]] = {}
        for el in hijos:
            padre, hijo = numeros.get(el.padre), numeros[el.clave]
            if padre is not None and padre not in actuales:
                salida = self.gh.leer('api', f'repos/{self.repo}/issues/{padre}/sub_issues', '--paginate',
                                      '--jq', '.[].number')
                actuales[padre] = {int(n) for n in (salida or '').split()}
            if padre is not None and hijo in actuales[padre]:
                print(f'    #{hijo} ya es sub-issue de #{padre}')
                continue
            print(f'    {el.titulo}: sub-issue de {f"#{padre}" if padre else "el principal"}')
            # La API pide el id numérico del sub-issue, no su número.
            id_hijo = None
            if hijo is not None:
                id_hijo = (self.gh.leer('api', f'repos/{self.repo}/issues/{hijo}', '--jq', '.id') or '').strip()
            self.gh.cambiar('api', '-X', 'POST', f'repos/{self.repo}/issues/{padre or "<N>"}/sub_issues',
                            '-F', f'sub_issue_id={id_hijo or "<id del issue>"}')

    def item_de(self, el: Elemento) -> dict | None:
        if el.tipo == 'borrador':
            return next((i for i in self.estado.elementos
                         if i['tipo'] == 'DRAFT_ISSUE' and marca_de(i['cuerpo']) == el.clave), None)
        return next((i for i in self.estado.elementos if i['tipo'] == 'ISSUE'
                     and (marca_de(i['cuerpo']) == el.clave
                          or normalizar_titulo(i['titulo']) == normalizar_titulo(el.titulo))
                     and (i['url'] or '').lower().startswith(f'https://github.com/{self.repo.lower()}/')), None)

    def poner_valores(self) -> None:
        print('  Fechas y bloque')
        campo_bloque = self.estado.campos.get(self.nombres_campos['bloque'], {})
        cambios = 0
        for el in self.elementos:
            if el.tipo == 'issue' and not self.crear_issues:
                continue
            item = self.item_de(el)
            id_item = item['id'] if item else f'<elemento "{abreviar(el.titulo)}">'
            valores = item['valores'] if item else {}
            objetivos = [('planteamiento', el.tramo.planteamiento.isoformat()),
                         ('limite', el.tramo.limite.isoformat())]
            for clave, fecha in objetivos:
                id_campo = self.id_campo(clave)
                if valores.get(id_campo) != fecha:
                    self.gh.cambiar('project', 'item-edit', '--id', id_item, '--project-id', self.id_proyecto,
                                    '--field-id', id_campo, '--date', fecha)
                    cambios += 1
            opcion = campo_bloque.get('opciones', {}).get(el.bloque) or f'<opción {el.bloque}>'
            if valores.get(campo_bloque.get('id')) != opcion:
                self.gh.cambiar('project', 'item-edit', '--id', id_item, '--project-id', self.id_proyecto,
                                '--field-id', campo_bloque.get('id') or f'<id del campo {self.nombres_campos["bloque"]}>',
                                '--single-select-option-id', opcion)
                cambios += 1
        if not cambios:
            print('    todo al día')


# --------------------------------------------------------------------------------------------

def comprobar_gh(prueba: bool) -> None:
    resultado = subprocess.run(['gh', 'auth', 'status'], capture_output=True, text=True, encoding='utf-8')
    salida = resultado.stdout + resultado.stderr
    if resultado.returncode != 0:
        sys.exit('Error: gh no está identificado. Ejecuta gh auth login (00_instalacionEntorno.md, §12).')
    if "'project'" not in salida:
        mensaje = 'el token de gh no tiene el permiso "project". Ejecuta: gh auth refresh -s project'
        if not prueba:
            sys.exit(f'Error: {mensaje}')
        print(f'Aviso: {mensaje}\n')


def main() -> None:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    analizador = argparse.ArgumentParser(description='Crea y mantiene los proyectos de GitHub que '
                                         'planifican los ejercicios del ePortfolio.')
    analizador.add_argument('--datos', default=str(Path(__file__).with_name('planificacion.toml')),
                            help='fichero de la planificación (por defecto, planificacion.toml)')
    analizador.add_argument('--dry-run', action='store_true', help='muestra lo que haría, sin tocar GitHub')
    analizador.add_argument('--sin-conexion', action='store_true',
                            help='con --dry-run, no consulta GitHub: supone que no existe nada')
    analizador.add_argument('--calendario', action='store_true', help='solo muestra las fechas calculadas')
    analizador.add_argument('--destino', action='append', metavar='NOMBRE',
                            help='procesa solo ese destino (se puede repetir)')
    analizador.add_argument('--csv', metavar='FICHERO', help='guarda las fechas calculadas en un CSV')
    opciones = analizador.parse_args()

    ruta = Path(opciones.datos)
    with open(ruta, 'rb') as fichero:
        datos = tomllib.load(fichero)
    calendario = Calendario(datos['calendario'])
    tramos, avisos = planificar(datos, calendario)
    elementos, avisos_issues = elementos_de(tramos, ruta.parent / datos['github']['carpeta_issues'])
    mostrar_calendario(tramos, elementos, calendario)
    for aviso in avisos + avisos_issues:
        print(f'Aviso: {aviso}')
    if opciones.csv:
        guardar_csv(opciones.csv, tramos)
    if opciones.calendario:
        return

    hay_gh = shutil.which('gh') is not None
    if not opciones.dry_run and (not hay_gh or opciones.sin_conexion):
        sys.exit('Error: hace falta gh para ejecutar el script sin --dry-run.')
    conexion = hay_gh and not opciones.sin_conexion
    if opciones.dry_run:
        print('\nMODO DE PRUEBA: no se cambia nada en GitHub.'
              + ('' if conexion else ' Sin conexión: se supone que no existe nada.'))
    if conexion:
        comprobar_gh(opciones.dry_run)
    gh = GH(opciones.dry_run, conexion)

    destinos = [Destino(d, datos, gh, tramos, elementos) for d in datos['destinos']]
    if opciones.destino:
        nombres = {d.nombre for d in destinos}
        desconocidos = [n for n in opciones.destino if n not in nombres]
        if desconocidos:
            sys.exit(f'Error: no hay ningún destino {", ".join(desconocidos)}. Destinos: {", ".join(sorted(nombres))}')

    # El de referencia va siempre primero: los demás proyectos se copian de él.
    referencia = next((d for d in destinos if d.referencia), None)
    numero_referencia = buscar_proyecto(gh, referencia.org, referencia.titulo) if referencia else None
    for destino in sorted(destinos, key=lambda d: not d.referencia):
        if opciones.destino and destino.nombre not in opciones.destino:
            continue
        numero = destino.procesar(numero_referencia)
        if destino.referencia and numero is not None:
            numero_referencia = numero


if __name__ == '__main__':
    main()
