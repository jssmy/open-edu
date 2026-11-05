<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';
import Alert from '@/design-system/Alert.vue';
import AlertDialogHost from '@/components/AlertDialogHost.vue';

const page = usePage<SharedProps>();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const isNavigationOpen = ref(false);
const profileMenu = ref<{ open: boolean } | null>(null);

interface NavigationLink {
    href: string;
    label: string;
    description: string;
    icon: 'home' | 'courses' | 'students' | 'study' | 'progress' | 'results' | 'users' | 'roles' | 'reports';
    permission?: string;
    matches: string[];
}

const navigationGroups = computed(() => {
    const links: NavigationLink[] = [
        {
            href: '/dashboard',
            label: 'Inicio',
            description: 'Resumen del portal académico',
            icon: 'home',
            matches: ['/dashboard'],
        },
        {
            href: '/courses',
            label: 'Cursos',
            description: 'Consulta la oferta académica',
            icon: 'courses',
            permission: 'courses.view',
            matches: ['/courses'],
        },
        {
            href: '/my/classes',
            label: 'Clases',
            description: 'Accede a tus clases y cursos asignados',
            icon: 'study',
            permission: 'scheduling.view-own',
            matches: ['/my/classes'],
        },
        {
            href: '/scheduling',
            label: 'Programación académica',
            description: 'Gestiona horarios, docentes y aulas',
            icon: 'courses',
            permission: 'scheduling.manage',
            matches: ['/scheduling'],
        },
        {
            href: '/my/schedule',
            label: 'Mi horario',
            description: 'Consulta tus clases asignadas',
            icon: 'courses',
            permission: 'scheduling.view-own',
            matches: ['/my/schedule'],
        },
        {
            href: '/students',
            label: 'Estudiantes',
            description: 'Consulta la comunidad estudiantil',
            icon: 'students',
            permission: 'students.view',
            matches: ['/students'],
        },
        {
            href: '/enrollments',
            label: 'Matrículas',
            description: 'Matricula estudiantes en clases',
            icon: 'students',
            permission: 'enrollments.manage',
            matches: ['/enrollments'],
        },
        {
            href: '/my/courses',
            label: 'Mis cursos',
            description: 'Continúa tu aprendizaje',
            icon: 'study',
            permission: 'study',
            matches: ['/my/courses'],
        },
        {
            href: '/my/progress',
            label: 'Mi progreso',
            description: 'Revisa tu avance académico',
            icon: 'progress',
            permission: 'study',
            matches: ['/my/progress'],
        },
        {
            href: '/my/results',
            label: 'Mis resultados',
            description: 'Consulta tus evaluaciones',
            icon: 'results',
            permission: 'study',
            matches: ['/my/results'],
        },
        {
            href: '/users',
            label: 'Usuarios',
            description: 'Administra las cuentas institucionales',
            icon: 'users',
            permission: 'users.manage',
            matches: ['/users'],
        },
        {
            href: '/roles',
            label: 'Roles y permisos',
            description: 'Configura accesos de la plataforma',
            icon: 'roles',
            permission: 'roles.manage',
            matches: ['/roles'],
        },
        {
            href: '/reports/enrollments',
            label: 'Reportes',
            description: 'Consulta los informes académicos',
            icon: 'reports',
            permission: 'reports.view',
            matches: ['/reports/enrollments', '/reports/progress', '/reports/results'],
        },
    ];

    return [
        {
            label: 'Portal',
            links: links.filter((link) => link.icon === 'home'),
        },
        {
            label: 'Académico',
            links: links.filter(
                (link) =>
                    link.permission &&
                    [
                        'courses.view',
                        'scheduling.manage',
                        'scheduling.view-own',
                        'students.view',
                        'enrollments.manage',
                        'study',
                    ].includes(link.permission),
            ),
        },
        {
            label: 'Administración',
            links: links.filter(
                (link) =>
                    link.permission &&
                    ['users.manage', 'roles.manage', 'reports.view'].includes(link.permission),
            ),
        },
    ]
        .map((group) => ({
            ...group,
            links: group.links.filter(
                (link) => !link.permission || permissions.value.includes(link.permission),
            ),
        }))
        .filter((group) => group.links.length > 0);
});

