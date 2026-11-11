<template>
    <ArtworkBaseModal
        title="Project process"
        description="Here you can see what was changed by whom and when."
        modal-size="sm:max-w-2xl"
        @close="closeModal(false)"
    >
        <BaseTabs
            v-if="historyTabs.length > 1"
            :tabs="historyTabs"
            navigation-mode="buttons"
            class="mt-0!"
            @tab-click="(tab) => changeHistoryTabs(tab.id)"
        />

        <div v-if="loading" class="space-y-2" aria-busy="true">
            <div v-for="placeholder in 4" :key="placeholder" class="h-12 animate-pulse rounded-lg bg-surface-sunken motion-reduce:animate-none"/>
        </div>

        <BaseAlertComponent
            v-else-if="loadError"
            type="error"
            class="mb-0!"
            message="History could not be loaded."
            use-translation
        />

        <template v-else>
            <EmptyState
                v-if="visibleItems.length === 0"
                icon="IconHistory"
                :title="$t('No entries found')"
            />
            <ol
                v-else
                class="max-h-96 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border-subtle"
                :aria-label="$t(currentTab === 'budget' ? 'Budget' : 'Project')"
            >
                <li
                    v-for="(historyItem, index) in visibleItems"
                    :key="historyItem.id ?? index"
                    class="flex items-start gap-x-3 px-3 py-2"
                >
                    <UserPopoverTooltip
                        :user="historyItem.changer"
                        :id="`${currentTab}-history-${index}`"
                        height="7"
                        width="7"
                        lazy-load
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-text">
                            {{ $t(historyItem.changes[0].translationKey, historyItem.changes[0].translationKeyPlaceholderValues) }}
                        </p>
                        <p class="mt-0.5 text-xs text-text-muted tabular-nums">{{ historyItem.created_at }}</p>
                    </div>
                </li>
            </ol>
        </template>
    </ArtworkBaseModal>
</template>


<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'

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
        const url = route('projects.history', { project: props.project_id })
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

// Reiter für BaseTabs (Namen werden dort übersetzt)
const historyTabs = computed(() => {
    const tabs = [{ id: 'project', name: 'Project', count: projectItems.value.length, current: currentTab.value === 'project', permission: true }]
    if (canSeeBudgetTab.value) {
        tabs.push({ id: 'budget', name: 'Budget', count: budgetItems.value.length, current: currentTab.value === 'budget', permission: true })
    }
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

// Ohne Budgetrecht nie Budget-Einträge zeigen, auch wenn der Reiter noch auf „budget“ steht
const visibleItems = computed(() =>
    currentTab.value === 'budget' && canSeeBudgetTab.value ? budgetItems.value : projectItems.value
)
</script>

