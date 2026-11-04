# Implementation Plan: Gestión de Roles (crear e inactivar)

**Branch**: `005-role-management` | **Date**: 2026-11-04 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/005-role-management/spec.md`

## Summary

Ampliar el módulo **Identity** para crear roles, inactivarlos/reactivarlos y bloquear el inicio de sesión de usuarios cuyo rol esté inactivo. Hoy los roles son un `enum Role` fijo con 4 casos, respaldado por la tabla `roles` de spatie/laravel-permission. El enfoque: la tabla `roles` pasa a ser la fuente de verdad (nuevas columnas `description`, `is_active`, `is_system`); el enum queda solo como identificador de los 4 roles de sistema. Los casos de uso nuevos viven en `Application`; el bloqueo de login se aplica en el `AuthController` existente mediante una condición adicional en `Auth::attempt`, con el mismo mensaje genérico.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (versión instalada), TypeScript + Vue 3 (`<script setup>`) con Inertia v3

**Primary Dependencies**: spatie/laravel-permission (ya instalada), componentes del sistema de diseño existentes (`Table`, `Pagination`, `IconButton`, `AlertDialog`, `useListFilters`). Sin dependencias nuevas.

**Storage**: PostgreSQL; una migración sobre la tabla `roles` de Spatie

**Testing**: Pest (Feature para endpoints y login; Unit para casos de uso)

**Target Platform**: Aplicación web (monolito modular)

**Project Type**: web-service (Laravel + Inertia)

**Performance Goals**: listado de roles paginado en servidor; la comprobación de rol en el login es una sola consulta adicional

**Constraints**: mensaje de login genérico; sesiones abiertas no se interrumpen (FR-008); Administrador no inactivable; nombre único incluso entre inactivos

**Scale/Scope**: decenas de roles, miles de usuarios; 1 pantalla ampliada (`Roles/Index`) y 1 formulario de creación

## Constitution Check

| Principio | Evaluación |
|-----------|-----------|
| I Monolito modular / hexagonal | Pasa. Casos de uso en `Identity/Application`; Eloquent/Spatie solo en `Infrastructure`; controlador delgado con Form Request. |
| II–IV SOLID, DRY, KISS, YAGNI | Pasa. Se amplía `RolePermissionRepository` en lugar de crear capas nuevas; sin herencia de permisos ni jerarquías (fuera de alcance). |
| V–VI Vue y sistema de diseño | Pasa. Se reutilizan componentes base; TypeScript con props tipadas. |
| VII Pruebas | Pasa. Se cubren éxito, error y casos límite (ver quickstart). |
| VIII Seguridad | Pasa. Permiso `roles.manage` en rutas, validación en backend, auditoría con `Auditor`, rate limiting del login se conserva. |
| IX Errores | Pasa. `DomainException` para reglas de negocio. |
| X Dependencias | Pasa. Ninguna nueva. |
| XI UI | Pasa. Listado con `Table`, `Pagination`, filtros en URL (`q`, `status`), acciones con `IconButton` y confirmación con `AlertDialog` (variante warning al inactivar). |
| Stack/persistencia | Pasa. Migración versionada, transacción al inactivar con auditoría. |

No hay violaciones; **Complexity Tracking** no aplica. Re-evaluado tras el diseño: sin cambios.

## Project Structure

### Documentation (this feature)

```text
specs/005-role-management/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── roles-http.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Modules/Identity/
├── Domain/
│   ├── Role.php                      # enum: solo roles de sistema (sin cambios de casos)
│   ├── RoleRecord.php                # nuevo: id, name, label, description, isActive, isSystem, userCount
│   └── RolePermissionRepository.php  # ampliar: create, setActive, find, paginate, countUsers
├── Application/
│   ├── CreateRole.php                # nuevo
│   ├── SetRoleStatus.php             # nuevo (inactivar/reactivar + reglas)
│   └── ChangeUserRole.php            # ajustar: aceptar rol por nombre y solo activos
├── Infrastructure/
│   ├── SpatieRolePermissionRepository.php
│   ├── RoleListQuery.php             # nuevo: filtros q/status + paginación
│   └── Http/
│       ├── RoleController.php        # ampliar: store, status
│       ├── AuthController.php        # bloqueo de login
│       └── Requests/StoreRoleRequest.php
database/migrations/                  # add_status_to_roles_table
database/seeders/RolesAndPermissionsSeeder.php   # marcar roles de sistema
resources/js/pages/Roles/Index.vue    # tabla + estado + acciones
resources/js/pages/Roles/Create.vue   # formulario de creación
routes/web.php                        # 2 rutas bajo can:roles.manage
tests/Feature/Identity/RoleManagementTest.php
tests/Unit/Identity/RoleUseCasesTest.php
```

**Structure Decision**: se mantiene la estructura modular existente; no se crean carpetas base nuevas.
