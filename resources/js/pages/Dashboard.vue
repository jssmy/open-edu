<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedProps } from '@/types';

type ShortcutTheme = 'sky' | 'emerald' | 'amber' | 'violet' | 'rose' | 'teal' | 'indigo';
type ShortcutCategory = 'Aprendizaje' | 'Académico' | 'Administración';
type ShortcutIcon =
    | 'catalog'
    | 'study'
    | 'progress'
    | 'results'
    | 'schedule'
    | 'calendar'
    | 'students'
    | 'reports'
    | 'users'
    | 'roles';

interface ShortcutItem {
    href: string;
    label: string;
    description: string;
    permission: string;
    badge: string;
    category: ShortcutCategory;
    theme: ShortcutTheme;
    icon: ShortcutIcon;
    actionLabel: string;
}

interface ThemeStyle {
    topBar: string;
    iconBg: string;
    badge: string;
    hoverBorder: string;
    actionText: string;
}

const page = usePage<SharedProps>();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const roles = computed(() => page.props.auth?.roles ?? []);
const selectedCategory = ref<'Todos' | ShortcutCategory>('Todos');
const activeStepIndex = ref(0);

const themeStyles: Record<ShortcutTheme, ThemeStyle> = {
    sky: {
        topBar: 'bg-vivid-sky/80',
        iconBg: 'border border-vivid-sky-border bg-vivid-sky-soft text-vivid-sky-dark',
        badge: 'bg-vivid-sky-soft text-vivid-sky-dark',
        hoverBorder: 'hover:border-vivid-sky/40',
        actionText: 'text-vivid-sky-dark',
    },
    emerald: {
        topBar: 'bg-vivid-emerald/80',
        iconBg: 'border border-vivid-emerald-border bg-vivid-emerald-soft text-vivid-emerald-dark',
        badge: 'bg-vivid-emerald-soft text-vivid-emerald-dark',
        hoverBorder: 'hover:border-vivid-emerald/40',
        actionText: 'text-vivid-emerald-dark',
    },
    amber: {
        topBar: 'bg-vivid-amber/80',
        iconBg: 'border border-vivid-amber-border bg-vivid-amber-soft text-vivid-amber-dark',
        badge: 'bg-vivid-amber-soft text-vivid-amber-dark',
        hoverBorder: 'hover:border-vivid-amber/40',
        actionText: 'text-vivid-amber-dark',
    },
    violet: {
        topBar: 'bg-vivid-violet/80',
        iconBg: 'border border-vivid-violet-border bg-vivid-violet-soft text-vivid-violet-dark',
        badge: 'bg-vivid-violet-soft text-vivid-violet-dark',
        hoverBorder: 'hover:border-vivid-violet/40',
        actionText: 'text-vivid-violet-dark',
    },
    rose: {
        topBar: 'bg-vivid-rose/80',
        iconBg: 'border border-vivid-rose-border bg-vivid-rose-soft text-vivid-rose-dark',
        badge: 'bg-vivid-rose-soft text-vivid-rose-dark',
        hoverBorder: 'hover:border-vivid-rose/40',
        actionText: 'text-vivid-rose-dark',
    },
    teal: {
        topBar: 'bg-vivid-teal/80',
        iconBg: 'border border-vivid-teal-border bg-vivid-teal-soft text-vivid-teal-dark',
        badge: 'bg-vivid-teal-soft text-vivid-teal-dark',
        hoverBorder: 'hover:border-vivid-teal/40',
        actionText: 'text-vivid-teal-dark',
    },
    indigo: {
        topBar: 'bg-vivid-indigo/80',
        iconBg: 'border border-vivid-indigo-border bg-vivid-indigo-soft text-vivid-indigo-dark',
        badge: 'bg-vivid-indigo-soft text-vivid-indigo-dark',
        hoverBorder: 'hover:border-vivid-indigo/40',
        actionText: 'text-vivid-indigo-dark',
    },
};

