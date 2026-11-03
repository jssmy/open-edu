# Quickstart: validación del LMS

Guía para comprobar de extremo a extremo que la funcionalidad opera. Detalle de datos en
[data-model.md](./data-model.md) y de rutas en [contracts/web-routes.md](./contracts/web-routes.md).

## Prerrequisitos
PHP 8.3, Composer, Node 20+, PostgreSQL 16 en ejecución y archivo `.env` basado en `.env.example`.

## Preparación
```bash
composer install && npm install
php artisan key:generate
php artisan migrate --seed   # crea roles, permisos y un Administrador inicial
npm run dev
php artisan serve
```

## Pruebas automáticas
```bash
php artisan test        # Pest: Unit y Feature
npm run test            # Vitest
npm run e2e             # Playwright
```

## Escenarios de validación manual
1. **Acceso (US1)**: iniciar sesión como Administrador, crear un Docente y un Estudiante; el Estudiante entra con contraseña temporal y debe cambiarla.
2. **Cursos (US2)**: como Gestor crear un curso con una unidad y 2 lecciones, reordenar, publicar.
3. **Inscripción (US3)**: inscribir al Estudiante; verificar que aparece en el curso y el curso en el estudiante; reintentar y comprobar que rechaza el duplicado.
4. **Estudio (US4)**: como Estudiante abrir el curso y completar una lección; el progreso se actualiza.
5. **Evaluación (US5)**: como Docente crear una evaluación de 3 preguntas; el Estudiante la rinde y ve puntaje y aprobado/reprobado; verificar bloqueo al agotar intentos.
6. **Estados de acceso**: suspender la inscripción (sin acceso) y cancelarla (solo historial).
7. **Reportes (US6)**: generar los tres reportes con filtros y exportar a CSV; un Docente solo ve sus cursos.

## Resultado esperado
Todos los escenarios cumplen los criterios de aceptación de [spec.md](./spec.md) y las pruebas automáticas pasan.
