"""Reúne en CSV los resultados de la corrección automática de los pull requests.

Lee los artefactos que guarda el flujo correccion.yml en uno o varios repositorios (los de
ePortfolio de los grupos) y genera dos ficheros:

- notas_detalle.csv: una fila por pull request y clase de test, con los tests aprobados,
  los fallidos y sus nombres. La columna tipo distingue el test del ejercicio, el que cuenta
  para la nota, de los tests de los issues anteriores, que solo comprueban que nada se ha roto.
- notas_resumen.csv: una fila por alumno y una columna por ejercicio (RA5_2, RA5_3, RA5_4,
  RA5_5_principal, RA5_5_evidencias…), con la nota (0-10) del test de ese ejercicio en su
  último pull request de ese ejercicio.

La columna revisar señala los pull requests que cambian ficheros de .github/: el flujo de
corrección que se ejecuta es el del propio pull request, así que su nota no es fiable hasta
que lo revises. Además, el script avisa si el correccion.yml de la rama principal de un
repositorio no coincide con el de esta carpeta.

También lee los artefactos del flujo anterior, el de marcapersonalfp (hasta 5.4), en los que
el ejercicio es el apartado de la rama.

Los CSV se escriben en UTF-8 con BOM, para que Excel y LibreOffice muestren bien las tildes.

Necesita GitHub CLI (gh) identificado con una cuenta con acceso a los repositorios
(00_instalacionEntorno.md, §12). Uso:

    python3 recoger_notas.py [--alumnos alumnos.csv] REPOSITORIO [REPOSITORIO ...]
    python3 recoger_notas.py --alumnos alumnos.csv 2DAW-CarlosIII/eportfolio_g{1..5}

alumnos.csv (opcional) relaciona cada usuario de GitHub con el nombre del alumno:

    usuario_github,alumno
    analopez,Ana López
"""

import argparse
import base64
import csv
import io
import json
import re
import subprocess
import sys
import xml.etree.ElementTree as ET
import zipfile
from pathlib import Path

FLUJO = '.github/workflows/correccion.yml'


def gh(*argumentos: str, tolerar: bool = False) -> bytes | None:
    """Ejecuta gh y devuelve su salida; si falla, termina con su mensaje de error (o devuelve None)."""
    resultado = subprocess.run(['gh', *argumentos], capture_output=True)
    if resultado.returncode != 0:
        if tolerar:
            return None
        sys.exit(resultado.stderr.decode(errors='replace'))
    return resultado.stdout


def artefactos(repositorio: str) -> list[dict]:
    """Artefactos 'resultados-pr-…' no caducados, del más antiguo al más reciente."""
    lineas = gh('api', f'repos/{repositorio}/actions/artifacts', '--paginate',
                '--jq', '.artifacts[] | [.id, .name, .created_at, .expired] | @tsv').decode().splitlines()
    lista = []
    for linea in lineas:
        id_, nombre, fecha, caducado = linea.split('\t')
        if nombre.startswith('resultados-pr-') and caducado == 'false':
            lista.append({'id': id_, 'nombre': nombre, 'fecha': fecha})
    return sorted(lista, key=lambda a: a['fecha'])


def ficheros_sospechosos(repositorio: str, pr: int) -> str:
    """Ficheros de .github/ que cambia el pull request: el flujo de corrección es el suyo."""
    rutas = gh('pr', 'view', str(pr), '-R', repositorio, '--json', 'files',
               '--jq', '.files[].path').decode().splitlines()
    return '; '.join(ruta for ruta in rutas if ruta.startswith('.github/'))


def comprobar_flujo(repositorio: str) -> None:
    """Avisa si el correccion.yml de la rama principal no es el de esta carpeta."""
    referencia = Path(__file__).with_name('correccion.yml').read_text(encoding='utf-8')
    salida = gh('api', f'repos/{repositorio}/contents/{FLUJO}', '--jq', '.content', tolerar=True)
    if salida is None:
        print(f'Aviso: {repositorio} no tiene {FLUJO} en su rama principal.')
        return
    contenido = base64.b64decode(salida.decode().replace('\\n', '')).decode('utf-8', errors='replace')
    if contenido.replace('\r\n', '\n').strip() != referencia.replace('\r\n', '\n').strip():
        print(f'Aviso: el {FLUJO} de {repositorio} no coincide con el de esta carpeta. '
              'Revisa quién lo ha cambiado antes de fiarte de sus notas.')


def clases_del_junit(xml: bytes) -> dict[str, dict]:
    """Por cada clase de test: tests, aprobados y nombres de los fallidos."""
    clases: dict[str, dict] = {}
    for caso in ET.fromstring(xml).iter('testcase'):
        clase = (caso.get('class') or caso.get('classname') or '').replace('.', '\\').split('\\')[-1]
        datos = clases.setdefault(clase, {'tests': 0, 'aprobados': 0, 'fallidos': []})
        datos['tests'] += 1
        if caso.find('failure') is not None or caso.find('error') is not None:
            datos['fallidos'].append(caso.get('name'))
        else:
            datos['aprobados'] += 1
    return clases


def orden_ejercicio(ejercicio: str) -> tuple:
    """RA5_2 < RA5_5_principal < RA5_5_ciclos-formativos < … < RA5_10."""
    encontrado = re.match(r'RA(\d+)_(\d+)_?(.*)', ejercicio)
    if not encontrado:
        return (999, 999, 1, ejercicio)
    ra, apartado, grupo = encontrado.groups()
    return (int(ra), int(apartado), grupo != 'principal' and grupo != '', grupo)


