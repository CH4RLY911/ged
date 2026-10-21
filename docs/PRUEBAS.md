# Verificación de la primera etapa

Ejecutada el 21/10/2026 con PHP 8.4.24 de 64 bits, SQLite, Node y Chromium/Playwright en Linux. Resultado final: **22 comprobaciones aprobadas**, sin comprobaciones fallidas, omitidas ni deshabilitadas en esa ejecución. También se verificó la sintaxis de todos los archivos PHP.

La suite `tests/acceptance.cjs` crea configuración, base y tapas en un directorio temporal, genera contraseñas aleatorias, instala el maestro por el comando real e interactúa con los formularios y operaciones HTTP. Los datos y las imágenes que aparecen en las capturas son exclusivamente de prueba; el catálogo instalado del propietario permanece vacío.

| Aceptación solicitada | Resultado y evidencia |
| --- | --- |
| 1. Instalación y maestro | Aprobada. Verificación de extensiones, migraciones dos veces sin errores, catálogo vacío y creación por consola. |
| 2. Acceso y cierre | Aprobada. Rechazo de contraseña incorrecta, acceso correcto, cierre y nuevo ingreso. |
| 3. Protección | Aprobada. Formulario de alta y operación de cambio de estado redirigen al acceso sin sesión; CSRF ausente recibe 403. |
| 4. Cambio de contraseña | Aprobada. Cambio desde sesión y posterior ingreso con la nueva contraseña. También recuperación por consola. |
| 5. Alta y consulta | Aprobada. Formulario real, ficha y lectura del registro SQLite. Ambos botones de guardado ejercitados. |
| 6. Modificación | Aprobada. El ID no cambia al editar el título. |
| 7. Duplicados | Aprobada. Código con diferencias de mayúsculas e ISBN repetido rechazados junto a sus campos; textos conservados. El reenvío del token usado recibe 409 y no duplica registros. |
| 8. Fichas incompletas | Aprobada. Campos opcionales vacíos aceptados, incluido ISBN y PVP. |
| 9. PVP | Aprobada. 24500 se almacena como entero y se muestra como `$ 24.500`; vacío conserva NULL. Cero rechazado por validación de servidor, enviando el formulario sin validación nativa del navegador. |
| 10. Búsquedas y filtros | Aprobada. Búsqueda por autor, código e ISBN, filtro de inactivos, orden por código, título y PVP. Paginación real: 20 registros en la primera página y 3 en la segunda. |
| 11. Baja lógica | Aprobada. Diálogo de confirmación, desactivación, consulta mediante filtro y reactivación del mismo registro. |
| 12. Tapas | Aprobada. Carga, vista previa, consulta, reemplazo y eliminación; conteo de archivos confirma ausencia de huérfanos en esas operaciones. Archivo que simula ser PNG rechazado por el servidor. |
| 13. Reinicio | Aprobada. Servidor detenido y reiniciado, título y cambios conservados. |
| 14. Respaldo/restauración | Aprobada. Comando de respaldo con servidor detenido, sustitución de base y carpeta de tapas desde el mismo respaldo, reinicio y lectura de ficha e imagen recuperadas. |

Además, se verificó el bloqueo tras intentos fallidos repetidos y su recuperación por consola. Las contraseñas de prueba y del propietario no están incluidas en el código.

## Revisión visual

Se inspeccionaron las capturas de acceso, biblioteca, ficha y formulario. Escritorio: 1440 × 1000. Móvil: 390 × 844. Se corrigió el desborde de la biblioteca en móvil y se volvió a verificar que la página no tenga desplazamiento horizontal. Las tapas conservan proporciones y se ven completas. Los campos tienen etiquetas y foco visible; el control de archivo mantiene acceso por teclado con su etiqueta resaltada. No se realizó una auditoría formal de accesibilidad ni pruebas en dispositivos físicos.

- [Acceso en computadora](capturas/login-desktop.png)
- [Biblioteca en computadora](capturas/library-desktop.png)
- [Biblioteca en móvil](capturas/library-mobile.png)
- [Ficha en computadora](capturas/detail-desktop.png)
- [Ficha en móvil](capturas/detail-mobile.png)
- [Formulario en móvil](capturas/form-mobile.png)

## Estado del entorno instalado

Se aplicaron las migraciones sobre la base privada real y se creó el usuario maestro solicitado. Se comprobó que hay un usuario y cero títulos. El servidor de desarrollo se inició en el puerto 8080 y se verificó internamente la respuesta del acceso y la redirección de una pantalla protegida.

Las instrucciones de instalación y arranque se guardaron en el borrador de configuración del entorno. Guardar ese borrador no equivale a publicarlo. La publicación y la restauración en una tarea nueva no se han verificado.

No se ejecutó despliegue en un alojamiento externo ni migración a MySQL: sus procedimientos y adaptaciones están documentados en el README. No se incorporaron módulos fuera de acceso, biblioteca y tapas.

## Adaptación para HostGator y `/ged`

Se incorporó detección del subdirectorio para navegación, CSS, JavaScript y alcance de cookies, más una redirección canónica a `/ged/` antes de iniciar la sesión. La entrada `deploy/cpanel-index.php` permite conservar el código y los datos en `ged-private`, fuera de `public_html`.

La suite completa se ejecutó también con `GED_TEST_PREFIX=/ged`, en una estructura temporal `public_html/ged` y `ged-private`: **23 comprobaciones aprobadas**. Incluye verificación de enlaces, respuesta del CSS y ruta de la cookie, además de las operaciones de catálogo y recuperación. El alojamiento real HostGator todavía no se configuró ni se probó.
