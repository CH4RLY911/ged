# Instalar GED en HostGator con cPanel

Destino previsto: `https://bagua.net.ar/ged/`. La página de Mobirise permanece en su lugar. No reemplazar `public_html/index.html`, el resto de la web ni sus reglas existentes.

## Obtener el código

Cuando el código esté subido al repositorio de GitHub, abrir el repositorio, seleccionar la rama que contiene la entrega y pulsar **Code → Download ZIP**. Ese ZIP sí se descarga a tu computadora. El visor lateral de archivos de Codex no equivale a una descarga del proyecto ni publica el código en GitHub.

## Carpetas

cPanel → Administrador de archivos → directorio de inicio de la cuenta (un nivel por encima de `public_html`). Crear esta estructura:

```
/home/TU_USUARIO/
├── ged-private/           aplicación completa, fuera de Internet
│   ├── app/
│   ├── bin/
│   ├── config/
│   ├── migrations/
│   ├── public/
│   ├── storage/
│   └── views/
└── public_html/
    ├── ...               web Mobirise existente: no modificar
    └── ged/
        ├── index.php     copia de deploy/cpanel-index.php, RENOMBRADA
        └── assets/       copia de public/assets/
```

1. Extraer el ZIP descargado en tu computadora. GitHub suele agregar una carpeta contenedora con el nombre de la rama.
2. Subir el contenido del proyecto a `ged-private`, creado en el directorio de inicio de cPanel, **fuera de `public_html`**. Si se usa un ZIP para subirlo, extraerlo y eliminar el archivo ZIP del servidor después. No dejar una carpeta contenedora adicional entre `ged-private` y `app`.
3. Dentro de `public_html`, crear `ged`. Copiar allí `public/assets` como `assets`.
4. Copiar `deploy/cpanel-index.php` a `public_html/ged` y **renombrarlo a `index.php`**. No usar el `public/index.php` normal en esa ubicación: la entrada especial encuentra el código privado dos niveles por encima.
5. Crear `storage/` dentro de `ged-private` si el ZIP no la incluye. Crear `config/local.php` copiando `config/example.php`, y cambiar `publisher` por el nombre de la editorial. No hace falta cambiar la ruta de la base. `base_path` en null detecta `/ged` automáticamente.

Las carpetas privadas se llaman exactamente `ged-private` y `storage`. Si el dominio utiliza una raíz distinta de `public_html` o el proveedor limita rutas con `open_basedir`, pedir al soporte la ubicación privada permitida y ajustar la línea `require` de la entrada pública. No trasladar la base a Internet para resolver un error de rutas.

## PHP e instalación

En cPanel, revisar **MultiPHP Manager** o **Seleccionar versión de PHP** (el nombre depende del plan). GED requiere PHP 8.2 o superior, de 64 bits, y PDO SQLite, mbstring, fileinfo, ctype y sesiones. El plan debe permitir escritura en disco privado persistente. Los nombres de extensiones pueden aparecer como `pdo_sqlite` y `sqlite3`; comprobarlo con el comando de requisitos. Si no aparecen, consultar al soporte de HostGator antes de instalar.

En **Terminal** de cPanel, desde el directorio de inicio:

```sh
cd ~/ged-private
php bin/console.php check
php bin/console.php migrate
php bin/console.php create-master bagua@outlook.com.ar
```

El último comando pide la contraseña dos veces. Escribir la elegida: no se muestra en pantalla. El ZIP no incluye el usuario ni la base del entorno de desarrollo, de modo que este paso es necesario en el alojamiento.

La versión PHP de la terminal puede diferir de la del dominio: `check` debe pasar con el ejecutable que corresponda a PHP 8.2 o posterior de tu plan. Si la terminal está deshabilitada, solicitar al soporte su activación o la ejecución privada de los comandos; no subir scripts públicos de instalación ni enviar contraseñas por chat.

El usuario PHP necesita escribir en `ged-private/storage` y leer el resto del código. Directorios privados 700 y archivos privados 600 funcionan si PHP corre con tu usuario de cPanel. Si usa otro usuario, ajustar propietario y grupo con soporte. No usar 777. Mantener HTTPS activo y `display_errors=Off`.

Para las tapas: PHP debe permitir `upload_max_filesize=6M` y `post_max_size=8M`; ajustar mediante el editor INI del dominio si hace falta. La app limita las imágenes a 5 MB.

## Abrir y comprobar

Abrir `https://bagua.net.ar/ged/`. Debe aparecer el acceso con estilo. Ingresar con el correo y contraseña creados. El catálogo estará vacío.

Crear un título, subir una tapa, editarlo, desactivarlo, filtrar inactivos y reactivarlo. Comprobar que el enlace del logo vuelve a `/ged/` y que la página principal de Mobirise sigue funcionando. Desde una ventana privada, intentar abrir `/ged/?action=new`: debe llevar al acceso.

Si aparece un error, consultar el registro de errores privado de PHP en cPanel. No publicar su contenido si contiene rutas o datos privados. La adaptación a `/ged` se prueba en una estructura local equivalente; el despliegue real requiere verificar el plan y la configuración de HostGator.

## Respaldo y recuperación

Usar los procedimientos del README desde `~/ged-private`. Guardar respaldos fuera de `public_html`. Para recuperar el acceso: `php bin/console.php reset-password bagua@outlook.com.ar`. El comando solicita la contraseña nueva de forma privada.
