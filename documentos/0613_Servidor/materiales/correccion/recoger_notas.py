"""Reúne en CSV los resultados de la corrección automática de los pull requests.

Lee los artefactos que guarda el flujo correccion.yml en el repositorio base (un JUnit XML
y un meta.json por ejecución) y genera dos ficheros:

- notas_detalle.csv: una fila por pull request y clase de test, con los tests aprobados,
  los fallidos y sus nombres.
- notas_resumen.csv: una fila por alumno y una columna por apartado (RA5_1 … RA5_8), con la
  nota (0-10) del test de ese apartado en su último pull request de ese apartado.

La columna revisar señala los pull requests que cambian ficheros de .github/: el flujo de
corrección que se ejecuta es el del propio pull request, así que su nota no es fiable hasta
que lo revises.

Los CSV se escriben en UTF-8 con BOM, para que Excel y LibreOffice muestren bien las tildes.

Necesita GitHub CLI (gh) identificado con una cuenta con acceso al repositorio
(00_instalacionEntorno.md, §12). Uso:

    python3 recoger_notas.py 2DAW-CarlosIII/marcapersonalfp2627 [alumnos.csv]

alumnos.csv (opcional) relaciona cada usuario de GitHub con el nombre del alumno:

    usuario_github,alumno
    analopez,Ana López
"""

import csv
import io
import json
import subprocess
import sys
import xml.etree.ElementTree as ET
import zipfile

APARTADOS = [f'RA5_{n}' for n in range(1, 9)]


def gh(*argumentos: str) -> bytes:
    """Ejecuta gh y devuelve su salida; si falla, termina con su mensaje de error."""
    resultado = subprocess.run(['gh', *argumentos], capture_output=True)
    if resultado.returncode != 0:
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


def main() -> None:
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    repositorio = sys.argv[1]

    alumnos: dict[str, str] = {}
    if len(sys.argv) > 2:
        with open(sys.argv[2], encoding='utf-8-sig', newline='') as fichero:
            alumnos = {fila['usuario_github'].lower(): fila['alumno'] for fila in csv.DictReader(fichero)}

    detalle = []
    resumen: dict[str, dict[str, str]] = {}   # usuario → apartado → nota
    revisar: dict[str, set[str]] = {}          # usuario → pull requests que tocan .github/
    sospechosos: dict[int, str] = {}           # pr → ficheros de .github/ que cambia

    for artefacto in artefactos(repositorio):
        contenido = gh('api', f'repos/{repositorio}/actions/artifacts/{artefacto["id"]}/zip')
        with zipfile.ZipFile(io.BytesIO(contenido)) as zip_:
            nombres = zip_.namelist()
            if 'meta.json' not in nombres:
                continue
            meta = json.loads(zip_.read('meta.json'))
            clases = clases_del_junit(zip_.read('resultados.xml')) if 'resultados.xml' in nombres else {}

        usuario = meta['autor'].lower()
        if meta['pr'] not in sospechosos:
            sospechosos[meta['pr']] = ficheros_sospechosos(repositorio, meta['pr'])
        if sospechosos[meta['pr']]:
            revisar.setdefault(usuario, set()).add(f"#{meta['pr']}")
        for clase, datos in sorted(clases.items()):
            nota = round(10 * datos['aprobados'] / datos['tests'], 2) if datos['tests'] else 0
            detalle.append({
                'usuario_github': usuario,
                'alumno': alumnos.get(usuario, ''),
                'pr': meta['pr'],
                'rama': meta['rama'],
                'apartado_pr': meta['apartado'],
                'clase': clase,
                'tests': datos['tests'],
                'aprobados': datos['aprobados'],
                'fallidos': len(datos['fallidos']),
                'nota': nota,
                'fecha': artefacto['fecha'],
                'sha': meta['sha'],
                'tests_fallidos': '; '.join(datos['fallidos']),
                'revisar': sospechosos[meta['pr']],
            })
            # La nota de un apartado es la de su propio test en el pull request de ese apartado.
            # Como los artefactos van en orden de fecha, el último pull request sobrescribe a los anteriores.
            if clase.startswith(meta['apartado'] + '_'):
                resumen.setdefault(usuario, {})[meta['apartado']] = nota

    with open('notas_detalle.csv', 'w', encoding='utf-8-sig', newline='') as fichero:
        campos = ['usuario_github', 'alumno', 'pr', 'rama', 'apartado_pr', 'clase', 'tests',
                  'aprobados', 'fallidos', 'nota', 'fecha', 'sha', 'tests_fallidos', 'revisar']
        escritor = csv.DictWriter(fichero, fieldnames=campos)
        escritor.writeheader()
        escritor.writerows(detalle)

    with open('notas_resumen.csv', 'w', encoding='utf-8-sig', newline='') as fichero:
        escritor = csv.writer(fichero)
        escritor.writerow(['usuario_github', 'alumno', *APARTADOS, 'revisar'])
        for usuario in sorted(set(resumen) | set(alumnos)):
            notas = resumen.get(usuario, {})
            escritor.writerow([usuario, alumnos.get(usuario, ''), *(notas.get(a, '') for a in APARTADOS),
                               ' '.join(sorted(revisar.get(usuario, set())))])

    print(f'{len(detalle)} filas en notas_detalle.csv; {len(resumen)} alumnos con nota en notas_resumen.csv.')


if __name__ == '__main__':
    main()
