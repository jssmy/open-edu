# Quickstart & Verification Guide: Módulo de Mis Clases del Estudiante

## 1. Escenario de Prueba Rápida con Datos Demo

1. Iniciar sesión como estudiante (`estudiante@openedu.test` / contraseña del seeder).
2. Verificar que en la barra de navegación lateral y en el panel aparezca **"Mis clases"** en lugar de "Mis cursos".
3. Al hacer clic en "Mis clases", se carga la URL `/my/classes`.
4. Si se intenta escribir manualmente `/my/courses`, el navegador es redirigido automáticamente a `/my/classes`.

## 2. Verificación de Avisos Superiores

1. En `/my/classes`, observar los bloques compactos superiores:
   - **Clases en curso**: Hasta 5 clases activas con su porcentaje de progreso y acceso directo.
   - **Próximas a empezar**: Hasta 5 clases futuras con la indicación clara de fecha de inicio.

## 3. Verificación de Restricciones Temporales

### Clase Próxima a Iniciar (`starts_on > hoy`)
- En la tarjeta del curso, el botón de ingresar aparece deshabilitado.
- Se muestra un badge con la fecha: `"Inicia el DD/MM/AAAA"`.
- Si se navega directamente a `/my/courses/{id}`, el sistema responde con error 403 Forbidden.

### Clase Finalizada (`ends_on < hoy`)
- La tarjeta permite ingresar al curso.
- En el visor de estudio se muestra el banner de advertencia: *"Este curso finalizó el DD/MM/AAAA. Modo de consulta"*.
- El botón de marcar lección como completada no se visualiza.
- No se permite iniciar ni enviar intentos de evaluación.

## 4. Ejecución de Pruebas Automatizadas

```bash
# Pruebas de integración de Laravel
vendor/bin/pest tests/Feature/Study/StudentClassesTest.php

# Pruebas unitarias y de frontend
npm test
```

