# Arquitectura de Open Edu

## Vista general

Open Edu es un monolito Laravel 13 con interfaz Vue 3 + TypeScript servida mediante Inertia. El
backend y el frontend comparten una aplicación y una sesión web; no existe una API separada ni
servicios desplegados por separado.

## Módulos

La lógica de negocio se organiza en `app/Modules` por área:

- **Identity**: usuarios, roles, permisos, autenticación y cambio de contraseña.
- **Courses**: cursos, docentes, unidades, lecciones y archivos.
- **Students**: perfiles de estudiantes.
- **Enrollments**: inscripción, estados y autorización de acceso.
- **Assessments**: evaluaciones, preguntas, intentos y calificación.
- **Progress**: finalización de lecciones y navegación de estudio.
- **Reports**: consultas de avance, inscripciones y resultados, más exportación CSV.

Los módulos usan `Domain`, `Application` e `Infrastructure` cuando la separación aporta valor.
Los repositorios y el almacenamiento de archivos son límites explícitos; Eloquent y HTTP quedan en
Infrastructure. `app/Shared` contiene únicamente servicios transversales como auditoría y errores.

## Solicitudes y autorización

Las rutas web están en `routes/web.php`. Inertia renderiza las páginas de `resources/js/pages`; los
formularios envían solicitudes al backend mediante la sesión y protección CSRF de Laravel. Las
Policies, Gates y permisos de `spatie/laravel-permission` autorizan cada operación en el servidor.
Los docentes tienen alcance por curso asignado y los estudiantes por inscripción activa y curso
publicado.

## Persistencia y archivos

PostgreSQL es el motor previsto para desarrollo y despliegue. Las migraciones están en
`database/migrations`; los datos de ejemplo se pueden cargar de forma optativa con
`php artisan db:seed --class=DemoDataSeeder`. Los archivos usan la abstracción `Storage` de Laravel
y el disco configurado en `FILESYSTEM_DISK`. `COURSE_MAX_UPLOAD_KB` limita los archivos de contenido
y su valor predeterminado es 10 240 KB.

## Seguridad y auditoría

Las contraseñas se guardan con hash; las cuentas iniciales y de demostración requieren cambio de
contraseña. El login aplica un límite de cinco intentos por minuto por cuenta e IP. Los eventos de
auditoría no deben incluir contraseñas, tokens ni direcciones de correo. La aplicación no debe
registrar secretos de configuración.

## Interfaz y calidad

Los componentes reutilizables están en `resources/js/design-system`, con tokens centrales en
`tokens.ts`. Las páginas y componentes usan Vue Composition API y TypeScript. Las pruebas se
distribuyen entre Pest (`tests/`), Vitest (`resources/js/__tests__`) y Playwright (`tests/e2e`).

Comandos principales:

```bash
php artisan test
npm run lint
npm run test
npm run build
npm run e2e
```

La prueba k6 de carga se ejecuta por separado con `k6 run tests/load/concurrency.js`; requiere que
la aplicación esté disponible en `BASE_URL` o en `http://127.0.0.1:8000`.

## Variables de entorno

Las variables usadas por la configuración de aplicación se definen en `.env.example`. Las más
relevantes son:

| Variable | Propósito |
|----------|-----------|
| `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | Entorno, diagnóstico, URL pública y cifrado de Laravel. |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión a PostgreSQL u otro motor configurado. |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Credenciales opcionales para el administrador inicial del seeder de roles. Si la contraseña está vacía, se genera una temporal. |
| `SESSION_DRIVER`, `SESSION_LIFETIME` | Almacenamiento y duración de sesiones. |
| `CACHE_STORE`, `QUEUE_CONNECTION` | Almacenamiento de caché y ejecución de trabajos. |
| `FILESYSTEM_DISK` | Disco de Laravel para archivos de contenido. |
| `COURSE_MAX_UPLOAD_KB` | Tamaño máximo de un archivo de curso, en KB (10 240 por defecto). |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS` | Transporte y remitente del correo. |
| `LOG_CHANNEL`, `LOG_LEVEL` | Canal y nivel de registro. |

No se deben guardar credenciales reales en `.env.example` ni en el repositorio. Configure secretos
de despliegue en el gestor de secretos del entorno correspondiente.
