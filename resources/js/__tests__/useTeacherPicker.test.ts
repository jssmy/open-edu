import { describe, expect, it } from 'vitest';
import { ref } from 'vue';
import { useTeacherPicker, type TeacherOption } from '@/composables/useTeacherPicker';

function makeTeachers(total: number): TeacherOption[] {
    return Array.from({ length: total }, (_, index) => ({ id: index + 1, name: `Docente ${String(index + 1).padStart(3, '0')}` }));
}

function picker(total: number, assigned: number[]) {
    return useTeacherPicker(ref(makeTeachers(total)), ref(assigned));
}

describe('useTeacherPicker', () => {
    it('muestra los asignados primero y completa a 10 filas', () => {
        const { visibleTeachers } = picker(30, [25, 3, 12]);

        expect(visibleTeachers.value).toHaveLength(10);
        expect(visibleTeachers.value.slice(0, 3).map((teacher) => teacher.id)).toEqual([3, 12, 25]);
    });

    it('muestra todos los asignados cuando son 10 o más', () => {
        const assigned = Array.from({ length: 100 }, (_, index) => index + 1);
        const { visibleTeachers } = picker(150, assigned);

        expect(visibleTeachers.value).toHaveLength(100);
    });

    it('muestra todos si hay menos de 10 docentes en total', () => {
        expect(picker(4, [2]).visibleTeachers.value.map((teacher) => teacher.id)).toEqual([2, 1, 3, 4]);
    });

    it('filtra sin distinguir mayúsculas ni tildes y mantiene asignados primero', () => {
        const teachers = ref<TeacherOption[]>([
            { id: 1, name: 'Álvaro Pérez' },
            { id: 2, name: 'Maria Lopez' },
            { id: 3, name: 'ALVARO Gómez' },
        ]);
        const { query, visibleTeachers } = useTeacherPicker(teachers, ref([3]));

        query.value = '  alvaro ';

        expect(visibleTeachers.value.map((teacher) => teacher.id)).toEqual([3, 1]);
    });

    it('al borrar la búsqueda restaura la vista priorizada', () => {
        const { query, visibleTeachers } = picker(30, [5]);

        query.value = 'Docente 029';
        expect(visibleTeachers.value).toHaveLength(1);

        query.value = '';
        expect(visibleTeachers.value).toHaveLength(10);
        expect(visibleTeachers.value[0].id).toBe(5);
    });

    it('no reordena al cambiar la selección, solo la asignación guardada', () => {
        const assigned = ref([2]);
        const { visibleTeachers } = useTeacherPicker(ref(makeTeachers(15)), assigned);

        expect(visibleTeachers.value[0].id).toBe(2);
    });
});
