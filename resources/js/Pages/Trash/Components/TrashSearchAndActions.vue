<template>
    <!-- Kopfzeile eines Papierkorb-Tabs: Anzahl, Suche, "Alle löschen" und der Leerzustand -->
    <div class="mb-4 flex w-full flex-wrap items-end justify-between gap-3">
        <p class="text-sm text-text-subtle tabular-nums" aria-live="polite">
            {{ total === 1 ? $t('1 entry') : $t('{count} entries', { count: total }) }}
        </p>
        <div class="flex items-end gap-x-3">
            <div v-if="searchable" class="w-64">
                <BaseInput id="trashSearch" v-model="search" :label="$t('Search')" is-small />
            </div>
            <BaseUIButton
                v-if="total > 0"
                variant="danger"
                icon="IconTrash"
                :label="isSearching ? 'Delete all in this tab' : 'Delete all'"
                :title="$t('Deletes all entries of this tab permanently, not only the search results.')"
                @click="$emit('delete-all')"
            />
        </div>
    </div>
    <div v-if="total === 0"
         class="flex w-full flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed border-border-subtle bg-surface-sunken/40 p-4 text-sm text-text-subtle">
        <span>{{ isSearching ? $t('No results found for your search.') : $t('Nothing in the recycle bin here.') }}</span>
        <button v-if="isSearching" type="button" class="text-accent-600 hover:text-accent-700" @click="search = ''">
            {{ $t('Reset search') }}
        </button>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash.debounce'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    // Name of the Inertia prop holding the paginator. Used for partial reloads.
    propertyName: { type: String, default: '' },
    // Total number of items across all pages (paginator.total).
    total: { type: Number, default: 0 },
    // Tabs ohne serverseitige Suche (Projekteinstellungen) blenden das Feld aus
    searchable: { type: Boolean, default: true },
})

defineEmits(['delete-all'])

// Initialise from the URL so the input survives full page loads / shared links.
const search = ref(new URLSearchParams(window.location.search).get('search') ?? '')
const isSearching = computed(() => search.value.trim() !== '')

const reload = debounce(() => {
    router.reload({
        only: [props.propertyName],
        // empty search removes the param; always reset to page 1 on a new query
        data: { search: search.value || undefined, page: 1 },
        preserveScroll: true,
        preserveState: true,
    })
}, 400)

watch(search, () => props.searchable && reload())
// sonst lädt eine späte Suche nach dem Wechsel des Reiters auf der neuen Seite
onBeforeUnmount(() => reload.cancel())
</script>
