<template>
    <ArtworkBaseModal @close="closeModal" :title="$t('Project process')" :description="$t('Here you can see what was changed by whom and when.')">
        <div class="mx-4">
            <!-- Header -->

            <!-- Tabs (Pill/Segmented) -->
            <div class="mb-4 pt-3">
                <nav class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-50/70 p-1 shadow-sm ring-1 ring-white/40"
                     aria-label="Tabs" role="tablist">
                    <button
                        v-for="tab in historyTabs"
                        :key="tab.id"
                        role="tab"
                        type="button"
                        @click="changeHistoryTabs(tab.id)"
                        :aria-selected="currentTab === tab.id"
                        :class="[
              'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold uppercase transition',
              currentTab === tab.id
                ? 'bg-white text-artwork-buttons-hover shadow-sm'
                : 'text-zinc-600 hover:text-zinc-800'
            ]"
                    >
                        <span>{{ $t(tab.name) }}</span>
                        <span
                            class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-[10px] tabular-nums"
                            :class="currentTab === tab.id
                ? 'border-artwork-navigation-color/30 text-artwork-buttons-hover bg-artwork-navigation-color/10'
                : 'border-zinc-300 text-zinc-600 bg-white/70'"
                        >
              {{ tab.id === 'project' ? (projectItems?.length || 0) : (budgetItems?.length || 0) }}
            </span>
                    </button>
                </nav>
            </div>

            <div class="mb-5 pb-5">
                <!-- Loading State -->
                <div v-if="loading" class="mt-2 flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 animate-spin text-artwork-buttons-hover" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-zinc-600">{{ $t('Loading...') }}</p>
                    </div>
                </div>

                <!-- Timeline: Project -->
                <div v-else-if="currentTab === 'project'" class="mt-2 max-h-96 overflow-y-auto pr-1.5" role="region" :aria-label="$t('Project')">
                    <div v-if="projectItems.length === 0" class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 text-sm text-zinc-600">
                        {{ $t('No entries found') }}
                    </div>

                    <ol
                        v-else
                        role="list"
                        class="relative pl-8 space-y-2
           before:content-[''] before:absolute before:left-3 before:top-0 before:bottom-0 before:w-px
           before:bg-artwork-buttons-hover/60 before:z-0"
                    >
                        <li v-for="(historyItem, index) in projectItems" :key="index" class="relative">
                            <!-- Dot exakt auf der Linie (Linie bei 12px = left-3, Dot-Radius 5px => 12-5 = 7px) -->
                            <span
                                class="absolute -left-6 top-5 block h-2.5 w-2.5 rounded-full bg-artwork-buttons-hover ring-2 ring-white z-10"
                                aria-hidden="true"
                            ></span>

                            <!-- Card rechts der Linie (weggerückt) -->
                            <div class="rounded-xl border border-zinc-200 bg-white/85 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                      <span class="inline-flex h-6 items-center rounded-full border border-artwork-navigation-color/25 bg-artwork-navigation-color/10 px-2 text-[11px] font-medium text-artwork-buttons-hover tabular-nums">
                                        {{ historyItem.created_at }}
                                      </span>

                                    <UserPopoverTooltip :user="historyItem.changer" :id="`project-${index}`" height="6" width="6" />

                                    <div class="min-w-0 grow text-xs text-zinc-700 subpixel-antialiased">
                                        {{ $t(historyItem.changes[0].translationKey, historyItem.changes[0].translationKeyPlaceholderValues) }}
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- Timeline: Budget -->
                <div v-else-if="currentTab === 'budget'" class="mt-2 max-h-96 overflow-y-auto" role="region" :aria-label="$t('Budget')">
                    <div v-if="budgetItems.length === 0" class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 text-sm text-zinc-600">
                        {{ $t('No entries found') }}
                    </div>

                    <div
                        v-else
                        class="relative pl-8
                       before:content-[''] before:absolute before:left-3 before:top-0 before:bottom-0 before:w-px
                       before:bg-amber-500/60"
                    >
                        <ol role="list" class="space-y-2">
                            <li v-for="(historyItem, index) in budgetItems" :key="index" class="relative">
                                <span
                                    class="absolute -left-6 top-1/2 -translate-y-1/2 block h-2.5 w-2.5 rounded-full
                                         bg-amber-500 ring-2 ring-white"
                                    aria-hidden="true"
                                ></span>

                                <div class="rounded-xl border border-amber-200/70 bg-amber-50/70 p-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex h-6 items-center rounded-full border border-amber-300/70 bg-amber-100/70 px-2 text-[11px] font-medium text-amber-800 tabular-nums">
                                          {{ historyItem.created_at }}
                                        </span>
                                        <UserPopoverTooltip :user="historyItem.changer" :id="`budget-${index}`" height="6" width="6" />
                                        <div class="min-w-0 grow text-xs text-amber-900 subpixel-antialiased">
                                            {{ $t(historyItem.changes[0].translationKey, historyItem.changes[0].translationKeyPlaceholderValues) }}
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>


