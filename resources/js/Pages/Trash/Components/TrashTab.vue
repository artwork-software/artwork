<template>
    <!-- Gemeinsames Gerüst aller Papierkorb-Tabs: Kopfzeile, einheitliche Zeilen mit Löschinformationen,
         Mehrfachauswahl, Blättern und die Rückfragen. Der Inhalt einer Zeile kommt aus dem Standard-Slot. -->
    <!-- Während eine Sammelaktion läuft, ist die Liste gesperrt: sonst liefen Einzelaktionen dazwischen -->
    <div class="w-full" :class="processing ? 'pointer-events-none opacity-60' : ''" :inert="processing" :aria-busy="processing">
    <TrashSearchAndActions
        :property-name="propertyName"
        :total="total"
        :searchable="searchable"
        @delete-all="showConfirmDeleteAll = true"
    />

    <div v-if="items.length > 0" class="mb-1 flex items-center gap-3 px-4 text-xs text-text-subtle">
        <SelectionCheckbox
            :checked="pageSelectionState === 'all'"
            :indeterminate="pageSelectionState === 'some'"
            :label="$t('Select all entries on this page')"
            @toggle="selectedKeys = toggleAllKeys(selectedKeys, pageKeys)"
        />
        <span>{{ $t('Select all entries on this page') }}</span>
    </div>

    <div
        v-for="item in items"
        :key="keyOf(item)"
        class="my-2 flex w-full items-center gap-3 rounded-lg border bg-white px-4 py-3"
        :class="selectedKeys.includes(keyOf(item)) ? 'border-accent-600/50 bg-accent-50/40' : 'border-border-subtle'"
    >
        <SelectionCheckbox
            :checked="selectedKeys.includes(keyOf(item))"
            :label="$t('Select {name}', { name: nameOf(item) })"
            @toggle="selectedKeys = toggleKey(selectedKeys, keyOf(item))"
        />
        <slot name="leading" :item="item" />
        <div class="min-w-0 flex-1">
            <slot :item="item" />
            <TrashDeletionInfo :info="item.trash" />
        </div>
        <slot name="trailing" :item="item" />
        <div class="flex shrink-0 items-center">
            <TrashEntryMenu
                :restore-href="restoreHref(item)"
                :force-href="forceHref(item)"
                :name="nameOf(item)"
                :restore-disabled="restoreDisabled(item)"
:after-restore="(result) => onRestored(item, result)"
            >
                <slot name="menu" :item="item" />
            </TrashEntryMenu>
        </div>
    </div>

    <BasePaginator
        v-if="paginator && total > 0"
        :entities="paginator"
        :property-name="propertyName"
        class="mt-6"
    />

    <!-- Leiste der Mehrfachauswahl -->
    <div v-if="selectedKeys.length > 0" class="pointer-events-none fixed inset-x-0 bottom-6 z-40 flex justify-center px-4">
        <div class="pointer-events-auto flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-surface-inverse py-2.5 pl-4 pr-2 text-sm text-white shadow-lg">
            <span class="text-white/70 tabular-nums" aria-live="polite">
                {{ processing
                    ? $t('{done} of {count} processed', { done: progress, count: progressTotal })
                    : $t('{count} selected', { count: selectedKeys.length }) }}
            </span>
            <button type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1 font-semibold hover:bg-white/25 disabled:opacity-50"
                    :disabled="processing || restorableSelection.length === 0"
                    :title="restorableSelection.length === 0 ? $t('None of the selected entries can be restored.') : ''"
                    @click="runBulk('restore')">
                <IconRefresh class="size-4" />
                {{ $t('Restore') }}
            </button>
            <button type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-danger px-3 py-1 font-semibold hover:opacity-90 disabled:opacity-50"
                    :disabled="processing"
                    @click="showConfirmBulkDelete = true">
                <IconTrash class="size-4" />
                {{ $t('Delete permanently') }}
            </button>
            <button type="button" class="rounded-lg p-1.5 hover:bg-white/15 disabled:opacity-50" :disabled="processing"
                    :title="$t('Clear selection')" :aria-label="$t('Clear selection')" @click="selectedKeys = []">
                <IconX class="size-4" />
            </button>
        </div>
    </div>

    <ConfirmDeleteModal
        v-if="showConfirmBulkDelete"
        :title="$t('Delete permanently')"
        :description="selectedKeys.length === 1
            ? $t('Are you sure you want to delete this entry permanently? This cannot be undone.')
            : $t('Are you sure you want to delete {count} entries permanently? This cannot be undone.', { count: selectedKeys.length })"
        @closed="showConfirmBulkDelete = false"
        @delete="confirmBulkDelete"
    />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconRefresh, IconTrash, IconX } from '@tabler/icons-vue'
import BasePaginator from '@/Components/Paginate/BasePaginator.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import SelectionCheckbox from '@/Pages/Areas/Components/SelectionCheckbox.vue'
import TrashDeletionInfo from '@/Pages/Trash/Components/TrashDeletionInfo.vue'
import TrashEntryMenu from '@/Pages/Trash/Components/TrashEntryMenu.vue'
import TrashSearchAndActions from '@/Pages/Trash/Components/TrashSearchAndActions.vue'
import { showAppToast } from '@/Helper/appToast'
import {
    bulkOutcome,
    pruneKeys,
    selectionState,
    toggleAllKeys,
    toggleKey,
    xsrfTokenFromCookie,
} from '@/Pages/Trash/Composables/trashTools.js'

