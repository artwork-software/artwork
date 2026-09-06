<template>
    <!-- Hilfe & Legende des Dienstplans: Info-Icon in der Funktionsleiste öffnet ein seitlich
         einfahrendes Panel (kein Dauer-Banner, kein Platzverbrauch im Raster). Drei Abschnitte:
         Legende (Zell-Marker), Bedienung (Ansichten, Zuweisen, Multi-Edit, Filter, Zoom) und
         Ablauf (Planen → Festschreiben → Freigabe → Änderungen → Regelverstöße). -->
    <button type="button" class="ui-button" :title="$t('Help & legend')" :aria-label="$t('Help & legend')" @click="openPanel">
        <IconInfoCircle class="h-5 w-5" stroke-width="1.5" />
    </button>

    <Teleport to="body">
        <div v-if="visible || closing" class="fixed inset-0 z-[110] flex justify-end" role="dialog" aria-modal="true" :aria-label="$t('Help & legend')">
            <!-- Backdrop -->
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="visible" class="absolute inset-0 bg-black/20" @click="closePanel"></div>
            </Transition>

            <Transition
                enter-active-class="transition-transform duration-200 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-150 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
                @after-leave="closing = false"
            >
                <aside
                    v-if="visible"
                    class="relative z-10 h-full w-[26rem] max-w-[92vw] bg-surface shadow-2xl border-l border-border flex flex-col"
                >
                    <header class="flex items-start gap-3 border-b border-border px-4 py-3 shrink-0">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-text">{{ $t('Help & legend') }}</h3>
                            <p class="text-[11px] text-text-subtle mt-0.5">{{ $t('Duty rosters') }}</p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-md p-1 text-text-subtle hover:bg-surface-sunken hover:text-text"
                            :aria-label="$t('Close')"
                            @click="closePanel"
                        >
                            <IconX class="size-5" stroke-width="1.5" />
                        </button>
                    </header>

                    <!-- Abschnitts-Tabs -->
                    <div class="px-4 pt-3 shrink-0">
                        <div class="grid grid-cols-3 gap-1 rounded-lg bg-surface-sunken p-1" role="tablist">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                role="tab"
                                :aria-selected="activeTab === tab.key"
                                class="rounded-md px-2 py-1.5 text-xs font-medium transition-colors"
                                :class="activeTab === tab.key ? 'bg-surface text-text shadow-sm' : 'text-text-subtle hover:text-text'"
                                @click="setTab(tab.key)"
                            >
                                {{ $t(tab.label) }}
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-4 py-4 text-sm text-text space-y-4">
                        <!-- ===================== Legende ===================== -->
                        <template v-if="activeTab === 'legend'">
                            <p class="text-xs text-text-subtle">{{ $t('Markers in the cells of the duty roster. Click a marker to open the day and process it.') }}</p>

                            <section class="space-y-2">
                                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle">{{ $t('Shifts') }}</h4>
                                <ul class="space-y-2.5">
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0">
                                            <PropertyIcon name="IconLock" class="h-3.5 w-3.5 text-text" :stroke-width="2" />
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Committed') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('The shift is locked. Assignments can only be changed by planners and are logged as changes after commitment.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0">
                                            <PropertyIcon name="IconGitPullRequest" class="h-3.5 w-3.5 text-text" :stroke-width="2" />
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Approval requested') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('The shift is part of a pending approval request and will be committed once an approver releases it.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0">
                                            <PropertyIcon name="IconClockEdit" class="h-3.5 w-3.5 text-warning" :stroke-width="2" />
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Changed after commitment') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('A committed shift was changed afterwards. The change appears in the change list until it is acknowledged.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0 text-[10px] font-semibold text-warning">3/2</span>
                                        <span>
                                            <span class="font-medium">{{ $t('Overbooked') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('More people are assigned to a slot than planned. Overbooked people are marked as such and count in addition to the regular slots.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center gap-1 shrink-0">
                                            <span class="inline-block h-2 w-2 rounded-full bg-danger"></span>
                                            <span class="inline-block h-2 w-2 rounded-full bg-warning"></span>
                                            <span class="inline-block h-2 w-2 rounded-full bg-success"></span>
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Staffing') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Red: no one assigned yet, orange: partially staffed, green: fully staffed.') }}</span>
                                        </span>
                                    </li>
                                </ul>
                            </section>

                            <section class="space-y-2">
                                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle">{{ $t('People') }}</h4>
                                <ul class="space-y-2.5">
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center gap-0.5 shrink-0">
                                            <svg class="h-3.5 w-3.5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-[9px] font-semibold text-warning">2</span>
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Open rule violation') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Warning triangle in the colour of the rule; the number shows how many violations are open on this day.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0">
                                            <span class="inline-flex h-4 items-center gap-0.5 rounded-full bg-surface-sunken text-text-subtle px-1">
                                                <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-[9px] font-semibold">1</span>
                                            </span>
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Processed rule violation') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Substitute days off have been booked; ignored violations are not shown in the cell.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0">
                                            <svg class="h-3.5 w-3.5 text-danger" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Assigned but not available') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Red for committed shifts, otherwise orange.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0 text-[10px] font-semibold">
                                            <span class="underline decoration-success decoration-2">10</span><span class="mx-px">/</span><span class="underline decoration-danger decoration-2">18</span>
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Acceptance / refusal') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Green underline: the person accepted the shift, red underline: the person declined. Details and comment in the hover text.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0">
                                            <PropertyIcon name="IconBriefcase" class="h-3.5 w-3.5 text-text-subtle" :stroke-width="1.5" />
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Day service') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Chip with icon and name in the person cell: the person has a day service on this day (e.g. workshop, office).') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0 text-[10px] font-semibold text-special-teal">½</span>
                                        <span>
                                            <span class="font-medium">{{ $t('Substitute day off / half day off') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Teal text in the person cell; half days show morning or afternoon. Booked from a rule violation or manually.') }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-4 w-6 items-center justify-center shrink-0">
                                            <span class="inline-flex h-4 items-center rounded-full bg-warning-surface text-warning border border-warning-border px-1">
                                                <PropertyIcon name="IconCalendarStar" class="h-3 w-3" />
                                            </span>
                                        </span>
                                        <span>
                                            <span class="font-medium">{{ $t('Special Day') }}</span>
                                            <span class="block text-xs text-text-subtle">{{ $t('Holiday marked as special day: without work the daily target is reduced (if the contract has the special day rule active).') }}</span>
                                        </span>
                                    </li>
                                </ul>
                            </section>
                        </template>

                        <!-- ===================== Bedienung ===================== -->
                        <template v-else-if="activeTab === 'operation'">
                            <section v-for="block in operationBlocks" :key="block.title" class="space-y-1">
                                <h4 class="flex items-center gap-1.5 text-xs font-semibold text-text">
                                    <PropertyIcon :name="block.icon" class="h-4 w-4 text-accent-600" :stroke-width="1.5" />
                                    {{ $t(block.title) }}
                                </h4>
                                <p class="text-xs text-text-subtle">{{ $t(block.text) }}</p>
                            </section>
                        </template>

                        <!-- ===================== Ablauf ===================== -->
                        <template v-else>
                            <ol class="space-y-3">
                                <li v-for="(step, index) in workflowSteps" :key="step.title" class="flex gap-3">
                                    <span class="flex items-center justify-center size-6 rounded-full bg-accent-600 text-white text-xs font-bold shrink-0">{{ index + 1 }}</span>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-semibold text-text">{{ $t(step.title) }}</h4>
                                        <p class="text-xs text-text-subtle mt-0.5">{{ $t(step.text) }}</p>
                                        <div v-if="step.links?.length" class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1">
                                            <Link
                                                v-for="link in step.links"
                                                :key="link.href"
                                                :href="link.href"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-accent-600 hover:text-accent-700"
                                            >
                                                <PropertyIcon name="IconArrowRight" class="h-3.5 w-3.5" />
                                                {{ $t(link.label) }}
                                            </Link>
                                        </div>
                                    </div>
                                </li>
                            </ol>
                        </template>
                    </div>
                </aside>
            </Transition>
        </div>
    </Teleport>
</template>

<script setup>
import {computed, onBeforeUnmount, ref, watch} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import {can, is} from 'laravel-permission-to-vuejs'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import {IconInfoCircle, IconX} from '@tabler/icons-vue'

const STORAGE_KEY = 'shift-plan-help.tab'

const visible = ref(false)
const closing = ref(false)
const activeTab = ref('legend')

const tabs = [
    {key: 'legend', label: 'Legend'},
    {key: 'operation', label: 'Operation'},
    {key: 'workflow', label: 'Process'},
]

const page = usePage()
const workflowEnabled = computed(() => !!page.props?.shiftCommitWorkflow)
const isAdmin = () => is('artwork admin')
const canOpenShiftSettings = computed(() => isAdmin() || can('shift.settings_view_edit'))
const canOpenRooms = computed(() => isAdmin() || can('create, delete and update rooms'))
const canOpenSpecialDays = computed(() => isAdmin() || can('change event settings') || can('can plan shifts'))
const canSeeChangeList = computed(() => !!page.props?.canSeeShiftPlanChangeList)
const canSeeRequests = computed(() => !!page.props?.canSeeShiftPlanRequestedPlans)

const operationBlocks = [
    {
        icon: 'IconLayoutColumns',
        title: 'Week, day and list',
        text: 'The week view shows rooms as rows and days as columns, the day view lists the rooms per day as a timeline and the list view shows shifts as a table. Switch with the calendar icon in the function bar.',
    },
    {
        icon: 'IconCirclePlus',
        title: 'Create a shift',
        text: 'Hover over a room cell and click the plus icon, or use the template icon to create several shifts at once from shift templates.',
    },
    {
        icon: 'IconHandGrab',
        title: 'Assign by drag & drop',
        text: 'Drag a person from the people overview at the bottom onto a shift. If several functions are open, you choose the slot; full slots can be overbooked if the setting allows it.',
    },
    {
        icon: 'IconPencil',
        title: 'Multi-edit: calendar vs. people',
        text: 'Multi-edit in the calendar selects several room days to create or delete shifts together. Multi-edit in the people overview selects one person and assigns or removes them from several shifts by ticking them.',
    },
    {
        icon: 'IconFocus2',
        title: 'Highlight',
        text: 'Highlighting dims everything except the selected person or shift so you can see at a glance where someone is planned.',
    },
    {
        icon: 'IconFilter',
        title: 'Filter vs. display settings',
        text: 'Filters restrict which rooms, crafts and people are loaded. Display settings only change how the loaded data is shown (e.g. functions, notes, project groups) and are remembered per person.',
    },
    {
        icon: 'IconZoomIn',
        title: 'Zoom',
        text: 'Below 100% zoom, shift cards become compact pills showing time, craft and staffing. Click a pill to open the shift; for drag & drop, zoom back to 100%.',
    },
]

const workflowSteps = computed(() => {
    const steps = [
        {
            title: 'Planning',
            text: 'Create shifts, define the required functions per shift and assign people. Rule violations are checked while you plan.',
            links: canOpenShiftSettings.value ? [{href: route('shift.settings'), label: 'Shift settings'}] : [],
        },
        {
            title: 'Commit',
            text: workflowEnabled.value
                ? 'With the approval workflow active, planners submit a calendar week per craft for approval instead of committing directly.'
                : 'Committing locks all shifts of a calendar week per craft. Committed shifts show a lock and are visible to the people in their own roster.',
            links: [],
        },
    ]

    if (workflowEnabled.value) {
        steps.push({
            title: 'Approval workflow',
            text: 'Approvers review the submitted plan and release or reject it. On release the shifts are committed; the requesting planners are notified.',
            links: canSeeRequests.value ? [{href: route('shifts.approvals.requests'), label: 'Requested duty rosters'}] : [],
        })
    }

    steps.push({
        title: 'Changes after commitment',
        text: 'Changes to committed shifts are logged and listed in the change list until they are acknowledged. The affected people are notified.',
        links: canSeeChangeList.value ? [{href: route('shifts.approvals.changes'), label: 'Shift plan Change list'}] : [],
    })

    steps.push({
        title: 'Rule violations and substitute days off',
        text: 'Violations of rest time, weekly hours or similar rules appear as a warning triangle at the person. Open the day to book a substitute day off, ignore the violation with a reason or adjust the plan.',
        links: [
            ...(canOpenSpecialDays.value ? [{href: route('holiday.management'), label: 'Manage special days'}] : []),
            ...(canOpenRooms.value ? [{href: route('areas.management'), label: 'Room management'}] : []),
        ],
    })

    return steps
})

const setTab = (key) => {
    activeTab.value = key
    try {
        localStorage.setItem(STORAGE_KEY, key)
    } catch (e) {
        // localStorage nicht verfügbar (z.B. Privatmodus) — Tab wird nicht gemerkt
    }
}

const onKeydown = (event) => {
    if (event.key === 'Escape') closePanel()
}

const openPanel = () => {
    try {
        const remembered = localStorage.getItem(STORAGE_KEY)
        if (remembered && tabs.some((t) => t.key === remembered)) activeTab.value = remembered
    } catch (e) {
        // ignore
    }
    visible.value = true
}

const closePanel = () => {
    if (!visible.value) return
    closing.value = true
    visible.value = false
}

watch(visible, (open) => {
    if (open) document.addEventListener('keydown', onKeydown)
    else document.removeEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>
