import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref, type Ref } from 'vue';
import { mount } from '@vue/test-utils';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';

const { router } = vi.hoisted(() => ({
    router: { on: vi.fn(), visit: vi.fn() },
}));

const { requestAlertDialog } = vi.hoisted(() => ({
    requestAlertDialog: vi.fn(),
}));

vi.mock('@/composables/useAlertDialog', () => ({ requestAlertDialog }));
vi.mock('@inertiajs/vue3', () => ({ router }));

let visitGuard: ((event: never) => boolean | void) | undefined;
const unsubscribe = vi.fn();

function mountGuard(isDirty: Ref<boolean>) {
    return mount(
        defineComponent({
            setup() {
                useUnsavedChangesGuard(() => isDirty.value);

                return () => h('div');
            },
        }),
    );
}

describe('useUnsavedChangesGuard', () => {
    beforeEach(() => {
        visitGuard = undefined;
        unsubscribe.mockClear();
        router.visit.mockClear();
        requestAlertDialog.mockReset();
        router.on.mockImplementation((_event, callback) => {
            visitGuard = callback;

            return unsubscribe;
        });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('does not interrupt navigation when the form is clean', () => {
        const wrapper = mountGuard(ref(false));

        expect(visitGuard?.({} as never)).toBeUndefined();
        expect(requestAlertDialog).not.toHaveBeenCalled();

        wrapper.unmount();
    });

    it('shows a custom warning and resumes the Inertia visit only after confirmation', async () => {
        const isDirty = ref(true);
        const wrapper = mountGuard(isDirty);
        const url = new URL('/courses', window.location.origin);
        const visit = { url };
        const event = {
            preventDefault: vi.fn(),
            detail: { visit },
        };

        requestAlertDialog.mockResolvedValueOnce(false);
        expect(visitGuard?.(event as never)).toBe(false);
        expect(event.preventDefault).toHaveBeenCalledOnce();
        expect(requestAlertDialog).toHaveBeenCalledWith({
            variant: 'warning',
            title: '¿Salir sin guardar?',
            message: 'Hay cambios sin guardar. Si sales ahora, perderás los cambios realizados.',
            confirmLabel: 'Salir sin guardar',
        });
        await Promise.resolve();
        expect(router.visit).not.toHaveBeenCalled();

        requestAlertDialog.mockResolvedValueOnce(true);
        expect(visitGuard?.(event as never)).toBe(false);
        await Promise.resolve();
        expect(router.visit).toHaveBeenCalledWith(url, visit);

        wrapper.unmount();
    });

    it('blocks browser exit while the form is dirty and removes guards on unmount', () => {
        const isDirty = ref(true);
        const wrapper = mountGuard(isDirty);
        const event = new Event('beforeunload', { cancelable: true });

        window.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);

        wrapper.unmount();
        expect(unsubscribe).toHaveBeenCalledOnce();

        const cleanEvent = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(cleanEvent);
        expect(cleanEvent.defaultPrevented).toBe(false);
    });
});
