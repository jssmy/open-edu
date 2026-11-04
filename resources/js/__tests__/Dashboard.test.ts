import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import Dashboard from '@/pages/Dashboard.vue';

const { page } = vi.hoisted(() => ({
    page: {
        url: '/dashboard',
        props: {
            auth: {
                user: { id: 8, name: 'Ana García', email: 'ana@example.edu' },
                roles: ['docente'],
                permissions: ['courses.view', 'study', 'scheduling.manage', 'users.manage'],
            },
            flash: { success: null },
        },
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        props: ['title'],
        template: '<title>{{ title }}</title>',
    },
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    router: { post: vi.fn() },
    usePage: () => page,
}));

describe('Dashboard', () => {
    beforeEach(() => {
        page.props.auth.permissions = ['courses.view', 'study', 'scheduling.manage', 'users.manage'];
        page.props.auth.roles = ['docente'];
    });

    it('renderiza el saludo personalizado, rol y tarjetas de herramientas autorizadas', () => {
        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Bienvenido, Ana García');
        expect(wrapper.text()).toContain('docente');
        expect(wrapper.text()).toContain('6 herramientas activas');
        expect(wrapper.find('a[href="/courses"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/my/courses"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/my/progress"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/my/results"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/scheduling"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/users"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/roles"]').exists()).toBe(false);
    });

    it('permite filtrar las herramientas por categoría', async () => {
        const wrapper = mount(Dashboard);

        const filterButtons = wrapper
            .get('[role="group"][aria-label="Filtrar herramientas por categoría"]')
            .findAll('button');
        const learningButton = filterButtons.find((btn) => btn.text() === 'Aprendizaje');
        expect(learningButton).toBeDefined();

        await learningButton!.trigger('click');

        expect(wrapper.find('#accesos-heading').exists()).toBe(true);
        expect(wrapper.text()).toContain('Mis cursos');
        expect(wrapper.text()).toContain('Mi progreso');
        expect(wrapper.text()).toContain('Mis resultados');
        expect(wrapper.text()).not.toContain('Administración de usuarios');
    });

    it('permite cambiar el paso activo en la línea de tiempo de Guía rápida', async () => {
        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Ideal al iniciar cada semana académica');

        const stepButtons = wrapper.get('ol').findAll('button');
        expect(stepButtons).toHaveLength(3);

        await stepButtons[1]!.trigger('click');
        expect(stepButtons[1]!.attributes('aria-current')).toBe('step');
        expect(wrapper.text()).toContain('Tu avance se guarda automáticamente al completar cada lección');
    });

    it('muestra el estado informativo cuando la cuenta no tiene permisos asignados', () => {
        page.props.auth.permissions = [];
        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Tu espacio académico');
        expect(wrapper.text()).toContain(
            'Por el momento no hay herramientas adicionales asignadas a tu cuenta.',
        );
    });
});