<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";

defineOptions({ name: 'ProjectHistoryComponent' })

const props = defineProps({
    project_id: { type: [Number, String], required: true },
})

const emit = defineEmits(['closed'])

const page = usePage()
const userId = computed(() => page?.props?.auth?.user?.id)
const userRoles = computed(() => page?.props?.auth?.user?.roles ?? page?.props?.auth?.roles ?? [])

// State for fetched history
const history = ref([])
const accessBudget = ref([])
const loading = ref(false)
const loadError = ref(null)

async function loadHistory() {
    if (!props.project_id) return
    loading.value = true
    loadError.value = null
    try {
        const url = route ? route('projects.history', { project: props.project_id }) : `/projects/${props.project_id}/history`
        const { data } = await axios.get(url)
        if (data && Array.isArray(data.history)) {
            history.value = data.history
            accessBudget.value = Array.isArray(data.access_budget) ? data.access_budget : []
        } else {
            history.value = Array.isArray(data) ? data : []
            // when backend still returns array only
            accessBudget.value = []
        }
    } catch (e) {
        // keep UI usable even on error
        loadError.value = e?.message ?? 'Failed to load history'
        history.value = []
        accessBudget.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadHistory)
watch(() => props.project_id, () => loadHistory())

// robust admin check (string/object)
const isAdmin = computed(() => {
    return (userRoles.value || []).some(r => {
        const name = typeof r === 'string' ? r : r?.name
        return name?.toLowerCase() === 'artwork admin'
    })
})

// Determine if current user can see budget tab
const hasBudgetAccessFromPage = computed(() => {
    const p = page?.props || {}
    // Option 1: direct access_budget array of users (objects with id)
    const accessList = p?.project?.access_budget || p?.headerObject?.project?.access_budget
    if (Array.isArray(accessList)) {
        return accessList.some(u => (u?.id ?? u) === userId.value)
    }
    // Option 2: usersArray with pivot_access_budget flag
    const usersArray = p?.project?.usersArray || p?.headerObject?.project?.usersArray
    if (Array.isArray(usersArray)) {
        return usersArray.some(u => u?.id === userId.value && !!u?.pivot_access_budget)
    }
    return false
})

const canSeeBudgetTab = computed(() => {
    const fromResponse = (accessBudget.value || []).some(u => (u?.id ?? u) === userId.value)
    return isAdmin.value || hasBudgetAccessFromPage.value || fromResponse
})

const currentTab = ref('project')

// Tabs (i18n labels in template)
const historyTabs = computed(() => {
    const tabs = [{ id: 'project', name: 'Project' }]
    if (canSeeBudgetTab.value) tabs.push({ id: 'budget', name: 'Budget' })
    return tabs
})

function changeHistoryTabs(id) {
    currentTab.value = id
}

function closeModal(bool) {
    emit('closed', bool)
}

// Filtered items (with guards)
const projectItems = computed(() =>
    (history.value ?? []).filter(h => {
        const t = h?.changes?.[0]?.type
        return t === 'project' || t === 'public_changes'
    })
)

const budgetItems = computed(() =>
    (history.value ?? []).filter(h => h?.changes?.[0]?.type === 'budget')
)
</script>

<style scoped>
.tabular-nums { font-variant-numeric: tabular-nums; }
</style>
