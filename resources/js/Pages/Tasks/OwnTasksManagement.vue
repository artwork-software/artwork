<template>
    <AppLayout :title="$t('My tasks')">
        <div class="artwork-container">

            <ToolbarHeader
                :icon="IconChecklist"
                :title="$t('My ToDo-Lists')"
                icon-bg-class="bg-green-600/10 text-green-700"
                :description="$t('Organize, filter and edit your tasks')"
                :search-enabled="false"
            >
                <template #actions>
                    <nav class="grid grid-cols-2 sm:flex gap-2">
                        <BaseUIButton label="New checklist" use-translation is-add-button @click="showChecklistEditModal = true" />

                    </nav>
                </template>
            </ToolbarHeader>

            <!-- Action Bar: Search + Chips + Sort + View Switch -->
            <section class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm px-5 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Search -->
                    <div class="relative w-full lg:w-96">
                        <input
                            v-model="search"
                            type="search"
                            :placeholder="$t('Search lists & tasks...')"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/40 transition"
                        />
                        <span v-if="search" class="absolute inset-y-0 right-0 flex items-center pr-2">
                          <button @click="search=''" class="rounded-lg p-1 text-gray-400 hover:text-gray-600">
                            <IconX class="size-4" />
                          </button>
                        </span>
                    </div>

                    <!-- Chips + Sort + View -->
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-end w-full">
                        <!-- Filter Chips -->
                        <div class="flex flex-col gap-2">
                            <!-- Row 1: Mutually-exclusive groups + Reset -->
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Project group: with/without project -->
                                <div class="inline-flex items-center gap-1 rounded-xl border border-gray-200 p-1" role="group" :aria-label="$t('Project filter')">
                                    <button
                                        @click="toggleFlag('checklist_has_projects')"
                                        :class="groupedChipClass(user.checklist_has_projects)"
                                        class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                        type="button"
                                    >
                                        <span class="size-2 rounded-full bg-emerald-500"></span>{{ $t('with project') }}
                                    </button>
                                    <button
                                        @click="toggleFlag('checklist_no_projects')"
                                        :class="groupedChipClass(user.checklist_no_projects)"
                                        class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                        type="button"
                                    >
                                        <span class="size-2 rounded-full bg-amber-500"></span>{{ $t('without project') }}
                                    </button>
                                </div>

                                <!-- Privacy group: personal/shared -->
                                <div class="inline-flex items-center gap-1 rounded-xl border border-gray-200 p-1" role="group" :aria-label="$t('Privacy filter')">
                                    <button
                                        @click="toggleFlag('checklist_private_checklists')"
                                        :class="groupedChipClass(user.checklist_private_checklists)"
                                        class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                        type="button"
                                    >
                                        <span class="size-2 rounded-full bg-indigo-500"></span>{{ $t('private') }}
                                    </button>
                                    <button
                                        @click="toggleFlag('checklist_no_private_checklists')"
                                        :class="groupedChipClass(user.checklist_no_private_checklists)"
                                        class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs"
                                        type="button"
                                    >
                                        <span class="size-2 rounded-full bg-pink-500"></span>{{ $t('shared') }}
                                    </button>
                                </div>

                                <!-- Reset button -->
                                <button @click="resetFilters" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-2.5 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M4 4h16M4 12h16M4 20h16"/></svg>
                                    {{ $t('Reset') }}
                                </button>
                            </div>

                            <!-- Row 2: Display options (separate line) -->
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    @click="toggleFlag('checklist_completed_tasks')"
                                    :class="chipClass(user.checklist_completed_tasks)"
                                    class="inline-flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs"
                                    type="button"
                                >
                                    <span class="size-2 rounded-full bg-slate-500"></span>{{ $t('Show completed') }}
                                </button>
                                <button
                                    @click="toggleFlag('checklist_show_without_tasks')"
                                    :class="chipClass(user.checklist_show_without_tasks)"
                                    class="inline-flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs"
                                    type="button"
                                >
                                    <span class="size-2 rounded-full bg-cyan-500"></span>{{ $t('Show empty lists') }}
                                </button>
                            </div>
                        </div>

                        <!-- Sort + View -->
                        <div class="flex items-center gap-2">
                            <!-- Sort -->
                            <div class="relative">
                                <button @click="sortOpen = !sortOpen" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-xs hover:bg-gray-50">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M3 7h14M3 12h10M3 17h6"/></svg>
                                    {{ sortLabel }}
                                </button>
                                <div v-if="sortOpen" @click.outside="sortOpen=false" class="absolute right-0 mt-2 w-64 rounded-xl border border-gray-100 bg-white shadow-lg p-1 z-10">
                                    <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="sortTo(1)">{{ $t('Project timeframe ascending') }} <IconCheck v-if="currentSort===1" class="inline size-4 ml-1" /></button>
                                    <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="sortTo(2)">{{ $t('Project timeframe descending') }} <IconCheck v-if="currentSort===2" class="inline size-4 ml-1" /></button>
                                    <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="currentSort=3; sortOpen=false">{{ $t('List name ascending') }} <IconCheck v-if="currentSort===3" class="inline size-4 ml-1" /></button>
                                    <button class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50" @click="currentSort=4; sortOpen=false">{{ $t('List name descending') }} <IconCheck v-if="currentSort===4" class="inline size-4 ml-1" /></button>
                                </div>
                            </div>

                            <!-- View switch -->
                            <div class="inline-flex rounded-xl border border-gray-200 p-1">
                                <button @click="view='list'" :class="view==='list' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 rounded-lg text-xs font-medium">
                                    {{ $t('List') }}
                                </button>
                                <button @click="view='kanban'" :class="view==='kanban' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 rounded-lg text-xs font-medium">
                                    {{ $t('Kanban') }}
                                </button>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- Save notification -->
                <transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-1">
                    <div v-show="filterSaved" class="text-xs bg-emerald-600 text-white rounded-lg px-3 py-1.5 mt-5">
                        {{ $t('Filter was saved') }}
                    </div>
                </transition>
            </section>

            <!-- Content -->
            <main class="mt-6">
                <!-- Empty state -->
                <div v-if="checklistsComputed.length===0" class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center">
                    <p class="text-sm text-gray-600">{{ $t('No fitting checklists found') }}</p>
                </div>

                <!-- List view -->
                <div v-else-if="view==='list'">
                    <ChecklistListView
                        :checklists="checklistsComputed"
                        :can-edit-component="false"
                        :project-can-write-ids="[]"
                        :project-manager-ids="[]"
                        :is-admin="false"
                        :checklist_templates="checklist_templates"
                        :project="null"
                        :tab_id="null"
                        :is-in-own-task-management="true"
                    />
                </div>

                <!-- Kanban view -->
                <div v-else>
                    <ChecklistKanbanView
                        :checklists="checklistsComputed"
                        :can-edit-component="false"
                        :project-can-write-ids="[]"
                        :project-manager-ids="[]"
                        :is-admin="false"
                        :checklist_templates="checklist_templates"
                        :project="null"
                        :tab_id="null"
                        :is-in-own-task-management="true"
                    />
                </div>

                <!-- Money Source Tasks -->
                <section class="mt-10" v-if="moneySourceTasks.length > 0">
                    <h2 class="text-lg font-semibold mb-2">{{ $t('Money Source Tasks') }}</h2>
                    <div v-if="moneySourceTasks.length" class="rounded-2xl border border-gray-100 bg-white shadow-sm divide-y">
                        <div v-for="task in moneySourceTasks" :key="task.id" class="px-5 py-4">
                            <SingleMoneySourceTask :task="task" />
                        </div>
                    </div>
                    <div v-else class="rounded-2xl border border-dashed border-gray-200 bg-white p-6 text-center">
                        <AlertComponent
                            type="info"
                            :text="$t('There are no money Source Tasks')"
                            show-icon
                            icon-size="h-6 w-6"
                            text-size="text-base"
                        />
                    </div>
                </section>
            </main>
        </div>

        <AddEditChecklistModal
            :checklist_templates="checklist_templates"
            :project="null"
            :checklist-to-edit="null"
            :tab_id="null"
            v-if="showChecklistEditModal"
            @closed="showChecklistEditModal = false"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AlertComponent from '@/Components/Alerts/AlertComponent.vue'