const allShortcuts: ShortcutItem[] = [
    {
        href: '/courses',
        label: 'Oferta académica',
        description: 'Explora el catálogo de cursos, unidades formativas y docentes asignados.',
        permission: 'courses.view',
        badge: 'Catálogo',
        category: 'Académico',
        theme: 'sky',
        icon: 'catalog',
        actionLabel: 'Explorar cursos',
    },
    {
        href: '/my/courses',
        label: 'Mis cursos',
        description: 'Retoma tus clases activas, revisa el material multimedia y continúa aprendiendo.',
        permission: 'study',
        badge: 'Aula virtual',
        category: 'Aprendizaje',
        theme: 'emerald',
        icon: 'study',
        actionLabel: 'Ir a mis clases',
    },
    {
        href: '/my/progress',
        label: 'Mi progreso',
        description: 'Visualiza tu porcentaje de avance y las lecciones completadas en cada curso.',
        permission: 'study',
        badge: 'Seguimiento',
        category: 'Aprendizaje',
        theme: 'amber',
        icon: 'progress',
        actionLabel: 'Ver mi avance',
    },
    {
        href: '/my/results',
        label: 'Mis resultados',
        description: 'Consulta tus calificaciones, intentos realizados y retroalimentación de evaluaciones.',
        permission: 'study',
        badge: 'Evaluaciones',
        category: 'Aprendizaje',
        theme: 'violet',
        icon: 'results',
        actionLabel: 'Revisar notas',
    },
    {
        href: '/my/schedule',
        label: 'Mi horario',
        description: 'Revisa tu agenda semanal de sesiones presenciales y virtuales asignadas.',
        permission: 'scheduling.view-own',
        badge: 'Agenda docente',
        category: 'Académico',
        theme: 'teal',
        icon: 'schedule',
        actionLabel: 'Ver mi agenda',
    },
    {
        href: '/scheduling',
        label: 'Programación académica',
        description: 'Coordina periodos, aulas físicas, modalidades y bloques horarios sin conflictos.',
        permission: 'scheduling.manage',
        badge: 'Planificación',
        category: 'Académico',
        theme: 'violet',
        icon: 'calendar',
        actionLabel: 'Gestionar horarios',
    },
    {
        href: '/students',
        label: 'Estudiantes',
        description: 'Consulta el directorio estudiantil, matrículas activas y trayectoria académica.',
        permission: 'students.view',
        badge: 'Comunidad',
        category: 'Académico',
        theme: 'rose',
        icon: 'students',
        actionLabel: 'Ver estudiantes',
    },
    {
        href: '/reports/enrollments',
        label: 'Reportes académicos',
        description: 'Analiza indicadores de matrícula, progreso estudiantil y rendimiento institucional.',
        permission: 'reports.view',
        badge: 'Analítica',
        category: 'Administración',
        theme: 'teal',
        icon: 'reports',
        actionLabel: 'Abrir reportes',
    },
    {
        href: '/users',
        label: 'Administración de usuarios',
        description: 'Gestiona las cuentas del personal docente, administrativo y estudiantil.',
        permission: 'users.manage',
        badge: 'Cuentas',
        category: 'Administración',
        theme: 'indigo',
        icon: 'users',
        actionLabel: 'Administrar cuentas',
    },
    {
        href: '/roles',
        label: 'Roles y permisos',
        description: 'Configura la matriz de privilegios y niveles de seguridad de la plataforma.',
        permission: 'roles.manage',
        badge: 'Seguridad',
        category: 'Administración',
        theme: 'amber',
        icon: 'roles',
        actionLabel: 'Configurar accesos',
    },
];

const shortcuts = computed(() =>
    allShortcuts
        .filter((shortcut) => permissions.value.includes(shortcut.permission))
        .map((shortcut, index) => ({
            ...shortcut,
            number: String(index + 1).padStart(2, '0'),
            styles: themeStyles[shortcut.theme],
        })),
);

const availableCategories = computed(() => {
    const categories = new Set<ShortcutCategory>();
    for (const item of shortcuts.value) {
        categories.add(item.category);
    }
    return ['Todos' as const, ...Array.from(categories)];
});

const filteredShortcuts = computed(() => {
    if (selectedCategory.value === 'Todos') {
        return shortcuts.value;
    }
    return shortcuts.value.filter((item) => item.category === selectedCategory.value);
});

