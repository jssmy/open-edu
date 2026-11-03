import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, toValue, type MaybeRefOrGetter } from 'vue';

const confirmationMessage =
    'Hay cambios sin guardar. ¿Deseas quedarte en esta página? Selecciona Aceptar para descartar y salir.';

export function useUnsavedChangesGuard(isDirty: MaybeRefOrGetter<boolean>): void {
    function guardBrowserExit(event: BeforeUnloadEvent): void {
        if (!toValue(isDirty)) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    }

    let removeInertiaGuard: (() => void) | undefined;

    onMounted(() => {
        removeInertiaGuard = router.on('before', () => {
            if (!toValue(isDirty)) {
                return;
            }

            return globalThis.confirm(confirmationMessage);
        });

        window.addEventListener('beforeunload', guardBrowserExit);
    });

    onBeforeUnmount(() => {
        removeInertiaGuard?.();
        window.removeEventListener('beforeunload', guardBrowserExit);
    });
}
