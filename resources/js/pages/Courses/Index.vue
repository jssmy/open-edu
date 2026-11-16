<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CourseCard from '@/components/courses/CourseCard.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import IconButton from '@/design-system/IconButton.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import Pagination from '@/design-system/Pagination.vue';
import Select from '@/design-system/Select.vue';
import { useListFilters } from '@/services/listFilters';

interface CategoryOption {
    id: number;
    name: string;
    slug: string;
    role_name?: string;
}

interface RoleOption {
    value: string;
    label: string;
}

interface CourseRow {
    id: number;
    name: string;
    excerpt: string;
    cover_url: string;
    units_count: number;
    status: 'draft' | 'published' | 'deactivated';
    category_id?: number | null;
    category_name?: string | null;
    category_role_name?: string | null;
    issues_certificate?: boolean;
    is_enrolled?: boolean;
    can_self_enroll?: boolean;
    study_url?: string | null;
    progress?: {
        completed_lessons: number;
        total_lessons: number;
        percentage: number;
    } | null;
    teachers: { id: number; name: string }[];
}

const props = withDefaults(
    defineProps<{
        courses: {
            data: CourseRow[];
            meta: { page: number; last_page: number; total: number; from: number; to: number };
        };
        categories?: CategoryOption[];
        roles?: RoleOption[];
        filters: { q: string; status: string; category_id?: string };
        canManageCourses: boolean;
        isRoleCatalogView?: boolean;
    }>(),
    {
        categories: () => [],
        roles: () => [
            { value: 'estudiante', label: 'Estudiante' },
            { value: 'docente', label: 'Docente' },
        ],
        isRoleCatalogView: false,
    },
);

const { filters, goToPage, reset } = useListFilters('/courses', {
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    category_id: props.filters.category_id ?? '',
});
const hasFilters = computed(
    () => filters.q !== '' || filters.status !== '' || filters.category_id !== '',
);

const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'draft', label: 'Borrador' },
    { value: 'published', label: 'Publicado' },
    { value: 'deactivated', label: 'Desactivado' },
];

const categoryOptions = computed(() => [
    { value: '', label: 'Todas las categorías' },
    ...props.categories.map((cat) => ({ value: String(cat.id), label: cat.name })),
]);

const roleOptions = computed(() =>
    props.roles && props.roles.length > 0
        ? props.roles
        : [
              { value: 'estudiante', label: 'Estudiante' },
              { value: 'docente', label: 'Docente' },
          ],
);

const isCategoryModalOpen = ref(false);
const categoryName = ref('');
const categoryRoleName = ref('estudiante');
const categoryForm = useForm({
    name: '',
    role_name: 'estudiante',
});
const categoryNameError = computed(() => categoryForm.errors.name);
const categoryRoleError = computed(() => categoryForm.errors.role_name);
const isCreatingCategory = computed(() => categoryForm.processing);
const enrollingCourseId = ref<number | null>(null);

function openCategoryModal(): void {
    categoryName.value = '';
    categoryRoleName.value = 'estudiante';
    categoryForm.reset();
    categoryForm.clearErrors();
    isCategoryModalOpen.value = true;
}

function closeCategoryModal(): void {
    isCategoryModalOpen.value = false;
    categoryName.value = '';
    categoryRoleName.value = 'estudiante';
    categoryForm.reset();
    categoryForm.clearErrors();
}

function submitCategory(): void {
    categoryForm.name = categoryName.value;
    categoryForm.role_name = categoryRoleName.value || 'estudiante';
    categoryForm.post('/course-categories', {
        preserveScroll: true,
        onSuccess: () => {
            closeCategoryModal();
        },
    });
}

