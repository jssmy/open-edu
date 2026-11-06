# Feature Specification: Admin Password Reset and Public Recovery Removal

**Feature Branch**: `017-admin-password-reset`

**Created**: 2026-11-05

**Status**: Ready for Planning

**Input**: User description: "solo admin puede resetear la contrasenia de los docentes y de los alumnos, en el login no debe existir la opcion de recuperar contrasenia"

## Clarifications

### Session 2026-11-05
- Q: ¿Cuando el administrador restablece la contraseña de un docente o alumno, debe obligarse al usuario a cambiarla en su siguiente inicio de sesión (`must_change_password = true`) o puede comenzar a usar la nueva clave directamente? → A: Obligatorio: tras el reseteo administrativo, el usuario debe cambiar su clave al entrar (`must_change_password = true`).

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Removal of Public Password Recovery Option in Login (Priority: P1)

As a security-conscious administrator and system user,  
I want the login screen to omit any link or flow for self-service password recovery,  
So that students and users without email cannot trigger broken email-based password recovery requests, avoiding confusion and securing credentials.

**Why this priority**: Essential UX and security posture. Eliminates public self-service password recovery that does not fit the institution's reality (where students have no email addresses).

**Independent Test**: Navigate to `/login`. Verify the link "¿Olvidaste tu contraseña?" is completely absent from the login form. Attempting to directly access `/forgot-password` or `/reset-password` redirects or rejects access safely.

**Acceptance Scenarios**:

1. **Given** any visitor or user on `/login`, **When** viewing the authentication form, **Then** only the identifier ("Usuario o Correo") and "Contraseña" inputs, with the "Entrar" submit button, are displayed, with zero references or links to "¿Olvidaste tu contraseña?".
2. **Given** a direct URL navigation to `/forgot-password` or `/reset-password/{token}`, **When** requested, **Then** the system disables the public self-service flow (returning 404 or redirecting to `/login`).

---

### User Story 2 - Administrator Password Reset for Students (Priority: P1)

As an educational administrator managing student accounts,  
I want to explicitly set or reset a student's password from their management screen or profile,  
So that students who forget their credentials can regain access immediately through their administrator.

**Why this priority**: Primary administrative duty. Because self-service recovery is removed, administrators must be able to change/reset passwords directly for students.

**Independent Test**: An administrator navigates to `/students/{id}`, enters a new password for the student, and saves. The student immediately logs in with their `username` and is prompted to choose their permanent password (`must_change_password = true`).

**Acceptance Scenarios**:

1. **Given** an authorized administrator on `/students/{id}`, **When** they provide a new valid password in the student edit form, **Then** the student's password is encrypted and updated, `must_change_password` is set to `true`, and the audit log records `student.password_reset`.
2. **Given** the student whose password was updated, **When** they submit their username and new password on `/login`, **Then** authentication succeeds and they are routed to change their password to a private one before accessing classes.
3. **Given** a non-admin user (e.g. teacher or student), **When** they attempt to execute a password reset for a student, **Then** the request is forbidden with HTTP 403.

---

### User Story 3 - Administrator Password Reset for Teachers and Staff (Priority: P2)

As an educational administrator managing platform staff and teachers,  
I want to reset passwords for teachers from the user management module (`/users`),  
So that faculty members who lose access can have their credentials updated by an administrator.

**Why this priority**: Completes centralized administrative password control for institutional staff.

**Independent Test**: An administrator goes to `/users/{id}` (or user listing), updates/resets the teacher's password, and the teacher verifies login with the new password and is required to change it.

**Acceptance Scenarios**:

1. **Given** an administrator editing a teacher at `/users/{id}`, **When** they fill in a new password and save, **Then** the user's password is updated with required security hashing, `must_change_password` is set to `true`, and active remember tokens are invalidated.
2. **Given** a teacher whose password was reset, **When** they log in with their email/username and temporary password, **Then** login succeeds and they are required to set their own password.
3. **Given** an unauthorized request to reset a teacher's password, **Then** the system returns HTTP 403 Forbidden.

---

### Edge Cases

- **Password complexity and length**: What happens if an administrator enters a password shorter than 8 characters? The system validates minimum length (8 characters) and displays clear validation errors.
- **Accidental password overwrite**: When an administrator updates other attributes (like student name or birth date) and leaves the password field empty, the system retains the existing password without modifying it.
- **Concurrent sessions**: When a password is reset by an administrator, the affected user's existing active sessions or remember tokens are cleared to protect the account.
- **Audit logging**: All administrator-driven password resets must register an audit entry (`auth.password_reset_by_admin`) identifying the actor ID and the target user.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST remove the password recovery link ("¿Olvidaste tu contraseña?") from the login view (`/login`).
- **FR-002**: System MUST disable public password recovery routes (`/forgot-password` and `/reset-password`), preventing self-service email reset requests.
- **FR-003**: System MUST permit administrators (users with `students.manage` permission) to set or reset a student's password directly from the student update action.
- **FR-004**: System MUST permit administrators (users with `users.manage` permission) to set or reset a teacher's or staff member's password directly from the user management action (`/users/{id}`).
- **FR-005**: System MUST require minimum 8 characters for newly assigned passwords when resetting.
- **FR-006**: System MUST leave existing passwords unchanged when the password field is submitted empty during student or user updates.
- **FR-007**: System MUST set `must_change_password = true` when an administrator resets a password for a student or teacher, forcing them to establish a private password upon first login.
- **FR-008**: System MUST audit every administrator password reset action recording the administrator's ID and the target user.
- **FR-009**: System MUST reject any attempt by non-administrators (teachers, students, guests) to reset another user's password with HTTP 403 Forbidden.

### Key Entities

- **User**:
  - `password`: Hashed credential.
  - `must_change_password`: Boolean indicator (set to `true` on reset).
  - `remember_token`: Cleared or regenerated on reset.
- **Student**:
  - Associated with `User` account whose password is reset.
- **Audit Log**:
  - `event`: Event identifier (`student.password_reset`, `user.password_reset_by_admin`).
  - `user_id`: Administrator ID executing the reset.
  - `target`: Target student/user reference.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of login interface sessions show zero links or buttons for password recovery.
- **SC-002**: 100% of direct HTTP requests to public recovery endpoints (`/forgot-password`, `/reset-password`) are blocked / disabled.
- **SC-003**: Administrators can reset a student's or teacher's password in under 30 seconds.
- **SC-004**: 100% of password resets by administrators force a password change (`must_change_password = true`) on the target user's next login.
- **SC-005**: 100% of unauthorized reset requests from non-admin accounts receive HTTP 403 Forbidden.

---

## Assumptions

- Users do not manage their own forgotten password resets via email; password resets are requested through administrative contact within the institution.
- Users who are already logged in can still change their own password via the existing authenticated change password flow (`/password/change`) if required.
- Passwords entered by administrators are hashed with the application's default password hasher (`bcrypt`).
