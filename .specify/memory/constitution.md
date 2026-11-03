<!--
Sync Impact Report
- Version change: 1.1.0 → 1.1.1 (PATCH: TypeScript fijado para el frontend, TODO cerrado)
- Previous change: 1.0.0 → 1.1.0
- Modified principles: I (Arquitectura Hexagonal → Monolito Modular con Arquitectura Hexagonal),
  III (DRY, KISS y YAGNI: añade abstracciones justificadas), IV (Clean Code: añade
  antipatrones), V (Frontend: añade convenciones), VII (Test-First → Calidad y Pruebas)
- Added principles: VIII Seguridad, IX Errores y Registros, X Dependencias, XI UX,
  XII Desarrollo Asistido por IA
- Added sections: Persistencia, Control de Versiones y Documentación, Criterios de Aceptación
- Removed sections: none
- Reconciliation: la nueva constitución exige monolito sin abstracciones innecesarias; se
  conserva la arquitectura hexagonal solicitada antes aplicándola DENTRO del monolito y
  limitando puertos a fronteras reales. Se conservan PostgreSQL y el sistema de diseño.
- Deferred TODOs: none
-->

# Open-Edu Constitution

## Core Principles

### I. Monolito Modular con Arquitectura Hexagonal (NON-NEGOTIABLE)
La solución MUST ser una aplicación web monolítica modular: un único repositorio y una única
aplicación desplegable, con Laravel (backend) y Vue.js (frontend). No se introducirán
microservicios, colas, brokers ni infraestructura distribuida sin una decisión arquitectónica
explícita y una necesidad técnica real.
- Cada módulo MUST tener responsabilidades bien definidas y bajo acoplamiento con otros.
- Dentro de cada módulo se aplica arquitectura hexagonal: **Dominio** (entidades, reglas),
  **Aplicación** (casos de uso/acciones/servicios) e **Infraestructura** (adaptadores:
  Eloquent/PostgreSQL, HTTP, servicios externos). Las dependencias apuntan hacia el dominio.
- Dominio y aplicación MUST NOT depender de Eloquent, facades de Laravel ni de Vue.
- Los puertos (interfaces) se definen solo en fronteras reales (persistencia, servicios
  externos, notificaciones); MUST NOT crearse interfaces o capas solo por seguir un patrón.
- Los controladores MUST ser delgados: validan (Form Request), invocan un caso de uso y
  responden; MUST NOT contener lógica de negocio.
- Las validaciones y reglas de negocio críticas MUST garantizarse en el backend y MUST NOT
  duplicarse entre Laravel y Vue.

### II. Principios SOLID
SRP, OCP, LSP, ISP y DIP se aplican cuando aportan valor: una razón de cambio por clase,
contratos sustituibles, interfaces pequeñas y dependencias inyectadas por constructor.

### III. DRY, KISS, YAGNI y Separation of Concerns
- DRY: la lógica de negocio MUST NOT duplicarse; se reutilizan componentes y servicios
  existentes antes de crear nuevos.
- KISS: se elige la solución más simple que cumpla el requisito.
- YAGNI: MUST NOT añadirse funcionalidad, capas ni abstracciones prematuras.
- Presentación, lógica de negocio y acceso a datos MUST mantenerse separados.
Toda complejidad añadida MUST justificarse por escrito en el plan.

### IV. Clean Code
Código legible, explícito y cohesivo, con nombres descriptivos. MUST evitarse: métodos
excesivamente extensos, clases con demasiadas responsabilidades, condicionales complejos
innecesarios, nombres ambiguos, código muerto o comentado y abstracciones prematuras.
El código MUST pasar linters y formateadores (Laravel Pint/PSR-12, ESLint/Prettier).

### V. Frontend con Vue y Componentes Reutilizables
- Vue.js con Composition API y `<script setup>`; componentes de responsabilidad específica y
  tamaño acotado, comunicados mediante props y eventos.
- La lógica reutilizable MUST encapsularse en composables; la comunicación con el backend en
  servicios/API, no en componentes de presentación.
- Estructura organizada de componentes, vistas, composables y servicios; componentes en
  niveles base, compuestos y de página.
- El frontend MUST escribirse en TypeScript, con props y eventos tipados.

### VI. Sistema de Diseño
Existirá un único sistema de diseño como fuente de verdad visual: tokens centralizados (color,
tipografía, espaciado, radios, sombras, breakpoints) y una biblioteca de componentes base
documentada (variantes, estados, accesibilidad).
- Las pantallas MUST usar componentes y tokens del sistema; MUST NOT usar estilos codificados
  a mano ni duplicar componentes existentes.
- Botones, formularios, tablas, modales y mensajes MUST ser consistentes.

### VII. Calidad y Pruebas
- Pruebas unitarias para lógica de negocio relevante; de integración para operaciones con
  varios componentes; Feature de Laravel para endpoints y flujos; de componentes en Vue
  cuando corresponda.
