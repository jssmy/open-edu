import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AppLayout from '@/layouts/AppLayout.vue';

const { page, post } = vi.hoisted(() => ({
    page: {
        url: '/courses/42',
        props: {
            auth: {
                user: { id: 8, name: 'Ana García', email: 'ana@example.edu' },
                roles: ['docente'],
                permissions: ['courses.view', 'study'],
            },
            flash: { success: null },
        },
    },
    post: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    router: { post },
    usePage: () => page,
}));

describe('AppLayout', () => {
    beforeEach(() => {
        page.url = '/courses/42';
        page.props.auth.permissions = ['courses.view', 'study'];
        post.mockClear();
    });

    it('muestra la navegación autorizada y marca la sección actual', () => {
        const wrapper = mount(AppLayout, { slots: { default: '<h1>Contenido</h1>' } });

        expect(wrapper.find('a[href="/courses"]').attributes('aria-current')).toBe('page');
        expect(wrapper.find('a[href="/my/classes"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/users"]').exists()).toBe(false);
    });

    it('abre y cierra la navegación móvil', async () => {
        const wrapper = mount(AppLayout);
        const sidebar = wrapper.get('#navegacion-principal');

        expect(sidebar.classes()).toContain('-translate-x-full');
        expect(sidebar.classes()).toContain('invisible');
        await wrapper.get('button[aria-label="Abrir menú de navegación"]').trigger('click');
        expect(sidebar.classes()).not.toContain('-translate-x-full');
        expect(sidebar.classes()).not.toContain('invisible');
        await wrapper.get('button[aria-label="Cerrar menú de navegación"]').trigger('click');
        expect(sidebar.classes()).toContain('-translate-x-full');
    });

    it('presenta la identidad del usuario y permite cerrar sesión', async () => {
        const wrapper = mount(AppLayout);

        expect(wrapper.text()).toContain('Ana García');
        expect(wrapper.text()).toContain('ana@example.edu');
        await wrapper.get('details > summary').trigger('click');
        expect(wrapper.get('details').attributes('open')).toBeDefined();
        await wrapper.get('details > summary').trigger('keydown', { key: 'Escape' });
        expect(wrapper.get('details').attributes('open')).toBeUndefined();
        await wrapper.get('details > summary').trigger('click');
        await wrapper.get('details button').trigger('click');

        expect(post).toHaveBeenCalledWith('/logout');
    });
});