import SingleMoneySourceTask from '@/Pages/Tasks/Components/SingleMoneySourceTask.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { IconCheck, IconX, IconChecklist } from '@tabler/icons-vue'
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import AddEditChecklistModal from "@/Components/Checklist/Modals/AddEditChecklistModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";

const $t = useTranslation()

const props = defineProps<{
    money_source_task: any[],
    first_project_tasks_tab_id: number,
    checklists: Array<any>,
    checklist_templates: Array<any>
}>()

const page = usePage()
const user = computed(() => page.props.auth.user)

const search = ref('')
const view = ref<'list' | 'kanban'>((user.value?.checklist_style === 'list' ? 'list' : 'kanban') as 'list' | 'kanban')
const currentSort = ref<number>(page.props.urlParameters?.filter > 0 ? parseInt(page.props.urlParameters?.filter) : 0)
const sortOpen = ref(false)
const showChecklistEditModal = ref(false)
const filterSaved = ref(false)

const sortLabel = computed(() => {
    switch (currentSort.value) {
        case 1: return $t('Projektzeitraum aufsteigend')
        case 2: return $t('Projektzeitraum absteigend')
        case 3: return $t('Listenname aufsteigend')
        case 4: return $t('Listenname absteigend')
        default: return $t('Sortieren')
    }
})

