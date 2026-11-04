import { computed, ref, type Ref } from 'vue';

export interface TeacherOption {
    id: number;
    name: string;
}

export const MIN_VISIBLE_TEACHERS = 10;

export function normalizeSearchText(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

/**
 * Orders teachers with the saved assignment first. Without a query, every assigned teacher is
 * shown and the list is padded with unassigned ones up to MIN_VISIBLE_TEACHERS rows. With a
 * query, every matching teacher is shown (assigned first).
 */
export function useTeacherPicker(teachers: Ref<TeacherOption[]>, assignedIds: Ref<number[]>) {
    const query = ref('');

    const visibleTeachers = computed<TeacherOption[]>(() => {
        const assigned = new Set(assignedIds.value);
        const term = normalizeSearchText(query.value);
        const matching = teachers.value.filter(
            (teacher) => term === '' || normalizeSearchText(teacher.name).includes(term),
        );
        const assignedFirst = [
            ...matching.filter((teacher) => assigned.has(teacher.id)),
            ...matching.filter((teacher) => !assigned.has(teacher.id)),
        ];

        if (term !== '') {
            return assignedFirst;
        }

        const assignedCount = matching.filter((teacher) => assigned.has(teacher.id)).length;

        return assignedCount >= MIN_VISIBLE_TEACHERS
            ? assignedFirst.slice(0, assignedCount)
            : assignedFirst.slice(0, MIN_VISIBLE_TEACHERS);
    });

    const hasTeachers = computed(() => teachers.value.length > 0);
    const isSearching = computed(() => normalizeSearchText(query.value) !== '');

    return { query, visibleTeachers, hasTeachers, isSearching };
}