function selfEnroll(courseId: number): void {
    if (enrollingCourseId.value !== null) {
        return;
    }

    enrollingCourseId.value = courseId;
    router.post(
        `/courses/${courseId}/enroll`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                enrollingCourseId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Cursos" />
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ isRoleCatalogView ? 'Cursos disponibles' : 'Cursos' }}
                </h1>
                <p v-if="isRoleCatalogView" class="mt-0.5 text-sm text-text-muted">
                    Explora los cursos disponibles para tu rol, inscríbete en un clic y avanza a tu propio ritmo.
                </p>
            </div>
            <div v-if="canManageCourses" class="flex flex-wrap items-center gap-2">
                <Button
                    variant="secondary"
                    data-testid="open-create-category-modal"
                    @click="openCategoryModal"
                >
                    Nueva categoría
                </Button>
                <Link
                    href="/courses/create"
                    class="inline-flex min-h-11 items-center justify-center rounded-control bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    Nuevo curso
                </Link>
            </div>
        </div>

        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-56 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Nombre del curso o docente" />
            </div>
            <div v-if="categories.length > 0" class="w-48">
                <label for="filter-category" class="mb-1 block text-xs text-text-muted">Categoría</label>
                <Select id="filter-category" v-model="filters.category_id" :options="categoryOptions" />
            </div>
            <div v-if="canManageCourses" class="w-48">
                <label for="filter-status" class="mb-1 block text-xs text-text-muted">Estado</label>
                <Select id="filter-status" v-model="filters.status" :options="statusOptions" />
            </div>
            <IconButton icon="search" label="Buscar" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <ul v-if="courses.data.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="course in courses.data" :key="course.id" class="flex flex-col">
                <CourseCard :course="course">
                    <template v-if="isRoleCatalogView" #badges>
                        <Badge
                            :variant="
                                course.is_enrolled
                                    ? (course.progress?.percentage ?? 0) >= 100
                                        ? 'success'
                                        : 'warning'
                                    : 'neutral'
                            "
                        >
                            {{
                                course.is_enrolled
                                    ? (course.progress?.percentage ?? 0) >= 100
                                        ? 'Completado · 100%'
                                        : `Inscrito · ${course.progress?.percentage ?? 0}%`
                                    : 'Disponible'
                            }}
                        </Badge>
                    </template>
                    <template v-if="isRoleCatalogView" #footer>
                        <span
                            :class="
                                course.is_enrolled
                                    ? 'text-xs font-semibold text-primary'
                                    : 'text-xs text-text-muted'
                            "
                        >
                            {{
                                course.is_enrolled
                                    ? `${course.progress?.percentage ?? 0}% de avance`
                                    : 'Inscripción libre'
                            }}
                        </span>
                        <Link
                            v-if="course.is_enrolled && course.study_url"
                            :href="course.study_url"
                            :data-testid="`continue-course-${course.id}`"
                            class="inline-flex items-center justify-center rounded-control bg-primary px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-hover"
                        >
                            {{ (course.progress?.percentage ?? 0) > 0 ? 'Continuar curso' : 'Iniciar curso' }}
                        </Link>
                        <Button
                            v-else
                            size="sm"
                            variant="primary"
                            :loading="enrollingCourseId === course.id"
                            :disabled="enrollingCourseId !== null"
                            :data-testid="`self-enroll-course-${course.id}`"
                            @click="selfEnroll(course.id)"
                        >
                            Inscribirme
                        </Button>
                    </template>
                </CourseCard>
            </li>
        </ul>
        <div v-else class="rounded-card border border-border bg-panel">
            <EmptyState
                :title="
                    hasFilters
                        ? 'Sin resultados'
                        : isRoleCatalogView
                          ? 'Aún no hay cursos publicados en tus categorías'
                          : 'Aún no hay cursos'
                "
                :description="
                    hasFilters
                        ? 'Prueba con otros filtros.'
                        : isRoleCatalogView
                          ? 'Cuando se publiquen cursos en las categorías asignadas a tu rol, aparecerán aquí para que puedas inscribirte.'
                          : 'Los cursos disponibles aparecerán aquí.'
                "
            />
        </div>
        <Pagination
            class="mt-4"
            :page="courses.meta.page"
            :last-page="courses.meta.last_page"
            :total="courses.meta.total"
            :from="courses.meta.from"
            :to="courses.meta.to"
            @change="goToPage"
        />

        <!-- Modal para crear nueva categoría con rol por defecto Estudiante -->
        <Modal
            :open="isCategoryModalOpen"
            title="Crear nueva categoría de curso"
            size="md"
            @close="closeCategoryModal"
        >
            <form id="create-category-form" class="space-y-4" @submit.prevent="submitCategory">
                <FormField
                    id="category-name"
                    label="Nombre de la categoría"
                    :error="categoryNameError"
                >
                    <Input
                        id="category-name"
                        v-model="categoryName"
                        placeholder="Ej. Innovación Pedagógica"
                        :error="categoryNameError"
                    />
                </FormField>

                <FormField
                    id="category-role"
                    label="Rol disponible para esta categoría"
                    :error="categoryRoleError"
                >
                    <Select
                        id="category-role"
                        v-model="categoryRoleName"
                        :options="roleOptions"
                    />
                </FormField>
            </form>
            <template #actions>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" @click="closeCategoryModal">Cancelar</Button>
                    <Button
                        type="submit"
                        form="create-category-form"
                        :loading="isCreatingCategory"
                    >
                        Guardar categoría
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