const currentPath = computed(() => page.url.split(/[?#]/, 1)[0] ?? page.url);
const userInitials = computed(() =>
    (page.props.auth?.user.name ?? 'Usuario')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toLocaleUpperCase() ?? '')
        .join(''),
);

function isActive(link: NavigationLink): boolean {
    return link.matches.some(
        (path) => currentPath.value === path || currentPath.value.startsWith(`${path}/`),
    );
}

function closeNavigation(): void {
    isNavigationOpen.value = false;
}

function closeMenus(): void {
    closeNavigation();
    if (profileMenu.value) {
        profileMenu.value.open = false;
    }
}

function logout(): void {
    closeMenus();
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen lg:flex" @keydown.esc="closeMenus">
        <a
            href="#contenido-principal"
            class="sr-only z-50 rounded-control bg-panel px-4 py-3 text-primary focus:not-sr-only focus:fixed focus:right-4 focus:top-4 focus-visible:ring-2 focus-visible:ring-primary"
        >
            Saltar al contenido
        </a>

        <button
            v-if="isNavigationOpen"
            type="button"
            class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
            aria-label="Cerrar menú de navegación"
            @click="closeMenus"
        />

        <aside
            id="navegacion-principal"
            class="fixed inset-y-0 left-0 z-40 flex w-[min(20rem,88vw)] flex-col border-r border-border bg-panel shadow-overlay transition-transform duration-200 motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:shrink-0 lg:translate-x-0 lg:visible lg:pointer-events-auto lg:shadow-none"
            :class="{
                '-translate-x-full': !isNavigationOpen,
                'invisible pointer-events-none': !isNavigationOpen,
            }"
            aria-label="Navegación lateral"
        >
            <div class="flex items-center gap-3 border-b border-border px-6 py-5">
                <span
                    class="flex size-11 items-center justify-center rounded-control bg-primary text-sm font-bold tracking-wide text-white"
                >
                    OE
                </span>
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold tracking-tight text-primary">Open Edu</p>
                    <p class="mt-0.5 text-xs text-text-muted">Portal institucional</p>
                </div>
                <button
                    type="button"
                    class="ml-auto inline-flex size-10 items-center justify-center rounded-control text-text-muted transition hover:bg-surface hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary lg:hidden"
                    aria-label="Cerrar menú de navegación"
                    @click="closeNavigation"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                        class="size-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6" aria-label="Principal">
                <section v-for="group in navigationGroups" :key="group.label" class="mb-7 last:mb-0">
                    <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-[0.12em] text-text-muted">
                        {{ group.label }}
                    </p>
                    <ul class="space-y-1">
                        <li v-for="link in group.links" :key="link.href">
                            <Link
                                :href="link.href"
                                :aria-current="isActive(link) ? 'page' : undefined"
                                class="group flex min-h-11 items-center gap-3 rounded-control border px-3 py-2.5 text-sm font-medium transition duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                :class="
                                    isActive(link)
                                        ? 'border-primary/10 bg-primary-soft text-primary'
                                        : 'border-transparent text-text-muted hover:border-border hover:bg-surface hover:text-text'
                                "
                                @click="closeNavigation"
                            >
                                <svg
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    class="size-5 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        v-if="link.icon === 'home'"
                                        d="m3.5 10 8.5-7 8.5 7v9.5a1 1 0 0 1-1 1h-5.25v-6h-4.5v6H4.5a1 1 0 0 1-1-1V10Z"
                                    />
                                    <path
                                        v-else-if="link.icon === 'courses' || link.icon === 'study'"
                                        d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Zm0 0V21m4-13h8m-8 4h6"
                                    />
                                    <path
                                        v-else-if="link.icon === 'students' || link.icon === 'users'"
                                        d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-7.5a4 4 0 0 1 0 7.5m2 3.5a3.5 3.5 0 0 1 2 3.15V20"
                                    />
                                    <path
                                        v-else-if="link.icon === 'progress' || link.icon === 'reports'"
                                        d="M4 19V5m0 14h16M8 15l3-4 3 2 5-6"
                                    />
                                    <path
                                        v-else-if="link.icon === 'results'"
                                        d="M6 3.5h9l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Zm8 0V8h5m-9 5 2 2 4-4"
                                    />
                                    <path
                                        v-else
                                        d="M12 3.5 14 5l2.5-.2.9 2.3 2.1 1.4-.7 2.4.7 2.4-2.1 1.4-.9 2.3L14 17l-2 1.5L10 17l-2.5.2-.9-2.3-2.1-1.4.7-2.4-.7-2.4 2.1-1.4.9-2.3L10 5l2-1.5Zm0 5.25a3.25 3.25 0 1 0 0 6.5 3.25 3.25 0 0 0 0-6.5Z"
                                    />
                                </svg>
                                <span class="min-w-0 truncate">{{ link.label }}</span>
                                <span
                                    v-if="isActive(link)"
                                    class="ml-auto size-1.5 shrink-0 rounded-full bg-primary"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    </ul>
                </section>
            </nav>

            <div class="border-t border-border px-6 py-4">
                <p class="text-xs font-medium text-text-muted">Open Edu</p>
                <p class="mt-1 text-xs text-text-muted">Formación · Comunidad · Futuro</p>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header
                class="sticky top-0 z-20 flex min-h-16 items-center justify-between gap-4 border-b border-border bg-panel/95 px-4 backdrop-blur sm:px-8"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-control border border-border bg-panel text-text-muted transition hover:bg-surface hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary lg:hidden"
                        :aria-expanded="isNavigationOpen"
                        aria-controls="navegacion-principal"
                        aria-label="Abrir menú de navegación"
                        @click="isNavigationOpen = !isNavigationOpen"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            class="size-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" />
                        </svg>
                    </button>
                    <div class="hidden min-w-0 sm:block">
                        <p class="truncate text-sm font-medium text-text">Plataforma de aprendizaje</p>
                        <p class="truncate text-xs text-text-muted">Espacio académico institucional</p>
                    </div>
                </div>

                <details v-if="page.props.auth" ref="profileMenu" class="relative">
                    <summary
                        class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-control px-2 py-1.5 transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:gap-3 sm:px-3"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-semibold text-primary"
                        >
                            {{ userInitials }}
                        </span>
                        <span class="hidden min-w-0 text-left sm:block">
                            <span class="block max-w-40 truncate text-sm font-medium text-text">{{
                                page.props.auth.user.name
                            }}</span>
                            <span class="block max-w-40 truncate text-xs text-text-muted">{{
                                page.props.auth.user.email
                            }}</span>
                        </span>
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 20 20"
                            class="hidden size-4 text-text-muted sm:block"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.2 7.5a.75.75 0 0 1 1.06 0L10 11.19l3.74-3.7a.75.75 0 1 1 1.06 1.07l-4.27 4.23a.75.75 0 0 1-1.06 0L5.2 8.56a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </summary>
                    <div
                        class="absolute right-0 top-full z-30 mt-2 w-72 rounded-card border border-border bg-panel p-2 shadow-overlay"
                    >
                        <div class="border-b border-border px-3 py-3">
                            <p class="truncate text-sm font-semibold text-text">
                                {{ page.props.auth.user.name }}
                            </p>
                            <p class="mt-1 truncate text-xs text-text-muted">
                                {{ page.props.auth.user.email }}
                            </p>
                            <p v-if="page.props.auth.roles.length" class="mt-2 text-xs text-text-muted">
                                {{ page.props.auth.roles.join(' · ') }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="mt-2 flex min-h-11 w-full items-center gap-2 rounded-control px-3 text-left text-sm font-medium text-danger transition hover:bg-danger-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            @click="logout"
                        >
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 20 20"
                                class="size-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >
                                <path
                                    d="M8 3.5H5.5a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1H8m4-3 3.5-3.5L12 6.5M15.5 10H8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            Cerrar sesión
                        </button>
                    </div>
                </details>
            </header>

            <main
                id="contenido-principal"
                class="mx-auto w-full max-w-[90rem] flex-1 px-4 py-7 sm:px-8 sm:py-9"
            >
                <slot />
            </main>
            <footer class="border-t border-border px-4 py-4 text-center text-xs text-text-muted sm:px-8">
                Open Edu <span aria-hidden="true">·</span> Plataforma institucional de aprendizaje
            </footer>
        </div>
        <AlertDialogHost />
    </div>
</template>
