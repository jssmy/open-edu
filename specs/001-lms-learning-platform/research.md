# Research: LMS

Todas las decisiones técnicas abiertas quedan resueltas aquí; no hay NEEDS CLARIFICATION pendientes.

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
- **Decision**: Storage de Laravel (disco local) tras un puerto `FileStorage`; videos por enlace o archivo con límite de tamaño configurable.
- **Rationale**: cambiar a almacenamiento en la nube sin tocar casos de uso.
- **Alternatives**: S3 desde el inicio (YAGNI).

## 7. Evaluaciones y calificación
- **Decision**: calificación automática síncrona en un caso de uso; intentos con máximo configurable (defecto 1); el resultado oficial es el último intento (spec, Clarifications).
- **Rationale**: respuesta < 5 s sin colas.
- **Alternatives**: cola asíncrona (innecesaria a esta escala).

## 8. Progreso y reportes
- **Decision**: progreso calculado desde registros de lecciones completadas (sin contar archivadas); reportes con consultas SQL optimizadas, filtros y exportación CSV nativa en streaming.
- **Rationale**: CSV cubre el supuesto de exportación sin dependencias; PDF queda diferido.
- **Alternatives**: PDF (dependencia extra, diferido).

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
