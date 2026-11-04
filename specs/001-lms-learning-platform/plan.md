# Implementation Plan: Sistema de Gestión de Aprendizaje (LMS)

**Branch**: `001-lms-learning-platform` | **Date**: 2026-11-03 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-lms-learning-platform/spec.md`

## Summary

LMS para una sola organización: autenticación y roles, cursos con unidades/lecciones, estudiantes e
inscripciones, evaluaciones auto-calificadas, seguimiento de progreso y reportes. Se construye como
un monolito modular Laravel + Vue.js (Inertia), con PostgreSQL, aplicando arquitectura hexagonal
dentro de cada módulo (Dominio / Aplicación / Infraestructura) y un sistema de diseño propio de
componentes Vue. Los campos de redacción libre usan Tiptap con documentos JSONB, imágenes
incrustadas autorizadas por curso y video de presentación validado de YouTube o Vimeo. Los editores
se presentan en páginas independientes con aviso de cambios sin guardar; las actualizaciones usan
control optimista de versiones para evitar sobrescrituras concurrentes. Los videos de lección son
enlaces externos HTTPS y los reportes se exportan como CSV. Los listados administrativos (usuarios,
estudiantes, cursos) se paginan y filtran en el servidor con filtros en la URL; cursos se presentan como
tarjetas con portada opcional (o una generada por el sistema) y las cargas de archivos usan un control
uniforme (FR-037–FR-039). La operación define RTO y RPO máximos
de 24 horas, con respaldo de base de datos y medios privados. Detalle de decisiones en
[research.md](./research.md).

## Technical Context

**Language/Version**: PHP `^8.3` (runtime local 8.4.23) · Laravel 13.39 · TypeScript 6 · Vue 3.5
(Composition API, `<script setup>`)

**Primary Dependencies**: Inertia.js 3 (Laravel↔Vue sin API separada), Vite 8, Tailwind CSS 4
(tokens del sistema de diseño), spatie/laravel-permission (permisos de rol editables), Tiptap v3
(editor enriquecido; ver [research.md](./research.md))

**Storage**: PostgreSQL 16; documentos enriquecidos JSONB; archivos de curso e imágenes subidas
en almacenamiento Laravel a través del puerto `FileStorage`; imágenes enriquecidas en disco privado
con respaldo y recuperación a punto en el tiempo (PITR) de base de datos y medios privados,
cubriendo al menos 24 h de recuperación

**Testing**: Pest (unitarias de dominio/aplicación, Feature de Laravel, incluido conflicto de versión),
Vitest + Vue Test Utils (componentes y aviso de navegación), Playwright (E2E de flujos críticos);
ensayo operativo de restauración en staging

**Target Platform**: Servidor Linux, navegadores modernos de escritorio y móvil

**Project Type**: Aplicación web monolítica (un repositorio, un despliegue)

**Performance Goals**: Resultado de evaluación < 5 s; reporte de 500 estudiantes < 30 s; 500
usuarios concurrentes (SC-005, SC-007, SC-008). **Recovery Goals**: servicio y datos restaurados
en un máximo de 24 h desde detección (RTO); pérdida máxima de hasta 24 h de cambios (RPO)
(SC-014, SC-016)

**Constraints**: Sin microservicios ni colas al inicio; reglas críticas solo en backend; WCAG 2.1 AA.
No se define SLA de disponibilidad ni objetivo independiente de métricas/tracing en esta versión;
los objetivos operativos medibles son recuperación (RTO/RPO).

**Scale/Scope**: Una organización; ~500 usuarios concurrentes; ~25 pantallas

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Evaluación | Estado |
|-----------|-----------|--------|
| I. Monolito modular + hexagonal | Un solo despliegue; módulos con Domain/Application/Infrastructure; puertos solo en persistencia y almacenamiento de archivos | PASS |
| II. SOLID | Casos de uso con dependencias inyectadas por constructor | PASS |
| III. DRY/KISS/YAGNI | Sin colas, sin API separada, sin PDF ni multi-tenant (fuera de alcance) | PASS |
| IV. Clean Code | Pint (PSR-12) + ESLint/Prettier en CI | PASS |
| V. Vue + componentes | Composition API, composables y servicios; TypeScript resuelve el TODO de la constitución | PASS |
| VI. Sistema de diseño | Tokens + biblioteca base en `resources/js/design-system` | PASS |
| VII. Calidad y pruebas | Pest, Vitest, Playwright | PASS |
| VIII. Seguridad | Policies/Gates, Form Requests, CSRF, rate limiting y validación backend del esquema JSON y de medios | PASS |
| IX. Errores y logs | Excepciones de dominio mapeadas a respuestas; auditoría de eventos | PASS |
| X. Dependencias | Cada dependencia justificada en research.md | PASS |
| XI. UX | Estados de carga/vacío/error; editores en páginas independientes con confirmación al salir de cambios sin guardar; listados con `Table` (cursos como tarjetas con vista previa), paginación en servidor, filtros en URL (`useListFilters`) y acciones con `IconButton` (FR-037); carga de archivos con `FileInput` (FR-039) | PASS |
| XII. IA | Plan y spec revisados antes de implementar | PASS |

**Post-diseño (re-evaluación)**: PASS. El editor, la validación estructural de documentos, el
control de versión concurrente y la entidad de medios se justifican por FR-030–FR-036. El respaldo
y la restauración de PostgreSQL y medios privados satisfacen SC-014/SC-016 sin añadir un proveedor
ni una dependencia de aplicación.

## Project Structure

### Documentation (this feature)

```text
specs/001-lms-learning-platform/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── web-routes.md
└── tasks.md             # lo genera /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Modules/
│   ├── Identity/          # usuarios, roles, permisos, autenticación
│   ├── Courses/           # cursos, unidades, lecciones
│   ├── Students/          # estudiantes
│   ├── Enrollments/       # inscripciones
│   ├── Assessments/       # evaluaciones, preguntas, intentos
│   ├── Progress/          # progreso por lección
│   └── Reports/           # consultas y exportación (sin capa Domain: solo lectura)
│       └── <Módulo>/
│           ├── Domain/          # entidades, value objects, excepciones, puertos (interfaces)
│           ├── Application/     # casos de uso (acciones), DTOs
│           └── Infrastructure/  # Eloquent, repositorios, controladores, Form Requests, Policies
└── Shared/                # kernel compartido mínimo (excepciones base, auditoría)

