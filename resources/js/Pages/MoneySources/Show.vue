<template>
    <AppLayout :title="moneySource.name">
        <div class="artwork-container">
            <!-- Header / Meta / Actions -->
            <header class="rounded-2xl border border-gray-100 bg-white shadow-sm px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <!-- Title & Meta -->
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <h1 class="text-xl lg:text-2xl font-semibold tracking-tight truncate">{{ moneySource.name }}</h1>
                            <span v-if="moneySource.is_group" class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] text-gray-700">
                                {{ $t('Gruppe') }}
                              </span>
                                            <span
                                                v-if="moneySource.hasSentExpirationReminderNotification"
                                                class="inline-flex items-center rounded-md border border-red-200 bg-red-50/70 px-2 py-0.5 text-[11px] text-red-700"
                                            >
                                {{ $t('Ablaufwarnung gesendet') }}
                              </span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-600">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">{{ $t('Erstellt von') }}</span>
                                <UserPopoverTooltip
                                    v-if="moneySource.creator"
                                    :user="moneySource.creator"
                                    :id="moneySource.creator?.id"
                                    :height="6"
                                    :width="6"
                                />
                            </div>

                            <div v-if="moneySource.group_id" class="flex items-center gap-2">
                                <img src="/Svgs/IconSvgs/icon_group_red.svg" class="h-4 w-4" alt="groupIcon" />
                                <span>{{ $t('Gehört zu') }}</span>
                                <Link :href="getEditHref(moneySource.group_id)" class="text-indigo-700 hover:text-indigo-800">
                                    {{ moneySource.moneySourceGroup?.name }}
                                </Link>
                            </div>

                            <div v-if="moneySource.source_name" class="flex items-center gap-2">
                                <span class="text-gray-500">{{ $t('Quelle') }}:</span>
                                <span class="font-medium">{{ moneySource.source_name }}</span>
                            </div>

                            <button
                                class="inline-flex items-center gap-1 text-indigo-700 hover:text-indigo-800"
                                @click="openMoneySourceHistoryModal"
                            >
                                <IconChevronRight class="h-3 w-3" />
                                {{ $t('Historie ansehen') }}
                            </button>
                        </div>

                        <p v-if="moneySource.description" class="mt-3 text-sm text-gray-700 max-w-3xl">
                            {{ moneySource.description }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-600">
                          <span v-if="moneySource.start_date && moneySource.end_date" class="ui-button-small">
                            {{ $t('Laufzeit') }}:
                            <strong class="ml-1">{{ formatDate(moneySource.start_date) }} – {{ formatDate(moneySource.end_date) }}</strong>
                          </span>
                                        <span
                                            v-if="moneySource.funding_start_date && moneySource.funding_end_date"
                                            :class="['ui-button-small', moneySource.hasSentExpirationReminderNotification ? 'border-red-200 text-red-700 bg-red-50/70' : '']"
                                        >
                            {{ $t('Förderzeitraum') }}:
                            <strong class="ml-1">{{ formatDate(moneySource.funding_start_date) }} – {{ formatDate(moneySource.funding_end_date) }}</strong>
                          </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2">
                        <BaseMenu  has-no-offset white-menu-background>
                            <BaseMenuItem white-menu-background title="Edit" :icon="IconEdit"  @click="openEditMoneySourceModal" />
                            <BaseMenuItem white-menu-background title="Duplicate" :icon="IconCopy"  @click="duplicateMoneySource(moneySource)" />
                            <BaseMenuItem white-menu-background  title="Delete" :icon="IconTrash" @click="openDeleteSourceModal(moneySource)" />
                        </BaseMenu>
                    </div>
                </div>
            </header>

            <!-- KPI Row -->
            <section class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Ursprüngliches Volumen') }}</p>
                    <p class="mt-2 text-3xl font-semibold">{{ toCurrency(moneySource.amount) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Noch verfügbar') }}</p>
                            <p
                                class="mt-2 text-3xl font-semibold"
                                :class="moneySource.amount_available <= 0 || moneySource.hasSentThresholdReminderNotification ? 'text-red-600' : ''"
                            >
                                {{ toCurrency(moneySource.amount_available) }}
                            </p>
                        </div>
                        <span
                            v-if="moneySource.hasSentThresholdReminderNotification"
                            class="inline-flex items-center rounded-md border border-red-200 bg-red-50/70 px-2 py-0.5 text-[11px] text-red-700"
                        >
              {{ $t('Schwelle erreicht') }}
            </span>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-2 rounded-full bg-emerald-500"
                                :style="{ width: utilizationPct + '%' }"
                            />
                        </div>
                        <p class="mt-1 text-[11px] text-gray-500">
                            {{ $t('Verbraucht') }}: {{ utilizationPct }}%
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Förderzeitraum') }}</p>
                    <p class="mt-2 text-sm text-gray-700" v-if="moneySource.funding_start_date && moneySource.funding_end_date">
                        {{ formatDate(moneySource.funding_start_date) }} – {{ formatDate(moneySource.funding_end_date) }}
                    </p>
                    <p class="mt-2 text-sm text-gray-500" v-else>—</p>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Laufzeit') }}</p>
                    <p class="mt-2 text-sm text-gray-700" v-if="moneySource.start_date && moneySource.end_date">
                        {{ formatDate(moneySource.start_date) }} – {{ formatDate(moneySource.end_date) }}
                    </p>
                    <p class="mt-2 text-sm text-gray-500" v-else>—</p>
                </div>
            </section>

            <!-- Linked Positions -->
            <section class="mt-8 rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="px-5 pt-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-base font-semibold">{{ $t('Verknüpfte Positionen') }}</h2>
                            <p class="text-xs text-gray-500">{{ $t('Alle Buchungen zu dieser Geldquelle, gefiltert nach Projekt') }}</p>
                        </div>

                        <!-- Project Filter (Listbox) -->
                        <Listbox v-model="wantedProject" as="div" class="relative w-full md:w-72">
                            <ListboxButton class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-left hover:bg-gray-50 inline-flex items-center justify-between">
                <span class="truncate">
                  {{ wantedProject ? wantedProject.name : $t('Alle Projekte') }}
                </span>
                                <IconChevronDown class="h-4 w-4 text-gray-500" />
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute right-0 mt-2 max-h-60 w-full overflow-auto rounded-xl border border-gray-100 bg-white shadow-lg p-1 z-10">
                                    <ListboxOption as="template" :value="null" v-slot="{ active, selected }">
                                        <li :class="['px-3 py-2 rounded-lg text-sm cursor-pointer', active ? 'bg-gray-50' : '']">
                                            <div class="flex items-center justify-between">
                                                <span class="truncate">{{ $t('Alle Projekte') }}</span>
                                                <IconCheck v-if="selected" class="size-4 text-emerald-600" />
                                            </div>
                                        </li>
                                    </ListboxOption>
                                    <ListboxOption
                                        v-for="project in linkedProjects"
                                        :key="project.id"
                                        :value="project"
                                        as="template"
                                        v-slot="{ active, selected }"
                                    >
                                        <li :class="['px-3 py-2 rounded-lg text-sm cursor-pointer', active ? 'bg-gray-50' : '']">
                                            <div class="flex items-center justify-between">
                                                <span class="truncate">{{ project.name }}</span>
                                                <IconCheck v-if="selected" class="size-4 text-emerald-600" />
                                            </div>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                </div>

                <!-- Project sums overview -->
                <div class="px-5 mt-4">
                    <div v-if="Object.keys(positionSumsPerProject).length" class="rounded-xl border border-gray-100 bg-white p-4">
                        <h3 class="text-sm font-semibold mb-3">{{ $t('Summen je Projekt') }}</h3>
                        <div class="space-y-3">
                            <div v-for="(sum, pid) in positionSumsPerProject" :key="pid" class="flex items-center gap-3">
                                <div class="w-48 truncate text-xs text-gray-600">
                                    {{ projectNameById(Number(pid)) }}
                                </div>
                                <div class="flex-1">
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                        <div
                                            class="h-2 rounded-full bg-sky-500"
                                            :style="{ width: barWidth(sum) + '%' }"
                                        />
                                    </div>
                                </div>
                                <div class="w-28 text-right text-xs font-medium">
                                    {{ toCurrency(sum) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Positions list -->
                <div class="px-5 pb-5 mt-5">
                    <div v-if="filteredPositions.length" class="grid grid-cols-1 gap-3">
                        <article
                            v-for="position in filteredPositions"
                            :key="position.id"
                            class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                        >
                            <div class="flex">
                                <!-- Color stripe -->
                                <div
                                    class="w-1.5"
                                    :class="position.type === 'COST' ? 'bg-red-500' : 'bg-emerald-500'"
                                />
                                <div class="flex-1 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px]"
                            :class="position.type === 'COST' ? 'border-red-200 text-red-700 bg-red-50/70' : 'border-emerald-200 text-emerald-700 bg-emerald-50/70'"
                        >
                          {{ position.type === 'COST' ? $t('Ausgabe') : $t('Einnahme') }}
                        </span>
                                                <span class="text-xs text-gray-500">{{ position.created_at }}</span>
                                            </div>

                                            <div class="mt-1 text-sm text-gray-600 flex flex-wrap items-center gap-2">
                                                <Link
                                                    :href="getProjectHref(position.project)"
                                                    class="inline-flex items-center gap-1 text-indigo-700 hover:text-indigo-800"
                                                >
                                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                        <path stroke-width="1.5" d="M4 7h16M4 12h10M4 17h16"/>
                                                    </svg>
                                                    {{ position.project?.name }}
                                                </Link>
                                                <span v-if="position.mainPositionName" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] text-gray-600">
                          {{ position.mainPositionName }}
                        </span>
                                                <span v-if="position.subPositionName?.length" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] text-gray-600">
                          {{ position.subPositionName }}
                        </span>
                                                <span v-if="position.column_name" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] text-gray-600">
                          {{ position.column_name }}
                        </span>
                                            </div>
                                        </div>

                                        <div
                                            class="text-right text-2xl font-semibold"
                                            :class="position.type === 'COST' ? 'text-red-600' : 'text-emerald-600'"
                                        >
                                            <span v-if="position.type === 'EARNING'">+</span><span v-else>-</span>
                                            {{ toCurrency(position.value) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center">
                        <p class="text-sm text-gray-600">{{ $t('Keine Positionen für den aktuellen Filter gefunden.') }}</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Modals & Sidenav -->
        <edit-money-source-component
            v-if="showEditMoneySourceModal"
            @closed="onEditMoneySourceModalClose"
            :moneySource="moneySource"
            :moneySources="moneySources"
            :moneySourceGroups="moneySourceGroups"
        />
        <BaseSidenav :show="showSide" @toggle="showSide = !showSide">
            <MoneySourceSidenav
                :users="moneySource.users"
                :tasks="moneySource.tasks"
                :money_source="moneySource"
                :money-source-files="moneySource.money_source_files"
                :linked-projects="linkedProjects"
                :competent="competent_member"
                :write-access="access_member"
                :money-source-categories="moneySourceCategories"
                :positionSumsPerProject="positionSumsPerProject"
                :first_project_budget_tab_id="first_project_budget_tab_id"
            />
        </BaseSidenav>

        <confirm-delete-modal
            v-if="showDeleteSourceModal"
            :title="$t('Förderquelle/-gruppe löschen')"
            :description="$t('Möchtest du die Quelle/Gruppe {0} wirklich löschen?', [sourceToDelete?.name])"
            @closed="afterConfirm(false)"
            @delete="afterConfirm(true)"
        />

        <MoneySourceHistoryComponent
            v-if="showMoneySourceHistory"
            :history="moneySource.history"
            @closed="closeMoneySourceHistoryModal"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EditMoneySourceComponent from '@/Layouts/Components/EditMoneySourceComponent.vue'
import BaseSidenav from '@/Layouts/Components/BaseSidenav.vue'
import MoneySourceSidenav from '@/Layouts/Components/MoneySourceSidenav.vue'
import MoneySourceHistoryComponent from '@/Layouts/Components/MoneySourceHistoryComponent.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'

// Icons
import {
    IconChevronRight,
    IconChevronDown,
    IconEdit,
    IconCopy,
    IconTrash,
    IconCheck,
    IconFolderOpen
} from '@tabler/icons-vue'
import { is, can } from 'laravel-permission-to-vuejs'
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

/** Props */
const props = defineProps<{
    moneySource: any,
    moneySourceGroups: any[],
    moneySources: any[],
    moneySourceCategories: any[],
    projects: any[],
    linkedProjects: any[],
    first_project_budget_tab_id: number | string
}>()

/** Page / user */
const page = usePage()
const authUser = computed(() => page.props.auth.user)

/** State */
const showEditMoneySourceModal = ref(false)
const showDeleteSourceModal = ref(false)
const showMoneySourceHistory = ref(false)
const sourceToDelete = ref<any | null>(null)
const wantedProject = ref<any | null>(null)
const showSide = ref(false)

/** Permissions */
const access_member = computed<number[]>(() => {
    const ids: number[] = []
    ;(props.moneySource?.users ?? []).forEach((u: any) => { if (u.pivot?.write_access) ids.push(u.id) })
    return ids
})
const competent_member = computed<number[]>(() => {
    const ids: number[] = []
    ;(props.moneySource?.users ?? []).forEach((u: any) => { if (u.pivot?.competent) ids.push(u.id) })
    return ids
})
const canManage = computed(() =>
    is('artwork admin') ||
    access_member.value.includes(authUser.value.id) ||
    competent_member.value.includes(authUser.value.id) ||
    can('view edit add money_sources') ||
    can('can edit and delete money sources')
)

/** Formatters */
function toCurrency(val: number | string) {
    const num = typeof val === 'string' ? Number(val) : val
    if (Number.isNaN(num as number)) return '—'
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 }).format(num as number)
}
function formatDate(isoDate: string) {
    // erwartet YYYY-MM-DD
    if (!isoDate) return '—'
    return `${isoDate.substring(8,10)}.${isoDate.substring(5,7)}.${isoDate.substring(0,4)}`
}

/** KPIs */
const utilizationPct = computed(() => {
    const total = Number(props.moneySource?.amount || 0)
    const avail = Number(props.moneySource?.amount_available || 0)
    if (total <= 0) return 0
    const used = Math.max(0, total - avail)
    return Math.min(100, Math.round((used / total) * 100))
})

/** Filtering */
const isGroup = computed(() => !!props.moneySource?.is_group)
const allPositions = computed<any[]>(() => isGroup.value ? (props.moneySource?.subMoneySourcePositions ?? []) : (props.moneySource?.positions ?? []))
const filteredPositions = computed<any[]>(() => {
    const pid = wantedProject.value?.id
    if (!pid) return allPositions.value
    return allPositions.value.filter(p => p.project?.id === pid)
})

/** Sums per project (over current filter) */
const positionSumsPerProject = computed<Record<number, number>>(() => {
    const sums: Record<number, number> = {}
    filteredPositions.value.forEach(p => {
        const pid = p.project?.id
        const val = parseFloat(p.value) || 0
        if (pid == null) return
        sums[pid] = (sums[pid] ?? 0) + val
    })
    return sums
})

function projectNameById(id: number) {
    const list = props.linkedProjects ?? []
    return list.find(p => p.id === id)?.name ?? `#${id}`
}

/** Relative bar widths for project sums */
function barWidth(sum: number) {
    const base = Number(props.moneySource?.amount) || 0
    if (base <= 0) return 0
    return Math.min(100, Math.round((Math.abs(sum) / base) * 100))
}

/** Routes */
function getProjectHref(project: any) {
    return route('projects.tab', { project: project.id, projectTab: props.first_project_budget_tab_id })
}
function getEditHref(moneySourceId: number | string) {
    return route('money_sources.show', { moneySource: moneySourceId })
}

/** Actions */
function openEditMoneySourceModal() { showEditMoneySourceModal.value = true }
function onEditMoneySourceModalClose() { showEditMoneySourceModal.value = false }

function duplicateMoneySource(ms: any) {
    // Inertia-Post auf deinen bestehenden Endpoint
    // @ts-ignore
    window?.Inertia?.post?.(`/money_sources/${ms.id}/duplicate`)
}

function openDeleteSourceModal(ms: any) {
    sourceToDelete.value = ms
    showDeleteSourceModal.value = true
}
function deleteMoneySource(ms: any) {
    // @ts-ignore
    window?.Inertia?.delete?.(`/money_sources/${ms.id}`)
    showDeleteSourceModal.value = false
}
function afterConfirm(confirm: boolean) {
    if (!confirm) {
        showDeleteSourceModal.value = false
        return
    }
    if (sourceToDelete.value) deleteMoneySource(sourceToDelete.value)
}
function openMoneySourceHistoryModal() { showMoneySourceHistory.value = true }
function closeMoneySourceHistoryModal() { showMoneySourceHistory.value = false }
</script>

<style scoped>
/* keine @apply; minimaler Scope falls nötig */
</style>