- MUST probarse escenarios exitosos, de error y casos límite.
- Las pruebas se escriben antes o junto con la implementación.
- Una funcionalidad con pruebas relevantes fallando MUST NOT considerarse terminada, y las
  pruebas MUST NOT modificarse solo para validar una implementación incorrecta.

### VIII. Seguridad
La seguridad es un requisito transversal.
- Todo dato del cliente MUST validarse; la autorización MUST aplicarse en el backend
  (Policies y Gates) independientemente de la interfaz.
- Autenticación y sesiones seguras; rutas y operaciones protegidas por permisos.
- Secretos y configuración sensible MUST estar en variables de entorno, nunca en el
  repositorio ni expuestos en respuestas o logs.
- MUST usarse las protecciones de Laravel contra CSRF, XSS y SQL Injection, y limitarse los
  intentos de acceso (rate limiting) en operaciones sensibles.

### IX. Gestión de Errores y Registros
Manejo consistente de excepciones, diferenciando errores de validación, autorización,
autenticación e internos. Los errores MUST aportar información útil para diagnóstico sin
exponer detalles internos al usuario. Los logs registran eventos relevantes y errores y MUST
NOT contener contraseñas, tokens ni información sensible.

### X. Gestión de Dependencias
Se priorizan las funcionalidades nativas de Laravel y Vue. Toda dependencia externa MUST tener
justificación técnica; MUST evitarse paquetes abandonados, con vulnerabilidades conocidas o
que dupliquen funcionalidad existente. Las dependencias se actualizan de forma controlada.

### XI. Interfaz y Experiencia de Usuario
Interfaz intuitiva, consistente, responsive y accesible (WCAG 2.1 AA). MUST proporcionarse
retroalimentación en carga y procesamiento, mensajes claros de éxito y error, y estados vacíos,
de error y de carga.

### XII. Desarrollo Asistido por IA
Las herramientas de IA MUST respetar esta constitución.
- Antes: revisar especificación y plan, comprender la estructura, identificar componentes y
  servicios reutilizables y verificar dependencias existentes.
- Durante: respetar decisiones arquitectónicas, no modificar funcionalidades no relacionadas,
  no inventar requerimientos, no duplicar código, implementar pruebas relevantes y no añadir
  dependencias sin justificación.
- Después: ejecutar pruebas, corregir errores introducidos, verificar criterios de aceptación
  y actualizar la documentación cuando corresponda.

## Stack, Persistencia y Restricciones Técnicas

- Backend: Laravel (PHP, PSR-12) · Frontend: Vue.js · Base de datos: PostgreSQL.
- La estructura de la BD MUST gestionarse con migraciones de Laravel versionadas y compatibles
  con el despliegue; MUST NOT modificarse manualmente.
- Modelos Eloquent con relaciones correctas; claves primarias, foráneas e índices según el
  dominio; integridad referencial garantizada; sin datos duplicados sin justificación.
- Operaciones críticas MUST usar transacciones; se evitan consultas innecesarias y problemas
  N+1. Eloquent y SQL quedan confinados a la infraestructura.
- Seeders y factories para datos iniciales y pruebas.

## Flujo de Desarrollo, Versiones y Documentación

- Todo cambio sigue el flujo Spec Kit: especificación → plan → tareas → implementación; el
  plan MUST incluir un "Constitution Check".
- Git con commits descriptivos (se recomienda Conventional Commits), pequeños y enfocados, sin
  mezclar funcionalidades no relacionadas; los cambios se revisan antes de integrarse a la
  rama principal. CI MUST ejecutar linters y pruebas.
- Se documentan decisiones arquitectónicas relevantes, procesos de negocio complejos, la
  instalación/configuración y las variables de entorno (archivo de ejemplo). Los comentarios
  explican decisiones no evidentes; se evita documentar lo que el código claro ya expresa.

## Criterios de Aceptación Generales

Una funcionalidad está terminada cuando: (1) cumple los requerimientos y criterios de
aceptación; (2) respeta arquitectura y convenciones; (3) implementa validaciones y
autorización necesarias; (4) incluye pruebas relevantes; (5) no genera regresiones;
(6) no introduce errores conocidos; (7) mantiene una UX consistente; (8) tiene la
documentación actualizada cuando sea necesario.

## Governance

Esta constitución prevalece sobre cualquier otra práctica del proyecto. Las enmiendas requieren
un PR que documente el cambio, su justificación, aprobación del equipo y, si aplica, un plan de
migración del código existente.

Versionado semántico: MAJOR por eliminación o redefinición incompatible de principios; MINOR
por nuevos principios/secciones o ampliación material; PATCH por aclaraciones y redacción.

Cumplimiento: todo PR y revisión MUST verificar la conformidad con los principios; las
desviaciones MUST justificarse explícitamente y aprobarse.

**Version**: 1.1.1 | **Ratified**: 2026-11-02 | **Last Amended**: 2026-11-03
