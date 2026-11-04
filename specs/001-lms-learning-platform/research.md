# Research: LMS

Las decisiones técnicas abiertas quedan resueltas aquí; no hay NEEDS CLARIFICATION pendientes. La
configuración concreta de respaldo depende del entorno de despliegue, que no está seleccionado.

## 1. Lenguaje del frontend
- **Decision**: TypeScript.
- **Rationale**: props y contratos tipados en componentes reutilizables y sistema de diseño; resuelve `TODO(FRONTEND_LANGUAGE)` de la constitución.
- **Alternatives**: JavaScript (menos seguro al refactorizar).

## 2. Integración Laravel ↔ Vue
- **Decision**: Inertia.js con Vue 3.
- **Rationale**: monolito sin API REST separada, autenticación por sesión y CSRF nativos, menos código (KISS).
- **Alternatives**: SPA + API con tokens (más superficie y duplicación); Blade + Vue parcial (peor reutilización).

## 3. Organización hexagonal en el monolito
- **Decision**: módulos en `app/Modules/<Módulo>/{Domain,Application,Infrastructure}`; puertos solo para repositorios y almacenamiento de archivos.
- **Rationale**: cumple principio I sin abstracciones innecesarias; dominio y casos de uso se prueban sin framework.
- **Alternatives**: Laravel clásico (viola I); puertos para todo (viola YAGNI).

## 4. Roles y permisos
- **Decision**: spatie/laravel-permission con los 4 roles sembrados y permisos editables por el Administrador; alcance por curso (docente asignado) vía Policies.
- **Rationale**: FR-004 exige gestionar permisos por rol; reimplementarlo es duplicar un paquete maduro y mantenido.
- **Alternatives**: Gates con roles fijos (no permite edición de permisos).

## 5. Estilos y sistema de diseño
- **Decision**: Tailwind CSS con tokens (colores, tipografía, espaciado, radios, sombras) en configuración central y componentes base en `resources/js/design-system`.
- **Rationale**: tokens como fuente única de verdad; componentes accesibles y consistentes.
- **Alternatives**: biblioteca UI de terceros (dependencia pesada, menos control).

## 6. Almacenamiento de contenidos
- **Decision**: Storage de Laravel tras un puerto `FileStorage`; los medios de lecciones mantienen
  los límites configurables definidos para cada tipo. Las imágenes de texto enriquecido usan
  almacenamiento privado y acceso autorizado por curso. Los videos de lección son enlaces HTTPS
  externos, sin subida de archivos; la presentación del curso acepta YouTube o Vimeo.
- **Rationale**: respeta FR-011 y conserva la opción de cambiar a almacenamiento en la nube sin
  tocar casos de uso. La organización no necesita alojar o transcodificar video.
- **Alternatives**: subir videos al almacenamiento propio (mayor costo, validación y operación).

## 7. Evaluaciones y calificación
- **Decision**: calificación automática síncrona en un caso de uso; intentos con máximo configurable (defecto 1); el resultado oficial es el último intento (spec, Clarifications).
- **Rationale**: respuesta < 5 s sin colas.
- **Alternatives**: cola asíncrona (innecesaria a esta escala).

## 8. Progreso y reportes
- **Decision**: progreso calculado desde registros de lecciones completadas (sin contar archivadas); reportes con consultas SQL optimizadas, filtros y exportación CSV nativa en streaming.
- **Rationale**: CSV es el único formato requerido por FR-026 y evita añadir dependencias de
  generación de documentos.
- **Alternatives**: PDF o exportar en ambos formatos (fuera de alcance de esta versión).

## 9. Primer acceso y recuperación de contraseña
- **Decision**: contraseña temporal con cambio obligatorio en el primer acceso (spec); recuperación por enlace de restablecimiento nativo de Laravel con vigencia limitada.
- **Rationale**: usa mecanismos nativos.

## 10. Auditoría y registros
- **Decision**: tabla de auditoría para inicios de sesión, cambios de rol e inscripciones (FR-029) más logs de Laravel sin datos sensibles.

## 12. Límite de intentos de inicio de sesión
- **Decision**: máximo 5 intentos por minuto por combinación de cuenta e IP (rate limiter nativo de Laravel); superado el límite responde 429.

## 13. Verificación automática de la constitución
- **Decision**: pruebas de arquitectura de Pest (Domain y Application no importan `Illuminate\*` ni Eloquent), regla de ESLint contra colores y estilos fijos fuera del sistema de diseño, y prueba de matriz rol × ruta.
- **Rationale**: convierte los principios I, VI y VIII en comprobaciones de CI.

## 11. Pruebas
- **Decision**: Pest (Unit/Feature sobre PostgreSQL), Vitest para componentes, Playwright para flujos críticos.

