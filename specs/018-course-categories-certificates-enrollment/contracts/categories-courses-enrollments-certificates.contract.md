# Interface Contracts: Role-Based Course Categories, Teacher Self-Enrollment, and Certificate Issuance

**Feature**: `018-course-categories-certificates-enrollment`
**Date**: 2026-11-15

## 1. Course Categories (`POST /course-categories`)

- **Middleware**: `['auth', 'can:courses.manage']`
- **Controller**: `App\Modules\Courses\Infrastructure\Http\CourseCategoryController@store`
- **Request Payload (`SaveCourseCategoryRequest`)**:
  ```json
  {
    "name": "Innovación Pedagógica",
    "role_name": "docente"
  }
  ```
- **Validation**:
  - `name`: `required|string|max:120` (case-insensitive unique check)
  - `role_name`: `sometimes|required|string|exists:roles,name` (defaults to `'estudiante'` when omitted, and pre-selected to `'estudiante'` in the UI modal)

---

## 2. Courses Module (`/courses`, `/courses/create`, `/courses/{course}/edit`, `POST /courses/{course}/enroll`)

### 2.1 `GET /courses` (`CourseController@index`)
- **For Administrators (`can:courses.manage`)**:
  - Lists all courses with filters `q`, `status`, `category_id`.
  - Passes `categories` (with `id`, `name`, `slug`, `role_name`) and `roles` (for the "Nueva categoría" modal, with `estudiante` default).
- **For Non-Student Role Users (e.g. `docente` without `courses.manage`)**:
  - Lists **all published courses** whose category has `role_name` matching the user's roles (e.g. `docente`).
  - Passes `categories` filtered to the user's roles (`docente`).
  - Each course item includes:
    - `is_enrolled`: `bool` (whether `enrollments` has an active row for `user_id = $user->id` and `course_id = $course->id`)
    - `study_url`: `"/my/courses/{$course->id}"` when `is_enrolled === true`
    - `enroll_url`: `"/courses/{$course->id}/enroll"` when `is_enrolled === false`
- **`CourseCard` UI Contract (`resources/js/components/courses/CourseCard.vue`)**:
  - Displays the **category** badge (`course.category_name ?? course.category?.name`) and never displays a role badge on the card.
  - Does not render the eye (`view`) icon button in the footer (`#footer` is rendered only when explicitly provided by the parent page).
  - Clicking either the **cover image preview** or the **course name** navigates to the course detail (`detailHref ?? course.study_url ?? "/courses/{course.id}"`).

### 2.2 `POST /courses/{course}/enroll` (`CourseController@selfEnroll` / `EnrollTeacher`)
- **Middleware**: `['auth']`
- **Behavior**:
  - Verifies the course is `published` and has a category whose `role_name` is a role held by `$request->user()` (and `role_name !== 'estudiante'`).
  - Creates (or reactivates) an `Enrollment` record with `user_id = $user->id`, `student_id = null`, `course_id = $course->id`, `course_offering_id = null`, `status = 'active'`.
  - Redirects to `route('study.courses.show', $course)` (`/my/courses/{course}`) with flash message `'Te has inscrito exitosamente en el curso.'`.

### 2.3 `GET /courses/create`, `POST /courses`, `GET /courses/{course}/edit`, `PUT /courses/{course}`
- **Request Payload (`SaveCourseRequest`)**:
  - `name`: `required|string|max:255`
  - `category_id`: `nullable|integer|exists:course_categories,id`
  - `issues_certificate`: `sometimes|boolean` (defaults to `true` on create)
  - Note: No separate `target_audience` field in the form; `target_audience` is automatically derived from the selected category's `role_name` (`'student'` if `role_name === 'estudiante'`, `'teacher'` otherwise).

---

## 3. Academic Scheduling (`/scheduling`) and Student Enrollments (`/enrollments`)

- **`GET /scheduling`, `GET /scheduling/pending`, `POST /scheduling/offerings`**:
  - Only courses belonging to categories with `role_name = 'estudiante'` (or legacy `category_id = null` student courses) are listed in the course selector and offerings list.
  - Attempting to schedule (`POST /scheduling/offerings`) a course whose category has `role_name !== 'estudiante'` returns a validation error on `course_id`.
- **`GET /enrollments`, `POST /enrollments`**:
  - Exclusively manages student enrollments (`student_id` + `course_offering_id`) for courses belonging to `estudiante` categories. Non-student categories do not appear in `/enrollments`.

---

## 4. Certificate Issuance & Download

- **`POST /courses/{course}/certificate`** (`CertificateController@issue`):
  - Verifies active enrollment (student or self-enrolled teacher/role), `course.issues_certificate === true`, 100% of non-archived lessons completed, and all published assessments passed.
  - Generates `.pdf` file in storage volume (`certificates/{code}.pdf`) via `CertificatePdfGenerator` and persists only `file_url` in `certificates`.
- **`GET /certificates/{certificate}/download`** (`CertificateController@download`):
  - Streams the `.pdf` file from the storage volume.
