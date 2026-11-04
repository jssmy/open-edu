# Research: Gestión de Roles

## D1. Fuente de verdad de los roles
- **Decision**: la tabla `roles` de Spatie, con columnas nuevas `description`, `is_active`, `is_system`. El enum `Role` queda solo para los 4 roles de sistema.
- **Rationale**: Spatie ya almacena roles y permisos; evita una segunda tabla (DRY/YAGNI). Los roles nuevos no pueden ser casos de un enum.
- **Alternatives**: tabla propia `identity_roles` enlazada a Spatie (duplica datos); extender el enum (imposible en tiempo de ejecución).

## D2. Bloqueo de login
- **Decision**: añadir a `Auth::attempt` una condición que excluya usuarios con algún rol inactivo (`whereDoesntHave('roles', is_active = false)`), manteniendo el mensaje y el rate limiting actuales. Usuarios sin rol conservan el comportamiento actual.
- **Rationale**: una sola consulta, sin revelar el motivo (FR-007), y reutiliza la auditoría `auth.failed`.
- **Alternatives**: middleware posterior al login (permitiría crear sesión antes de bloquear); mensaje específico (descartado en la clarificación).

## D3. Sesiones abiertas
- **Decision**: no tocar `EnsureUserIsActive`; solo evalúa `users.is_active`. Las sesiones continúan hasta expirar (FR-008). No se renuevan por login.
- **Rationale**: decisión explícita de la clarificación 4.

## D4. Protección y unicidad
- **Decision**: Administrador no inactivable (regla en `SetRoleStatus`); unicidad de nombre insensible a mayúsculas (índice único existente de Spatie `name+guard_name` más validación `lower(name)` en `StoreRoleRequest`) incluyendo inactivos.
- **Alternatives**: índice único funcional sobre `lower(name)` en PostgreSQL; se añade en la migración como respaldo a nivel de BD.

## D5. Usuarios con roles personalizados
- **Decision**: `UserRecord` expone el nombre del rol como `string` y `role` enum opcional; `ChangeUserRole`, `StoreUserRequest` y `UpdateUserRequest` validan que el rol exista y esté activo en lugar de `Rule::enum`.
- **Rationale**: sin esto no se podría asignar un rol nuevo (US1 prueba independiente).

## D6. Confirmación y auditoría
- **Decision**: la confirmación con conteo de usuarios (FR-004b) se resuelve en el cliente con `AlertDialog` usando `userCount` enviado en el listado; el backend exige el parámetro `confirm=true` cuando el rol tiene usuarios. Se audita `role.created`, `role.deactivated`, `role.activated`.
