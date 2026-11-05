import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import AlertDialogHost from '@/components/AlertDialogHost.vue';
import {
    alertDialogState,
    notifyError,
    notifySuccess,
    requestAlertDialog,
    respondToAlertDialog,
} from '@/composables/useAlertDialog';

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

    it('permite mostrar alertas puras con notifySuccess sin botón cancelar', async () => {
        const wrapper = mount(AlertDialogHost);
        const response = notifySuccess('Los datos se guardaron correctamente.');

        await nextTick();
        const dialog = wrapper.get('[role="alertdialog"]');
        expect(dialog.text()).toContain('Operación exitosa');
        expect(dialog.text()).toContain('Los datos se guardaron correctamente.');

        // Debe haber un solo botón ("Entendido") y NO debe existir botón "Cancelar"
        const buttons = dialog.findAll('button');
        expect(buttons.length).toBe(1);
        expect(buttons[0].text()).toBe('Entendido');

        await buttons[0].trigger('click');
        await expect(response).resolves.toBe(true);
        await nextTick();
        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(false);

        wrapper.unmount();
    });

    it('permite mostrar alertas puras con notifyError sin botón cancelar', async () => {
        const wrapper = mount(AlertDialogHost);
        const response = notifyError('No se pudo procesar la solicitud.');

        await nextTick();
        const dialog = wrapper.get('[role="alertdialog"]');
        expect(dialog.text()).toContain('No se pudo completar la operación');
        expect(dialog.text()).toContain('No se pudo procesar la solicitud.');

        const buttons = dialog.findAll('button');
        expect(buttons.length).toBe(1);
        expect(buttons[0].text()).toBe('Entendido');

        await buttons[0].trigger('click');
        await expect(response).resolves.toBe(true);
        await nextTick();
        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(false);

        wrapper.unmount();
    });
});
