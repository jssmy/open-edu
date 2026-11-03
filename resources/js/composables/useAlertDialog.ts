import { nextTick, reactive } from 'vue';

export type AlertDialogVariant = 'info' | 'success' | 'warning' | 'danger';

export interface AlertDialogOptions {
    variant: AlertDialogVariant;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
}

interface QueuedAlertDialog extends AlertDialogOptions {
    resolve: (confirmed: boolean) => void;
}

export const alertDialogState = reactive<{
    open: boolean;
    variant: AlertDialogVariant;
    title: string;
    message: string;
    confirmLabel: string;
    cancelLabel: string;
}>({
    open: false,
    variant: 'info',
    title: '',
    message: '',
    confirmLabel: 'Continuar',
    cancelLabel: 'Cancelar',
});

const requests: QueuedAlertDialog[] = [];
let currentRequest: QueuedAlertDialog | undefined;

function showNextAlertDialog(): void {
    if (currentRequest || requests.length === 0) {
        return;
    }

    const request = requests.shift();
    if (!request) {
        return;
    }

    currentRequest = request;
    Object.assign(alertDialogState, {
        open: true,
        variant: request.variant,
        title: request.title,
        message: request.message,
        confirmLabel: request.confirmLabel ?? 'Continuar',
        cancelLabel: request.cancelLabel ?? 'Cancelar',
    });
}

export function requestAlertDialog(options: AlertDialogOptions): Promise<boolean> {
    return new Promise((resolve) => {
        requests.push({ ...options, resolve });
        showNextAlertDialog();
    });
}

export function respondToAlertDialog(confirmed: boolean): void {
    const request = currentRequest;
    if (!request) {
        return;
    }

    currentRequest = undefined;
    alertDialogState.open = false;
    request.resolve(confirmed);
    void nextTick(showNextAlertDialog);
}
