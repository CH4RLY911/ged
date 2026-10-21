# GED — Gestión Editorial

Primera etapa: acceso del propietario y biblioteca de ediciones con almacenamiento real en SQLite. Sin dependencias de ejecución externas ni compilación del frontend. El catálogo empieza vacío.

## Módulos

- `app/Access/`: autenticación, sesiones, contraseña y controlador de acceso.
- `app/Library/`: servicio de validación `Catalog`, repositorio PDO `BookRepository` y controlador del catálogo.
- `app/Covers/`: validación y almacenamiento privado de imágenes.
- `app/Database.php`: conexión y migraciones versionadas.
- `views/`: HTML del acceso, biblioteca, ficha y formularios.
- `public/`: único directorio que debe exponerse por HTTP; entrada y recursos estáticos.
- `config/`: configuración privada y ejemplo sin secretos.
- `storage/`: SQLite y tapas; excluidos de Git.
- `bin/console.php`: instalación, recuperación y respaldo.
- `tests/acceptance.cjs`: pruebas de extremo a extremo en una instalación temporal.

Los módulos comparten persistencia y utilidades; no son servicios separados. Una futura API podrá invocar el servicio `Catalog` y los repositorios sin usar las vistas. No hay endpoints ni botones de integraciones futuras.

## Requisitos e instalación local

PHP **8.2 o posterior de 64 bits**, PDO SQLite, SQLite 3.27+, mbstring, fileinfo, ctype y sesiones. No requiere Composer, Node o un servidor de base de datos para funcionar.

```sh
cd /ruta/ged
php bin/console.php check
cp config/example.php config/local.php
# Editar publisher (nombre de la editorial) y, si corresponde, dsn.
php bin/console.php migrate
php bin/console.php create-master propietario@ejemplo.com
php -d upload_max_filesize=6M -d post_max_size=8M -S 127.0.0.1:8080 -t public
```

Abrir el servidor local en el navegador. El comando solicita la contraseña dos veces sin mostrarla en una terminal interactiva. No existe una contraseña predeterminada en el código. No pasar contraseñas como argumentos de consola. `create-master` impide crear otro usuario cuando ya existe el maestro. Las migraciones se pueden repetir; no borran registros.

En el entorno de desarrollo preparado, PHP está en `/workspace/tools/php/php`; sustituir `php` por esa ruta si no hay un PHP global.

## Alojamiento PHP

1. Subir el proyecto completo a un directorio privado, por ejemplo `/home/cuenta/ged`.
2. Configurar el **DocumentRoot del dominio o subdominio a `/home/cuenta/ged/public`**. Para una instalación bajo `/ged` con cPanel, seguir la [guía de HostGator](docs/HOSTGATOR.md); las rutas y cookies detectan el subdirectorio de instalación. No publicar el directorio raíz del proyecto ni mover SQLite a `public/`.
3. Configurar PHP 8.2+, las extensiones indicadas, HTTPS, `display_errors=Off`, `log_errors=On`, `upload_max_filesize=6M` y `post_max_size=8M`. Si hay un proxy TLS, el servidor debe establecer `HTTPS=on` solo desde ese proxy confiable. Las cookies se marcan Secure al detectar HTTPS.
4. Ejecutar los comandos `check`, `migrate` y `create-master` desde la consola privada del alojamiento. Si no dispone de SSH, usar su terminal o tareas programadas privadas; nunca subir un instalador público ni un archivo con la contraseña.
5. Dar escritura al usuario PHP en `storage/`, su base y tapas; lectura en el resto. Usar permisos restrictivos (directorio privado 700 y archivos 600 cuando consola y PHP comparten usuario; ajustar propietario/grupo si no lo comparten). Proteger también `config/local.php`. No usar permisos 777.
6. Verificar acceso, alta y carga de tapa. El servidor integrado de PHP es solo para desarrollo.

No se necesita rewrite: las rutas usan `index.php` y parámetros de consulta. SQLite funciona con un único servidor y disco local persistente; evitar sistemas de archivos compartidos sin bloqueo fiable. Para este catálogo pequeño, las escrituras breves y un busy timeout de 5 segundos son suficientes.

## Uso

Después de ingresar se abre la biblioteca de activos. Buscar por código, título, autor o ISBN; seleccionar estado y orden y pulsar **Buscar**. Hay 20 resultados por página. **Nuevo título** permite guardar una ficha con solo código y título; el resto puede quedar pendiente. Los códigos admiten letras ASCII, números, guiones y guiones bajos (hasta 40 caracteres), y se normalizan a mayúsculas.

