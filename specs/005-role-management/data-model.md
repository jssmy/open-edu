# Data Model: Gestión de Roles

## Rol (tabla `roles`, extendida)

| Campo | Tipo | Reglas |
|-------|------|--------|
| id | bigint | PK (Spatie) |
| name | string | único sin distinguir mayúsculas, 3–50 caracteres, incluye inactivos |
| guard_name | string | `web` |
| description | string nullable | máx. 255 |
| is_active | boolean | por defecto `true`; los roles existentes migran a `true` |
| is_system | boolean | por defecto `false`; `true` para administrador, gestor_de_cursos, docente, estudiante |
| created_at / updated_at | timestamps | Spatie |

**Transiciones**: `Activo → Inactivo` (requiere confirmación si hay usuarios; prohibido para `administrador`); `Inactivo → Activo` (siempre permitido). No existe eliminación.

**Relaciones**: Rol 1—N Usuario (vía `model_has_roles`, un rol por usuario); Rol N—N Permiso (`role_has_permissions`).

## Usuario
Sin cambios de esquema. Un usuario con rol inactivo conserva `is_active` y su rol, pero no puede iniciar sesión.

## Auditoría
Se reutiliza `Auditor` con acciones `role.created`, `role.deactivated` (metadato `affected_users`), `role.activated`: actor, objetivo `role:{name}`, fecha.
