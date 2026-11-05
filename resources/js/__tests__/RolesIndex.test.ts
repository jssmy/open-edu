import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import RolesIndex from '@/pages/Roles/Index.vue';
import type { PermissionItem } from '@/types';
import * as useAlertDialogModule from '@/composables/useAlertDialog';

const { putMock } = vi.hoisted(() => ({
    putMock: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { template: '<a><slot /></a>' },
    router: { put: putMock },
    usePage: () => ({ props: { errors: {} } }),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

describe('Roles/Index.vue', () => {
    const mockPermissions: PermissionItem[] = [
        {
            name: 'courses.manage',
            label: 'Gestión de cursos',
            module: 'Cursos',
            description: 'Crear y editar cursos',
        },
        {
            name: 'scheduling.manage',
            label: 'Gestión de programación académica',
            module: 'Programación',
            description: 'Horarios y clases',
        },
    ];

    const mockRoles = [
        {
            id: 1,
            name: 'docente',
            label: 'Docente',
            description: 'Profesor de la plataforma',
            is_active: true,
            is_system: true,
            locked: false,
            user_count: 3,
            permissions: ['courses.manage'],
        },
    ];

    it('abre el modal y renderiza los nombres legibles de permisos agrupados por módulo', async () => {
        const wrapper = mount(RolesIndex, {
            props: {
                roles: mockRoles,
                filters: { q: '', status: '' },
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

        // Abrir modal de permisos
        const editButton = wrapper.find('button[aria-label="Configurar permisos"]');
        expect(editButton.exists()).toBe(true);
        await editButton.trigger('click');

        // Verificar labels de permisos y módulos en el modal
        expect(wrapper.text()).toContain('Permisos de: Docente');
        expect(wrapper.text()).toContain('Gestión de cursos');
        expect(wrapper.text()).toContain('Gestión de programación académica');
        expect(wrapper.text()).toContain('Cursos');
        expect(wrapper.text()).toContain('Programación');
    });

    it('ejecuta router.put y notifica el éxito mediante notifySuccess al guardar', async () => {
        const notifySpy = vi.spyOn(useAlertDialogModule, 'notifySuccess').mockResolvedValue(true);

        const wrapper = mount(RolesIndex, {
            props: {
                roles: mockRoles,
                filters: { q: '', status: '' },
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

        const editButton = wrapper.find('button[aria-label="Configurar permisos"]');
        await editButton.trigger('click');

        const saveButton = wrapper.findAll('button').find((b) => b.text().includes('Guardar y cerrar'));
        expect(saveButton).toBeDefined();

        putMock.mockImplementation((url, data, options) => {
            options?.onSuccess?.();
        });

        await saveButton?.trigger('click');

        expect(putMock).toHaveBeenCalledWith(
            '/roles/1/permissions',
            { permissions: ['courses.manage'] },
            expect.any(Object),
        );
        expect(notifySpy).toHaveBeenCalledWith('Los permisos del rol han sido guardados correctamente.');

        notifySpy.mockRestore();
    });
});