const primaryShortcut = computed(() => shortcuts.value[0] ?? null);
const secondaryShortcut = computed(() => shortcuts.value[1] ?? null);

const pathwaySteps = [
    {
        number: '01',
        label: 'Planifica',
        title: 'Explora tu oferta y agenda semanal',
        description:
            'Consulta el catálogo de cursos o revisa los horarios programados para ubicar tus sesiones presenciales y virtuales.',
        tip: 'Ideal al iniciar cada semana académica',
        dotActive: 'border-vivid-sky bg-vivid-sky text-white ring-4 ring-vivid-sky-soft',
        dotIdle: 'border-vivid-sky-border bg-vivid-sky-soft text-vivid-sky-dark hover:border-vivid-sky',
        accentText: 'text-vivid-sky-dark',
        barColor: 'bg-vivid-sky',
    },
    {
        number: '02',
        label: 'Aprende',
        title: 'Avanza en tus unidades y lecciones',
        description:
            'Recorre el contenido multimedia de cada módulo, completa las lecturas y registra tu progreso de forma continua.',
        tip: 'Tu avance se guarda automáticamente al completar cada lección',
        dotActive: 'border-vivid-emerald bg-vivid-emerald text-white ring-4 ring-vivid-emerald-soft',
        dotIdle:
            'border-vivid-emerald-border bg-vivid-emerald-soft text-vivid-emerald-dark hover:border-vivid-emerald',
        accentText: 'text-vivid-emerald-dark',
        barColor: 'bg-vivid-emerald',
    },
    {
        number: '03',
        label: 'Evalúa',
        title: 'Mide logros y consulta resultados',
        description:
            'Realiza las evaluaciones publicadas o revisa los reportes de seguimiento para acompañar el rendimiento académico.',
        tip: 'Revisa la retroalimentación de cada intento',
        dotActive: 'border-vivid-amber bg-vivid-amber text-white ring-4 ring-vivid-amber-soft',
        dotIdle:
            'border-vivid-amber-border bg-vivid-amber-soft text-vivid-amber-dark hover:border-vivid-amber',
        accentText: 'text-vivid-amber-dark',
        barColor: 'bg-vivid-amber',
    },
] as const;

const currentStep = computed(() => pathwaySteps[activeStepIndex.value] ?? pathwaySteps[0]);
</script>

