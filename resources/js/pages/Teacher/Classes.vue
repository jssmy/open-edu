<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import RichTextContent from '@/components/editor/RichTextContent.vue';
import type { RichTextDocument } from '@/types/richText';

interface TeacherClass {
    id: number;
    group_code: string;
    modality: string;
    starts_on: string | null;
    ends_on: string | null;
    course: {
        id: number;
        title: string;
        description: RichTextDocument | string | null;
        cover_image_url: string | null;
        units_count: number;
        lessons_count: number;
    };
}

defineProps<{
    classes: TeacherClass[];
}>();

function isRichText(content: unknown): content is RichTextDocument {
    return content !== null && typeof content === 'object' && (content as { type?: string }).type === 'doc';
}
</script>

<template>
    <Head title="Mis Clases" />
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Mis Clases Asignadas</h1>
                <p class="text-sm text-text-muted mt-1">
                    Gestiona y proyecta las diapositivas de los cursos que tienes a tu cargo.
                </p>
            </div>
        </div>

        <EmptyState
            v-if="classes.length === 0"
            title="No tienes clases programadas"
            description="Actualmente no cuentas con clases ni horarios asignados para dictar."
        />

        <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <Card
                v-for="item in classes"
                :key="item.id"
                class="flex flex-col justify-between overflow-hidden border border-border shadow-sm hover:shadow transition-shadow"
            >
                <div class="flex flex-col gap-3">
                    <!-- Portada o placeholder -->
                    <div class="relative h-44 w-full overflow-hidden rounded-t-card bg-surface-muted">
                        <img
                            v-if="item.course.cover_image_url"
                            :src="item.course.cover_image_url"
                            :alt="item.course.title"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center bg-primary/10 text-primary">
                            <span class="text-4xl">📚</span>
                        </div>
                        <div class="absolute top-3 left-3 flex gap-2">
                            <Badge variant="neutral">{{ item.group_code }}</Badge>
                            <Badge variant="success">{{ item.modality }}</Badge>
                        </div>
                    </div>

                    <!-- Datos del curso -->
                    <div class="p-2 flex flex-col gap-2">
                        <h2 class="text-lg font-bold text-text line-clamp-1">{{ item.course.title }}</h2>
                        <div class="text-xs text-text-muted line-clamp-2">
                            <RichTextContent
                                v-if="isRichText(item.course.description)"
                                :content="item.course.description"
                                :label="item.course.title"
                            />
                            <p v-else-if="typeof item.course.description === 'string'">
                                {{ item.course.description }}
                            </p>
                            <p v-else>Sin descripción registrada.</p>
                        </div>

                        <div class="flex items-center gap-4 text-xs font-medium text-text-muted mt-2 border-t border-border pt-2">
                            <span>📖 {{ item.course.units_count }} módulos</span>
                            <span>🎯 {{ item.course.lessons_count }} diapositivas</span>
                        </div>
                    </div>
                </div>

                <!-- Botón de acción / Ingreso al visor -->
                <div class="p-2 pt-0 mt-3 border-t border-border/50">
                    <Link
                        :href="`/my/classes/${item.id}/viewer`"
                        class="mt-2 flex w-full items-center justify-center rounded-control bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        🖥️ Entrar a Clase (Ver Diapositivas)
                    </Link>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

