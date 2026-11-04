import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/** Estandariza filtros + paginación de listados: la consulta vive en la URL (GET) y el servidor pagina. */
export function useListFilters<T extends Record<string, string>>(url: string, initial: T) {
    const filters = reactive({ ...initial }) as T;
    let timer: ReturnType<typeof setTimeout> | undefined;

    function visit(page = 1) {
        const query: Record<string, string | number> = {};
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== '') {
                query[key] = value;
            }
        });
        if (page > 1) {
            query.page = page;
        }
        router.get(url, query, { preserveState: true, preserveScroll: true, replace: true });
    }

    watch(filters, () => {
        clearTimeout(timer);
        timer = setTimeout(() => visit(1), 300);
    });

    function reset() {
        Object.keys(initial).forEach((key) => ((filters as Record<string, string>)[key] = ''));
    }

    return { filters, goToPage: visit, reset };
}