<template>
    <AppLayout>
        <Head title="Inicio" />
        <div class="space-y-10">
            <!-- Hero abierto sin card ni bordes -->
            <section class="relative pb-2 pt-1">
                <div class="relative z-10 max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-secondary/20 bg-secondary-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-secondary"
                        >
                            <span class="size-2 rounded-full bg-secondary" aria-hidden="true" />
                            Portal académico institucional
                        </span>
                        <span
                            v-for="role in roles"
                            :key="role"
                            class="inline-flex items-center rounded-full border border-primary/15 bg-primary-soft px-3 py-1 text-xs font-semibold capitalize text-primary"
                        >
                            {{ role }}
                        </span>
                    </div>

                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-primary sm:text-4xl">
                        Bienvenido{{ page.props.auth?.user.name ? `, ${page.props.auth.user.name}` : '' }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-base leading-7 text-text-muted">
                        Un espacio central para aprender, acompañar trayectorias y gestionar la vida académica
                        con comodidad. Accede directamente a las herramientas de tu cuenta.
                    </p>

                    <div v-if="primaryShortcut" class="mt-6 flex flex-wrap items-center gap-3">
                        <Link
                            :href="primaryShortcut.href"
                            class="inline-flex min-h-11 items-center gap-2 rounded-control bg-primary px-5 py-2.5 text-sm font-semibold text-white transition duration-150 motion-reduce:transition-none hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <span>{{ primaryShortcut.actionLabel }}</span>
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 20 20"
                                class="size-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M4 10h11m-4-4 4 4-4 4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </Link>
                        <Link
                            v-if="secondaryShortcut"
                            :href="secondaryShortcut.href"
                            class="inline-flex min-h-11 items-center gap-2 rounded-control border border-border bg-panel px-5 py-2.5 text-sm font-semibold text-text transition duration-150 motion-reduce:transition-none hover:border-primary/30 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <span>{{ secondaryShortcut.label }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Franja integrada con divisores limpios (en lugar de múltiples cards) -->
                <div
                    class="relative z-10 mt-8 grid gap-4 border-t border-border/80 pt-5 sm:grid-cols-3 sm:gap-0 sm:divide-x sm:divide-border/80"
                >
                    <div class="flex items-center gap-3 sm:pr-6">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-vivid-emerald-soft text-vivid-emerald-dark"
                        >
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 24 24"
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M12 3.5 4 7.5l8 4 8-4-8-4Zm-8 8 8 4 8-4M4 15.5l8 4 8-4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs text-text-muted">Espacio habilitado</p>
                            <p class="truncate text-sm font-semibold text-text">
                                {{ shortcuts.length }}
                                {{ shortcuts.length === 1 ? 'herramienta activa' : 'herramientas activas' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 sm:px-6">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-vivid-sky-soft text-vivid-sky-dark"
                        >
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 24 24"
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs text-text-muted">Flujo unificado</p>
                            <p class="truncate text-sm font-semibold text-text">
                                Aprendizaje y gestión en un solo lugar
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 sm:pl-6">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-vivid-amber-soft text-vivid-amber-dark"
                        >
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 24 24"
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs text-text-muted">Cuenta institucional</p>
                            <p class="truncate text-sm font-semibold text-text">
                                {{ page.props.auth?.user.email ?? 'Sesión activa' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Accesos Directos con acentos de color suaves y equilibrados -->
            <section v-if="shortcuts.length" aria-labelledby="accesos-heading" class="space-y-5">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-secondary">
                            Acceso directo
                        </p>
                        <h2 id="accesos-heading" class="mt-1 text-xl font-bold tracking-tight text-text">
                            Herramientas de tu espacio
                        </h2>
                        <p class="mt-1 text-sm text-text-muted">
                            Disponibles según tus permisos institucionales
                        </p>
                    </div>

                    <div
                        v-if="availableCategories.length > 2"
                        class="flex flex-wrap items-center gap-2"
                        role="group"
                        aria-label="Filtrar herramientas por categoría"
                    >
                        <button
                            v-for="category in availableCategories"
                            :key="category"
                            type="button"
                            class="cursor-pointer rounded-full border px-3.5 py-1.5 text-xs font-semibold transition duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :class="
                                selectedCategory === category
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-border bg-panel text-text-muted hover:border-primary/30 hover:text-text'
                            "
                            :aria-pressed="selectedCategory === category"
                            @click="selectedCategory = category"
                        >
                            {{ category }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="shortcut in filteredShortcuts"
                        :key="shortcut.href"
                        :href="shortcut.href"
                        class="group flex min-h-44 flex-col overflow-hidden rounded-card border border-border bg-panel shadow-card transition duration-200 motion-reduce:transition-none hover:-translate-y-0.5 hover:shadow-overlay focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        :class="shortcut.styles.hoverBorder"
                    >
                        <div class="h-1 w-full" :class="shortcut.styles.topBar" aria-hidden="true" />

                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-center justify-between gap-3">
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-control"
                                    :class="shortcut.styles.iconBg"
                                >
                                    <svg
                                        aria-hidden="true"
                                        viewBox="0 0 24 24"
                                        class="size-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            v-if="shortcut.icon === 'catalog'"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                        />
                                        <path
                                            v-else-if="shortcut.icon === 'study'"
                                            d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"
                                        />
                                        <path
                                            v-else-if="shortcut.icon === 'progress'"
                                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"
                                        />
                                        <path
                                            v-else-if="shortcut.icon === 'results'"
                                            d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"
                                        />
                                        <path
                                            v-else-if="
                                                shortcut.icon === 'schedule' || shortcut.icon === 'calendar'
                                            "
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
                                        />
                                        <path
                                            v-else-if="
                                                shortcut.icon === 'students' || shortcut.icon === 'users'
                                            "
                                            d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
                                        />
                                        <path
                                            v-else-if="shortcut.icon === 'reports'"
                                            d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6ZM13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z"
                                        />
                                        <path
                                            v-else
                                            d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"
                                        />
                                    </svg>
                                </span>

                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="shortcut.styles.badge"
                                    >
                                        {{ shortcut.badge }}
                                    </span>
                                    <span class="text-xs font-semibold tabular-nums text-text-muted">
                                        {{ shortcut.number }}
                                    </span>
                                </div>
                            </div>

                            <h3 class="mt-4 text-base font-semibold text-text group-hover:text-primary">
                                {{ shortcut.label }}
                            </h3>
                            <p class="mt-1.5 flex-1 text-sm leading-6 text-text-muted">
                                {{ shortcut.description }}
                            </p>

                            <div
                                class="mt-4 flex items-center justify-between pt-2 text-xs font-semibold"
                                :class="shortcut.styles.actionText"
                            >
                                <span>{{ shortcut.actionLabel }}</span>
                                <svg
                                    aria-hidden="true"
                                    viewBox="0 0 20 20"
                                    class="size-4 transition-transform duration-150 motion-reduce:transition-none group-hover:translate-x-0.5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        d="M4 10h11m-4-4 4 4-4 4"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Estado vacío cuando no tiene herramientas asignadas -->
            <section v-else class="rounded-card border border-border bg-panel p-6 shadow-card">
                <h2 class="text-base font-semibold text-text">Tu espacio académico</h2>
                <p class="mt-2 text-sm leading-6 text-text-muted">
                    Por el momento no hay herramientas adicionales asignadas a tu cuenta.
                </p>
            </section>

            <!-- Guía Rápida: Línea de tiempo / Stepper interactivo continuo (Cero Cards) -->
            <section aria-labelledby="ruta-academica-heading" class="border-t border-border pt-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-secondary">
                            Guía rápida
                        </p>
                        <h2
                            id="ruta-academica-heading"
                            class="mt-1 text-xl font-bold tracking-tight text-text"
                        >
                            Cómo funciona tu recorrido en el campus
                        </h2>
                    </div>
                    <p class="text-xs text-text-muted">Selecciona un paso para ver la recomendación</p>
                </div>

                <!-- Stepper horizontal conectado por una línea continua -->
                <div class="relative mt-7">
                    <div
                        class="pointer-events-none absolute left-6 right-6 top-5 hidden h-0.5 bg-gradient-to-r from-vivid-sky-border via-vivid-emerald-border to-vivid-amber-border md:block"
                        aria-hidden="true"
                    />

                    <ol class="relative grid gap-6 md:grid-cols-3">
                        <li v-for="(step, index) in pathwaySteps" :key="step.number" class="relative">
                            <button
                                type="button"
                                class="group flex w-full cursor-pointer items-start gap-4 text-left focus-visible:outline-none md:flex-col md:gap-3"
                                :aria-current="activeStepIndex === index ? 'step' : undefined"
                                @click="activeStepIndex = index"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold tabular-nums transition duration-200 motion-reduce:transition-none"
                                        :class="activeStepIndex === index ? step.dotActive : step.dotIdle"
                                    >
                                        {{ step.number }}
                                    </span>
                                    <span
                                        class="text-xs font-bold uppercase tracking-wider"
                                        :class="step.accentText"
                                    >
                                        {{ step.label }}
                                    </span>
                                </div>

                                <div class="min-w-0 md:pr-4">
                                    <h3
                                        class="text-base font-semibold transition-colors"
                                        :class="
                                            activeStepIndex === index
                                                ? 'text-primary'
                                                : 'text-text group-hover:text-primary'
                                        "
                                    >
                                        {{ step.title }}
                                    </h3>
                                    <p class="mt-1 text-sm leading-6 text-text-muted">
                                        {{ step.description }}
                                    </p>
                                </div>
                            </button>
                        </li>
                    </ol>
                </div>

                <!-- Franja editorial de consejo activo (sin card, solo acento lateral limpio) -->
                <div class="mt-6 flex items-center gap-3 border-l-4 border-secondary pl-4 py-1">
                    <span class="text-xs font-bold uppercase tracking-wider" :class="currentStep.accentText">
                        Paso {{ currentStep.number }} · {{ currentStep.label }}:
                    </span>
                    <p class="text-sm text-text-muted">
                        {{ currentStep.tip }}
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
