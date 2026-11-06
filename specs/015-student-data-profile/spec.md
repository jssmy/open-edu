# Feature Specification: Student Profile Data and Username Authentication

**Feature Branch**: `015-student-data-profile`

**Created**: 2026-11-05

**Status**: Ready for Planning

**Input**: User description: "actualiza ya que el campo correo en el estudiante no es requerido el campo no debe existir, el login del estudiante se hace con el campo username"

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Student Account Registration & Management by Administrator (Priority: P1)

As an educational administrator managing student records in Open-Edu,  
I want to register and update student profiles using only their personal name, document identifier, date of birth, username, and password, without any email or phone field,  
So that student registration matches institutional reality where students do not possess or use email addresses or phone numbers.

**Why this priority**: Core identity baseline. Student profiles must strictly operate without email or phone fields in forms, views, or required input payloads.

**Independent Test**: An administrator creates a student entering only full name, document identifier, date of birth, username, and password. The student is created without email or phone. Neither the creation modal, the edit form, nor the student detail card render an email or phone field.

**Acceptance Scenarios**:

1. **Given** an administrator is on the Student Creation modal (`/students`), **When** they view the form, **Then** only the following fields are presented:
   - Full Name (`name`)
   - Document Identifier (`identifier`, e.g., DNI/CE)
   - Date of Birth (`birth_date`)
   - Username (`username`, unique login handle)
   - Initial Password (`temporary_password`)
   **And** no email field and no phone field exist in the interface or required payload.
2. **Given** valid student details submitted without email or phone, **When** the administrator submits the form, **Then** the student and their associated user account are created with `email = null` and the specified `username`.
3. **Given** an existing student record, **When** the administrator views `/students/{id}` or opens the edit form, **Then** the form reflects name, identifier, username, and birth date, with no email or phone inputs or placeholders displayed.
4. **Given** an administrator attempts to register a student with an already registered username or document identifier, **When** submitting, **Then** the system prevents creation and shows immediate validation error feedback.

---

### User Story 2 - Student Authentication via Username (Priority: P2)

As a student registered in Open-Edu,  
I want to log into the platform using exclusively my username and password,  
So that I can access my classes and courses without having or entering an email address.

**Why this priority**: Fundamental access path. Because students have no email, authentication for students is performed using their `username`.

**Independent Test**: A student navigates to the login screen, enters their username and password into the login form, and successfully logs into their account without needing an email.

**Acceptance Scenarios**:

1. **Given** a student with a registered username and password, **When** they enter their username and password on the login screen, **Then** authentication succeeds and they are redirected to their authorized dashboard/courses.
2. **Given** an existing administrator or teacher with an email, **When** logging in, **Then** the login interface supports entering either username or email so that existing staff logins continue functioning seamlessly.
3. **Given** invalid credentials, **When** a user attempts to log in, **Then** authentication is rejected with secure validation feedback and rate limiting is applied.

---

### User Story 3 - Student Detail Profile & Lists Presentation (Priority: P3)

As an administrator or teacher viewing student lists and student detail profiles,  
I want to see the student's personal information (identifier, username, date of birth) without empty email or phone placeholders,  
So that the interface displays relevant institutional identity data cleanly.

**Why this priority**: Ensures cohesive UI consistency across student cards, table listings, and enrollments.

**Independent Test**: View `/students` and `/students/{id}`. Confirm columns and cards display student name, username, document identifier, and date of birth, without displaying email or phone columns/rows for students.

**Acceptance Scenarios**:

1. **Given** an active student profile, **When** an authorized user views `/students/{id}`, **Then** the personal information card shows document identifier, username, and date of birth, and contains no email or phone label/value.
2. **Given** the students index table at `/students`, **When** the administrator views the list, **Then** the student column displays the student's name and username without displaying email subtitles or phone columns.

---

### Edge Cases

- **Email column at database level**: The underlying `users.email` column must remain nullable to permit student accounts to have `email = null` while preserving email authentication for teachers/administrators.
- **Search filtering**: When administrators search in `/students?q=...`, the query searches across `identifier`, `user.name`, and `user.username`. Searches do not depend on email or phone.
- **Username formatting**: Enforces alphanumeric characters, hyphens, and underscores without spaces (`alpha_dash:ascii`), with case-insensitive uniqueness.
- **Birth date boundaries**: Must be a valid date in the past (`before:today`).

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST manage students with ONLY the following fields: Full Name (`name`), Document Identifier (`identifier`), Date of Birth (`birth_date`), Username (`username`), and Password (`temporary_password`).
- **FR-002**: System MUST NOT include an email field or phone field in student registration, edit forms, or student detail cards.
- **FR-003**: System MUST permit student accounts to be registered with `email` as null.
- **FR-004**: System MUST authenticate students on the login interface using their unique `username` and password.
- **FR-005**: System MUST ensure `username` is mandatory, unique across user accounts, case-insensitive, and adheres to valid username formatting (3 to 50 characters, `alpha_dash:ascii`).
- **FR-006**: System MUST ensure student `identifier` (document identifier) is mandatory and unique across students.
- **FR-007**: System MUST validate that student `birth_date` is a valid date in the past.
- **FR-008**: System MUST maintain backward compatibility for existing staff logins via email or username.
- **FR-009**: System MUST display document identifier, username, and date of birth in the student details page and students table, without email or phone columns/labels.

### Key Entities

- **Student**:
  - `identifier`: Unique document identifier (DNI, CE, Passport).
  - `birth_date`: Date of birth of the student.
  - `is_active`: Status flag.
  - Linked to platform User account.
- **User (Account/Credentials)**:
  - `name`: Full personal name.
  - `username`: Unique username used for login authentication.
  - `email`: Nullable (null for students; populated for staff/administrators).
  - `password`: Securely hashed password.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Administrators can register a student in under 45 seconds without filling or seeing any email or phone field.
- **SC-002**: 100% of students can log in using only their username and password.
- **SC-003**: Zero email or phone input fields, table columns, or empty placeholders exist across student creation, edit, and profile views.
- **SC-004**: System blocks 100% of duplicate username or document identifier submission attempts with clear error feedback.

---

## Assumptions

- Students do not use or have email accounts; password resets and credentials administration are handled directly by institutional administrators.
- Administrators and teachers retain their email addresses for authentication and communications.
- Existing student records in the database with previously assigned demo emails will simply have their email ignored in student UI views and authenticate via their generated `username`.