/** --- Filtering & mapping --- */
const filteredChecklists = computed(() => {
    const lists = (props.checklists ?? []).filter((cl: any) => {
        let include = true
        if (search.value) {
            const s = search.value.toLowerCase()
            include =
                cl.name?.toLowerCase()?.includes(s) ||
                (cl.tasks || []).some((t: any) => t.name?.toLowerCase()?.includes(s))
        }
        if (user.value.checklist_has_projects) include = include && cl.hasProject === true
        if (user.value.checklist_no_projects) include = include && cl.hasProject === false
        if (user.value.checklist_private_checklists) include = include && cl.private === true
        if (user.value.checklist_no_private_checklists) include = include && cl.private === false

        if (user.value.checklist_show_without_tasks) {
            include = include && (cl.tasks?.length ?? 0) >= 0
        } else {
            const visible = (cl.tasks || []).some((t: any) => !t.done)
            include = include && (cl.tasks?.length ?? 0) > 0 && visible
        }
        return include
    })
    return lists
})

const checklistsComputed = computed(() => {
    // map visible tasks
    const lists = filteredChecklists.value.map((cl: any) => {
        const tasks = user.value.checklist_completed_tasks ? (cl.tasks ?? []) : (cl.tasks ?? []).filter((t: any) => !t.done)
        return { ...cl, tasks }
    })

    if (currentSort.value === 3) return lists.sort((a: any, b: any) => a.name.localeCompare(b.name))
    if (currentSort.value === 4) return lists.sort((a: any, b: any) => b.name.localeCompare(a.name))
    // 1 & 2 werden serverseitig gehandhabt (Projektzeitraum)
    return lists
})

const moneySourceTasks = ref(props.money_source_task ?? [])

/** --- Actions --- */
function sortTo(type: number) {
    currentSort.value = type
    sortOpen.value = false
    // 1/2 über Server, rest lokal
    if (type === 1 || type === 2) {
        router.reload({
            data: { filter: type },
            only: ['checklists'],
            preserveScroll: true,
            preserveState: true
        })
    }
}

function resetFilters() {
    user.value.checklist_has_projects = false
    user.value.checklist_no_projects = false
    user.value.checklist_private_checklists = false
    user.value.checklist_no_private_checklists = false
    user.value.checklist_completed_tasks = false
    user.value.checklist_show_without_tasks = false
    saveFiltersNow()
}

function chipClass(active: boolean) {
    return active
        ? 'border-indigo-300 bg-indigo-50/70 text-indigo-700'
        : 'border-gray-200 text-gray-600 hover:bg-gray-50'
}

// Variant for chips inside an outlined group (no inner border, clearer active state)
function groupedChipClass(active: boolean) {
    return active
        ? 'bg-indigo-50/80 text-indigo-700 ring-1 ring-inset ring-indigo-200'
        : 'text-gray-600 hover:bg-gray-50'
}

function toggleFlag(key: string) {
    // @ts-ignore
    user.value[key] = !user.value[key]

    // Make project-related filters mutually exclusive like a slider
    if (key === 'checklist_has_projects' && user.value.checklist_has_projects) {
        user.value.checklist_no_projects = false
    } else if (key === 'checklist_no_projects' && user.value.checklist_no_projects) {
        user.value.checklist_has_projects = false
    }

    // Make privacy-related filters (personal/shared) mutually exclusive like a slider
    if (key === 'checklist_private_checklists' && user.value.checklist_private_checklists) {
        user.value.checklist_no_private_checklists = false
    } else if (key === 'checklist_no_private_checklists' && user.value.checklist_no_private_checklists) {
        user.value.checklist_private_checklists = false
    }

    saveFiltersNow()
}

function saveFiltersNow() {
    router.patch(
        route('user.update.checklist.filter', user.value.id),
        {
            checklist_has_projects: user.value.checklist_has_projects,
            checklist_no_projects: user.value.checklist_no_projects,
            checklist_private_checklists: user.value.checklist_private_checklists,
            checklist_no_private_checklists: user.value.checklist_no_private_checklists,
            checklist_completed_tasks: user.value.checklist_completed_tasks,
            checklist_show_without_tasks: user.value.checklist_show_without_tasks
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                filterSaved.value = true
                setTimeout(() => (filterSaved.value = false), 3000)
            }
        }
    )
}

/** --- Sync view back to user prefs (optional) --- */
watch(view, async (v) => {
    // wenn du später eine Einstellung dafür speicherst, kannst du hier ein PATCH senden
    // user.checklist_style = v === 'list' ? 'list' : 'kanban'
})
</script>

<style scoped>
/* kein @apply; nur minimaler Scope falls nötig */
</style>
