import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref, type Ref } from 'vue';
import { mount } from '@vue/test-utils';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';

const { router } = vi.hoisted(() => ({
    router: { on: vi.fn() },
}));

vi.mock('@inertiajs/vue3', () => ({ router }));

let visitGuard: ((event: unknown) => boolean | void) | undefined;
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
        router.on.mockImplementation((_event, callback) => {
            visitGuard = callback;

            return unsubscribe;
        });
        vi.stubGlobal('confirm', vi.fn());
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('does not interrupt navigation when the form is clean', () => {
        const wrapper = mountGuard(ref(false));

        expect(visitGuard?.({})).toBeUndefined();
        expect(globalThis.confirm).not.toHaveBeenCalled();

        wrapper.unmount();
    });

    it('lets the user stay or confirm discarding before an Inertia visit', () => {
        const isDirty = ref(true);
        const wrapper = mountGuard(isDirty);
        const confirm = vi.mocked(globalThis.confirm);

        confirm.mockReturnValue(false);
        expect(visitGuard?.({})).toBe(false);

        confirm.mockReturnValue(true);
        expect(visitGuard?.({})).toBe(true);
        expect(confirm).toHaveBeenCalledWith(
            'Hay cambios sin guardar. ¿Deseas quedarte en esta página? Selecciona Aceptar para descartar y salir.',
        );

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
