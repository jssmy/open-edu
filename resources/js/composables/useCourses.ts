import type { CourseStatus } from '@/services/courses';

export function useCourses() {
    const variants: Record<CourseStatus, 'neutral' | 'success' | 'warning'> = {
        draft: 'neutral',
        published: 'success',
        deactivated: 'warning',
    };

    function statusLabel(status: CourseStatus): string {
        return {
            draft: 'Borrador',
            published: 'Publicado',
            deactivated: 'Desactivado',
        }[status];
    }

    function statusVariant(status: CourseStatus): 'neutral' | 'success' | 'warning' {
        return variants[status];
    }

    return { statusLabel, statusVariant };
}
