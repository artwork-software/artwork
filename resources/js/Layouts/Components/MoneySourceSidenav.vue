<template>
    <div class="w-full mt-6">
        <!-- Section: Zugriff / Verantwortliche -->
        <section class="rounded-2xl border border-zinc-700 shadow-sm p-4">
            <header class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">{{ $t('Freigaben') }}</h3>
                <button
                    v-if="canManage"
                    class="inline-flex items-center justify-center size-7 rounded-full bg-gray-700 text-white hover:bg-gray-900 transition"
                    @click="openEditUsersModal"
                    type="button"
                >
                    <IconEdit class="size-4" />
                </button>
            </header>

            <!-- Verantwortliche -->
            <div class="mt-3">
                <p class="text-xs text-white">{{ $t('Verantwortlich') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-1">
                    <template v-for="u in users" :key="'resp-'+u.id">
                        <NewUserToolTip
                            v-if="u.pivot?.competent"
                            :type="1"
                            :user="u"
                            :height="9"
                            :width="9"
                            :id="u.id"
                        />
                    </template>
                    <p v-if="!competentUsers.length" class="text-xs text-zinc-300">{{ $t('Keine Verantwortlichen zugewiesen') }}</p>
                </div>
            </div>

            <!-- Schreibzugriff -->
            <div class="mt-4">
                <p class="text-xs text-white">{{ $t('Schreibzugriff') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-1">
                    <template v-for="u in users" :key="'write-'+u.id">
                        <NewUserToolTip
                            v-if="u.pivot?.write_access && !u.pivot?.competent"
                            :user="u"
                            :height="9"
                            :width="9"
                            :id="u.id + '-write'"
                        />
                    </template>
                    <p v-if="!writeUsers.length" class="text-xs text-zinc-300">{{ $t('Niemand mit Schreibzugriff') }}</p>
                </div>
            </div>
        </section>

        <!-- Section: Kategorien -->
        <section class="mt-5 rounded-2xl border border-zinc-700  shadow-sm">
            <header class="flex items-center justify-between px-4 py-3">
                <button class="flex items-center gap-2 text-sm font-semibold text-white" @click="showMoneySourceCategories = !showMoneySourceCategories" type="button">
                    {{ $t('Quellen-Kategorien') }}
                    <IconChevronDown class="size-4 transition" :class="showMoneySourceCategories ? 'rotate-180' : ''" />
                </button>
                <button
                    v-if="canManage"
                    class="inline-flex items-center justify-center size-7 rounded-full bg-gray-700 text-white hover:bg-gray-900 transition"
                    @click="openMoneySourceCategoriesModal"
                    type="button"
                >
                    <IconEdit class="size-4" />
                </button>
            </header>

            <div v-if="showMoneySourceCategories" class="px-4 pb-4">
                <div v-if="(money_source.categories?.length || 0) > 0" class="flex flex-wrap gap-2">
                    <TagComponent
                        v-for="cat in money_source.categories"
                        :key="cat.id"
                        :displayed-text="cat.name"
                        type="gray"
                        hide-x="true"
                    />
                </div>
                <p v-else class="text-xs text-zinc-300">{{ $t('Keine Kategorien vergeben') }}</p>
            </div>
        </section>

        <!-- Section: Finanzierte Projekte -->
        <section class="mt-5 rounded-2xl border border-zinc-700  shadow-sm">
            <header class="flex items-center justify-between px-4 py-3">
                <button class="flex items-center gap-2 text-sm font-semibold text-white" @click="showLinkedProjects = !showLinkedProjects" type="button">
                    {{ $t('Finanzierte Projekte') }}
                    <IconChevronDown class="size-4 transition" :class="showLinkedProjects ? 'rotate-180' : ''" />
                </button>
                <button
                    v-if="canManage"
                    class="inline-flex items-center justify-center size-7 rounded-full bg-gray-700 text-white hover:bg-gray-900 transition"
                    @click="openLinkProjectsModal"
                    type="button"
                >
                    <IconEdit class="size-4" />
                </button>
            </header>

            <div v-if="showLinkedProjects" class="px-4 pb-4 space-y-2">
                <div
                    v-for="proj in linkedProjects"
                    :key="proj.id"
                    class="rounded-xl border border-zinc-700  px-3 py-2 flex items-center justify-between gap-3"
                >
                    <div class="min-w-0">
                        <Link
                            v-if="proj.id"
                            :href="route('projects.tab', { project: proj.id, projectTab: first_project_budget_tab_id })"
                            class="text-sm text-blue-300 hover:text-blue-400 truncate inline-flex items-center gap-1"
                        >
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M4 7h16M4 12h10M4 17h16"/></svg>
                            {{ proj.name }}
                        </Link>
                        <span v-else class="text-sm text-white">{{ proj.name }}</span>
                        <p class="text-[11px] text-zinc-300">
                            {{ $t('verwendet') }}
                        </p>
                    </div>
                    <div class="shrink-0 text-sm font-medium text-zinc-300">
                        {{ formatCurrency(positionSumsPerProject?.[proj.id] || 0) }}
                    </div>
                </div>

                <p v-if="!linkedProjects?.length" class="text-xs text-zinc-300">
                    {{ $t('Keine verknüpften Projekte') }}
                </p>
            </div>
        </section>

        <!-- Section: Dokumente -->
        <section class="mt-5 rounded-2xl border border-zinc-700  shadow-sm">
            <header class="flex items-center gap-2 px-4 py-3">
                <button class="flex items-center gap-2 text-sm font-semibold text-white" @click="showMoneySourceFiles = !showMoneySourceFiles" type="button">
                    {{ $t('Dokumente') }}
                    <IconChevronDown class="size-4 transition" :class="showMoneySourceFiles ? 'rotate-180' : ''" />
                </button>
                <button
                    v-if="canManage"
                    class="ml-auto inline-flex items-center justify-center size-7 rounded-full bg-gray-800 text-white hover:bg-gray-900 transition"
                    @click="openFileUploadModal"
                    type="button"
                    :title="$t('Datei hochladen')"
                >
                    <IconUpload class="size-4" />
                </button>
            </header>

            <div v-if="showMoneySourceFiles" class="px-4 pb-4">
                <div v-if="(moneySourceFiles?.data?.length || 0) > 0" class="space-y-2">
                    <div
                        v-for="file in moneySourceFiles.data"
                        :key="file.id || file.name"
                        class="group flex items-center gap-2 rounded-xl border border-gray-100 bg-white px-3 py-2 hover:bg-gray-50"
                    >
                        <button
                            v-if="canManage"
                            class="text-gray-700 hover:text-gray-900"
                            type="button"
                            @click="downloadMoneySourceFile(file)"
                        >
                            <IconDownload class="size-4" />
                        </button>

                        <button
                            v-if="canManage"
                            class="flex-1 text-left text-sm text-gray-700 hover:text-gray-900 truncate"
                            type="button"
                            @click="openFileEditModal(file)"
                        >
                            {{ file.name }}
                        </button>
                        <span v-else class="flex-1 text-sm text-gray-700 truncate">{{ file.name }}</span>

                        <button
                            v-if="canManage"
                            class="ml-auto text-red-600 hover:text-red-700"
                            type="button"
                            @click="openFileDeleteModal(file)"
                        >
                            <IconCircleX class="size-4" />
                        </button>
                    </div>
                </div>
                <p v-else class="text-xs text-zinc-300">{{ $t('Keine Dokumente vorhanden') }}</p>
            </div>
        </section>

        <!-- Section: Aufgaben -->
        <section class="mt-5 rounded-2xl border border-zinc-700 shadow-sm p-4">
            <header class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">{{ $t('Aufgaben') }}</h3>
                <button
                    v-if="canManage"
                    class="inline-flex items-center justify-center size-7 rounded-full bg-gray-700 text-white hover:bg-gray-900 transition"
                    @click="openAddMoneySourceTask"
                    type="button"
                >
                    <IconEdit class="size-4" />
                </button>
            </header>

            <ul class="mt-3 space-y-3">
                <li
                    v-for="task in tasks"
                    :key="task.id"
                    class="rounded-xl border border-zinc-700  px-3 py-2"
                >
                    <div class="flex items-start gap-3">
                        <div class="pt-0.5" v-if="canManage">
                            <input
                                type="checkbox"
                                :checked="task.done"
                                @change="updateTask(task)"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium leading-tight truncate" :class="task.done ? 'line-through text-gray-400' : ''">
                                    {{ task.name }}
                                </p>
                                <span
                                    class="text-[11px]"
                                    :class="isOverdue(task.deadline) && !task.done ? 'text-red-600' : 'text-gray-500'"
                                >
                  {{ $t('bis') }} {{ formatDate(task.deadline) }}
                </span>

                                <!-- Assignees -->
                                <div class="flex items-center -space-x-2 ml-1">
                                    <template v-for="(u, idx) in task.money_source_task_users" :key="(u?.id ?? idx)+'-taskuser-'+task.id">
                                        <NewUserToolTip
                                            v-if="u"
                                            :user="u"
                                            :height="7"
                                            :width="7"
                                            :id="`money_source_task_${task.id}_${idx}`"
                                        />
                                    </template>
                                </div>
                            </div>
                            <p v-if="task.description" class="text-xs text-zinc-300 mt-1">
                                {{ task.description }}
                            </p>
                        </div>
                    </div>
                </li>
            </ul>

            <p v-if="!tasks?.length" class="text-xs text-zinc-300 mt-3">
                {{ $t('Keine Aufgaben vorhanden') }}
            </p>
        </section>

        <!-- Modals -->
        <create-money-source-task
            v-if="showMoneySourceTaskModal"
            @closed="onCreateMoneySourceTask"
            :money_source_id="money_source.id"
        />
        <EditMoneySourceUsersModal
            v-if="showEditUsersModal"
            @closed="onCloseEditUsersModal"
            :moneySource="money_source"
        />
        <link-projects-to-money-sources-component
            v-if="showLinkProjectsModal"
            @closed="onCloseLinkProjectsModal"
            :moneySource="money_source"
            :linkedProjects="linkedProjects"
        />
        <MoneySourceFileUploadModal
            v-if="showFileUploadModal"
            :close-modal="closeFileUploadModal"
            :money-source-id="money_source.id"
        />
        <MoneySourceFileEditModal
            v-if="showFileEditModal"
            :close-modal="closeFileEditModal"
            :file="moneySourceFileToEdit"
        />
        <MoneySourceFileDeleteModal
            v-if="showFileDeleteModal"
            :money-source-id="money_source.id"
            :close-modal="closeFileDeleteModal"
            :file="moneySourceFileToDelete"
        />
        <MoneySourceCategoriesModal
            v-if="showMoneySourceCategoriesModal"
            :money-source-id="money_source.id"
            :money-source-categories="moneySourceCategories"
            :money-source-current-categories="money_source.categories"
            :close-modal="closeMoneySourceCategoriesModal"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue'
import TagComponent from '@/Layouts/Components/TagComponent.vue'
import CreateMoneySourceTask from '@/Layouts/Components/CreateMoneySourceTask.vue'
import MoneySourceFileUploadModal from '@/Layouts/Components/MoneySourceFileUploadModal.vue'
import MoneySourceFileEditModal from '@/Layouts/Components/MoneySourceFileEditModal.vue'
import MoneySourceFileDeleteModal from '@/Layouts/Components/MoneySourceFileDeleteModal.vue'
import MoneySourceCategoriesModal from '@/Layouts/Components/MoneySourceCategoriesModal.vue'
import LinkProjectsToMoneySourcesComponent from '@/Layouts/Components/LinkProjectsToMoneySourcesComponent.vue'
import EditMoneySourceUsersModal from '@/Layouts/Components/EditMoneySourceUsersModal.vue'
import { is } from 'laravel-permission-to-vuejs'

// Icons (Tabler)
import { IconChevronDown, IconUpload, IconDownload, IconCircleX, IconEdit } from '@tabler/icons-vue'

/** Props */
const props = defineProps<{
    users: any[],
    tasks: any[],
    money_source: any,
    moneySourceFiles: any,
    linkedProjects: any[],
    competent: number[],
    writeAccess: number[],
    moneySourceCategories: any[],
    positionSumsPerProject: Record<number, number>,
    first_project_budget_tab_id: number | string
}>()

/** Page */
const page = usePage()

/** Permissions */
const canManage = computed(() => {
    const uid = page.props.auth.user.id
    return is('artwork admin') || props.writeAccess.includes(uid) || props.competent.includes(uid)
})

/** UI State */
const showMoneySourceTaskModal = ref(false)
const showFileUploadModal = ref(false)
const showFileEditModal = ref(false)
const showFileDeleteModal = ref(false)
const showMoneySourceFiles = ref(false)
const moneySourceFileToEdit = ref<any | null>(null)
const moneySourceFileToDelete = ref<any | null>(null)
const showLinkProjectsModal = ref(false)
const showEditUsersModal = ref(false)
const showLinkedProjects = ref(false)
const showMoneySourceCategories = ref(false)
const showMoneySourceCategoriesModal = ref(false)

/** Derived lists */
const competentUsers = computed(() => (props.users ?? []).filter(u => u.pivot?.competent))
const writeUsers = computed(() => (props.users ?? []).filter(u => u.pivot?.write_access && !u.pivot?.competent))

/** Helpers */
function formatDate(date: string) {
    const d = new Date(date)
    return d.toLocaleString('de-DE', { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
function isOverdue(date: string) {
    try {
        return new Date(date).getTime() < Date.now()
    } catch { return false }
}
function formatCurrency(val: number) {
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 }).format(val || 0)
}

/** File Actions */
function downloadMoneySourceFile(file: any) {
    const link = document.createElement('a')
    // route helper available globally in your stack
    // @ts-ignore
    link.href = route('money_sources_download_file', { money_source_file: file })
    link.target = '_blank'
    link.click()
}
function openFileUploadModal() { showFileUploadModal.value = true }
function openFileEditModal(file: any) { moneySourceFileToEdit.value = file; showFileEditModal.value = true }
function openFileDeleteModal(file: any) { moneySourceFileToDelete.value = file; showFileDeleteModal.value = true }
function closeFileUploadModal() { showFileUploadModal.value = false }
function closeFileEditModal() { showFileEditModal.value = false; moneySourceFileToEdit.value = null }
function closeFileDeleteModal() { showFileDeleteModal.value = false; moneySourceFileToDelete.value = null }

/** Categories Modal */
function openMoneySourceCategoriesModal() { showMoneySourceCategoriesModal.value = true }
function closeMoneySourceCategoriesModal() { showMoneySourceCategoriesModal.value = false }

/** Users / Projects */
function openEditUsersModal() { showEditUsersModal.value = true }
function onCloseEditUsersModal() { showEditUsersModal.value = false }
function openLinkProjectsModal() { showLinkProjectsModal.value = true }
function onCloseLinkProjectsModal() { showLinkProjectsModal.value = false }

/** Tasks */
function openAddMoneySourceTask() { showMoneySourceTaskModal.value = true }
function onCreateMoneySourceTask() { showMoneySourceTaskModal.value = false }

function updateTask(task: any) {
    if (!canManage.value) return
    if (!task.done) {
        router.patch(route('money_source.task.done', { moneySourceTask: task.id }), {}, { preserveState: true })
    } else {
        router.patch(route('money_source.task.undone', { moneySourceTask: task.id }), {}, { preserveState: true })
    }
}
</script>

<style scoped>
/* keine @apply; nur Utilities im Template */
</style>
