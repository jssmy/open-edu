import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import AlertDialogHost from '@/components/AlertDialogHost.vue';
import { alertDialogState, requestAlertDialog, respondToAlertDialog } from '@/composables/useAlertDialog';

describe('AlertDialogHost', () => {
    afterEach(() => {
        if (alertDialogState.open) {
            respondToAlertDialog(false);
        }
    });

    it('shows queued alerts with their variants and resolves each response', async () => {
        const wrapper = mount(AlertDialogHost);
        const firstResponse = requestAlertDialog({
            variant: 'warning',
            title: '¿Salir sin guardar?',
            message: 'Perderás los cambios realizados.',
            confirmLabel: 'Salir',
        });
        const secondResponse = requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar el curso?',
            message: 'Esta acción no se puede deshacer.',
            confirmLabel: 'Eliminar',
        });

        await nextTick();
        expect(wrapper.get('[role="alertdialog"]').text()).toContain('¿Salir sin guardar?');
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
        expect(wrapper.get('button.bg-primary').text()).toBe('Salir');

        await wrapper.get('button.bg-primary').trigger('click');
        await expect(firstResponse).resolves.toBe(true);
        await nextTick();

        expect(wrapper.get('[role="alertdialog"]').text()).toContain('¿Eliminar el curso?');
        expect(wrapper.find('svg').exists()).toBe(true);
        await wrapper.get('button.bg-danger').trigger('click');
        await expect(secondResponse).resolves.toBe(true);
        await nextTick();
        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(false);

        wrapper.unmount();
    });
});
