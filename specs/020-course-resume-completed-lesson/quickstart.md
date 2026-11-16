# Quickstart: Validación del Posicionamiento en la Última Lección Completada

**Feature**: `020-course-resume-completed-lesson`  
**Status**: Completed  
**Spec**: [spec.md](spec.md)

---

## 1. Verificación Rápida con Pruebas Automatizadas

### Pruebas de Componente Frontend (Vitest)
Ejecutar la suite de pruebas del visor que valida la resolución del índice de reanudación:
```bash
PATH="/Users/jssmy/.nvm/versions/node/v22.23.4/bin:$PATH" npx vitest run resources/js/__tests__/CourseViewerResume.test.ts
```

### Pruebas de Integración Backend (Pest)
Verificar que los controladores entregan los estados de lecciones correctamente:
```bash
vendor/bin/pest tests/Feature/Study/SlideViewerResumeTest.php tests/Feature/Progress/StudentClassProgressSyncTest.php
```

---

## 2. Validación Manual en el Navegador

### Escenario A: Usuario con Avance Parcial (ej. Estudiante Bruno en Curso 13)
1. Iniciar sesión como `bruno.estudiante.demo@openedu.test` (contraseña demo).
2. Dirigirse a **Mis Clases** (`/my/classes`) o **Mis Cursos** (`/my/courses`).
3. Localizar el curso con avance previo (ej. 60% de avance).
4. Hacer clic en **"Entrar a Clase"** o **"Continuar curso"**.
5. **Resultado Esperado**:
   - La pantalla proyecta directamente la diapositiva correspondiente a la última lección completada (ej. Diapositiva 3 / 5).
   - En el encabezado aparece el distintivo "✓ Lección completada" (o "✓ Lección dictada").
   - En el temario lateral (drawer), el botón de la última lección completada aparece resaltado como el tema activo.

### Escenario B: Navegación Manual
1. Pulsar **"Siguiente →"** o presionar la flecha derecha del teclado.
2. La vista avanza a la lección 4 (pendiente).
3. Salir al catálogo y volver a ingresar.
4. **Resultado Esperado**: Vuelve a posicionarse en la lección 3 (la última marcada como completada).

