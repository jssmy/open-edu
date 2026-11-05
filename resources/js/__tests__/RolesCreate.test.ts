import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import RolesCreate from '@/pages/Roles/Create.vue';
import type { PermissionItem } from '@/types';

// Mock de Inertia
vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { template: '<a><slot /></a>' },
    useForm: (initial: any) => ({
        ...initial,
        post: vi.fn(),
        processing: false,
        errors: {},
    }),
    usePage: () => ({ props: { errors: {} } }),
}));

// Mock de AppLayout
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

describe('Roles/Create.vue', () => {
    const mockPermissions: PermissionItem[] = [
        {
            name: 'courses.manage',
            label: 'Gestión de cursos',
            module: 'Cursos',
            description: 'Crear y editar cursos',
        },
        {
            name: 'users.manage',
            label: 'Gestión de usuarios',
            module: 'Usuarios',
            description: 'Crear y suspender usuarios',
        },
    ];

    it('renderiza los permisos con sus etiquetas descriptivas en lugar de nombres técnicos crudos', () => {
        const wrapper = mount(RolesCreate, {
            props: {
                permissions: mockPermissions,
            },
            global: {
                mocks: {
                    $page: {
                        props: { errors: {} },
                    },
                },
            },
        });

        // Debe contener los labels legibles
        expect(wrapper.text()).toContain('Gestión de cursos');
        expect(wrapper.text()).toContain('Gestión de usuarios');

        // Debe contener los módulos
        expect(wrapper.text()).toContain('Cursos');
        expect(wrapper.text()).toContain('Usuarios');

        // Debe contener descripciones
        expect(wrapper.text()).toContain('Crear y editar cursos');
    });
});