El PVP se expresa en pesos argentinos enteros, sin decimales, de 1 a 999.999.999.999; vacío significa pendiente. Se almacena como entero, sin cálculos monetarios de punto flotante. ISBN se conserva como texto, sin espacios ni guiones, y valida longitud y dígito de control de ISBN-10/13. Se admite vacío. Páginas: 1–100.000; año: 1000–2100; dimensiones: más de 0 hasta 300 cm, hasta dos decimales.

La ficha permite editar, desactivar previa confirmación y reactivar. El ID interno es permanente. No hay eliminación definitiva. Las tapas admiten JPEG, PNG y WebP de hasta 5 MB, 10.000 px por lado y 40 megapíxeles; se valida contenido y MIME. Se muestran completas; al reemplazar o quitar una tapa se elimina su archivo anterior. Ante errores de campos, se conservan los textos y se debe volver a seleccionar la imagen nueva.

Las sesiones vencen tras 30 minutos de inactividad. Hay un máximo de cinco intentos de acceso por IP en 15 minutos. No se confía en cabeceras de IP enviadas por el cliente; detrás de un proxy compartido, configurar en el servidor la dirección real desde proxies confiables para evitar agrupar usuarios. Todas las operaciones de escritura requieren CSRF y sesión; la creación usa además un token de formulario de un solo uso y redirección después del guardado.

## Recuperar contraseña

Con acceso privado al servidor:

```sh
php bin/console.php reset-password propietario@ejemplo.com
```

Ingresar y confirmar una contraseña nueva. No requiere correo saliente. Invalida las otras sesiones y restablece el bloqueo de intentos. El cambio desde la sesión requiere la contraseña actual y también invalida las otras sesiones. Guardar las credenciales del alojamiento de forma segura: son la vía de recuperación del propietario.

## Respaldo y restauración

Detener temporalmente el acceso a la aplicación (modo mantenimiento del alojamiento o detener el servidor local), para que no se modifiquen tapas durante la copia. Desde la raíz:

```sh
php bin/console.php backup /ruta/privada/respaldos/ged-2026-10-21
```

La ruta debe ser nueva. Se genera una copia consistente de SQLite mediante `VACUUM INTO` y una carpeta `covers/` completa. Respaldar también la configuración privada por un canal seguro, por separado. Los respaldos contienen hashes de acceso e información editorial: nunca ubicarlos en el directorio público.

Para restaurar: detener la aplicación, guardar una copia del estado actual, reemplazar la base indicada en `dsn` por `ged.sqlite` del respaldo y reemplazar la carpeta de tapas **completa** por `covers/` del mismo respaldo. Restablecer propietario y permisos; ejecutar `php bin/console.php migrate` y reiniciar. Verificar acceso, cantidad de títulos y visualización de tapas. No mezclar una base con tapas de otra fecha. Se puede cambiar `covers` en la configuración si se usa otro directorio privado.

## Configuración y evolución

`config/local.php` reemplaza el ejemplo; también se puede indicar un archivo privado absoluto mediante la variable de servidor `GED_CONFIG` (utilizada por las pruebas para aislar sus datos). La conexión admite `dsn`, `user` y `password`; los secretos reales nunca deben entrar a Git.

La migración futura a MySQL requiere un adaptador de migraciones en `Database`, archivos de esquema específicos para el motor (AUTO_INCREMENT, BIGINT para PVP, tipos y colaciones), PDO MySQL y DSN `mysql:host=…;dbname=…;charset=utf8mb4`. Definir código único con comparación normalizada, ISBN único permitiendo múltiples NULL y fechas UTC; convertir dimensiones de texto decimal canónico a DECIMAL(5,2) si se desea. Revisar búsqueda/colaciones, respaldo y bloqueo de intentos concurrentes en la capa de persistencia. Exportar/importar manteniendo IDs y referencias de tapas; verificar duplicados y conteos. La aplicación **no declara MySQL operativo todavía**. Las consultas del catálogo son parametrizadas; orden y nombres de campos se seleccionan mediante listas permitidas. Las diferencias de motor actuales (PRAGMA, esquema y VACUUM) quedan en persistencia/administración.

## Pruebas

Ver [resultado de aceptación](docs/PRUEBAS.md). Para reproducir, se necesitan únicamente en desarrollo Node, Playwright y Chromium:

```sh
# Instalar Playwright en una ubicación de desarrollo externa si no está disponible.
GED_PHP=php node tests/acceptance.cjs
```

`CHROMIUM_PATH` permite indicar otro ejecutable (por defecto `/usr/bin/chromium`). La suite usa archivos temporales, un usuario aleatorio y el puerto 8766; no toca el catálogo real ni conserva contraseñas en el repositorio. Produce capturas de computadora y móvil. La aplicación de producción no necesita estas herramientas.

## HostGator y cPanel

Ver la [guía paso a paso para bagua.net.ar/ged](docs/HOSTGATOR.md). No reemplaza la web Mobirise ni expone la base privada.
