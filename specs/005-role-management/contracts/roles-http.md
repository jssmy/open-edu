# Contrato HTTP: Roles

Todas las rutas bajo `can:roles.manage`; respuestas Inertia.

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/roles?q=&status=&page=` | Listado paginado. Props: `roles.data[]` {id, name, label, description, isActive, isSystem, locked, userCount, permissions}, `filters`, `permissions`. `status`: `active` \| `inactive` \| vacío |
| GET | `/roles/create` | Formulario de creación |
| POST | `/roles` | Crea rol activo. Body: `name` (req), `description`, `permissions[]`. 422 si el nombre existe (activo o inactivo) |
| PUT | `/roles/{id}/status` | Body: `active` (bool), `confirm` (bool). 422 si es Administrador; 422 con `requires_confirmation` y `affected_users` si se inactiva con usuarios sin `confirm=true` |
| PUT | `/roles/{id}/permissions` | Existente; sin cambios |

**Login** (`POST /login`): usuario con rol inactivo recibe el mismo error `Las credenciales no son válidas.` que una credencial incorrecta.
