import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

/**
 * Tracks whether the drafts differ from the state they were built from and
 * holds Inertia navigation until the person confirms losing them.
 */
export function usePendingChanges(drafts) {
    const { t } = useI18n()
    const snapshot = ref(JSON.stringify(drafts.value))

    const dirty = computed(() => JSON.stringify(drafts.value) !== snapshot.value)

    function markClean() {
        snapshot.value = JSON.stringify(drafts.value)
    }

    let stopGuard = null
    onMounted(() => {
        stopGuard = router.on('before', (event) => {
            if (dirty.value && event.detail.visit.method === 'get' && !window.confirm(t('You have unsaved changes'))) {
                event.preventDefault()
            }
        })
    })
    onBeforeUnmount(() => stopGuard?.())

    return { dirty, markClean }
}
