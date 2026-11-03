import { router } from '@inertiajs/vue3';

export type CourseStatus = 'draft' | 'published' | 'deactivated';

export function changeCourseStatus(courseId: number, status: CourseStatus): void {
    router.patch(`/courses/${courseId}/status`, { status }, { preserveScroll: true });
}

export function removeCourse(courseId: number): void {
    router.delete(`/courses/${courseId}`, {
        data: { confirm_impact: true },
        preserveScroll: true,
    });
}

export function assignCourseTeachers(courseId: number, teachers: number[]): void {
    router.put(`/courses/${courseId}/teachers`, { teachers }, { preserveScroll: true });
}

export function reorderUnits(unitIds: number[]): void {
    router.patch('/units/reorder', { unit_ids: unitIds }, { preserveScroll: true });
}

export function reorderLessons(lessonIds: number[]): void {
    router.patch('/lessons/reorder', { lesson_ids: lessonIds }, { preserveScroll: true });
}

export function updateLessonPosition(
    lesson: { id: number; title: string; position: number; content_type: string; content: string | null },
    position: number,
): void {
    router.put(`/lessons/${lesson.id}`, { ...lesson, position }, { preserveScroll: true });
}

export function archiveLesson(lessonId: number): void {
    router.patch(`/lessons/${lessonId}/archive`, {}, { preserveScroll: true });
}

export function removeLesson(lessonId: number): void {
    router.delete(`/lessons/${lessonId}`, { preserveScroll: true });
}
