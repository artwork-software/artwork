import { computed, onScopeDispose, ref, watch } from 'vue'

/* Dialogs mounted outside a modal's component tree (e.g. once in AppLayout) can still open above it.
   Headless UI does not see them as nested, so a click on them would close the modal underneath. */

const openOverlays = ref(0)

/** True while such a dialog is open; modals ignore their outside-click close then. */
export const overlayAbove = computed(() => openOverlays.value > 0)

/** Counts the calling dialog as open while isOpen is true. */
export function useOverlay(isOpen) {
    watch(isOpen, (open, wasOpen = false) => {
        if (open !== wasOpen) openOverlays.value += open ? 1 : -1
    }, { immediate: true })

    onScopeDispose(() => {
        if (isOpen.value) openOverlays.value -= 1
    })
}
