# 0. Instalación y preparación del entorno

Todo el módulo, desde el Bloque 2 hasta el Bloque 8, se trabaja sobre **el mismo entorno**: una máquina con _Docker_ y _Laradock_ (_nginx_ + _php-fpm_ + base de datos + herramientas de línea de comandos). Esta página describe cómo se construye ese entorno **desde una instalación mínima de Debian**. Si estás usando la máquina virtual que se entrega con el módulo, **ya lo tienes preinstalado** y puedes ir directamente al [Bloque 2](./RA2_1_phpEmbebido.md); esta guía sirve como referencia y para reconstruir el entorno si hiciera falta.

## Punto de partida

- **Debian** ([netinstall, versiones 12/13](https://cdimage.debian.org/debian-cd/current/amd64/iso-cd/)), instalado seleccionando únicamente *standard system utilities* — sin entorno de escritorio.
- **Particionado con LVM.** En el paso *Partition disks* del instalador, elige **"Guided - use entire disk and set up LVM"**, selecciona el disco y, cuando pregunte el esquema, marca **"Separate /home partition"** (para tener `/` y `/home` como volúmenes separados). El instalador propondrá un reparto automático del espacio; **antes de confirmar** ("Finish partitioning and write changes to disk"), entra en cada volumen lógico propuesto y usa la opción de redimensionar para dejar aproximadamente **25 GB para `/`** y **10 GB para `/home`** (el resto del disco puede quedar sin asignar en el volumen de grupo, o repartirse como prefieras). Confirma los cambios y deja que el instalador escriba la tabla de particiones.
- Conexión a internet.

## 1. Escritorio: Debian Openbox

Sobre esa instalación mínima montamos un escritorio ligero (Openbox + terminal + editor + accesos a las apps que usaremos) con [debian-openbox](https://github.com/leomarcov/debian-openbox), un conjunto de scripts que automatizan toda la configuración.

Antes de lanzar el instalador hacen falta algunas herramientas base que una instalación mínima de Debian no trae por defecto:

```bash
sudo apt update
sudo apt install -y curl git build-essential gcc make
```

Con eso ya se puede clonar el repositorio y ejecutar el instalador:

```bash
git clone https://github.com/leomarcov/debian-openbox
cd debian-openbox
./install -y
```

`-y` responde automáticamente "sí" a todas las acciones del instalador (unas 40, entre escritorio, atajos de teclado, aplicaciones, etc.), para una instalación desatendida de principio a fin — es la opción recomendada para preparar la VM del módulo. Si en algún momento quieres elegir qué instalar, `./install` (sin flags) pregunta acción por acción, `./install -d` aplica solo las acciones marcadas por defecto, y `./install -l` lista las acciones disponibles.

Al terminar, reinicia la máquina.

## 2. Docker

Instalación de _Docker Engine_ en Debian mediante el repositorio oficial de _apt_:

```bash
sudo apt update
sudo apt install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/debian
Suites: $(. /etc/os-release && echo "$VERSION_CODENAME")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Verifica que funciona:

```bash
sudo docker run hello-world
```

Para no tener que anteponer `sudo` a cada comando `docker`/`docker compose` (los usaremos constantemente en el resto de esta guía y en cada bloque del módulo), añade tu usuario al grupo `docker`:

```bash
sudo groupadd docker
sudo usermod -aG docker $USER
newgrp docker
```

(si `newgrp` no surte efecto, cierra sesión y vuelve a entrar). Comprueba que ya funciona sin `sudo`:

```bash
docker run hello-world
```

## 3. Descargar Laradock

```bash
cd ~/Documentos
mkdir laravel
cd laravel/
```

```bash
git clone https://github.com/Laradock/laradock.git
```

## 4. Crear el fichero de configuración

```bash
cd laradock && cp .env.example .env && cd ..
```

## 5. Seleccionar la versión de PHP y el motor de base de datos

Edita el `.env` de la carpeta `laradock`:

- **Versión de PHP:** `PHP_VERSION=8.4` — es la que usa el proyecto Laravel del módulo (Laravel 12 requiere PHP ≥ 8.2; usamos 8.4 para que coincida con el entorno real de desarrollo del código).
- **Motor de base de datos de phpMyAdmin:** `PMA_DB_ENGINE=mariadb`

## 6. Node.js y npm en el contenedor `workspace`

Aunque en el Bloque 2 solo usaremos PHP, **Node y npm harán falta más adelante**: el arranque de _Breeze_ (Bloque 4) compila sus assets con _Vite_/npm, y _React-Admin_ (Bloque 7) también depende de un toolchain de Node. Como todo el módulo comparte el mismo entorno, se instalan ahora junto con lo demás en vez de a mitad de curso.

En el `.env`:

```
WORKSPACE_INSTALL_NODE=true
WORKSPACE_NODE_VERSION=22
```

(`22` es la versión LTS activa; ajusta el número si para cuando impartas el curso hay una LTS más reciente). Node y npm quedan disponibles **dentro del contenedor `workspace`**, igual que Composer — no hace falta instalar nada en el sistema.

## 7. Activar la depuración con Xdebug

En el `.env`:

```
WORKSPACE_INSTALL_XDEBUG=true
PHP_FPM_INSTALL_XDEBUG=true
WORKSPACE_XDEBUG_PORT=9003
PHP_FPM_XDEBUG_PORT=9003
```

Laradock ya resuelve por sí mismo el problema de "cómo llega el contenedor hasta tu editor": tanto `workspace/compose.yml` como `php-fpm/compose.yml` declaran

```yaml
extra_hosts:
  - "dockerhost:${DOCKER_HOST_IP}"
```

es decir, dentro de ambos contenedores el nombre **`dockerhost`** apunta siempre a la IP que definas en `DOCKER_HOST_IP` (`.env`). El valor por defecto (`10.0.75.1`) es un resto de Docker Desktop para Mac/Windows y **no sirve en esta VM Debian**; hay que sustituirlo por la IP del bridge de Docker del host:

```bash
docker network inspect bridge --format '{{(index .IPAM.Config 0).Gateway}}'
```

(normalmente `172.17.0.1`) y fijarla en el `.env`:

```
DOCKER_HOST_IP=172.17.0.1
```

Y en **ambos** ficheros `php-fpm/xdebug.ini` y `workspace/xdebug.ini`, apunta a `dockerhost` (no a `host.docker.internal`):

```ini
; NOTE: The actual debug.so extention is NOT SET HERE but rather (/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini)

xdebug.client_host="dockerhost"
xdebug.discover_client_host=1
xdebug.client_port=9003
xdebug.idekey=vsc
xdebug.mode=debug
xdebug.start_with_request=trigger

xdebug.cli_color=0
xdebug.output_dir="~/xdebug/phpstorm/tmp/profiling"

xdebug.var_display_max_children=-1
xdebug.var_display_max_data=-1
xdebug.var_display_max_depth=-1
```

> **Por qué `dockerhost` y no `host.docker.internal`.** Este mecanismo lo aporta el propio Laradock vía `extra_hosts`/`DOCKER_HOST_IP`, y funciona igual en Linux, macOS y Windows — a diferencia de `host.docker.internal`, que en Docker Engine sobre Linux no siempre se resuelve. Compruébalo tras levantar los contenedores (paso 9):
>
> ```bash
> docker compose exec workspace getent hosts dockerhost
> docker compose exec php-fpm getent hosts dockerhost
> ```
>
> Ambos deben devolver la IP que pusiste en `DOCKER_HOST_IP`.

**Una configuración, todo el curso.** Xdebug se activa a nivel del intérprete PHP (dentro de `php-fpm`), así que depura por igual los scripts *vanilla* de los Bloques 2–3 (`vanilla_php/public/RA2_*.php`) y las peticiones de _Laravel_ del Bloque 4 en adelante (`public/index.php` del proyecto Laravel) — no hay que tocar nada al cambiar de uno a otro.

## 8. Ajustes de MariaDB

- Edita `mariadb/my.cnf` y asigna **256M** a `innodb_log_file_size` (en lugar de los 4048M por defecto, excesivos para una VM de desarrollo).
- Edita `mariadb/Dockerfile` y sustituye `CMD ["mysqld"]` por `CMD ["mariadbd"]`. Es un ajuste conocido en versiones anteriores de la imagen; si al levantar el contenedor arranca sin problema tal cual, no hace falta tocarlo — solo compruébalo (paso 9) y aplícalo si el contenedor falla con `mysqld: not found`.

## 9. Levantar el entorno

```bash
cd laradock
docker compose up -d nginx php-fpm workspace mariadb phpmyadmin
```

(Verifica con `docker compose config --services` los nombres exactos de los servicios en tu versión de Laradock, por si difieren.)

> **Si ya tenías los contenedores levantados y cambias algo del `.env` o de un `xdebug.ini` después**, un simple reinicio no basta para todo. `PHP_VERSION`, `WORKSPACE_INSTALL_NODE`/`NODE_VERSION`, `*_INSTALL_XDEBUG` y el propio contenido de `xdebug.ini` se incorporan a la imagen **en tiempo de build** (`COPY`/`ARG` en el `Dockerfile`), así que necesitan reconstrucción; en cambio `DOCKER_HOST_IP` o los puertos de Xdebug solo necesitan recrear el contenedor. Un único comando cubre ambos casos (la caché de capas de Docker evita reconstruir lo que no cambió):
> ```bash
> docker compose up -d --build workspace php-fpm
> ```

## 10. Verificación

```bash
docker compose ps
```

Todos los contenedores deben aparecer como `running`. Después:

```bash
docker compose exec workspace php --version
docker compose exec workspace composer --version
docker compose exec workspace node --version
docker compose exec workspace npm --version
```

Y, para Xdebug, la comprobación de `dockerhost` del paso 7.

## 11. Proyecto `vanilla_php` y PHPUnit (Bloques 2 y 3)

`~/Documentos/laravel/` no es un proyecto único: es la carpeta de trabajo donde a lo largo del curso se irán creando varios proyectos — el de PHP «vanilla» de los Bloques 2 y 3, y más adelante el proyecto _Laravel_ de _marcapersonalFP_ (Bloque 4), además de otros que puedan surgir. Por eso cada proyecto vive en su propia subcarpeta, y esta primera se llama **`vanilla_php`**.

> **"Vanilla" frente a "con framework".** En programación, "vanilla" (del inglés, "sabor base, sin nada añadido") significa escribir código usando solo las herramientas propias del lenguaje, sin ningún _framework_ de por medio: aquí, PHP tal cual lo procesa el intérprete, embebido en HTML. Un _framework_ (como _Laravel_, en el Bloque 4) es una capa de librerías y convenciones que automatiza buena parte de ese trabajo (rutas, plantillas, acceso a datos…) a cambio de que sigas sus reglas. Empezar en `vanilla_php` deja ver qué hace cada línea antes de delegarlo en un framework.

Crea la carpeta y su estructura:

```bash
cd ~/Documentos/laravel
mkdir -p vanilla_php/public vanilla_php/tests
```

`vanilla_php/composer.json`:

```json
{
    "require-dev": {
        "phpunit/phpunit": "^11"
    }
}
```

`vanilla_php/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Bloques 2-3">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Instala PHPUnit dentro de `workspace`, situándote en esa subcarpeta (Composer ya está disponible ahí, no hace falta nada en el sistema):

```bash
docker compose exec --workdir /var/www/vanilla_php workspace composer install
```

Verifica:

```bash
docker compose exec --workdir /var/www/vanilla_php workspace vendor/bin/phpunit --version
```

Como `~/Documentos/laravel/` ahora aloja varios proyectos, hay que decirle a _nginx_ cuál está activo. Para los Bloques 2 y 3, edita `laradock/nginx/sites/default.conf` y cambia:

```nginx
root /var/www/public;
```

por:

```nginx
root /var/www/vanilla_php/public;
```

Este fichero está montado como volumen (no forma parte de la imagen), así que no hace falta reconstruir nada — basta con `docker compose restart nginx` si los contenedores ya estaban levantados, o simplemente arrancarlos por primera vez en el paso siguiente.

Cada ejercicio de los Bloques 2 y 3 tiene su test correspondiente en [`materiales/ejercicios-vanilla/tests/`](./materiales/ejercicios-vanilla/tests/) — el propio bloque indica, en cada caso, qué fichero copiar a `vanilla_php/tests/` y con qué comando ejecutarlo.

> **Esto es temporal.** Tanto `vanilla_php/composer.json`/`phpunit.xml` como el `root` de _nginx_ apuntando a `vanilla_php/public` son específicos de la etapa PHP vanilla. En el Bloque 4, al crear el proyecto _Laravel_ de _marcapersonalFP_ en su propia subcarpeta (`composer create-project`), se repite el mismo gesto — apuntar el `root` de _nginx_ a la subcarpeta activa — y se pasa a usar `php artisan test` en lugar de `vendor/bin/phpunit` directamente.

## 12. Limpiar la MV si se va a exportar

Si la intención es exportar la máquina virtual para su posterior exportación, conviene limpiar contenido que no sea útil para la exportación y que incrementaría el tamaño del archivo de exportación.

Para ello, genera el script `limpiar_vm.sh` con el siguiente contenido:

```bash
#!/usr/bin/env bash
#
# limpiar_vm.sh
# Limpieza previa a la exportación OVA de una VM Debian 13 + Openbox + Docker/Laradock
#
# USO:
#   sudo bash limpiar_vm.sh
#
# Qué hace:
#   1. Limpia cachés y paquetes huérfanos de APT
#   2. Limpia recursos "sueltos" de Docker (sin tocar las imágenes/volúmenes de laradock en uso)
#   3. Vacía logs, journal, /tmp, /var/tmp, cachés de usuario
#   4. Borra kernels antiguos no usados
#   5. Rellena el espacio libre con ceros para que el disco comprima mejor al exportar
#
# IMPORTANTE:
#   - Ejecútalo como root (sudo).
#   - Detén servicios que generen logs pesados si puedes, antes de correrlo.
#   - El paso de "zero-fill" tarda bastante y necesita espacio libre similar al usado;
#     al terminar borra el fichero temporal automáticamente.
#   - Tras apagar la VM, desde el HOST conviene compactar el disco:
#       VBoxManage modifymedium --compact /ruta/al/disco.vdi
#     (solo funciona si el disco es de tipo "dynamically allocated").

# set -euo pipefail

# ---- comprobación de root ----
if [[ $EUID -ne 0 ]]; then
  echo "Este script debe ejecutarse como root (usa: sudo bash $0)" >&2
  exit 1
fi

echo "==> 1. Limpiando APT (paquetes, cachés, huérfanos)"
apt-get clean
apt-get autoclean -y
apt-get autoremove --purge -y

echo "==> 2. Eliminando kernels antiguos (se conserva el actual)"
CURRENT_KERNEL=$(uname -r)
dpkg -l 'linux-image-*' 2>/dev/null | awk '/^ii/{print $2}' | grep -v "$CURRENT_KERNEL" | while read -r pkg; do
  echo "   Eliminando $pkg"
  apt-get purge -y "$pkg" || true
done || true
apt-get autoremove --purge -y

echo "==> 3. Limpiando Docker"
if command -v docker &>/dev/null; then
  # Elimina contenedores parados, redes no usadas, imágenes dangling y caché de build.
  # NO borra imágenes/volúmenes en uso por los contenedores activos de laradock.
  docker container prune -f
  docker network prune -f
  docker image prune -f
  docker builder prune -af

  # Si además quieres eliminar TODAS las imágenes no usadas por ningún contenedor
  # (incluso las que no son "dangling"), descomenta la siguiente línea.
  # Cuidado: tendrás que volver a descargar/reconstruir esas imágenes.
  # docker image prune -af

  echo "   Espacio usado por Docker tras la limpieza:"
  docker system df
else
  echo "   Docker no encontrado, se omite este paso."
fi

echo "==> 4. Vaciando journal y logs"
journalctl --vacuum-time=1d || true
find /var/log -type f -regex '.*\.\(gz\|[0-9]\)$' -delete
find /var/log -type f -name "*.log" -exec truncate -s 0 {} \;

echo "==> 5. Limpiando /tmp y /var/tmp"
find /tmp -mindepth 1 -delete 2>/dev/null || true
find /var/tmp -mindepth 1 -delete 2>/dev/null || true

echo "==> 6. Limpiando cachés de usuario (thumbnails, cachés de apps, papelera)"
for home in /root /home/*; do
  [[ -d "$home" ]] || continue
  rm -rf "$home/.cache"/* 2>/dev/null || true
  rm -rf "$home/.local/share/Trash"/* 2>/dev/null || true
  rm -rf "$home/.thumbnails"/* 2>/dev/null || true
  # historial de bash (opcional, descomenta si te vale)
  # : > "$home/.bash_history" 2>/dev/null || true
done

echo "==> 7. Limpiando cachés de Composer / npm dentro del contenedor 'workspace'"
# PHP, Composer y npm no están instalados en el host (viven solo en los
# contenedores de Laradock), así que las cachés a limpiar están ahí dentro.
# Se busca el contenedor por nombre parcial y se usa "docker exec" directo:
# Compose prefija el nombre real con el del proyecto (p. ej.
# "laradock-workspace-1"), así que no conviene darlo por fijo. Tampoco se
# depende de reconstruir la ruta al docker-compose.yml (bajo "sudo", $HOME
# pasa a ser el de root, no el del usuario que invoca el script).
WORKSPACE_CONTAINER=$(docker ps --format '{{.Names}}' | grep workspace | head -1)
if [[ -n "$WORKSPACE_CONTAINER" ]]; then
  docker exec "$WORKSPACE_CONTAINER" composer clear-cache 2>/dev/null || true
  docker exec "$WORKSPACE_CONTAINER" npm cache clean --force 2>/dev/null || true
else
  echo "   No se encontró ningún contenedor 'workspace' en ejecución, se omite este paso."
  echo "   (Súbelo con 'docker compose up -d workspace' antes de ejecutar este script si quieres limpiar sus cachés.)"
fi

echo "==> 8. Rellenando espacio libre con ceros (mejora la compresión del OVA)"
# / y /home son volúmenes LVM separados (ver "Punto de partida"), así que
# hay que rellenar el espacio libre de los dos, no solo el de /.
for MOUNT in / /home; do
  mountpoint -q "$MOUNT" || continue
  FREE_MB=$(df --output=avail -m "$MOUNT" | tail -1)
  # Dejamos un margen de seguridad de 200 MB para no llenar el disco al 100%
  SAFE_MB=$(( FREE_MB - 200 ))
  ZEROFILE="${MOUNT%/}/ZEROFILE"
  if (( SAFE_MB > 0 )); then
    echo "   Escribiendo ~${SAFE_MB}MB de ceros en $ZEROFILE (puede tardar varios minutos)..."
    dd if=/dev/zero of="$ZEROFILE" bs=1M count="$SAFE_MB" status=progress || true
    rm -f "$ZEROFILE"
  else
    echo "   Poco espacio libre en $MOUNT, se omite el zero-fill."
  fi
done
sync

echo "==> Limpieza completada."
echo ""
echo "Siguientes pasos recomendados:"
echo "  1. Apaga la VM (poweroff)."
echo "  2. (Opcional, VirtualBox) En el HOST compacta el disco:"
echo "       VBoxManage modifymedium --compact /ruta/al/disco.vdi"
echo "       & 'C:\Program Files\Oracle\VirtualBox\VBoxManage.exe' modifymedium --compact /ruta/al/disco.vdi"
echo "  3. Exporta con VBoxManage export o desde el menú Archivo > Exportar servicio virtualizado."
```

Ejecútalo así (al invocarlo con `bash`, no hace falta darle permiso de ejecución):

```bash
sudo bash limpiar_vm.sh
```

---

Con esto el entorno está listo para todo el módulo. El primer uso práctico —servir un script PHP y verlo en el navegador— se explica en [2.1. PHP embebido en HTML](./RA2_1_phpEmbebido.md).
