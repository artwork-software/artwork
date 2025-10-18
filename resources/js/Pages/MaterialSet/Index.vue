<template>
    <MaterialSetSettingsHeader
        :title="$t('Material Sets')"
        :description="$t('Manage material sets for inventory planning.')"
    >
        <template #actions>
            <button
                v-if="can('set.create_edit') || is('artwork admin')"
                class="ui-button-add"
                type="button"
                @click="openCreate()"
            >
                <component :is="IconCopyPlus" stroke-width="1" class="size-5" />
                <span>{{ $t('New Material Set') }}</span>
            </button>
        </template>

            <!-- Toolbar -->
            <div class="rounded-2xl border border-zinc-200 bg-white/80 p-4 shadow-sm backdrop-blur">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <!-- Suche -->
                    <label class="relative block w-full md:max-w-md">
                        <span class="sr-only">{{ $t('Search') }}</span>
                        <input
                            v-model.trim="search"
                            type="text"
                            :placeholder="$t('Search material sets…')"
                            class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 pr-9 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        />
                        <IconSearch class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                    </label>

                    <!-- rechts: Zähler + View Toggle -->
                    <div class="flex items-center justify-between gap-3">
            <span
                class="inline-flex items-center gap-1 rounded-full bg-zinc-50 px-2.5 py-1 text-[11px] font-medium text-zinc-700 ring-1 ring-inset ring-zinc-200"
            >
              <span class="inline-block size-2 rounded-full bg-indigo-500 ring-2 ring-indigo-200/60" />
              {{ $t('Found') }}: {{ filteredSets.length }}
            </span>

                        <div class="inline-flex rounded-lg border border-zinc-200 bg-white p-1">
                            <button
                                type="button"
                                class="rounded-md px-2 py-1 text-sm"
                                :class="viewMode === 'table' ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-600'"
                                @click="viewMode = 'table'"
                                :aria-pressed="viewMode === 'table'"
                            >
                                <IconList class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-2 py-1 text-sm"
                                :class="viewMode === 'cards' ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-600'"
                                @click="viewMode = 'cards'"
                                :aria-pressed="viewMode === 'cards'"
                            >
                                <IconLayoutGrid class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTENT -->
            <div class="card white mt-2 rounded-2xl border border-zinc-200 bg-white/90 p-0 shadow-sm backdrop-blur">
                <!-- TABLE VIEW -->
                <div v-if="viewMode === 'table'" class="flow-root">
                    <div class="overflow-x-auto">
                        <div class="inline-block min-w-full align-middle p-5">
                            <table class="min-w-full divide-y divide-zinc-200">
                                <thead class="">
                                <tr class="divide-x divide-zinc-200">
                                    <th scope="col" class="py-3.5 pl-4 pr-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 sm:pl-0">
                                        {{ $t('Name') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                        {{ $t('Description') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                        {{ $t('Articles') }}
                                    </th>
                                    <th scope="col" class="py-3.5 pl-4 pr-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 sm:pr-0">
                                        {{ $t('Actions') }}
                                    </th>
                                </tr>
                                </thead>

                                <tbody class="divide-y divide-zinc-100 bg-white">
                                <SingleMaterialSet
                                    v-for="set in filteredSets"
                                    :key="set.id"
                                    :set="set"
                                />
                                <tr v-if="!filteredSets.length">
                                    <td colspan="4" class="py-8">
                                        <BaseAlertComponent
                                            :message="$t('No material sets found.')"
                                            type="info"
                                            class="mx-auto max-w-lg text-center"
                                            use-translation
                                        />
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CARD VIEW -->
                <div v-else class="p-5">
                    <div v-if="filteredSets.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="set in filteredSets"
                            :key="set.id"
                            class="group rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="truncate text-sm font-semibold text-zinc-900">
                                    {{ set.name }}
                                </h3>
                                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-700 ring-1 ring-inset ring-zinc-200">
                                  <IconStackForward class="h-3.5 w-3.5 text-zinc-500" />
                                  {{ articlesCount(set) }}
                                </span>
                            </div>

                            <p v-if="set.description" class="mt-1 line-clamp-3 text-xs text-zinc-600">
                                {{ set.description }}
                            </p>

                            <div class="mt-3 flex items-center justify-end gap-2">
                                <button
                                    v-if="can('set.create_edit') || is('artwork admin')"
                                    type="button"
                                    class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-zinc-700 ring-1 ring-inset ring-zinc-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                    @click="openEdit(set)"
                                >
                                    {{ $t('Edit') }}
                                </button>
                                <!-- Optional: Details/Route-Button falls vorhanden
                                <Link :href="route('material-sets.show', set.id)" class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200">
                                  {{ $t('Open') }}
                                </Link>
                                -->
                            </div>
                        </article>
                    </div>

                    <div v-else class="rounded-xl border border-dashed border-zinc-300 bg-white/60 p-8 text-center">
                        <BaseAlertComponent :message="$t('No material sets found.')" type="info" use-translation />
                    </div>
                </div>
            </div>

        <!-- Modal -->
        <CreateOrUpdateMaterialSetModal
            v-if="showCreateOrUpdateMaterialSetModal"
            :material-set="selectedSet"
            @close="closeModal"
        />
    </MaterialSetSettingsHeader>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import MaterialSetSettingsHeader from '@/Pages/MaterialSet/Components/MaterialSetSettingsHeader.vue'
import SingleMaterialSet from '@/Pages/MaterialSet/Components/SingleMaterialSet.vue'
import CreateOrUpdateMaterialSetModal from '@/Pages/MaterialSet/Components/CreateOrUpdateMaterialSetModal.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import GlassyIconButton from '@/Artwork/Buttons/GlassyIconButton.vue'
import { can, is } from 'laravel-permission-to-vuejs'
import { IconSearch, IconLayoutGrid, IconList, IconCopyPlus, IconStackForward } from '@tabler/icons-vue'

const props = defineProps<{
    materialSets: any[] | { data?: any[] } // unterstützt Array oder Paginator-Objekt
}>()

// Rohdaten vereinheitlichen
const rawSets = computed<any[]>(() => {
    return Array.isArray(props.materialSets)
        ? props.materialSets
        : (props.materialSets?.data ?? [])
})

// Suche & Filter
const search = ref('')
const normalized = (s: unknown) => String(s ?? '').toLowerCase().trim()
const filteredSets = computed(() => {
    const q = normalized(search.value)
    if (!q) return rawSets.value
    return rawSets.value.filter((s: any) => {
        return normalized(s.name).includes(q) || normalized(s.description).includes(q)
    })
})

// View Toggle
const viewMode = ref<'table' | 'cards'>('table')

// Artikelanzahl robust ermitteln (unterstützt verschiedene Backends)
function articlesCount(set: any): number {
    return (
        set.articles_count ??
        set.items_count ??
        (Array.isArray(set.items) ? set.items.length : 0) ??
        (Array.isArray(set.articles) ? set.articles.length : 0)
    ) as number
}

// Modal-Steuerung
const showCreateOrUpdateMaterialSetModal = ref(false)
const selectedSet = ref<any | null>(null)

function openCreate() {
    selectedSet.value = null
    showCreateOrUpdateMaterialSetModal.value = true
}
function openEdit(set: any) {
    selectedSet.value = set
    showCreateOrUpdateMaterialSetModal.value = true
}
function closeModal() {
    showCreateOrUpdateMaterialSetModal.value = false
    selectedSet.value = null
}
</script>
