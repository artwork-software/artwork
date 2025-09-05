<template>
    <div class="bg-backgroundGray mt-6 pb-6">
        <div class="ml-14 pt-3 pr-14">
            <!-- Sticky Toolbar -->
            <div class="stickyHeader">
                <div
                    class="flex items-center justify-between gap-6 rounded-xl border border-zinc-200 bg-white/80 px-4 py-3 shadow-sm ring-1 ring-black/5 backdrop-blur"
                >
                    <!-- Left: Commit Switch + Conflicts -->
                    <div class="flex w-full items-center justify-between gap-6">
                        <!-- Commit Switch -->
                        <SwitchGroup
                            v-if="loadedProjectInformation['ShiftTab']?.events_with_relevant?.length > 0 && checkCommitted() && (can('can commit shifts') || isAdmin)"
                            as="div"
                            class="flex items-center gap-2"
                        >
                            <Switch
                                v-model="commitSwitchProxy"
                                @update:modelValue="updateCommitmentOfShifts"
                                :class="[
                  !hasUncommittedShift ? 'bg-artwork-buttons-create' : 'bg-zinc-300',
                  'relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-hover focus:ring-offset-2'
                ]"
                            >
                <span
                    aria-hidden="true"
                    :class="[
                    !hasUncommittedShift ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                  ]"
                />
                            </Switch>
                            <SwitchLabel as="span" class="ml-2 text-sm">
                                <span class="font-medium text-zinc-900">{{ $t('Fixed') }}</span>
                            </SwitchLabel>
                        </SwitchGroup>

                        <!-- Conflicts -->
                        <div v-if="conflictMessage.length > 0" class="text-error">
                            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.401 3.007c1.155-2.01 4.043-2.01 5.198 0l7.19 12.52c1.157 2.015-.289 4.53-2.6 4.53H4.81c-2.31 0-3.757-2.515-2.6-4.53l7.19-12.52zM12 8a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0112 8zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                  </svg>
                  {{ $t('Shift conflict') }}
                </span>

                                <div class="flex flex-wrap items-center gap-2 text-sm text-red-700">
                  <span
                      v-for="(conflict, idx) in conflictMessage"
                      :key="idx"
                      class="inline-flex items-center rounded-full border border-red-200 bg-white px-2 py-0.5 text-xs"
                  >
                    {{ dayjs(conflict.date).format('DD.MM.YYYY') }}, {{ conflict.abbreviation }}
                  </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Sort + Users -->
                    <div class="flex items-center gap-3">
                        <!-- Sort Menu -->
                        <BaseMenu show-sort-icon dots-size="w-6 h-6" menu-width="w-fit">
                            <div class="flex items-center justify-end py-1">
                <span class="pr-4 pt-0.5 xxsLight cursor-pointer text-right w-full" @click="resetSort">
                  {{ $t('Reset') }}
                </span>
                            </div>
                            <MenuItem
                                v-for="type in loadedProjectInformation['ShiftTab'].shift_sort_types"
                                :key="type"
                                v-slot="{ active }"
                            >
                                <div
                                    @click="applySort(type)"
                                    :class="[
                    active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary',
                    'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased'
                  ]"
                                >
                                    {{ getSortEnumTranslation(type) }}
                                    <IconCheck class="ml-3 h-5 w-5" v-if="$page.props.auth.user.sort_type_shift_tab === type" />
                                </div>
                            </MenuItem>
                        </BaseMenu>

                        <!-- Users Button -->
                        <div
                            v-if="can('can plan shifts') || isAdmin"
                            ref="userWindowButton"
                            @click="openUserWindow"
                        >
                            <ToolTipComponent icon="IconUsers" :tooltip-text="$t('Users')" direction="left" :stroke="1.5" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Window (Draggable) -->
            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <div
                    v-show="userWindow"
                    ref="containerRef"
                    class="z-50 absolute right-10 w-80 origin-top-right rounded-xl border border-zinc-200 bg-primary/95 px-4 py-3 shadow-2xl ring-1 ring-black/10 backdrop-blur"
                    @mousedown.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <button class="flex items-center gap-2 text-white" @click="openFilter = !openFilter">
                            <IconFilter class="h-5 w-5 text-white" />
                            <IconChevronDown v-if="openFilter" class="h-5 w-5 text-white" />
                            <IconChevronUp v-else class="h-5 w-5 text-white" />
                        </button>

                        <!-- Compact Mode -->
                        <div class="flex items-center pl-2 py-1">
                            <Switch
                                @click="toggleCompactMode"
                                :class="[
                  $page.props.auth.user.compact_mode ? 'bg-artwork-buttons-create' : 'bg-darkGrayBg',
                  'relative inline-flex h-5 w-10 cursor-pointer rounded-full border-2 border-transparent transition-colors ease-in-out duration-200 focus:outline-none'
                ]"
                            >
                <span
                    aria-hidden="true"
                    :class="[
                    $page.props.auth.user.compact_mode ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200'
                  ]"
                />
                            </Switch>
                            <div
                                :class="[
                  $page.props.auth.user.compact_mode ? 'xsLight text-secondaryHover' : 'xsLight',
                  'ml-2 text-white'
                ]"
                            >
                                {{ $t('Compact Mode') }}
                            </div>
                        </div>

                        <button class="rounded-lg p-1 hover:bg-white/10" @click="userWindow = false">
                            <IconX class="h-5 w-5 text-white" />
                        </button>
                    </div>

                    <!-- Filters -->
                    <div v-if="openFilter" class="mt-4">
                        <div class="mb-4 space-y-3">
                            <label class="flex items-center gap-2">
                                <input v-model="showIntern" type="checkbox" class="input-checklist-dark" />
                                <span :class="[showIntern ? 'xsWhiteBold' : 'xsLight', 'text-white']">
                  {{ $t('Internal employees') }}
                </span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input v-model="showExtern" type="checkbox" class="input-checklist-dark" />
                                <span :class="[showExtern ? 'xsWhiteBold' : 'xsLight', 'text-white']">
                  {{ $t('External employees') }}
                </span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input v-model="showProvider" type="checkbox" class="input-checklist-dark" />
                                <span :class="[showProvider ? 'xsWhiteBold' : 'xsLight', 'text-white']">
                  {{ $t('Service provider') }}
                </span>
                            </label>

                            <!-- Search -->
                            <div>
                                <label for="user-search" class="block text-xs font-medium leading-6 text-white">
                                    {{ $t('Search') }}
                                </label>
                                <div class="relative mt-2">
                                    <input
                                        v-model="userSearch"
                                        id="user-search"
                                        type="text"
                                        class="block w-full rounded-lg border border-zinc-600 bg-darkGrayBg py-1.5 pr-10 text-white placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-0 sm:text-sm"
                                        :placeholder="$t('Search users...')"
                                    />
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <IconSearch class="h-5 w-5 text-zinc-400" v-if="userSearch.length === 0" />
                                    </div>
                                    <button
                                        v-if="userSearch.length > 0"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400"
                                        @click="userSearch = ''"
                                    >
                                        <IconX class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <CraftFilter is_tiny :crafts="loadedProjectInformation['ShiftTab'].crafts" />
                        </div>

                        <div class="my-2 h-px w-full bg-[#3A374D]" />
                    </div>

                    <!-- User list (draggable area) -->
                    <div
                        @mousedown="preventContainerDrag"
                        class="shiftUserWindow max-h-72 overflow-auto pr-1"
                    >
                        <div v-for="craft in searchUserWithCrafts" :key="craft.id" class="mb-2">
                            <div
                                v-if="craft.users.length > 0"
                                @click="changeCraftVisibility(craft.id)"
                                class="flex h-6 cursor-pointer items-center justify-between rounded-md px-1 text-xs text-white hover:bg-white/5"
                            >
                                <span class="truncate">{{ craft.name }}</span>
                                <IconChevronDown
                                    class="h-4 w-4 transition"
                                    :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                />
                            </div>
                            <div v-if="!closedCrafts.includes(craft.id)" class="mt-1">
                                <DragElement
                                    v-for="(user, idx) in craft.users"
                                    :key="idx"
                                    :item="user.element"
                                    :plannedHours="user.plannedWorkingHours"
                                    :expected-hours="user.expectedWorkingHours"
                                    :type="user.type"
                                    :color="craft.color"
                                    :craft="craft"
                                    class="mb-1"
                                    :disabled="!can('can plan shifts') && !isAdmin.value"
                                />
                            </div>
                        </div>

                        <div v-if="searchUserWithoutCrafts.length > 0" class="mt-2">
                            <div
                                @click="changeCraftVisibility('noCraft')"
                                class="flex h-6 cursor-pointer items-center justify-between rounded-md px-1 text-xs text-white hover:bg-white/5"
                            >
                                <span>{{ $t('Without craft assignment') }}</span>
                                <IconChevronDown
                                    class="h-4 w-4 transition"
                                    :class="closedCrafts.includes('noCraft') ? '' : 'rotate-180 transform'"
                                />
                            </div>
                            <div v-if="!closedCrafts.includes('noCraft')" class="mt-1">
                                <DragElement
                                    v-for="(user, idx) in searchUserWithoutCrafts"
                                    :key="idx"
                                    :item="user.element"
                                    :plannedHours="user.plannedWorkingHours"
                                    :expected-hours="user.expectedWorkingHours"
                                    :type="user.type"
                                    :color="null"
                                    :craft="null"
                                    class="mb-1"
                                    :disabled="!can('can plan shifts') && !isAdmin.value"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
            <!-- /User Window -->

            <!-- Empty State -->
            <div class="xsDark mt-5" v-if="loadedProjectInformation['ShiftTab'].events_with_relevant.length === 0">
                {{ $t('So far, there are no shift-relevant events for this project.') }}
            </div>

            <!-- Events -->
            <div class="mt-5">
                <SingleRelevantEvent
                    v-for="event in loadedProjectInformation['ShiftTab'].events_with_relevant"
                    :key="event.event?.id"
                    :crafts="loadedProjectInformation['ShiftTab'].crafts"
                    :currentUserCrafts="loadedProjectInformation['ShiftTab'].current_user_crafts"
                    :event="event"
                    :relevant-event-id="relevantEventId"
                    :event-types="headerObject.eventTypes"
                    :shift-qualifications="loadedProjectInformation['ShiftTab'].shift_qualifications"
                    :shift-time-presets="loadedProjectInformation['ShiftTab'].shift_time_presets"
                    :can-edit-component="can('can plan shifts') || isAdmin"
                    @dropFeedback="showDropFeedback"
                />
            </div>
        </div>
    </div>

    <SideNotification v-if="dropFeedback" type="error" :text="dropFeedback" @close="dropFeedback = null" />
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { can } from 'laravel-permission-to-vuejs'
import { MenuItem, Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue'

import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import CraftFilter from '@/Components/Filter/CraftFilter.vue'
import SideNotification from '@/Layouts/Components/General/SideNotification.vue'
import DragElement from '@/Pages/Projects/Components/DragElement.vue'
import SingleRelevantEvent from '@/Pages/Projects/Components/SingleRelevantEvent.vue'
import { useSortEnumTranslation } from '@/Composeables/SortEnumTranslation.js'


import { IconFilter, IconSearch, IconX, IconCheck, IconChevronDown, IconChevronUp } from '@tabler/icons-vue'

defineOptions({ name: 'ShiftTab' })

const props = defineProps({
    loadedProjectInformation: { type: Object, required: true },
    headerObject: { type: Object, required: true },
    canEditComponent: { type: Boolean, required: false, default: true },
})

const { getSortEnumTranslation } = useSortEnumTranslation()

// ---------- STATE ----------
const userWindow = ref(false)
const openFilter = ref(false)
const showIntern = ref(false)
const showExtern = ref(false)
const showProvider = ref(false)
const activeCraftFilters = ref([]) // placeholder for future use
const dropFeedback = ref(null)
const closedCrafts = ref([])
const userSearch = ref('')
const relevantEventId = ref(null)

const userWindowButton = ref(null)
const containerRef = ref(null)

// ---------- AUTH / ROLES ----------
const page = usePage()
const roles = computed(() => page?.props?.auth?.user?.roles ?? page?.props?.auth?.roles ?? [])
const isAdmin = computed(() => (roles.value || []).some(r => (typeof r === 'string' ? r : r?.name)?.toLowerCase() === 'artwork admin'))

// ---------- COMPUTEDS ----------
const dropUsers = computed(() => {
    const users = []
    const tab = props.loadedProjectInformation['ShiftTab']
    if (!tab) return users

    const noFilters = !showIntern.value && !showExtern.value && !showProvider.value

    if (showIntern.value || noFilters) {
        tab.users_for_shifts.forEach(user => {
            users.push({ element: user.user, type: 0, plannedWorkingHours: user.plannedWorkingHours })
        })
    }
    if (showExtern.value || noFilters) {
        tab.freelancers_for_shifts.forEach(freelancer => {
            users.push({ element: freelancer.freelancer, type: 1, plannedWorkingHours: freelancer.plannedWorkingHours })
        })
    }
    if (showProvider.value || noFilters) {
        tab.service_providers_for_shifts.forEach(sp => {
            users.push({ element: sp.service_provider, type: 2, plannedWorkingHours: sp.plannedWorkingHours })
        })
    }
    return users
})

const conflictMessage = computed(() => {
    const conflicts = []
    props.loadedProjectInformation['ShiftTab'].events_with_relevant.forEach(event => {
        event.shifts.forEach(shift => {
            shift.users.forEach(user => {
                if (user.formatted_vacation_days?.includes(shift.event_start_day)) {
                    conflicts.push({ date: shift.event_start_day, abbreviation: shift.craft.abbreviation })
                }
            })
        })
    })
    return conflicts
})

const usersWithNoCrafts = computed(() =>
    dropUsers.value.filter(u => !u.element.assigned_craft_ids || u.element.assigned_craft_ids?.length === 0)
)

const craftsToDisplay = computed(() => {
    const tab = props.loadedProjectInformation['ShiftTab']
    const all = tab?.crafts?.map(craft => ({
        name: craft.name,
        id: craft.id,
        users: dropUsers.value.filter(u => u.element.assigned_craft_ids?.includes(craft.id)),
        color: craft?.color,
        universally_applicable: craft.universally_applicable,
        abbreviation: craft.abbreviation,
    })) ?? []

    const visibleIds = page?.props?.auth?.user?.show_crafts ?? []
    if (!visibleIds || visibleIds.length === 0) return all
    return all.filter(c => visibleIds.includes(c.id))
})

const searchUserWithoutCrafts = computed(() =>
    usersWithNoCrafts.value.filter(user => {
        if (user.element.first_name && user.element.last_name) {
            return (
                user.element.first_name.toLowerCase().includes(userSearch.value.toLowerCase()) ||
                user.element.last_name.toLowerCase().includes(userSearch.value.toLowerCase())
            )
        } else if (user.element.provider_name) {
            return user.element.provider_name.toLowerCase().includes(userSearch.value.toLowerCase())
        }
    })
)

const searchUserWithCrafts = computed(() =>
    craftsToDisplay.value.map(craft => ({
        ...craft,
        users: craft.users.filter(user => {
            if (user.element.first_name && user.element.last_name) {
                return (
                    user.element.first_name.toLowerCase().includes(userSearch.value.toLowerCase()) ||
                    user.element.last_name.toLowerCase().includes(userSearch.value.toLowerCase())
                )
            } else if (user.element.provider_name) {
                return user.element.provider_name.toLowerCase().includes(userSearch.value.toLowerCase())
            }
        }),
    }))
)

// hat es uncommitted Shifts?
const hasUncommittedShift = computed(() =>
    props.loadedProjectInformation['ShiftTab']?.events_with_relevant.some(event =>
        event.shifts.find(shift => shift.is_committed === false)
    )
)

// HeadlessUI Switch braucht v-model; wir nutzen Proxy (visuell) & bleiben server-sourcetruth
const commitSwitchProxy = ref(!hasUncommittedShift.value)
watch(hasUncommittedShift, (v) => { commitSwitchProxy.value = !v })

// ---------- WATCHERS ----------
watch(userSearch, v => {
    if (v?.length > 0) {
        closedCrafts.value = []
    }
})

// ---------- LIFECYCLE ----------
onMounted(() => {
    // Drag behavior for the floating panel
    makeContainerDraggable()

    // Echo Live Updates (falls global vorhanden)
    try {
        // eslint-disable-next-line no-undef
        Echo?.private('shifts')
            .listen('.shift.updated', () => router.reload())
            .listen('.shift.deleted', () => router.reload())
            .listen('.shift.assigned', () => router.reload())
    } catch (e) {
        // Echo not available, ignore
    }

    // Scroll to event (URL param)
    setTimeout(() => {
        const params = page?.props?.urlParameters
        if (params?.scrollToEvent) {
            const scrollToEvent = () => {
                const el = document.getElementById('event-' + params.scrollToEvent)
                if (el) {
                    const yOffset = -100
                    const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset
                    window.scrollTo({ top: y, behavior: 'smooth' })
                } else {
                    requestAnimationFrame(scrollToEvent)
                }
            }
            requestAnimationFrame(scrollToEvent)
        }
    }, 1000)
})

// ---------- METHODS ----------
function applySort(type) {
    page.props.auth.user.sort_type_shift_tab = type
    router.patch(
        route('user.update.shift_tab_sort', { user: page.props.auth.user.id }),
        { sortBy: type },
        { preserveState: false, preserveScroll: true }
    )
}
function resetSort() {
    page.props.auth.user.sort_type_shift_tab = null
    router.patch(
        route('user.update.shift_tab_sort', { user: page.props.auth.user.id }),
        { sortBy: null },
        { preserveState: false, preserveScroll: true }
    )
}
function changeCraftVisibility(id) {
    const i = closedCrafts.value.indexOf(id)
    if (i >= 0) closedCrafts.value.splice(i, 1)
    else closedCrafts.value.push(id)
}
function toggleCompactMode() {
    router.post(
        route('user.compact.mode.toggle', { user: page.props.auth.user.id }),
        { compact_mode: !page.props.auth.user.compact_mode },
        { preserveScroll: true, preserveState: true }
    )
}
function checkCommitted() {
    return props.loadedProjectInformation['ShiftTab'].events_with_relevant?.length > 0
}
function showDropFeedback(feedback) {
    dropFeedback.value = feedback
    setTimeout(() => (dropFeedback.value = null), 2000)
}
function updateCommitmentOfShifts() {
    // Wir verwenden den aktuellen Zustand, um das Ziel zu setzen:
    // wenn es uncommitted gibt => setze is_committed=true (commit all), sonst false (unlock all)
    router.patch(
        route('update.shift.commitment'),
        {
            project_id: props.headerObject.project.id,
            shifts: props.loadedProjectInformation['ShiftTab']?.events_with_relevant.flatMap(e => e.shifts.map(s => s.id)),
            is_committed: hasUncommittedShift.value, // entspricht deiner ursprünglichen Logik
            committing_user_id: page.props.auth.user.id,
        },
        { preserveScroll: true }
    )
}

function makeContainerDraggable() {
    const container = containerRef.value
    if (!container) return
    let isDragging = false
    let offsetX = 0
    let offsetY = 0

    container.addEventListener('mousedown', (e) => {
        isDragging = true
        offsetX = e.clientX - container.offsetLeft
        offsetY = e.clientY - container.offsetTop
        document.body.classList.add('select-none')
    })
    document.addEventListener('mousemove', (e) => {
        if (isDragging) {
            container.style.left = `${e.clientX - offsetX}px`
            container.style.top = `${e.clientY - offsetY}px`
        }
    })
    document.addEventListener('mouseup', () => {
        isDragging = false
        document.body.classList.remove('select-none')
    })
}

function preventContainerDrag(e) {
    e.stopPropagation()
}

function openUserWindow() {
    if (userWindow.value) {
        userWindow.value = false
        return
    }
    const container = containerRef.value
    const button = userWindowButton.value
    if (!container || !button) {
        userWindow.value = !userWindow.value
        return
    }

    const rect = button.getBoundingClientRect()
    const scrollX = window.scrollX
    const scrollY = window.scrollY

    const left = rect.left + scrollX - 330
    const top = rect.top + scrollY + button.offsetHeight

    container.style.left = `${left}px`
    container.style.top = `${top}px`
    userWindow.value = true
}
</script>

<style scoped>
.shiftUserWindow { overflow: overlay; }

/* Sticky header container */
.stickyHeader {
    position: sticky;
    top: 0;
    z-index: 21;
}

/* Optional: sanftere Scrollbar in User Window (WebKit) */
.shiftUserWindow::-webkit-scrollbar { width: 8px; }
.shiftUserWindow::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); border-radius: 8px; }
.shiftUserWindow::-webkit-scrollbar-track { background: transparent; }
</style>
