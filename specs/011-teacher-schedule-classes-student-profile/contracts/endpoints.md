# Contracts: Horario por Curso, Clases Próximas y Ficha Integral de Estudiante

## 1. Teacher Schedule Endpoint

- **Ruta**: `GET /my/schedule`
- **Controlador**: `SchedulingController@mySchedule`
- **Props de Inertia**:
  ```json
  {
    "schedules": [...],
    "calendarBlocks": [
      {
        "id": 1,
        "scheduleId": 10,
        "sessionId": 1,
        "blockIndex": 0,
        "title": "Sesión 1",
        "groupCode": "A1",
        "courseId": 5,
        "courseName": "Matemática Básica",
        "teacherId": 2,
        "teacherName": "Profesor Juan",
        "modality": "in_person",
        "classroomName": "Aula 101",
        "sessionDate": "2026-11-05",
        "dayOfWeek": 4,
        "startsAt": "08:00",
        "endsAt": "10:00",
        "readOnly": true
      }
    ]
  }
  ```

---

## 2. Teacher Classes Endpoint

- **Ruta**: `GET /my/classes`
- **Controlador**: `TeacherClassController@index`
- **Orden de resultados**: Las clases se retornan ordenadas cronológicamente con la oferta más próxima a iniciar primero (`starts_on` ASC).
- **Props de Inertia**:
  ```json
  {
    "classes": [
      {
        "id": 12,
        "group_code": "SEC-01",
        "modality": "in_person",
        "starts_on": "2026-11-06",
        "ends_on": "2026-12-20",
        "course": {
          "id": 3,
          "name": "Física I",
          "title": "Física I",
          "excerpt": "Introducción a la mecánica...",
          "cover_url": "/courses/3/cover",
          "units_count": 4,
          "lessons_count": 12,
          "status": "published",
          "teachers": [{"id": 2, "name": "Profesor Juan"}]
        }
      }
    ]
  }
  ```

---

## 3. Student Detail Endpoint

- **Ruta**: `GET /students/{studentId}`
- **Controlador**: `StudentController@show`
- **Autorización**: `can:students.view` + `StudentPolicy::view` (admin o docente asignado).
- **Props de Inertia**:
  ```json
  {
    "student": {
      "id": 2,
      "user_id": 4,
      "name": "Carlos Alumno",
      "email": "carlos@example.com",
      "identifier": "78945612",
      "phone": "999888777",
      "is_active": true
    },
    "courses": [
      {
        "id": 5,
        "enrollment_id": 14,
        "name": "Matemática Básica",
        "status": "active",
        "group_code": "MAT-A",
        "enrolled_at": "2026-10-15T10:00:00Z",
        "progress": {
          "completed_lessons": 3,
          "total_lessons": 10,
          "percentage": 30
        },
        "assessments": [
          {
            "id": 1,
            "title": "Examen Parcial",
            "total_score": 20,
            "passing_score": 11,
            "latest_attempt": {
              "id": 8,
              "attempt_number": 1,
              "score": 15,
              "passed": true,
              "status": "submitted",
              "submitted_at": "2026-11-01T14:30:00Z"
            }
          }
        ]
      }
    ],
    "canManageStudents": false,
    "canManageEnrollments": false
  }
  ```

