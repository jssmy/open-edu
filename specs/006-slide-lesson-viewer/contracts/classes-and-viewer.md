# Contracts: Clases y Visor de Lecciones

## Endpoints HTTP

### 1. Listado de Clases del Docente
- **Ruta**: `GET /my/classes`
- **Controlador**: `App\Modules\Scheduling\Infrastructure\Http\TeacherClassController@index`
- **Middleware**: `auth`, `can:scheduling.view-own`
- **Respuesta Inertia**: `Teacher/Classes`
  - Props:
    ```ts
    {
      classes: Array<{
        id: number;
        code: string;
        starts_on: string | null;
        ends_on: string | null;
        course: {
          id: number;
          title: string;
          description: string;
          cover_image_url: string | null;
          units_count: number;
          lessons_count: number;
        };
      }>
    }
    ```

### 2. Visor de Lecciones en Diapositivas para Docente
- **Ruta**: `GET /my/classes/{offering}/viewer`
- **Controlador**: `App\Modules\Scheduling\Infrastructure\Http\TeacherClassController@viewer`
- **Middleware**: `auth`, `can:scheduling.view-own`
- **Respuesta Inertia**: `Study/CourseViewer`
  - Props:
    ```ts
    {
      course: CourseViewerData, // id, title, description, units -> lessons
      isTeacherView: true,
      offeringId: number,
      backUrl: '/my/classes'
    }
    ```

### 3. Visor de Lecciones para Estudiante
- **Ruta**: `GET /study/courses/{course}`
- **Controlador**: `App\Modules\Progress\Infrastructure\Http\StudyController@show`
- **Middleware**: `auth`, `can:accessCourse,course`
- **Respuesta Inertia**: `Study/CourseViewer`
  - Props:
    ```ts
    {
      course: CourseViewerData,
      isTeacherView: false,
      backUrl: '/my/courses'
    }
    ```