defineOptions({ name: 'TrashTab' })

const props = defineProps({
    // Paginator des Tabs; Tabs ohne Blättern übergeben stattdessen "entries"
    paginator: { type: Object, default: null },
    entries: { type: Array, default: null },
    // Name der Inertia-Prop für Suche und Blättern
    propertyName: { type: String, default: '' },
    searchable: { type: Boolean, default: true },
    forceAllHref: { type: String, required: true },
    restoreHref: { type: Function, required: true },
    forceHref: { type: Function, required: true },
    nameOf: { type: Function, required: true },
    keyOf: { type: Function, default: (item) => item.id },
    restoreDisabled: { type: Function, default: () => false },
    // Ziel des Links in der Meldung nach dem Wiederherstellen; null = kein Link
    openHref: { type: Function, default: () => null },
    // Endgültiges Löschen läuft im Hintergrund (Projekte): der Eintrag steht noch kurz in der Liste
    forceIsQueued: { type: Boolean, default: false },
})

const { t } = useI18n()

const items = computed(() => props.entries ?? props.paginator?.data ?? [])
const total = computed(() => props.entries ? props.entries.length : (props.paginator?.total ?? 0))
const pageKeys = computed(() => items.value.map((item) => props.keyOf(item)))

const selectedKeys = ref([])
const processing = ref(false)
const progress = ref(0)
const progressTotal = ref(0)
const showConfirmBulkDelete = ref(false)
const showConfirmDeleteAll = ref(false)

const pageSelectionState = computed(() => selectionState(selectedKeys.value, pageKeys.value))
const selectedItems = computed(() => items.value.filter((item) => selectedKeys.value.includes(props.keyOf(item))))
const restorableSelection = computed(() => selectedItems.value.filter((item) => !props.restoreDisabled(item)))

// Einträge, die verschwunden sind (wiederhergestellt, gelöscht, andere Seite), fallen aus der Auswahl
watch(pageKeys, (keys) => {
    if (!processing.value) {
        selectedKeys.value = pruneKeys(selectedKeys.value, keys)
    }
})

const onRestored = (item, result) => {
    // hat der Server selbst eine Meldung geschickt, reicht diese
    if (result?.serverMessage) return
    const href = props.openHref(item)
    showAppToast(
        'success',
        t('{name} has been restored.', { name: props.nameOf(item) }),
        href ? { label: t('Open entry'), href } : null,
    )
}

/**
 * Ein Request je Eintrag über die vorhandenen Routen, damit dieselben Rechte und Aufräumarbeiten
 * gelten wie beim einzelnen Klick. Weiterleitungen werden nicht verfolgt.
 */
const send = async (method, url) => {
    try {
        const response = await fetch(url, {
            method,
            redirect: 'manual',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfTokenFromCookie(document.cookie),
                Accept: 'application/json',
            },
        })

        return response.type === 'opaqueredirect' || response.ok
    } catch (error) {
        return false
    }
}

const runBulk = async (action) => {
    const targets = action === 'restore' ? restorableSelection.value : selectedItems.value
    if (processing.value || targets.length === 0) return

    processing.value = true
    progress.value = 0
    progressTotal.value = targets.length
    const processedKeys = targets.map((item) => props.keyOf(item))
    let rejected = 0
    for (const item of targets) {
        const ok = action === 'restore'
            ? await send('PATCH', props.restoreHref(item))
            : await send('DELETE', props.forceHref(item))
        if (!ok) rejected++
        progress.value++
    }

    router.reload({
        preserveScroll: true,
        onFinish: () => {
            processing.value = false
            reportBulk(action, processedKeys, rejected)
            leaveEmptyPage()
        },
    })
}

// Ist die letzte Seite nach einer Sammelaktion leer, auf die jetzt letzte Seite wechseln
const leaveEmptyPage = () => {
    const paginator = props.paginator
    if (!paginator || paginator.data?.length > 0 || !(paginator.current_page > 1)) return

    router.reload({
        data: { page: Math.max(1, Math.min(paginator.current_page - 1, paginator.last_page ?? 1)) },
        preserveScroll: true,
    })
}

const reportBulk = (action, processedKeys, rejected) => {
    if (action === 'force' && props.forceIsQueued) {
        selectedKeys.value = []
        showAppToast(rejected > 0 ? 'error' : 'success', rejected > 0
            ? t('Entries that could not be processed: {count}', { count: rejected })
            : t('The permanent deletion has been started and runs in the background.'))
        return
    }

    const outcome = bulkOutcome(processedKeys, pageKeys.value)
    // was nicht geklappt hat, bleibt gewählt
    selectedKeys.value = outcome.failedKeys
    if (outcome.done === 1) {
        showAppToast('success', t(action === 'restore'
            ? '1 entry has been restored.'
            : '1 entry has been deleted permanently.'))
    } else if (outcome.done > 1) {
        showAppToast('success', action === 'restore'
            ? t('{count} entries have been restored.', { count: outcome.done })
            : t('{count} entries have been deleted permanently.', { count: outcome.done }))
    }
    if (outcome.failed > 0) {
        showAppToast('error', t('Entries that could not be processed: {count}', { count: outcome.failed }))
    }
}

const confirmBulkDelete = () => {
    showConfirmBulkDelete.value = false
    runBulk('force')
}

const forceDeleteAll = () => {
    router.delete(props.forceAllHref, {
        preserveScroll: true,
        onFinish: () => {
            showConfirmDeleteAll.value = false
        },
    })
}
</script>
