# Quickstart: validación de Gestión de Roles

**Prerrequisitos**: `php artisan migrate`, `php artisan db:seed --class=RolesAndPermissionsSeeder`, usuario administrador.

**Pruebas**:
```bash
php artisan test --compact tests/Feature/Identity/RoleManagementTest.php
php artisan test --compact tests/Unit/Identity/RoleUseCasesTest.php
php artisan test --compact tests/Feature/Identity tests/Feature/Security
```

**Escenarios manuales**
1. Crear rol "Coordinador" en `/roles/create` → aparece Activo y es asignable a un usuario (US1).
2. Crear otro con el mismo nombre (incluso en minúsculas o inactivo) → error de validación (FR-001).
3. Inactivar "Coordinador" con usuarios → diálogo con el número de usuarios; confirmar → estado Inactivo; ya no aparece al asignar roles (US2, FR-004b, FR-006).
4. Con ese usuario, iniciar sesión → mensaje genérico de credenciales inválidas (US3).
5. Con sesión abierta antes de inactivar → sigue funcionando hasta expirar (FR-008).
6. Intentar inactivar Administrador → rechazado (FR-009).
7. Reactivar el rol → el usuario inicia sesión normalmente (SC-004).