def main() -> None:
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    analizador = argparse.ArgumentParser(description='Reúne en CSV los resultados de la corrección automática.')
    analizador.add_argument('repositorios', nargs='+', metavar='REPOSITORIO',
                            help='organización/repositorio, por ejemplo 2DAW-CarlosIII/eportfolio_g1')
    analizador.add_argument('--alumnos', metavar='CSV', help='relación entre usuarios de GitHub y alumnos')
    opciones = analizador.parse_args()

    alumnos: dict[str, str] = {}
    if opciones.alumnos:
        with open(opciones.alumnos, encoding='utf-8-sig', newline='') as fichero:
            alumnos = {fila['usuario_github'].lower(): fila['alumno'] for fila in csv.DictReader(fichero)}

    detalle = []
    resumen: dict[str, dict[str, float]] = {}  # usuario → ejercicio → nota
    revisar: dict[str, set[str]] = {}          # usuario → pull requests que tocan .github/
    grupos: dict[str, set[str]] = {}           # usuario → repositorios en los que tiene pull requests
    ejercicios: set[str] = set()

    for repositorio in opciones.repositorios:
        comprobar_flujo(repositorio)
        sospechosos: dict[int, str] = {}  # pr → ficheros de .github/ que cambia
        for artefacto in artefactos(repositorio):
            contenido = gh('api', f'repos/{repositorio}/actions/artifacts/{artefacto["id"]}/zip')
            with zipfile.ZipFile(io.BytesIO(contenido)) as zip_:
                nombres = zip_.namelist()
                if 'meta.json' not in nombres:
                    continue
                meta = json.loads(zip_.read('meta.json'))
                junit = {tipo: clases_del_junit(zip_.read(fichero)) for tipo, fichero in
                         (('ejercicio', 'resultados.xml'), ('anterior', 'anteriores.xml')) if fichero in nombres}

            usuario = meta['autor'].lower()
            # En el flujo de marcapersonalfp no había ejercicio: era el apartado de la rama.
            ejercicio = meta.get('ejercicio', meta['apartado'])
            grupos.setdefault(usuario, set()).add(repositorio)
            if meta['pr'] not in sospechosos:
                sospechosos[meta['pr']] = ficheros_sospechosos(repositorio, meta['pr'])
            if sospechosos[meta['pr']]:
                revisar.setdefault(usuario, set()).add(f"{repositorio}#{meta['pr']}")

            for fichero, clases in junit.items():
                for clase, datos in sorted(clases.items()):
                    # En los artefactos de marcapersonalfp, resultados.xml traía también los apartados anteriores.
                    tipo = 'ejercicio' if fichero == 'ejercicio' and clase.startswith(meta['apartado'] + '_') else 'anterior'
                    nota = round(10 * datos['aprobados'] / datos['tests'], 2) if datos['tests'] else 0
                    detalle.append({
                        'repositorio': repositorio,
                        'usuario_github': usuario,
                        'alumno': alumnos.get(usuario, ''),
                        'pr': meta['pr'],
                        'rama': meta['rama'],
                        'ejercicio': ejercicio,
                        'clase': clase,
                        'tipo': tipo,
                        'tests': datos['tests'],
                        'aprobados': datos['aprobados'],
                        'fallidos': len(datos['fallidos']),
                        'nota': nota,
                        'fecha': artefacto['fecha'],
                        'sha': meta['sha'],
                        'tests_fallidos': '; '.join(datos['fallidos']),
                        'revisar': sospechosos[meta['pr']],
                    })
                    # La nota de un ejercicio es la de su propio test en el pull request de ese ejercicio.
                    # Como los artefactos van en orden de fecha, el último pull request sobrescribe a los anteriores.
                    if tipo == 'ejercicio' and ejercicio:
                        resumen.setdefault(usuario, {})[ejercicio] = nota
                        ejercicios.add(ejercicio)

    with open('notas_detalle.csv', 'w', encoding='utf-8-sig', newline='') as fichero:
        campos = ['repositorio', 'usuario_github', 'alumno', 'pr', 'rama', 'ejercicio', 'clase', 'tipo', 'tests',
                  'aprobados', 'fallidos', 'nota', 'fecha', 'sha', 'tests_fallidos', 'revisar']
        escritor = csv.DictWriter(fichero, fieldnames=campos)
        escritor.writeheader()
        escritor.writerows(detalle)

    columnas = sorted(ejercicios, key=orden_ejercicio)
    with open('notas_resumen.csv', 'w', encoding='utf-8-sig', newline='') as fichero:
        escritor = csv.writer(fichero)
        escritor.writerow(['usuario_github', 'alumno', 'repositorio', *columnas, 'revisar'])
        for usuario in sorted(set(resumen) | set(alumnos)):
            notas = resumen.get(usuario, {})
            escritor.writerow([usuario, alumnos.get(usuario, ''), ' '.join(sorted(grupos.get(usuario, set()))),
                               *(notas.get(e, '') for e in columnas),
                               ' '.join(sorted(revisar.get(usuario, set())))])

    print(f'{len(detalle)} filas en notas_detalle.csv; {len(resumen)} alumnos con nota en notas_resumen.csv.')


if __name__ == '__main__':
    main()
