import { nextTick, reactive } from 'vue';

export type AlertDialogVariant = 'info' | 'success' | 'warning' | 'danger';

export interface AlertDialogOptions {
    variant: AlertDialogVariant;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string | null;
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
    cancelLabel: string | null;
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
        confirmLabel: request.confirmLabel ?? (request.cancelLabel === null ? 'Entendido' : 'Continuar'),
        cancelLabel: request.cancelLabel !== undefined ? request.cancelLabel : 'Cancelar',
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

/**
 * Notificación modal de éxito con solo botón de confirmación ("Entendido").
 */
export function notifySuccess(message: string, title = 'Operación exitosa'): Promise<boolean> {
    return requestAlertDialog({
        variant: 'success',
        title,
        message,
        confirmLabel: 'Entendido',
        cancelLabel: null,
    });
}

/**
 * Notificación modal de error con solo botón de confirmación ("Entendido").
 */
export function notifyError(message: string, title = 'No se pudo completar la operación'): Promise<boolean> {
    return requestAlertDialog({
        variant: 'danger',
        title,
        message,
        confirmLabel: 'Entendido',
        cancelLabel: null,
    });
}

/**
 * Notificación modal de advertencia con solo botón de confirmación ("Entendido").
 */
export function notifyWarning(message: string, title = 'Atención'): Promise<boolean> {
    return requestAlertDialog({
        variant: 'warning',
        title,
        message,
        confirmLabel: 'Entendido',
        cancelLabel: null,
    });
}

/**
 * Notificación modal informativa con solo botón de confirmación ("Entendido").
 */
export function notifyInfo(message: string, title = 'Información'): Promise<boolean> {
    return requestAlertDialog({
        variant: 'info',
        title,
        message,
        confirmLabel: 'Entendido',
        cancelLabel: null,
    });
}