database/
├── migrations/
├── factories/
└── seeders/

resources/js/
├── design-system/         # tokens + componentes base (Button, IconButton, FileInput, Input, Table, Pagination, Modal, Alert…)
├── components/            # componentes compuestos reutilizables
├── composables/
├── services/              # llamadas al backend
├── layouts/
└── pages/                 # una carpeta por módulo

tests/
├── Unit/                  # dominio y aplicación sin framework ni BD
├── Feature/               # endpoints y flujos (PostgreSQL)
└── e2e/                   # Playwright
resources/js/__tests__/    # Vitest
```

**Structure Decision**: Monolito Laravel con módulos hexagonales en `app/Modules` y frontend Vue en
`resources/js`, servido con Inertia. Los puertos (interfaces) existen solo para repositorios y
almacenamiento de archivos (fronteras reales); el resto se implementa directamente como casos de uso.

### Diseño de listados y portada (FR-037–FR-039)
- **Listados**: `UserListQuery` y `StudentListQuery` (Infraestructura) y la consulta de `CourseController@index`
  paginan 10 por página y aplican `q`, `status` y `role`; responden `{data, meta}` + `filters`. En el cliente,
  `useListFilters` mantiene los filtros en la URL (GET, Inertia `preserveState`, espera de 300 ms),
  `Table`/`Pagination`/`IconButton` renderizan usuarios y estudiantes, y `Courses/Index.vue` usa tarjetas.
  Alcance exclusivo: usuarios, estudiantes y cursos.
- **Portada**: columnas `courses.cover_path`/`cover_mime`; caso de uso `SetCourseCover` (puerto `FileStorage`,
  elimina el archivo anterior), `CourseCoverController` (`GET /courses/{id}/cover`, mismo acceso que el contenido)
  y `DefaultCourseCover` (SVG por defecto, sin servicios externos). El formulario viaja como multipart; los
  documentos Tiptap se serializan como JSON (`rich_text_encoded`). La portada de un curso eliminado lógicamente se
  conserva y no se sirve.
- **Carga de archivos**: `FileInput` del sistema de diseño en portada, archivo de lección e imagen del editor.

### Diseño de contenido enriquecido

- Los campos extensos indicados en FR-030 se editan con un componente Tiptap Vue reutilizable; los
  campos breves y estructurados permanecen como controles simples.
- Curso (`description`, `objectives`), lección (`content`), evaluación (`instructions`) y pregunta
  (`statement`) persisten como documentos JSONB Tiptap. La migración del contenido de texto existente,
  si lo hay, lo envuelve en nodos de párrafo sin perder el texto.
- El backend limita nodos, marcas, atributos y protocolos permitidos; no acepta HTML arbitrario,
  contenido `data:` ni `iframe` dentro del documento. Consulta y edición usan el mismo esquema, y la
  vista de solo lectura no inserta HTML de usuario con `v-html`.
- Una entidad de imagen de curso guarda identificador aleatorio, curso, ruta privada, MIME, tamaño y
  usuario creador. La subida y lectura son rutas autenticadas/autorizadas por curso; el nodo JSON
  referencia la ruta interna autorizada y `alt` (vacío solo para imágenes decorativas). URLs externas
  son HTTPS y se renderizan directamente con `referrerpolicy="no-referrer"`; no se solicitan desde el
  servidor.
- `intro_video_url` se recibe como URL, pero se valida y normaliza a proveedor e ID de YouTube/Vimeo.
  La URL de reproducción se construye en una plantilla de iframe fija; YouTube usa el host sin cookies.
  CSP permite marcos solo desde `youtube-nocookie.com` y `player.vimeo.com`.
- Las vistas de crear/editar curso, lección y evaluación son páginas Inertia independientes; el
  editor nunca se monta en un modal o diálogo. Mientras el formulario esté sucio, la navegación
  interna y la salida del navegador muestran confirmación para quedarse o descartar.
- Los videos de lección son enlaces HTTPS externos. No se suben archivos de video; los límites de
  subida configurables aplican a los tipos de archivo permitidos distintos de video.
- Los reportes exportan CSV únicamente; no se añade generación PDF.
- Los cursos, lecciones y evaluaciones mantienen un `lock_version` entero. Cada actualización envía
  la versión leída; la escritura compara e incrementa el contador de forma atómica. Una versión
  desactualizada responde HTTP 409, conserva los datos guardados y pide recargar. Las modificaciones
  de preguntas o alternativas también incrementan la versión de su evaluación.
- La operación de producción debe conservar respaldos recuperables de PostgreSQL y del
  almacenamiento privado de medios con cobertura de al menos 24 horas, preferiblemente mediante
  base backup más archivado continuo de WAL y recuperación a punto en el tiempo (PITR), o una
  capacidad gestionada equivalente. El procedimiento debe restablecer aplicación, servicio y datos
  en 24 horas desde la detección; la pérdida no puede superar 24 horas de cambios. La plataforma de
  despliegue no está seleccionada, por lo que su mecanismo concreto de backup/restore se configura
  y verifica en staging con una restauración real.
- Las decisiones y fuentes se detallan en [research.md](./research.md), los campos en
  [data-model.md](./data-model.md) y las rutas en [contracts/web-routes.md](./contracts/web-routes.md).

## Complexity Tracking

El uso de Tiptap y del almacenamiento de imágenes privado agrega componentes y rutas, justificados
por la edición enriquecida, el límite de carga y la autorización del contenido. El contador de
versión agrega una columna y una comprobación atómica por recurso editable para evitar pérdida de
cambios. Los respaldos y las pruebas de restauración son responsabilidades operativas del entorno
de despliegue, no un servicio externo añadido a la aplicación. No se agregan servicios externos ni
una API separada.
