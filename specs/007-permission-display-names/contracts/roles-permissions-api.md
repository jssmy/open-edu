# Contratos de Interfaz: Nombres Descriptivos de Permisos en Gestión de Roles

**Feature**: `007-permission-display-names`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Contratos Inertia / HTTP

### 1.1 `GET /roles/create`
**Propósito**: Renderizar el formulario de creación de rol con el catálogo de permisos legibles.

#### Inertia Props:
```json
{
  "permissions": [
    {
      "name": "courses.manage",
      "label": "Gestión de cursos",
      "module": "Cursos",
      "description": "Crear, editar estructura y administrar cursos institucionales."
    },
    {
      "name": "scheduling.manage",
      "label": "Gestión de programación académica",
      "module": "Programación",
      "description": "Programar periodos, clases, grupos, aulas y horarios."
    }
  ]
}
```

### 1.2 `GET /roles`
**Propósito**: Listar los roles con sus permisos asignados y proporcionar el catálogo completo de permisos legibles para el modal de edición/asignación.

#### Inertia Props:
```json
{
  "roles": [
    {
      "id": 1,
      "name": "docente",
      "label": "Docente",
      "description": "Profesor o instructor",
      "is_active": true,
      "is_system": true,
      "locked": false,
      "user_count": 5,
      "permissions": ["courses.view", "content.manage", "scheduling.view-own"]
    }
  ],
  "permissions": [
    {
      "name": "courses.manage",
      "label": "Gestión de cursos",
      "module": "Cursos",
      "description": "Crear, editar estructura y administrar cursos institucionales."
    }
  ],
  "filters": {
    "q": "",
    "status": ""
  }
}
```

### 1.3 `POST /roles` (Sin cambio de contrato de entrada)
**Payload**:
```json
{
  "name": "Coordinador Académico",
  "description": "Encargado de coordinar programas",
  "permissions": ["courses.view", "scheduling.manage"]
}
```

### 1.4 `PUT /roles/{id}/permissions` (Sin cambio de contrato de entrada)
**Payload**:
```json
{
  "permissions": ["courses.view", "scheduling.view-own"]
}
```

---

## 2. Contratos UI / Componentes Vue

### `Roles/Create.vue` & `Roles/Index.vue`
Los componentes reciben `permissions: PermissionItem[]`.

#### Comportamiento UI:
1. Agrupación por `module`: Los permisos se pueden mostrar clasificados por tarjetas de módulo o acordiones con un checkbox por cada permiso.
2. Formato del Checkbox:
   - Texto principal: `p.label` (ej. "Gestión de cursos").
   - Subtexto / ayuda: `p.description` (texto pequeño y atenuado debajo del checkbox).
   - Identificador técnico secundario: `p.name` (mostrado sutilmente o en atributo `title`/badge gris para auditoría técnica si se requiere).

