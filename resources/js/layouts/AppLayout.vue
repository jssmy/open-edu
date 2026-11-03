<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';
import Alert from '@/design-system/Alert.vue';

const page = usePage<SharedProps>();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const links = computed(() =>
    [
        { href: '/users', label: 'Usuarios', permission: 'users.manage' },
        { href: '/roles', label: 'Roles', permission: 'roles.manage' },
        { href: '/courses', label: 'Cursos', permission: 'courses.view' },
        { href: '/students', label: 'Estudiantes', permission: 'students.view' },
        { href: '/reports/enrollments', label: 'Reportes', permission: 'reports.view' },
        { href: '/my/courses', label: 'Mis cursos', permission: 'study' },
    ].filter((l) => permissions.value.includes(l.permission)),
);
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-border bg-panel">
            <nav
                class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-gutter py-3 sm:gap-6"
                aria-label="Principal"
            >
                <span class="font-semibold text-primary">Open Edu</span>
                <Link v-for="l in links" :key="l.href" :href="l.href" class="text-sm hover:text-primary">{{
                    l.label
                }}</Link>
                <button type="button" class="ml-auto text-sm text-text-muted" @click="router.post('/logout')">
                    Cerrar sesión
                </button>
            </nav>
        </header>
        <main class="mx-auto max-w-6xl px-gutter py-8">
            <Alert v-if="page.props.flash?.success" variant="success" class="mb-4">{{
                page.props.flash.success
            }}</Alert>
            <slot />
        </main>
    </div>
</template>
