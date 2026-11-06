# Quickstart & Validation Guide: Admin Password Reset and Public Recovery Removal

**Feature Branch**: `017-admin-password-reset`  
**Date**: 2026-11-05  
**Spec Reference**: `specs/017-admin-password-reset/spec.md`  
**Obsidian Note**: `[[REQ-028 - Restablecimiento de Contraseña Exclusivo por Administrador y Eliminación de Recuperacion en Login]]`

---

## 1. Automated Test Execution

Run targeted Pest feature tests:
```bash
# Verify public recovery route removal & login link removal
php artisan test --filter=AuthenticationTest

# Verify student password reset by admin
php artisan test --filter=StudentRegistrationTest
php artisan test --filter=StudentManagementTest

# Verify teacher/user password reset by admin & forced password change
php artisan test --filter=UserManagementTest
php artisan test --filter=ForcePasswordChangeTest
```

---

## 2. End-to-End Validation Scenarios

### Scenario A: Login Screen Has No Password Recovery Link
1. Navigate to `/login`.
2. Inspect the form: verify that there is NO "¿Olvidaste tu contraseña?" link or button.
3. Attempt to directly visit `/forgot-password`: verify a 404 response is returned.

### Scenario B: Administrator Resets a Student's Password
1. Log in as an administrator (`admin@openedu.test`).
2. Navigate to `/students` and open any student profile (e.g. `/students/4`).
3. Under the personal information card, enter a new password (e.g., `NewPass123!`) in the "Nueva contraseña" field.
4. Click "Guardar cambios".
5. Log out.
6. Log in as the student using their `username` and `NewPass123!`.
7. Verify that the student is prompted to set their own permanent password (`/password/change`).

### Scenario C: Administrator Resets a Teacher's Password
1. Log in as an administrator (`admin@openedu.test`).
2. Navigate to `/users` and click edit on a teacher (`/users/{id}`).
3. Enter `TeacherPass123!` in the "Nueva contraseña" field and click "Guardar".
4. Log out.
5. Log in as the teacher with `TeacherPass123!`.
6. Verify the teacher is redirected to `/password/change` before accessing teacher dashboard.