## 14. Editor de texto enriquecido
- **Decision**: Tiptap v3 para Vue 3 (`@tiptap/vue-3`, `@tiptap/pm`, `@tiptap/starter-kit` y `@tiptap/extension-image`). StarterKit cubre el formato básico requerido; la extensión de imagen admite texto alternativo y no sube archivos por sí sola. Los paquetes base requeridos son MIT.
- **Rationale**: integración oficial con Vue 3, esquema extensible y control explícito de las marcas y nodos admitidos. StarterKit v3 incluye enlaces; su configuración permite bloquear protocolos inseguros. La persistencia recomendada por Tiptap es JSON, que conserva la estructura sin aceptar HTML arbitrario.
- **Alternatives**: VueQuill/Quill 2, que ofrece un editor más preconfigurado, pero tiene un modelo de contenido menos directamente alineado con el esquema JSON y controles menos granulares para el allowlist de enlaces.
- **Persistencia y renderizado**: guardar los documentos Tiptap como JSONB de PostgreSQL. El servidor valida recursivamente nodos, marcas, atributos y URLs permitidos; no confía en la validación del cliente. El modo de consulta utiliza el mismo esquema Tiptap en solo lectura y no `v-html`.
- **Formato permitido**: párrafos, negrita, cursiva, encabezados, listas, citas, enlaces e imágenes. Enlaces solo HTTP/HTTPS; imágenes externas solo HTTPS; `data:` y contenido HTML crudo no se admiten. La extensión de imagen mantiene `allowBase64` desactivado.
- **Fuentes**: [instalación Vue 3](https://tiptap.dev/docs/editor/getting-started/install/vue3), [StarterKit](https://tiptap.dev/docs/editor/extensions/functionality/starterkit), [persistencia](https://tiptap.dev/docs/editor/core-concepts/persistence), [Image](https://tiptap.dev/docs/editor/extensions/nodes/image), [Link](https://tiptap.dev/docs/editor/extensions/marks/link).

## 15. Imágenes subidas y externas
- **Decision**: almacenar las imágenes subidas como recursos privados asociados al curso, con identificador aleatorio, tipo y tamaño verificados por el servidor, y resolución a través de una ruta que comprueba el acceso al curso. El documento JSON guarda la URL interna de esa ruta y los atributos de accesibilidad. Las URLs externas HTTPS se muestran directamente en el navegador; el servidor no las descarga ni las proxifica.
- **Rationale**: la ruta de lectura mantiene las mismas reglas de acceso que el contenido del curso y evita convertir una URL proporcionada por usuarios en una solicitud servidor-a-servidor (SSRF). Las imágenes externas pueden revelar a su proveedor la IP y actividad del estudiante; se establece `referrerpolicy="no-referrer"` para no enviar la URL de la página como referente.
- **Validación**: aceptar solo JPEG, PNG y WebP; validar imagen y tipo real del archivo en Laravel, con un máximo de 5120 KB; generar el nombre en el servidor y no confiar en nombre, extensión ni MIME aportados por el cliente. No seguir redirecciones ni realizar solicitudes a URLs externas.
- **Accesibilidad**: imagen informativa requiere `alt` no vacío; imagen decorativa requiere `alt=""`. La interfaz debe distinguir explícitamente ambas opciones y persistir el atributo `alt` incluso si está vacío.
- **Fuentes**: [Laravel file validation](https://laravel.com/docs/13.x/validation#validating-files), [Laravel filesystem](https://laravel.com/docs/13.x/filesystem), [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html), [OWASP SSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html).

## 16. Seguridad del contenido enriquecido
- **Decision**: aceptar y persistir únicamente documentos JSON conformes al esquema limitado del editor; validar la estructura y URLs en el backend antes de guardar. No aceptar ni persistir HTML/iframes arbitrarios. El contenido se muestra con el editor Tiptap de solo lectura y se auditan los usos de `v-html` para no aplicarlo a contenido de usuario.
- **Rationale**: reduce el riesgo XSS por diseño frente a almacenar HTML de usuario. Las defensas del cliente no sustituyen la validación del servidor; URLs de enlace e imagen tienen reglas de protocolo independientes.
- **URLs de video**: recibir la URL de YouTube o Vimeo, extraer y persistir proveedor e ID validados, y construir el `iframe` solo desde una plantilla fija. YouTube usa `youtube-nocookie.com`; Vimeo usa `player.vimeo.com`. No se aceptan HTML ni URLs de iframe suministradas por el usuario. La política CSP limita `frame-src` a esos dos hosts.
- **Fuentes**: [Vue Security Guide](https://vuejs.org/guide/best-practices/security.html), [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html), [YouTube embedded players](https://developers.google.com/youtube/player_parameters), [Vimeo player embeds](https://developer.vimeo.com/player/sdk/embed).

## 17. Páginas de edición y cambios sin guardar
- **Decision**: cada formulario de curso, lección o evaluación que use el editor enriquecido se
  presenta como página Inertia independiente, nunca como modal. Mientras el formulario tenga
  cambios sin guardar, interceptar visitas Inertia y la salida del navegador para permitir
  quedarse o confirmar el descarte.
- **Rationale**: satisface el requisito de espacio de edición y previene pérdidas accidentales.
  Inertia v3 documenta el evento `before` del router para interceptar visitas; la salida de página
  usa el evento estándar `beforeunload`.
- **Alternatives**: guardar borrador automáticamente (cambia la semántica de guardado y crea
  estados parciales); salir sin aviso (pérdida silenciosa).
- **Caveat**: el navegador controla el texto de confirmación de `beforeunload`, y puede no
  mostrarlo en algunas salidas móviles; se adjunta solo mientras existan cambios sin guardar.
- **Fuentes**: [Inertia v3 events: Before](https://inertiajs.com/docs/v3/advanced/events#before),
  [MDN beforeunload](https://developer.mozilla.org/en-US/docs/Web/API/Window/beforeunload_event).

## 18. Detección de ediciones concurrentes
- **Decision**: añadir un contador `lock_version` a Course, Lesson y Assessment. Las solicitudes
  de actualización envían la versión observada al abrir el formulario. La escritura usa una
  comparación e incremento atómicos; si no coincide, devuelve HTTP 409, conserva los datos y
  pide recargar. Los cambios de preguntas y alternativas incrementan la versión de su evaluación.
- **Rationale**: el control optimista no mantiene bloqueos mientras alguien edita y protege contra
  la sobrescritura silenciosa. La comprobación y escritura se realizan dentro de una transacción
  y una sola operación condicional de base de datos.
- **Alternatives**: aceptar siempre el último guardado (pierde cambios); bloqueo pesimista durante
  toda la edición (conexiones y bloqueos largos, UX frágil).
- **Fuentes**: [Laravel 13 database transactions](https://laravel.com/docs/13.x/database#database-transactions).

## 19. Listados, portada y control de carga de archivos
- **Decision**: paginación en servidor de 10 registros fijos, filtros (`q`, `status`, `role`) en la URL aplicados por consultas dedicadas por módulo; solo usuarios, estudiantes y cursos. Cursos como tarjetas con portada opcional subida por el usuario (JPEG/PNG/WebP ≤5 MB, almacenamiento privado) y, si falta, un SVG generado por el sistema. Un único componente `FileInput` para toda carga de archivos. La portada de cursos eliminados lógicamente se conserva.
- **Rationale**: evita cargar todos los registros y mantiene búsquedas compartibles/recargables; la portada propia evita peticiones a terceros (se descartó la miniatura de YouTube por exponer la IP del usuario y no existir en Vimeo); el SVG por defecto no requiere activos ni servicios; conservar el archivo permite restaurar el curso sin pérdida.
- **Alternatives**: paginar/filtrar en el cliente (carga todo, no escala); selector de tamaño de página (complejidad sin necesidad actual); miniatura de YouTube/Vimeo (dependencia externa); imagen estática por defecto (menos distinguible entre cursos); borrar la portada al eliminar el curso (impide restaurar íntegramente).

## 20. Respaldo y recuperación
- **Decision**: mantener respaldos recuperables de PostgreSQL y del almacenamiento privado de
  medios, con cobertura de al menos 24 horas. Preferir base backup más archivado continuo de WAL y
  recuperación a punto en el tiempo (PITR), o una capacidad gestionada equivalente. Documentar y
  ensayar la restauración de la aplicación, la base y los medios en staging. El objetivo es
  recuperar servicio y datos en <=24 h desde detección (RTO), con pérdida máxima de hasta 24 h de
  cambios (RPO).
- **Rationale**: traduce SC-014 y SC-016 en una comprobación operativa medible sin escoger un
  proveedor de despliegue no especificado. PostgreSQL documenta WAL archiving como mecanismo para
  recuperación a punto en el tiempo; un respaldo lógico `pg_dump` por sí solo tiene una granularidad
  de restauración más limitada. Un respaldo de la base sin los archivos privados tampoco restaura
  por completo el contenido del curso. No se fija un SLA de disponibilidad ni un objetivo de
  métricas/tracing independiente en esta versión.
- **Alternatives**: `pg_dump` cada 24 h como mínimo (cumplimiento sin margen operativo y sin PITR);
  réplica síncrona o warm standby (mayor costo/operación, innecesario para RPO de 24 h); no fijar
  objetivos cuantificados (no verificable).
- **Fuentes**: [PostgreSQL 16 backup and restore](https://www.postgresql.org/docs/16/backup.html),
  [PostgreSQL 16 continuous archiving and PITR](https://www.postgresql.org/docs/16/continuous-archiving.html).
