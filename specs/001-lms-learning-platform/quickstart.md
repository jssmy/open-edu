# Quickstart: validación del LMS

Guía para comprobar de extremo a extremo que la funcionalidad opera. Detalle de datos en
[data-model.md](./data-model.md) y de rutas en [contracts/web-routes.md](./contracts/web-routes.md).

## Prerrequisitos
PHP 8.3 o superior, Composer, Node 20+, PostgreSQL 16 en ejecución y archivo `.env` basado en `.env.example`.

## Preparación
```bash
composer install && npm install
php artisan key:generate
php artisan migrate --seed   # crea roles, permisos y un Administrador inicial
php artisan db:seed --class=DemoDataSeeder  # datos de ejemplo (opcional, seguro al repetir)
npm run dev
php artisan serve
```

Las cuentas de demostración se crean con contraseñas aleatorias y requieren cambio de contraseña; un Administrador puede asignarles una contraseña antes de probar el acceso.

## Pruebas automáticas
```bash
php artisan test        # Pest: Unit y Feature
npm run test            # Vitest
npm run e2e             # Playwright
```

## Escenarios de validación manual
1. **Acceso (US1)**: iniciar sesión como Administrador, crear un Docente y un Estudiante; el Estudiante entra con contraseña temporal y debe cambiarla.
2. **Cursos (US2)**: como Gestor crear un curso con descripción y objetivos, insertar formato enriquecido e imágenes, asociar una URL de video de YouTube o Vimeo, crear una unidad y 2 lecciones, reordenar y publicar.
3. **Inscripción (US3)**: inscribir al Estudiante; verificar que aparece en el curso y el curso en el estudiante; reintentar y comprobar que rechaza el duplicado.
4. **Estudio (US4)**: como Estudiante abrir el curso y completar una lección; el progreso se actualiza.
5. **Evaluación (US5)**: como Docente crear una evaluación de 3 preguntas; el Estudiante la rinde y ve puntaje y aprobado/reprobado; verificar bloqueo al agotar intentos.
6. **Estados de acceso**: suspender la inscripción (sin acceso) y cancelarla (solo historial).
7. **Reportes (US6)**: generar los tres reportes con filtros y exportar a CSV; un Docente solo ve sus cursos.
8. **Editor y medios (FR-030–FR-034)**: verificar negrita, cursiva, encabezados, listas y enlaces al guardar, editar y consultar descripción, objetivos, contenido de lección, instrucciones y enunciados extensos. Operar barra y controles de accesibilidad con teclado y en viewport móvil; comprobar foco visible y que no se recorten herramientas o contenido.
9. **Validación y acceso a imágenes**: insertar una imagen subida JPEG, PNG y WebP (hasta 5 MB), una imagen con URL HTTPS y una decorativa con `alt=""`. Verificar que HTTP, formatos no permitidos y archivos mayores a 5 MB se rechazan con error claro; imagen informativa sin texto alternativo también se rechaza. Un usuario sin acceso al curso no puede obtener la imagen subida.
10. **Seguridad de contenido y video**: comprobar que HTML/script arbitrario nunca se ejecuta; los enlaces `javascript:`/`data:`, imágenes `data:` y las URLs de proveedor de video no permitido se rechazan. Confirmar que URLs válidas de YouTube/Vimeo se reproducen con el proveedor correcto, que las lecciones aceptan enlaces HTTPS externos pero no archivos de video y que el video no admite HTML/iframe arbitrario.
11. **Página de edición y cambios sin guardar (FR-035)**: abrir creación y edición de curso, lección y evaluación; verificar que cada editor aparece en una página independiente y no en un modal. Modificar el contenido e intentar navegar a otra página interna; elegir quedarse y confirmar que sigue en la página, luego repetir y confirmar descarte. Repetir con recarga o salida del navegador y verificar que aparece la confirmación nativa cuando el navegador la soporta.
12. **Ediciones simultáneas (FR-036)**: abrir el mismo curso, lección y evaluación en dos sesiones. Guardar un cambio en la sesión A; luego intentar guardar desde la versión abierta en la sesión B. En cada caso, confirmar que se rechaza la versión desactualizada con un aviso para recargar y que no sobrescribe el cambio de A.
13. **Recuperación operativa (SC-014, SC-016)**: en un entorno de staging con la rutina de respaldo habilitada, confirmar que cada respaldo de PostgreSQL y medios privados tiene como máximo 24 horas. Restaurar el respaldo más reciente en un entorno aislado, registrar el tiempo desde la detección de la interrupción hasta que el servicio y los datos estén disponibles (máximo 24 horas) y comprobar que la pérdida de datos no supera los cambios posteriores al último respaldo.

## Resultado esperado
Todos los escenarios cumplen los criterios de aceptación de [spec.md](./spec.md) y las pruebas automáticas pasan.
