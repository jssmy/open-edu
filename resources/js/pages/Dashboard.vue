<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedProps } from '@/types';

const page = usePage<SharedProps>();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const shortcuts = computed(() =>
    [
        {
            href: '/courses',
            label: 'Oferta académica',
            description: 'Explora los cursos disponibles en la institución.',
            permission: 'courses.view',
            number: '01',
        },
        {
            href: '/my/courses',
            label: 'Mis cursos',
            description: 'Retoma tus cursos y continúa aprendiendo.',
            permission: 'study',
            number: '02',
        },
        {
            href: '/students',
            label: 'Estudiantes',
            description: 'Consulta la información de la comunidad estudiantil.',
            permission: 'students.view',
            number: '03',
        },
        {
            href: '/reports/enrollments',
            label: 'Reportes académicos',
            description: 'Revisa los informes de actividad y matrícula.',
            permission: 'reports.view',
            number: '04',
        },
        {
            href: '/users',
            label: 'Administración de usuarios',
            description: 'Gestiona las cuentas de la plataforma.',
            permission: 'users.manage',
            number: '05',
        },
        {
            href: '/roles',
            label: 'Roles y permisos',
            description: 'Configura los niveles de acceso institucional.',
            permission: 'roles.manage',
            number: '06',
        },
    ].filter((shortcut) => permissions.value.includes(shortcut.permission)),
);
</script>

<template>
    <Head title="Inicio" />
    <AppLayout>
        <div class="space-y-8">
            <section
                class="relative overflow-hidden rounded-card border border-border bg-panel p-6 shadow-card sm:p-9"
            >
                <div
                    class="absolute inset-y-0 right-0 hidden w-1/3 bg-primary-soft/70 lg:block"
                    aria-hidden="true"
                />
                <div class="relative max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-secondary">
                        Portal académico institucional
                    </p>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-primary sm:text-4xl">
                        Bienvenido{{ page.props.auth?.user.name ? `, ${page.props.auth.user.name}` : '' }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-text-muted">
                        Un espacio central para aprender, acompañar trayectorias y gestionar la vida
                        académica. Accede a las herramientas disponibles para tu cuenta.
                    </p>
                </div>
                <div class="relative mt-7 flex items-center gap-3 border-t border-border pt-5">
                    <span
                        class="flex size-9 items-center justify-center rounded-full bg-secondary-soft text-secondary"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            class="size-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <path
                                d="M12 3.5 4 7.5l8 4 8-4-8-4Zm-8 8 8 4 8-4M4 15.5l8 4 8-4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-text">Aprendizaje y gestión en un solo lugar</p>
                </div>
            </section>

            <section v-if="shortcuts.length" aria-labelledby="accesos-heading">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-text-muted">
                            Acceso directo
                        </p>
                        <h2 id="accesos-heading" class="mt-1 text-xl font-semibold tracking-tight text-text">
                            Herramientas de tu espacio
                        </h2>
                    </div>
                    <p class="text-sm text-text-muted">Disponibles según tus permisos institucionales</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="shortcut in shortcuts"
                        :key="shortcut.href"
                        :href="shortcut.href"
                        class="group flex min-h-40 flex-col rounded-card border border-border bg-panel p-5 shadow-card transition duration-200 motion-reduce:transition-none hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-overlay focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold tabular-nums tracking-[0.12em] text-text-muted"
                            >
                                {{ shortcut.number }}
                            </span>
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 20 20"
                                class="size-5 text-text-muted transition group-hover:translate-x-0.5 group-hover:text-primary"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >
                                <path
                                    d="M4 10h11m-4-4 4 4-4 4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-base font-semibold text-text group-hover:text-primary">
                            {{ shortcut.label }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-text-muted">{{ shortcut.description }}</p>
                    </Link>
                </div>
            </section>

            <section v-else class="rounded-card border border-border bg-panel p-6 shadow-card">
                <h2 class="text-base font-semibold text-text">Tu espacio académico</h2>
                <p class="mt-2 text-sm leading-6 text-text-muted">
                    Por el momento no hay herramientas adicionales asignadas a tu cuenta.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
