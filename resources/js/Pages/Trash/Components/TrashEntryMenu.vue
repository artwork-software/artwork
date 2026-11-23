<template>
    <!-- Menü eines Papierkorb-Eintrags: wiederherstellen oder nach Rückfrage endgültig löschen -->
    <BaseMenu has-no-offset white-menu-background>
        <BaseMenuItem
            :icon="IconRefresh"
            title="Restore"
            white-menu-background
            :disabled="restoreDisabled"
            :class="restoreDisabled ? 'opacity-50' : ''"
            @click="restore"
        />
        <slot />
        <BaseMenuItem :icon="IconTrash" title="Delete permanently" white-menu-background @click="showConfirm = true" />
    </BaseMenu>
    <ConfirmDeleteModal
        v-if="showConfirm"
        :title="$t('Delete permanently')"
        :description="name
            ? $t('Are you sure you want to delete {name} permanently? This cannot be undone.', { name })
            : $t('Are you sure you want to delete this entry permanently? This cannot be undone.')"
        @closed="showConfirm = false"
        @delete="forceDelete"
    />
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { IconRefresh, IconTrash } from '@tabler/icons-vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'

defineOptions({ name: 'TrashEntryMenu' })

const props = defineProps({
    restoreHref: { type: String, required: true },
    forceHref: { type: String, required: true },
    // Name des Eintrags für die Rückfrage
    name: { type: String, default: '' },
    restoreDisabled: { type: Boolean, default: false },
    // Rückruf statt Event: die Zeile ist nach dem Wiederherstellen schon entfernt, ein Event käme nicht mehr an
    afterRestore: { type: Function, default: null },
})

const showConfirm = ref(false)

const restore = () => {
    if (props.restoreDisabled) return
    router.patch(props.restoreHref, {}, {
        preserveScroll: true,
        // meldet der Server einen Fehler (z. B. Raum der Schicht fehlt), gilt der Eintrag nicht als zurückgeholt
        onSuccess: (page) => {
            if (!page.props.flash?.error) {
                props.afterRestore?.({ serverMessage: typeof page.props.flash?.success === 'string' })
            }
        },
    })
}

const forceDelete = () => {
    router.delete(props.forceHref, {
        preserveScroll: true,
        onFinish: () => {
            showConfirm.value = false
        },
    })
}
</script>
