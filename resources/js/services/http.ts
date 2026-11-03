import { router } from '@inertiajs/vue3';

type Method = 'post' | 'put' | 'patch' | 'delete';

/** Único punto de envío de formularios/acciones hacia el backend (Inertia, sin API separada). */
export function submit(
    method: Method,
    url: string,
    data: Record<string, unknown> = {},
    onFinish?: () => void,
) {
    router[method](url, data as never, { preserveScroll: true, onFinish });
}

export function can(permissions: string[] | undefined, permission: string): boolean {
    return !!permissions?.includes(permission);
}
