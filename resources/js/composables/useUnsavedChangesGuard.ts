import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, toValue, type MaybeRefOrGetter } from 'vue';
import { requestAlertDialog } from '@/composables/useAlertDialog';

export function useUnsavedChangesGuard(isDirty: MaybeRefOrGetter<boolean>): void {
    function guardBrowserExit(event: BeforeUnloadEvent): void {
        if (!toValue(isDirty)) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    }

    let removeInertiaGuard: (() => void) | undefined;
    let allowNextVisit = false;

    onMounted(() => {
        removeInertiaGuard = router.on('before', (event) => {
            if (allowNextVisit) {
                allowNextVisit = false;

                return;
            }

            if (!toValue(isDirty)) {
                return;
            }

            event.preventDefault();
            const visit = event.detail.visit;

            void requestAlertDialog({
                variant: 'warning',
                title: '¿Salir sin guardar?',
                message: 'Hay cambios sin guardar. Si sales ahora, perderás los cambios realizados.',
                confirmLabel: 'Salir sin guardar',
            }).then((confirmed) => {
                if (!confirmed) {
                    return;
                }

                allowNextVisit = true;
                router.visit(visit.url, visit);
            });

            return false;
        });

        window.addEventListener('beforeunload', guardBrowserExit);
    });

    onBeforeUnmount(() => {
        removeInertiaGuard?.();
        window.removeEventListener('beforeunload', guardBrowserExit);
    });
}
