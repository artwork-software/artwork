<template>
    <ArtworkBaseModal
        :title="modalTitle"
        :description="$t('Please make sure that you allow for preparation and follow-up time.')"
        modal-size="max-w-4xl"
        @close="closeModal"
    >
        <div class="space-y-5">

            <!-- Created by -->
            <div
                v-if="(isRoomAdmin || hasAdminRole()) && event?.id"
                class="flex items-center gap-2 text-[12px] text-zinc-500"
            >
                {{ $t('Created by') }}
                <UserPopoverTooltip
                    :user="event?.created_by"
                    :id="event?.created_by?.id ?? 'deletedUserTooltip'"
                    height="6"
                    width="6"
                />
            </div>

            <!-- Small tip -->
            <p class="rounded-lg bg-indigo-50/70 px-3 py-2 text-[12px] text-indigo-700">
                {{ $t('Tip: Keep titles short and descriptive—this helps everyone find the right event faster.') }}
            </p>

            <!-- EDIT MODE -->
            <div v-if="canEdit" class="space-y-5">
                <!-- Type / Status / Title -->
                <section class="card-event-container">
                    <header class="card-header">
                        <span class="card-dot bg-indigo-400"></span>
                        <h3 class="card-title">{{ $t('Basics') }}</h3>
                    </header>

                    <div class="grid grid-cols-1 items-end gap-x-4" :class="statusModule ? 'md:grid-cols-3' : 'md:grid-cols-2'">
                        <ArtworkBaseListbox
                            v-model="selectedEventType"
                            :items="eventTypes"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Event type"
                            :use-translations="false"
                        />
                        <!--<ArtworkBaseListbox
                            v-model="selectedEventType"
                            :items="eventTypes"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Event type"
                            :use-translations="false"
                            :button-class="lbBtn"
                            :options-class="lbOpts"
                        >
                            <template #button="{ selected, placeholder }">
                                <div class="lbb">
                                    <div class="lbb-text">
                                        <div class="lbb-label">{{ $t('Event type') }}</div>
                                        <div class="lbb-value">
                                  <span
                                      v-if="selected"
                                      class="inline-block size-3 rounded-full"
                                      :style="{ backgroundColor: selected?.hex_code }"
                                  />
                                                        <span class="truncate">{{ selected ? selected?.name : placeholder }}</span>
                                                    </div>
                                                </div>
                                                <IconChevronUp class="lbb-icon" />
                                            </div>
                                        </template>
                                        <template #option="{ item, selected }">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block size-2.5 rounded-full" :style="{ backgroundColor: item.hex_code }" />
                                                <span :class="selected ? 'font-semibold' : ''" class="truncate">
                                {{ item.name }}
                              </span>
                                </div>
                            </template>
                        </ArtworkBaseListbox>-->

                        <ArtworkBaseListbox
                            v-if="statusModule"
                            v-model="selectedEventStatus"
                            :items="eventStatuses"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Event Status"
                            :use-translations="false"
                            :button-class="lbBtn"
                            :options-class="lbOpts"
                        >
                            <template #button="{ selected, placeholder }">
                                <div class="lbb">
                                    <div class="lbb-text">
                                        <div class="lbb-label">{{ $t('Event Status') }}</div>
                                        <div class="lbb-value">
                      <span
                          v-if="selected"
                          class="inline-block size-3 rounded-full"
                          :style="{ backgroundColor: selected?.color }"
                      />
                                            <span class="truncate">{{ selected ? selected?.name : placeholder }}</span>
                                        </div>
                                    </div>
                                    <IconChevronUp class="lbb-icon" />
                                </div>
                            </template>
                            <template #option="{ item, selected }">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block size-2.5 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <span :class="selected ? 'font-semibold' : ''" class="truncate">
                    {{ item.name }}
                  </span>
                                </div>
                            </template>
                        </ArtworkBaseListbox>

                        <BaseInput
                            v-model="eventName"
                            id="eventTitle"
                            :label="selectedEventType?.individual_name ? $t('Event name') + '*' : $t('Event name')"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-1 md:grid-cols-3 mt-1">
                        <p class="err" v-if="errorMsg('eventType')" v-html="errorMsg('eventType')" />
                        <span />
                        <p class="err" v-if="selectedEventType?.individual_name && errorMsg('eventName')" v-html="errorMsg('eventName')" />
                    </div>
                </section>

                <!-- Time -->
                <section class="card-event-container">
                    <header class="card-header">
                        <span class="card-dot bg-sky-400"></span>
                        <h3 class="card-title">{{ $t('Date & Time') }}</h3>
                    </header>

                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="allDayEvent"
                                class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                @change="checkChanges"
                            />
                            <span class="text-sm text-zinc-700">{{ $t('Full day') }}</span>
                        </label>
                    </div>

                    <div class="mt-3 form-grid-2">
                        <div class="flex gap-3 items-end">
                            <BaseInput type="date" id="startDate" v-model="startDate" :label="$t('Start')" @change="checkChanges" class="h-11" />
                            <BaseInput
                                v-if="!allDayEvent"
                                type="time"
                                id="startTime"
                                v-model="startTime"
                                :label="$t('Start time')"
                                @change="checkChanges"
                                class="h-11"
                            />
                        </div>
                        <div class="flex gap-3 items-end">
                            <BaseInput type="date" id="endDate" v-model="endDate" :label="$t('End')" @change="checkChanges" class="h-11" />
                            <BaseInput
                                v-if="!allDayEvent"
                                type="time"
                                id="endTime"
                                v-model="endTime"
                                :label="$t('End time')"
                                @change="checkChanges"
                                class="h-11"
                            />
                        </div>
                    </div>

                    <div class="mt-1 form-grid-2">
                        <p class="err" v-if="errorMsg('start')" v-html="errorMsg('start')" />
                        <p class="err" v-if="errorMsg('end')" v-html="errorMsg('end')" />
                    </div>

                    <p v-if="helpTextLengthRoom" class="mt-2 err">{{ helpTextLengthRoom }}</p>
                </section>

                <!-- Repeat -->
                <section class="card-event-container">
                    <header class="card-header">
                        <span class="card-dot bg-amber-400"></span>
                        <h3 class="card-title">{{ $t('Repeat') }}</h3>
                    </header>

                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" v-model="series" class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-zinc-700">{{ $t('Repeat event') }}</span>
                        </label>
                        <span class="hint">{{ $t('Enable if this event should repeat automatically.') }}</span>
                    </div>

                    <div v-show="series" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                        <ArtworkBaseListbox
                            v-model="selectedFrequency"
                            :items="frequencies"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Frequency"
                            :use-translations="false"
                        />
                        <BaseInput id="seriesEndDate" type="date" v-model="seriesEndDate" :label="$t('End date Repeat event')"  />
                    </div>

                    <p v-if="event?.is_series" class="mt-2 hint">
                        {{ $t('Event is part of a repeat event') }} —
                        {{ $t('Cycle: {0} to {1}', [selectedFrequency?.name, convertDateFormat(seriesEndDate)]) }}
                    </p>
                </section>

                <!-- Room -->
                <section class="card-event-container">
                    <header class="card-header">
                        <span class="card-dot bg-rose-400"></span>
                        <h3 class="card-title">{{ $t('Room') }}</h3>
                    </header>

                    <div class="mb-1 flex items-center justify-between">
                        <span class="hint">{{ $t('Pick a room for this event.') }}</span>
                        <div v-if="selectedRoom && roomCollisionArray?.[selectedRoom.id] > 0" class="text-[12px] text-amber-600">
                            {{ $t('{0} potential conflicts detected', [roomCollisionArray[selectedRoom.id]]) }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto]">
                        <RoomSearch :label="$t('Search for Rooms')" @room-selected="onRoomSelected" />
                        <div
                            v-if="selectedRoom"
                            class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm"
                        >
                            <span class="truncate">{{ selectedRoom.name }}</span>
                            <button class="ml-1 text-zinc-400 transition hover:text-rose-600" @click="selectedRoom = null">
                                <IconCircleX class="size-4" />
                            </button>
                        </div>
                    </div>

                    <p class="mt-1 err" v-if="errorMsg('roomId')" v-html="errorMsg('roomId')" />
                </section>

                <!-- Project -->
                <section class="card-event-container">
                    <header class="card-header">
                        <span class="card-dot bg-emerald-400"></span>
                        <h3 class="card-title">{{ $t('Project') }}</h3>
                    </header>


                    <div class="mt-3 flex items-center gap-2">
                        <input id="showProjectInfo" type="checkbox" v-model="showProjectInfo" class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="showProjectInfo" class="text-sm text-zinc-700">{{ $t('Enable project assignment') }}</label>
                    </div>

                    <div v-if="showProjectInfo" class="mt-3 space-y-3">
                        <div class="flex items-center gap-2 text-[13px]">
                            <span class="text-zinc-500">{{ $t('Currently assigned') }}:</span>
                            <template v-if="selectedProject?.id">
                                <a
                                    v-if="canAccessProject()"
                                    :href="route('projects.tab', { project: selectedProject.id, projectTab: first_project_calendar_tab_id })"
                                    class="text-indigo-600 hover:underline"
                                >
                                    {{ selectedProject?.name }}
                                </a>
                                <span v-else class="text-zinc-800">{{ selectedProject?.name }}</span>
                                <button class="text-zinc-400 transition hover:text-rose-600" @click="selectedProject = null">
                                    <IconCircleX class="size-4" />
                                </button>
                            </template>
                            <span v-else class="text-zinc-400">{{ $t('No project selected') }}</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="text-sm" :class="creatingProject ? 'font-semibold text-zinc-900' : 'text-zinc-500'">{{ $t('New project') }}</label>
                            <input type="checkbox" v-model="creatingProject" class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" />
                            <label class="text-sm" :class="!creatingProject ? 'font-semibold text-zinc-900' : 'text-zinc-500'">{{ $t('Existing project') }}</label>
                        </div>

                        <div>
                            <ProjectSearch
                                v-if="!creatingProject"
                                :label="$t('Search for projects')"
                                @project-selected="chooseProject"
                            />
                            <BaseInput v-else id="projectName" :label="$t('New project name')" v-model="projectName" class="h-11" />
                        </div>

                        <p class="err" v-if="errorMsg('projectId')" v-html="errorMsg('projectId')" />
                        <p class="err" v-if="errorMsg('projectName')" v-html="errorMsg('projectName')" />
                    </div>
                </section>

                <!-- Notes / Booking messages -->
                <section class="card-event-container">
                    <header class="card-header">
                        <span class="card-dot bg-violet-400"></span>
                        <h3 class="card-title">{{ $t('Notes & booking') }}</h3>
                    </header>

                    <div class="space-y-4">
                        <BaseTextarea
                            :label="$t('What do I need to bear in mind for the event?')"
                            id="description"
                            v-model="description"
                            rows="4"
                        />

                        <div v-if="event?.occupancy_option" class="space-y-3">
                            <BaseTextarea
                                :label="$t('Comment on the booking (inquirer will be notified)')"
                                id="adminComment"
                                v-model="adminComment"
                                rows="3"
                            />

                            <div class="flex flex-wrap items-center gap-x-8 gap-y-2">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="accept"
                                        class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                                        @change="toggleAccept('accept')"
                                    />
                                    <span class="text-sm" :class="accept ? 'text-zinc-900' : 'text-zinc-500'">{{ $t('Commitments') }}</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="optionAccept"
                                        class="size-4 rounded border-zinc-300 text-amber-600 focus:ring-amber-500"
                                        @change="toggleAccept('option')"
                                    />
                                    <span class="text-sm" :class="optionAccept ? 'text-zinc-900' : 'text-zinc-500'">{{ $t('Optional commitment') }}</span>
                                </label>
                            </div>

                            <div v-if="optionAccept" class="max-w-sm">
                                <ArtworkBaseListbox
                                    v-model="optionString"
                                    :items="bookingOptions"
                                    option-label="name"
                                    option-key="name"
                                    by="name"
                                    label="Option"
                                    :use-translations="false"
                                    :button-class="lbBtn"
                                    :options-class="lbOpts"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Properties -->
                <section v-if="(event_properties?.length || 0) > 0" class="card-event-container">
                    <header class="card-header">
                        <span class="card-dot bg-cyan-400"></span>
                        <h3 class="card-title">{{ $t('Properties') }}</h3>
                    </header>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label
                            v-for="ep in event_properties"
                            :key="ep.id"
                            class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 transition hover:bg-white"
                        >
                            <input type="checkbox" v-model="ep.checked" class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" />
                            <component :is="ep.icon" class="size-4 text-zinc-700" />
                            <span class="text-sm" :class="ep.checked ? 'font-medium text-zinc-900' : 'text-zinc-700'">{{ ep.name }}</span>
                        </label>
                    </div>

                    <div v-if="checkedEventProperties.length" class="mt-3 flex flex-wrap items-center gap-2">
                        <span
                            v-for="(ep, i) in checkedEventProperties"
                            :key="ep.id ?? i"
                            class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-sm text-zinc-800"
                        >
                          <component :is="ep.icon" class="size-4" />
                          <span>{{ ep.name }}</span>
                        </span>
                    </div>
                </section>

                <!-- Actions -->
                <div class="flex w-full justify-center py-2">
                    <FormButton
                        v-if="canCreateDirect"
                        :disabled="isPrimaryDisabled"
                        @click="updateOrCreateEvent()"
                        :text="primaryButtonText"
                    />
                    <FormButton
                        v-else
                        :disabled="requestDisabled"
                        @click="updateOrCreateEvent(true)"
                        :text="$t('Request occupancy')"
                    />
                </div>
            </div>

            <!-- READONLY MODE -->
            <div v-else class="card-event-container">
                <header class="card-header">
                    <span class="card-dot bg-indigo-400"></span>
                    <h3 class="card-title">{{ $t('Event overview') }}</h3>
                </header>

                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-full ring-2 ring-zinc-100" :style="{ backgroundColor: selectedEventType?.hex_code }" />
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-zinc-900">{{ eventName }}</h2>
                        <div class="mt-0.5 flex items-center gap-3 text-sm text-zinc-600">
                            <span>{{ selectedEventType?.name }}</span>
                            <span v-if="selectedEventStatus" class="inline-flex items-center gap-1">
                            <span class="inline-block size-2 rounded-full" :style="{ backgroundColor: selectedEventStatus?.color }"></span>
                            {{ selectedEventStatus?.name }}
                          </span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 form-grid-2">
                    <div class="space-y-2">
                        <div>
                            <div class="hint">{{ $t('Date & Time') }}</div>
                            <div class="text-sm text-zinc-800">
                                <span v-if="startDate === endDate">{{ startDate }} • {{ startTime }} – {{ endTime }}</span>
                                <span v-else>{{ startDate }} {{ startTime }} — {{ endDate }} {{ endTime }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="hint">{{ $t('Room') }}</div>
                            <div class="text-sm text-zinc-800">{{ selectedRoom?.name }}</div>
                        </div>
                        <div v-if="selectedProject?.id">
                            <div class="hint">{{ $t('Project') }}</div>
                            <div class="text-sm">
                                <a
                                    v-if="canAccessProject()"
                                    :href="route('projects.tab', { project: selectedProject.id, projectTab: first_project_calendar_tab_id })"
                                    class="text-indigo-600 hover:underline"
                                >
                                    {{ selectedProject?.name }}
                                </a>
                                <span v-else class="text-zinc-800">{{ selectedProject?.name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="hint">{{ $t('This event is read-only for you.') }}</div>
                        <div v-if="event?.is_series" class="text-sm text-zinc-800">
                            {{ $t('Cycle: {0} to {1}', [selectedFrequency?.name, convertDateFormat(seriesEndDate)]) }}
                        </div>
                    </div>
                </div>

                <div v-if="description" class="mt-3 border-t border-zinc-100 pt-3 text-sm text-zinc-800">
                    {{ description }}
                </div>

                <div v-if="checkedEventProperties.length" class="mt-3 border-t border-zinc-100 pt-3">
                    <div class="mb-2 hint">{{ $t('Properties') }}</div>
                    <div class="flex flex-wrap gap-2">
            <span
                v-for="(ep, i) in checkedEventProperties"
                :key="ep.id ?? i"
                class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-sm text-zinc-800"
            >
              <component :is="ep.icon" class="size-4" />
              <span>{{ ep.name }}</span>
            </span>
                    </div>
                </div>

                <div v-if="showComments && event?.comments?.length" class="mt-3 border-t border-zinc-100 pt-3">
                    <div class="mb-2 hint">{{ $t('Comments') }}</div>
                    <div class="space-y-3">
                        <div
                            v-for="c in event?.comments"
                            :key="c.id"
                            class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-3"
                        >
                            <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500">
                                <NewUserToolTip :id="c.id" :user="c.user" :height="6" :width="6" />
                                <span>{{ c.created_at }}</span>
                            </div>
                            <div class="text-sm text-zinc-800">{{ c.comment }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex w-full justify-center">
                    <FormButton :text="$t('Close')" @click="closeModal" />
                </div>
            </div>
        </div>

        <!-- Confirmations / Series -->
        <ConfirmationComponent
            v-if="deleteComponentVisible"
            :confirm="$t('Delete')"
            :titel="$t('Delete event?')"
            :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [event?.title ?? ''])"
            @closed="afterConfirm"
        />
        <ChangeAllSubmitModal
            v-if="showSeriesEdit"
            @closed="closeSeriesEditModal"
            @all="saveAllSeriesEvents"
            @single="singleSaveEvent"
        />
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import dayjs from 'dayjs'
import { can } from 'laravel-permission-to-vuejs'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue'
import ChangeAllSubmitModal from '@/Layouts/Components/ChangeAllSubmitModal.vue'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import ProjectSearch from '@/Components/SearchBars/ProjectSearch.vue'
import RoomSearch from '@/Components/SearchBars/RoomSearch.vue'

import { IconChevronUp, IconCircleX } from '@tabler/icons-vue'
import { useEvent } from '@/Composeables/Event.js'
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import {useI18n} from "vue-i18n";
const { t } = useI18n(), $t = t;
const props = defineProps({
    showHints: { type: Boolean, default: false },
    eventTypes: { type: Array, required: true },
    rooms: { type: Array, required: true },
    isAdmin: { type: Boolean, default: false },
    event: { type: Object, default: null },
    project: { type: Object, default: null },
    wantedRoomId: { type: [Number, String], default: null },
    roomCollisions: { type: Object, default: () => ({}) },
    showComments: { type: Boolean, default: false },
    first_project_calendar_tab_id: { type: [Number, String], required: true },
    usedInBulkComponent: { type: Boolean, default: false },
    requiresAxiosRequests: { type: Boolean, default: false },
    calendarProjectPeriod: { type: Boolean, default: false },
    eventStatuses: { type: Array, default: () => [] },
    isPlanning: { type: Boolean, default: false },
    wantedDate: { type: String, default: null },
})
const emit = defineEmits(['closed'])

const page = usePage()
const statusModule = computed(() => page.props.event_status_module)
const { getDaysOfEvent } = useEvent()
const event_properties = inject('event_properties', [])

// --- State
const submit = ref(true)
const startDate = ref(null)
const startTime = ref(null)
const endDate = ref(null)
const endTime = ref(null)
const oldStartDate = ref(null)
const oldStartTime = ref(null)
const oldEndDate = ref(null)
const oldEndTime = ref(null)

const showSeriesEdit = ref(false)
const allSeriesEvents = ref(false)
const series = ref(false)
const seriesEndDate = ref(null)
const selectedFrequency = ref({ id: 2, name: 'Wöchentlich' })
const frequencies = [
    { id: 1, name: 'Täglich' },
    { id: 2, name: 'Wöchentlich' },
    { id: 3, name: 'Alle 2 Wochen' },
    { id: 4, name: 'Monatlich' },
]

const projectName = ref(null)
const title = ref(null)
const isOption = ref(null)
const eventName = ref(null)
const selectedEventType = ref(props.eventTypes?.[0] ?? null)
const selectedEventStatus = ref(props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null)

const showProjectInfo = ref(Boolean(props.project) || (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id))
const allDayEvent = ref(false)
const selectedProject = ref(null)
const selectedRoom = ref(null)
const error = ref(null)
const creatingProject = ref(false)
const description = ref(null)
const canEdit = ref(false)
const declinedRoomId = ref(null)
const deleteComponentVisible = ref(false)
const adminComment = ref('')
const optionString = ref(null)
const accept = ref(true)
const optionAccept = ref(false)
const roomCollisionArray = ref(props.roomCollisions ?? {})
const helpTextLengthRoom = ref('')
const initialRoomId = ref(null)
const showRejections = ref(false)

const bookingOptions = [{ name: 'Option 1' }, { name: 'Option 2' }, { name: 'Option 3' }, { name: 'Option 4' }]

const answerRequestForm = useForm({ accepted: false })

// --- Computeds
const isRoomAdmin = computed(() => {
    return props.rooms.find(r => r.id === props.event?.roomId)?.admins?.some(a => a.id === page.props.auth.user.id) || false
})
const isCreator = computed(() => (props.event ? props.event.created_by?.id === page.props.auth.user.id : false))
const hasAdminRole = () => props.isAdmin || page.props.auth.user?.roles?.some?.(r => r.name?.toLowerCase?.().includes('admin'))

const roomAdminIds = computed(() => selectedRoom.value?.room_admins?.map(a => a.id) ?? [])

const modalTitle = computed(() => {
    if (props.event?.id) {
        if (props.event?.occupancy_option) return $t('Change & confirm occupancy')
        if (props.event?.isPlanning) return $t('Planned Event')
        return $t('Event')
    }
    return props.isPlanning ? $t('Create planned Event') : $t('New room allocation')
})

const checkedEventProperties = computed(() => (event_properties ?? []).filter(p => p.checked))

const canCreateDirect = computed(
    () => hasAdminRole() || selectedRoom.value?.everyone_can_book || roomAdminIds.value.includes(page.props.auth.user.id) || can('create events without request')
)

const isPrimaryDisabled = computed(() => {
    const invalidSeries = series.value && (!seriesEndDate.value || (endDate.value && seriesEndDate.value && endDate.value > seriesEndDate.value))
    const missingRoom = !selectedRoom.value
    const missingSubmit = !submit.value
    const needDecision = props.event?.occupancy_option && accept.value === false && optionAccept.value === false && adminComment.value === ''
    return missingRoom || missingSubmit || invalidSeries || needDecision
})
const primaryButtonText = computed(() => {
    if (!props.event?.occupancy_option) return $t('Save')
    if (accept.value) return $t('Commitments')
    if (optionAccept.value) return $t('Optional commitment')
    if (adminComment.value) return $t('Send message')
    return $t('Save')
})
const requestDisabled = computed(() => {
    const invalidSeries = series.value && (!seriesEndDate.value || (endDate.value && seriesEndDate.value && endDate.value > seriesEndDate.value))
    if (!selectedRoom.value || !submit.value || invalidSeries) return true
    if (!can('request room occupancy') && !props.isPlanning) return true
    if (!can('can see planning calendar') && props.isPlanning) return true
    return false
})

// --- Watches / Mount
watch(selectedRoom, () => checkChanges(), { deep: true })
watch(() => props.event, () => openModal(), { deep: true, immediate: true })

onMounted(() => {
    if (props.wantedDate) {
        startDate.value = props.wantedDate
        startTime.value = '09:00'
        endDate.value = props.wantedDate
        endTime.value = '10:00'
    }
    if (props.wantedRoomId) {
        selectedRoom.value = props.rooms.find(r => r.id === Number(props.wantedRoomId)) || null
    } else if (props.event) {
        selectedRoom.value = props.rooms.find(r => r.id === props.event.roomId) || null
    }
})

// --- Methods
function canAccessProject() {
    if (can('view projects') || can('write projects')) return true
    if (selectedProject.value?.creator_id === page.props.auth.user.id) return true
    if (selectedProject.value?.team_members?.some?.(m => m.id === page.props.auth.user.id)) return true
    return false
}
function convertDateFormat(dateString) {
    const parts = (dateString ?? '').split('-')
    if (parts.length !== 3) return dateString
    return `${parts[2]}.${parts[1]}.${parts[0]}`
}
function openModal() {
    canEdit.value = (!props.event?.id) || isCreator.value || isRoomAdmin.value || hasAdminRole()

    if (!props.event) {
        selectedEventType.value = props.eventTypes?.[0] ?? null
        selectedEventStatus.value = props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null
        if (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id) {
            selectedProject.value = { id: page.props.auth.user.calendar_settings.time_period_project_id, name: page.props.projectNameOfCalendarProject }
        }
        return
    }

    if (props.event?.project) {
        selectedProject.value = { id: props.event.project.id, name: props.event.project.name }
    } else if (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id) {
        selectedProject.value = { id: page.props.auth.user.calendar_settings.time_period_project_id, name: page.props.projectNameOfCalendarProject }
    }

    const start = dayjs(props.event.start)
    const end = dayjs(props.event.end)
    startDate.value = start.format('YYYY-MM-DD')
    startTime.value = start.format('HH:mm')
    endDate.value = end.format('YYYY-MM-DD')
    endTime.value = end.format('HH:mm')
    oldStartDate.value = startDate.value
    oldStartTime.value = startTime.value
    oldEndDate.value = endDate.value
    oldEndTime.value = endTime.value

    title.value = props.event.title
    eventName.value = props.event.eventName

    const eventStatusId = props.event?.eventStatus?.id ?? props.event?.eventStatusId ?? props.event?.event_status_id
    selectedEventStatus.value = props.eventStatuses?.find(s => s.id === eventStatusId) ?? selectedEventStatus.value
    allDayEvent.value = !!props.event.allDay

    selectedEventType.value = props.event.eventType?.id
        ? props.eventTypes?.find(t => t.id === props.event.eventType.id) ?? props.eventTypes?.[0]
        : props.eventTypes?.[0]
    if (props.event?.eventTypeId) {
        selectedEventType.value = props.eventTypes?.find(t => t.id === props.event.eventTypeId) ?? selectedEventType.value
    }

    series.value = !!props.event.is_series
    if (series.value) {
        seriesEndDate.value = props.event.series?.end_date ?? null
        const found = frequencies.find(f => f.id === props.event.series?.frequency_id)
        if (found) selectedFrequency.value = found
    }

    if (selectedProject.value?.id) showProjectInfo.value = true

    if (props.wantedRoomId) {
        selectedRoom.value = props.rooms.find(r => r.id === Number(props.wantedRoomId)) || null
    } else {
        selectedRoom.value = props.rooms.find(r => r.id === props.event.roomId) || null
    }

    if (props.wantedDate) {
        startDate.value = props.wantedDate
        startTime.value = '09:00'
        endDate.value = props.wantedDate
        endTime.value = '10:00'
    }

    initialRoomId.value = selectedRoom.value?.id ?? null
    description.value = props.event.description ?? ''

    ;(event_properties ?? []).forEach(ep => {
        ep.checked = props.event?.eventProperties?.some(eep => eep.id === ep.id) || false
    })

    checkCollisions()
}
function closeModal(closedOnPurpose = false) {
    if (closedOnPurpose) {
        emit(
            'closed',
            closedOnPurpose,
            Array.from(new Set([initialRoomId.value, selectedRoom.value?.id].filter(Boolean))),
            getDaysOfEvent(startDate.value, series.value ? seriesEndDate.value : endDate.value),
            getDaysOfEvent(oldStartDate.value, oldEndDate.value),
        )
    } else {
        emit('closed', closedOnPurpose)
    }

    // minimal reset
    startDate.value = startTime.value = endDate.value = endTime.value = null
    oldStartDate.value = oldStartTime.value = oldEndDate.value = oldEndTime.value = null
    eventName.value = description.value = null
    selectedProject.value = selectedRoom.value = null
    selectedEventType.value = props.eventTypes?.[0] ?? null
    selectedEventStatus.value = props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null
    allDayEvent.value = false
    series.value = false
    seriesEndDate.value = null
    showProjectInfo.value = Boolean(props.project) || (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id)
    creatingProject.value = false
    projectName.value = null
    adminComment.value = ''
    accept.value = true
    optionAccept.value = false
    optionString.value = null
    error.value = null
    ;(event_properties ?? []).forEach(p => (p.checked = false))
    initialRoomId.value = null
}
function formatDate(date, time) {
    if (!date || !time) return null
    return new Date(`${date} ${time}`).toISOString()
}
async function checkCollisions() {
    if ((startTime.value && startDate.value && endTime.value && endDate.value) || (allDayEvent.value && startDate.value && endDate.value)) {
        const startFull = formatDate(startDate.value, startTime.value ?? '00:00')
        const endFull = formatDate(endDate.value, endTime.value ?? '23:59')
        try {
            const { data } = await axios.post('/collision/room', { params: { start: startFull, end: endFull } })
            roomCollisionArray.value = data
        } catch { /* ignore */ }
    }
}
function checkYear(date) {
    return parseInt(String(date).split('-')[0]) > 1900
}
function getNextHourString(timeString) {
    const h = Number(timeString.slice(0, 2)) + 1
    const m = timeString.slice(3, 5)
    return `${h < 10 ? '0' + h : h}:${m}`
}
function validateStartBeforeEndTime() {
    error.value = null
    if (startDate.value && endDate.value && startTime.value && endTime.value) {
        // optional server-side validation hook
    }
}
function updateTimes() {
    if (startDate.value) {
        if (!endDate.value && checkYear(startDate.value)) endDate.value = startDate.value
        if (startTime.value && !endTime.value) {
            if (startTime.value === '23:00') endTime.value = '23:59'
            else {
                const startHours = startTime.value.slice(0, 2)
                if (startHours === '23') {
                    endTime.value = `00:${startTime.value.slice(3, 5)}`
                    const d = new Date(endDate.value)
                    endDate.value = new Date(d.setDate(new Date(endDate.value).getDate() + 1)).toISOString().slice(0, 10)
                } else endTime.value = getNextHourString(startTime.value)
            }
        }
    }
    validateStartBeforeEndTime()
    checkCollisions()
}
function checkChanges() {
    if (selectedRoom.value?.temporary) {
        const startFull = formatDate(startDate.value, startTime.value)
        const endFull = formatDate(endDate.value, endTime.value)
        const start = dayjs(startFull)
        const end = dayjs(endFull)
        const roomStart = dayjs(selectedRoom.value.start_date)
        const roomEnd = dayjs(selectedRoom.value.end_date)

        if (start.isBefore(roomStart)) {
            helpTextLengthRoom.value = $t('The start time is before the availability of this temporary room.')
            submit.value = false
        } else if (end.isAfter(roomEnd)) {
            helpTextLengthRoom.value = $t('The end time is after the availability of this temporary room.')
            submit.value = false
        } else {
            helpTextLengthRoom.value = ''
            submit.value = true
        }
    }
    updateTimes()
}

const lbBtn =
    'menu-button bg-white focus:outline-hidden focus:ring-0 w-full text-left rounded-md border border-zinc-200 shadow-sm px-3 py-2 h-11 text-sm text-zinc-900'
const lbOpts =
    'absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm'

function toggleAccept(type) {
    if (type === 'option') {
        if (optionAccept.value) {
            accept.value = false
            optionString.value = bookingOptions[0].name
        }
    } else {
        if (accept.value) {
            optionAccept.value = false
            optionString.value = null
        }
    }
}
function chooseProject(project) {
    selectedProject.value = project
    projectName.value = ''
}
function onRoomSelected(room) {
    selectedRoom.value = room
    checkChanges()
}
function errorMsg(field) {
    const e = error.value
    if (!e) return ''
    const raw = Array.isArray(e?.[field]) ? e[field].join('.<br> ') : e?.[field]
    return raw || ''
}
function payload() {
    return {
        title: title.value,
        eventName: eventName.value,
        eventStatusId: selectedEventStatus.value?.id,
        start: formatDate(startDate.value, allDayEvent.value ? '00:00' : startTime.value),
        end: formatDate(endDate.value, allDayEvent.value ? '23:59' : endTime.value),
        roomId: selectedRoom.value?.id,
        description: description.value,
        isOption: isOption.value,
        eventNameMandatory: !!selectedEventType.value?.individual_name,
        projectId: showProjectInfo.value ? selectedProject.value?.id : null,
        projectName: showProjectInfo.value ? (creatingProject.value ? projectName.value : '') : '',
        eventTypeId: selectedEventType.value?.id,
        projectIdMandatory: !!(selectedEventType.value?.project_mandatory && !creatingProject.value),
        creatingProject: showProjectInfo.value ? creatingProject.value : false,
        declinedRoomId: declinedRoomId.value,
        is_series: !!series.value,
        seriesFrequency: selectedFrequency.value.id,
        seriesEndDate: seriesEndDate.value,
        allSeriesEvents: allSeriesEvents.value,
        adminComment: adminComment.value,
        optionString: optionAccept.value ? optionString.value : null,
        accept: accept.value,
        optionAccept: optionAccept.value,
        allDay: allDayEvent.value,
        usedInBulkComponent: props.usedInBulkComponent,
        showProjectPeriodInCalendar: props.calendarProjectPeriod,
        event_properties: (event_properties ?? []).filter(p => p.checked).map(p => p.id),
        isPlanning: props.event ? props.event.isPlanning : props.isPlanning,
    }
}
async function updateOrCreateEvent(isOptionParam = false) {
    isOption.value = isOptionParam

    if (allDayEvent.value) {
        startTime.value = '00:00'
        endTime.value = '23:59'
    }
    if (accept.value === false && optionAccept.value === false) {
        isOption.value = true
    }

    const data = payload()

    if (!props.requiresAxiosRequests &&
        (props.usedInBulkComponent ||
            (page.props.auth.user.calendar_settings.time_period_project_id === selectedProject.value?.id &&
                props.calendarProjectPeriod))) {
        if (!props.event?.id) {
            router.post(route('events.store'), data, {
                preserveScroll: true,
                preserveState: (pg) => typeof pg?.component === 'undefined',
                onSuccess: () => closeModal(true),
                onError: (resp) => (error.value = resp),
            })
        } else {
            router.put(route('events.update', { event: props.event.id }), data, {
                preserveScroll: true,
                preserveState: (pg) => typeof pg?.component === 'undefined',
                onSuccess: () => closeModal(true),
                onError: (resp) => (error.value = resp),
            })
        }
        return
    }

    try {
        if (!props.event?.id) await axios.post('/events', data)
        else await axios.put(`/events/${props.event.id}`, data)
        closeModal(true)
    } catch (e) {
        error.value = e?.response?.data?.errors ?? e
    }
}
async function singleSaveEvent() {
    try {
        await axios.put(`/events/${props.event?.id}`, payload())
        closeModal(true)
        closeSeriesEditModal()
    } catch (e) {
        error.value = e?.response?.data?.errors ?? e
    }
}
async function saveAllSeriesEvents() {
    allSeriesEvents.value = true
    try {
        await axios.put(`/events/${props.event?.id}`, payload())
        closeModal(true)
        closeSeriesEditModal()
    } catch (e) {
        error.value = e?.response?.data?.errors ?? e
    }
}
function closeSeriesEditModal() {
    showSeriesEdit.value = false
}
async function afterConfirm(confirmed) {
    if (!confirmed) {
        deleteComponentVisible.value = false
        return
    }
    await axios.delete(`/events/${props.event.id}`)
    closeModal(true)
}
</script>
