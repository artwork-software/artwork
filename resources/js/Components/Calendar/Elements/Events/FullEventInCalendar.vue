<template>
    <section
        :style="containerStyle"
        class="rounded-lg group/singleEvent h-full"
        :class="containerClasses"
        ref="rootEl"
    >
        <!-- Multi-Select Overlay -->
        <button
            v-show="showMultiEditOverlay"
            @click="toggleMultiEdit"
            type="button"
            class="absolute inset-0 z-10 rounded-lg grid place-items-center"
            :class="event.considerOnMultiEdit ? 'bg-green-200/50' : 'bg-artwork-buttons-create/50'"
            aria-pressed="event.considerOnMultiEdit"
        >
      <span v-if="event.considerOnMultiEdit" class="bg-white rounded-lg">
        <component is="IconSquareCheckFilled" class="size-6 text-green-500" />
      </span>
            <!-- echte Checkbox für A11y -->
            <input
                v-model="event.considerOnMultiEdit"
                :id="event.id"
                name="candidates"
                type="checkbox"
                class="hidden"
                @change="emitMultiEditChange"
            />
        </button>

        <!-- Status-Badge -->
        <p v-if="showStatusBanner" :class="[cls.statusBanner, statusBannerClass]">
            <!-- Text ist statisch übersetzbar: einmalig rendern -->
            <span v-once>{{ $t('Planned Event') }}</span>
            <span v-if="event.hasVerification" v-once>{{ $t('Verification requested') }}</span>
        </p>

        <!-- DETAIL: große Ansicht -->
        <section v-if="!isSmallView" class="grid grid-cols-1 md:grid-cols-3 w-full px-1">
            <div class="py-2 px-1 col-span-2">
                <div class="flex items-stretch gap-x-3 h-full min-h-full">
                    <i v-if="!settings.high_contrast" class="p-1 rounded-lg w-1" :style="{ backgroundColor: baseHex }" />

                    <header :style="styles.headerTextStyle" :class="cls.titleHeader" class="relative grow">
                        <!-- Titelzeile -->
                        <div class="flex items-center gap-1">
              <span
                  v-if="showProjectStatusDot"
                  class="text-center rounded-full border group relative min-h-4 min-w-4 size-4 cursor-pointer"
                  :style="projectStatusDotStyle"
              >
                <span class="absolute hidden group-hover:block top-5">
                  <span class="bg-artwork-navigation-background text-white text-xs rounded-full px-3 py-0.5">
                    {{ event?.project?.status?.name }}
                  </span>
                </span>
              </span>

                            <a v-if="hasProjNameAndId" :href="hrefs.project" class="truncate inline-flex items-center !w-20">
                                {{ event.project?.name }}
                            </a>
                        </div>

                        <p v-if="settings.project_artists && event.project?.artistNames" class="truncate w-28">
                            {{ event.project?.artistNames }}
                        </p>

                        <p v-if="settings.event_name && event.eventName" class="truncate w-28">
                            {{ event.eventName }}
                        </p>

                        <p class="truncate w-28">
                            {{ event?.eventType?.name }}
                        </p>

                        <span v-if="settings.project_status && event.projectStateColor" class="absolute right-5">
              <span :class="[styles.projectStateDotBorder]" class="rounded-full" />
            </span>

                        <!-- Zeit + Optionen -->
                        <div class="flex items-center gap-1 mt-1 w-28">
                            <component :is="IconClock" class="size-3.5" stroke-width="2" v-if="!event.allDay && sameDay" />

                            <div :style="styles.timeTextStyle" :class="cls.timeTextBase" class="flex">
                                <template v-if="isSameDayNonProject">
                                    <span v-if="event.allDay">{{ $t('Full day') }}</span>
                                    <span v-else>{{ event.formattedDates.startTime + ' - ' + event.formattedDates.endTime }}</span>
                                </template>
                                <template v-else>
                                    <template v-if="event.allDay">
                    <span v-if="atAGlance && sameDay">
                      {{ $t('Full day') }}, {{ event.formattedDates.start_without_year }}
                    </span>
                                        <span v-else>
                      {{ $t('Full day') }}, {{ event.formattedDates.start_without_year }} - {{ event.formattedDates.end_without_year }}
                    </span>
                                    </template>
                                    <template v-else>
                    <span v-if="!sameDay" class="items-center">
                      <span class="text-error">!</span>
                      {{ event.formattedDates.startDateTime_without_year  + ' - ' +  event.formattedDates.endDateTime_without_year }}
                    </span>
                                        <span v-else>
                      <span v-if="atAGlance">{{ event.formattedDates.startDateTime_without_year + ' - ' + event.formattedDates.endTime }}</span>
                      <span v-else>{{ event.formattedDates.startTime + ' - ' + event.formattedDates.endTime }}</span>
                    </span>
                                    </template>
                                </template>
                            </div>

                            <span v-if="settings.options && event.option_string" class="flex items-center">
                <span v-if="!atAGlance && sameDay" class="eventTime font-medium subpixel-antialiased" :style="headerBaseStyle">
                  , {{ event.option_string }}
                </span>
                <span v-else class="eventTime font-medium subpixel-antialiased ml-0.5">
                  ({{ event.option_string.charAt(7) }})
                </span>
              </span>
                        </div>

                        <!-- Wiederkehrend (Label einmalig) -->
                        <p v-if="settings.repeating_events && event.is_series" class="uppercase inline-flex items-center font-semibold" :style="styles.repeatTextStyle">
                            <component :is="IconRepeat" class="mr-1 min-h-3 min-w-3" stroke-width="2" />
                            <span v-once>{{ $t('Repeat event') }}</span>
                        </p>

                        <!-- Beschreibung (memo: ändert sich nur, wenn event.id oder event.updated_at sich ändert) -->
                        <EventNoteComponent
                            v-if="settings.description"
                            v-memo="[event.id, event.updated_at]"
                            :event="event"
                            :style="styles.timeTextStyle"
                        />
                    </header>

                    <!-- Work Shifts (memoisiert pro Liste) -->
                    <ul
                        v-if="settings.work_shifts"
                        v-memo="[event.id, (event.shifts||[]).length, firstProjectShiftTabId]"
                        class="grid grid-cols-1 md:grid-cols-2 text-xs my-2"
                    >
                        <li v-if="firstProjectShiftTabId" v-for="shift in event.shifts" :key="shift.id">
                            <a :href="hrefs.shifts">
                                <span>{{ shift?.craft?.abbreviation }}</span>
                                <span>&nbsp;({{ shift?.worker_count }}/{{ shift?.max_worker_count }})</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- rechte Menüleiste -->
            <aside class="flex gap-1 mt-5">
                <!-- Eigenschaften (memo: nur bei Anzahl/Name/Icon-Änderung neu) -->
                <div
                    class="grid md:grid-cols-2 gap-1"
                    v-memo="[(event.eventProperties||[]).length]"
                >
                    <ToolTipComponent
                        v-for="property in (event.eventProperties || [])"
                        :key="property.name"
                        :icon="property.icon"
                        icon-size="size-4"
                        :tooltip-text="property.name"
                        classes="text-black"
                        stroke="1.5"
                        direction="left"
                        no-relative
                    />
                </div>

                <div :class="cls.rightMenuWrapper">
                    <BaseMenu
                        has-no-offset
                        menuWidth="w-fit"
                        :dots-color="settings.high_contrast ? 'text-white' : ''"
                        white-menu-background
                        v-memo="[event.id, event.hasVerification, (verifierForEventTypIds||[]).length, event.eventType?.id, isRoomAdmin, isCreator, hasAdminRole]"
                    >
                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && !event.hasVerification" @click="SendEventToVerification" :icon="IconLock" :title="$t('Request verification')" />
                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && event.hasVerification" @click="cancelVerification" :icon="IconLockOpen" :title="$t('Withdraw verification request')" />
                        <BaseMenuItem white-menu-background v-if="event.hasVerification && (verifierForEventTypIds?.includes(event.eventType.id) || false)" @click="approveRequest" :icon="IconChecks" :title="$t('Approve verification')" />
                        <BaseMenuItem white-menu-background v-if="event.hasVerification && (verifierForEventTypIds?.includes(event.eventType.id) || false)" @click="showRejectEventVerificationModal = true" :icon="IconCircleX" :title="$t('Reject verification')" />

                        <BaseMenuItem white-menu-background @click="$emit('editEvent', event)" :icon="IconEdit" :title="$t('edit')" />
                        <BaseMenuItem white-menu-background v-if="(isRoomAdmin || isCreator || hasAdminRole) && event?.eventType?.id === 1" @click="$emit('openAddSubEventModal', event, 'create', null)" :icon="IconCirclePlus" :title="$t('Add Sub-Event')" />
                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" :icon="IconX" :title="$t('Decline event')" />
                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" @click="$emit('openConfirmModal', event, 'main')" :icon="IconTrash" :title="$t('Delete')" />
                    </BaseMenu>
                </div>
            </aside>
        </section>

        <!-- KOMPAKT: kleine Ansicht -->
        <section
            v-else
            class="w-full flex px-2 h-full"
            :class="[zoom_factor < 0.6 ? 'justify-center' : 'py-3', zoom_factor === 0.4 ? 'items-center' : '']"
        >
            <Popover class="relative">
                <Float auto-placement portal :offset="{  5 : -10}">
                    <PopoverButton class="flex items-center justify-start gap-1 ring-0 focus:ring-0">
                        <component :is="IconInfoCircle" class="size-6" stroke-width="1.5"/>
                        <div class="w-16 max-w-16 xsDark text-left" v-if="zoom_factor > 0.4">
                            <div v-if="settings.event_name && event.eventName" class="truncate">{{ event.eventName }}</div>
                            <a v-if="event.project?.id" :href="hrefs.project" class="truncate block">{{ event.project?.name }}</a>
                        </div>
                    </PopoverButton>

                    <PopoverPanel class="absolute z-10 w-fit bg-white shadow-lg rounded-lg p-2">
                        <div class="px-3 py-2">
                            <header :style="styles.headerTextStyle" :class="cls.miniHeader" class="relative">
                <span v-if="showProjectStatusDot" class="text-center rounded-full border group size-4 cursor-pointer" :style="projectStatusDotStyle">
                  <span class="absolute hidden group-hover:block top-5">
                    <span class="bg-artwork-navigation-background text-white text-xs rounded-full px-3 py-0.5">
                      {{ event?.project?.status?.name }}
                    </span>
                  </span>
                </span>

                                <a v-if="hasProjNameAndId" :href="hrefs.project" class="truncate inline-flex items-center !w-28">
                                    {{ event.project?.name }}
                                </a>

                                <p v-if="settings.project_artists && event.projectArtists" class="truncate">{{ event.projectArtists }}</p>
                                <p v-if="settings.event_name && event.eventName" class="truncate">{{ event.eventName }}</p>
                                <p class="truncate">{{ event.eventTypeName }}</p>

                                <span v-if="settings.project_status && event.projectStateColor" class="absolute right-5">
                  <span :class="[styles.projectStateDotBorder]" class="rounded-full" />
                </span>

                                <span v-if="event.audience" class="absolute top-5 right-4">
                  <IconUsersGroup stroke-width="1.5" :width="12 * zoom_factor" :height="12 * zoom_factor"/>
                </span>
                            </header>

                            <div class="flex">
                                <div :style="styles.timeTextStyle" :class="cls.timeTextBase" class="flex">
                                    <template v-if="isSameDayNonProject">
                                        <span v-if="event.allDay">{{ $t('Full day') }}</span>
                                        <span v-else>{{ startHHmm + ' - ' + endHHmm }}</span>
                                    </template>
                                    <template v-else>
                                        <template v-if="event.allDay">
                                            <span v-if="atAGlance && sameDay">{{ $t('Full day') }}, {{ startDDMM }}</span>
                                            <span v-else>{{ $t('Full day') }}, {{ startDDMM }} - {{ endDDMM }}</span>
                                        </template>
                                        <template v-else>
                                            <span v-if="!sameDay"><span class="text-error">!</span> {{ startDDMMHHmm + ' - ' + endDDMMHHmm }}</span>
                                            <span v-else>
                        <span v-if="atAGlance">{{ startDDMMHHmm + ' - ' + endHHmm }}</span>
                        <span v-else>{{ startHHmm + ' - ' + endHHmm }}</span>
                      </span>
                                        </template>
                                    </template>
                                </div>

                                <span v-if="settings.options && event.option_string" class="flex items-center">
                  <span v-if="!atAGlance && sameDay" class="eventTime font-medium subpixel-antialiased" :style="headerBaseStyle">, {{ event.option_string }}</span>
                  <span v-else class="ml-0.5 eventTime font-medium subpixel-antialiased">({{ event.option_string.charAt(7) }})</span>
                </span>
                            </div>

                            <p v-if="settings.repeating_events && event.is_series" class="uppercase inline-flex items-center font-semibold" :style="styles.repeatTextStyle">
                                <IconRepeat class="mx-1 h-3 w-3" stroke-width="1.5"/>
                                <span v-once>{{ $t('Repeat event') }}</span>
                            </p>

                            <div class="flex items-center">
                                <div class="grid md:grid-cols-2 gap-1" v-memo="[(event.eventProperties||[]).length]">
                                    <ToolTipComponent
                                        v-for="property in (event.eventProperties || [])"
                                        :key="property.name"
                                        :icon="property.icon"
                                        icon-size="size-4"
                                        :tooltip-text="property.name"
                                        classes="text-black"
                                        stroke="1.5"
                                    />
                                </div>

                                <div class="invisible group-hover/singleEvent:visible">
                                    <BaseMenu has-no-offset menuWidth="w-fit" :dots-color="settings.high_contrast ? 'text-white' : ''" white-menu-background>
                                        <!-- gleiche Items wie oben, kann via Slot/Partial wiederverwendet werden -->
                                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && !event.hasVerification" @click="SendEventToVerification" :icon="IconLock" :title="$t('Request verification')" />
                                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && event.hasVerification" @click="cancelVerification" :icon="IconLockOpen" :title="$t('Withdraw verification request')" />
                                        <BaseMenuItem white-menu-background v-if="event.hasVerification && (verifierForEventTypIds?.includes(event.eventType.id) || false)" @click="approveRequest" :icon="IconChecks" :title="$t('Approve verification')" />
                                        <BaseMenuItem white-menu-background v-if="event.hasVerification && (verifierForEventTypIds?.includes(event.eventType.id) || false)" @click="showRejectEventVerificationModal = true" :icon="IconCircleX" :title="$t('Reject verification')" />
                                        <BaseMenuItem white-menu-background @click="$emit('editEvent', event)" :icon="IconEdit" :title="$t('edit')" />
                                        <BaseMenuItem white-menu-background v-if="(isRoomAdmin || isCreator || hasAdminRole) && event?.eventType?.id === 1" @click="$emit('openAddSubEventModal', event, 'create', null)" :icon="IconCirclePlus" :title="$t('Add Sub-Event')" />
                                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" :icon="IconX" :title="$t('Decline event')" />
                                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" @click="$emit('openConfirmModal', event, 'main')" :icon="IconTrash" :title="$t('Delete')" />
                                    </BaseMenu>
                                </div>
                            </div>

                            <ul v-if="settings.work_shifts" class="ml-1 pb-1 text-xs" v-memo="[(event.shifts||[]).length, firstProjectShiftTabId]">
                                <li v-if="firstProjectShiftTabId" v-for="shift in (event.shifts || [])" :key="shift.id">
                                    <a :href="hrefs.shiftsCompact">
                                        <span>{{ shift.craft.abbreviation }}</span>
                                        <span>&nbsp;({{ shift.worker_count }}/{{ shift.max_worker_count }})</span>
                                    </a>
                                </li>
                            </ul>

                            <EventNoteComponent
                                v-if="settings.description"
                                v-memo="[event.id, event.updated_at]"
                                :event="event"
                                class="p-0.5 ml-0.5"
                                :style="styles.timeTextStyle"
                            />
                        </div>
                    </PopoverPanel>
                </Float>
            </Popover>
        </section>
    </section>

    <!-- Sub-Events -->
    <section v-if="(event.subEvents?.length || 0) > 0" class="w-full">
        <article
            v-for="subEvent in event.subEvents"
            :key="subEvent.id"
            class="mb-1 relative w-full group rounded-lg border-l-[6px]"
            :style="{ borderColor: subEventBorderColor }"
        >
            <div class="bg-indigo-500/50 hidden absolute inset-0 rounded-lg group-hover:flex items-center justify-center">
                <div class="flex items-center gap-2">
                    <button @click="$emit('editSubEvent', subEvent, 'edit', event)" type="button" class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <IconEdit class="h-4 w-4" stroke-width="1.5"/>
                    </button>
                    <button v-if="isRoomAdmin || isCreator || hasAdminRole" @click="$emit('openConfirmModal', subEvent, 'sub')" type="button" class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        <IconTrash class="w-4 h-4" stroke-width="1.5"/>
                    </button>
                </div>
            </div>

            <section :class="subEvent.class" :style="subEventStyle(subEvent)" class="px-2 py-1 rounded-r-lg overflow-y-auto">
                <header :style="headerBaseStyle" :class="cls.subEventHeader">
          <span class="flex truncate" v-if="subEvent.eventName?.length">
            <span v-if="subEvent.type.abbreviation" class="mr-1 truncate">{{ subEvent.type.abbreviation }}:</span>
            <span class="flex items-center truncate">{{ subEvent.eventName }}</span>
          </span>
                </header>

                <div :style="subEventTimeStyle" :class="cls.timeTextBase" class="flex">
                    <template v-if="subEvent.formattedDates.is_same_day && !project && !atAGlance">
                        <span v-if="subEvent.allDay">{{ $t('Full day') }}</span>
                        <span v-else>{{ subEvent.formattedDates.start_time }} - {{ subEvent.formattedDates.end_time }}</span>
                    </template>
                    <template v-else>
                        <template v-if="subEvent.allDay">
              <span v-if="atAGlance && subEvent.formattedDates.start_date === subEvent.formattedDates.end_date">
                {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }}
              </span>
                            <span v-else>
                <span class="text-error">{{ subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date ? '!' : '' }}</span>
                {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }} - {{ subEvent.formattedDates.end_date }}
              </span>
                        </template>
                        <template v-else>
                            <span class="text-error">{{ subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date ? '!' : '' }}</span>
                            {{ subEvent.formattedDates.start_date_time }} - {{ subEvent.formattedDates.end_date_time }}
                        </template>
                    </template>
                </div>

                <ul v-if="settings.work_shifts" class="ml-0.5 text-xs" :style="{ color: timeTextColor }" v-memo="[(subEvent.shifts||[]).length]">
                    <li v-for="shift in (subEvent.shifts || [])" :key="shift.id">
                        <span>{{ shift.craft.abbreviation }}</span>
                        ( <VueMathjax :formula="convertToMathJax(decimalToFraction(shift.user_count ? shift.user_count : 0))"/>/{{ shift.number_employees }}
                        <span v-if="shift.number_masters > 0">| {{ shift.master_count }}/{{ shift.number_masters }}</span> )
                    </li>
                </ul>
            </section>
        </article>
    </section>

    <RejectEventVerificationRequestModal
        v-if="showRejectEventVerificationModal"
        @close="showRejectEventVerificationModal = false"
        :event="event"
    />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
    IconCirclePlus, IconEdit, IconRepeat, IconTrash, IconUsersGroup, IconX, IconClock,
    IconInfoCircle, IconSquareCheckFilled, IconLock, IconLockOpen, IconChecks, IconCircleX
} from "@tabler/icons-vue";
import { Menu, MenuButton, MenuItem, MenuItems, Popover, PopoverButton, PopoverPanel } from "@headlessui/vue";
import VueMathjax from "vue-mathjax-next";
import { useI18n } from "vue-i18n";
import { useColorHelper } from "@/Composeables/UseColorHelper.js";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import EventNoteComponent from "@/Layouts/Components/EventNoteComponent.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import { Float } from "@headlessui-float/vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import RejectEventVerificationRequestModal from "@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue";

const { t } = useI18n(), $t = t;

const props = defineProps({
    event: { type: Object, required: true },
    multiEdit: { type: Boolean, default: false },
    fontSize: { type: String, default: '0.875rem' },
    lineHeight: { type: String, default: '1.25rem' },
    first_project_tab_id: { type: Number, default: 1 },
    project: { type: Object, default: null },
    hasAdminRole: { type: Boolean, default: false },
    rooms: { type: Object, required: true },
    width: { type: Number, required: true },
    firstProjectShiftTabId: { type: [Number, String], default: null },
    isHeightFull: { type: Boolean, default: false },
    isInDailyView: { type: Boolean, default: false },
    verifierForEventTypIds: { type: Array, default: [] },
    isPlanning: { type: Boolean, default: false }
});

const emits = defineEmits(['editEvent','editSubEvent','openAddSubEventModal','openConfirmModal','showDeclineEventModal','changedMultiEditCheckbox']);

const page = usePage();
const user = computed(() => page.props.auth.user);
const settings = computed(() => user.value.calendar_settings);
const hcPercent = computed(() => page.props.high_contrast_percent || 0.0);
const zoom_factor = ref(user.value.zoom_factor ?? 1);
const atAGlance  = ref(user.value.at_a_glance ?? false);
const showRejectEventVerificationModal = ref(false);

// Zeit-Basis
const startDate = computed(() => new Date(props.event.start));
const endDate   = computed(() => new Date(props.event.end));
const sameDay   = computed(() => startDate.value.toDateString() === endDate.value.toDateString());
const pad = (n:number) => (n < 10 ? '0' + n : '' + n);
const startHHmm = computed(() => `${pad(startDate.value.getHours())}:${pad(startDate.value.getMinutes())}`);
const endHHmm   = computed(() => `${pad(endDate.value.getHours())}:${pad(endDate.value.getMinutes())}`);
const startDDMM = computed(() => `${pad(startDate.value.getDate())}.${pad(startDate.value.getMonth()+1)}.`);
const endDDMM   = computed(() => `${pad(endDate.value.getDate())}.${pad(endDate.value.getMonth()+1)}.`);
const startDDMMHHmm = computed(() => `${pad(startDate.value.getDate())}.${pad(startDate.value.getMonth()+1)}. ${pad(startDate.value.getHours())}:${pad(startDate.value.getMinutes())}`);
const endDDMMHHmm   = computed(() => `${pad(endDate.value.getDate())}.${pad(endDate.value.getMonth()+1)}. ${pad(endDate.value.getHours())}:${pad(endDate.value.getMinutes())}`);

// Klassen (stabil)
const containerClasses = computed(() => ({
    'event-disabled': !!props.event.occupancy_option,
    'border-[3px] border-dashed border-pink-500': (settings.value.time_period_project_id === props?.event?.project?.id) || isHighlighted.value,
    'h-full': props.isHeightFull,
    'overflow-y-scroll': user.value.daily_view,
    'relative': props.multiEdit
}));

// Flags
const showMultiEditOverlay = computed(() => {
    if (!props.multiEdit) return false;
    if (props.isPlanning) return props.event.hasVerification || props.event.isPlanning;
    return zoom_factor.value > 0.4;
});
const isHighlighted = computed(() => {
    const id = page.props.urlParameters?.highlightEventId;
    return id && parseInt(id) === parseInt(props.event.id);
});
const showProjectStatusDot = computed(() => settings.value.project_status && !!props.event.project?.status);
const isSameDayNonProject = computed(() => sameDay.value && !props.project && !atAGlance.value);
const hasProjNameAndId    = computed(() => !!(props.event.project?.name && props.event.project?.id));
const isSmallView         = computed(() => zoom_factor.value <= 0.6);
const showStatusBanner    = computed(() => (props.event.isPlanning && !props.event.hasVerification) || props.event.hasVerification);

// Status-Banner-Klasse
const statusBannerClass = computed(() =>
    props.event.hasVerification ? 'bg-orange-500' : 'bg-artwork-buttons-create'
);

// Farben/Hintergrund
const { backgroundColorWithOpacity, detectParentBackgroundColor, getTextColorBasedOnBackground, parentBackgroundColor } = useColorHelper();
const baseHex = computed(() =>
    (settings.value?.use_event_status_color ? props.event?.eventStatus?.color : null) ??
    props.event?.eventType?.hex_code ??
    props.event?.event_type_color ??
    '#9CA3AF'
);
const hcAlpha = computed(() => {
    const v = Number(hcPercent.value ?? 0);
    return v > 1 ? v / 100 : v;
});
function hexToRgb(hex: string) {
    const h = hex.replace('#','');
    const n = h.length === 3 ? h.split('').map(c => c + c).join('') : h;
    const r = parseInt(n.slice(0,2),16), g = parseInt(n.slice(2,4),16), b = parseInt(n.slice(4,6),16);
    return [r,g,b] as const;
}
function rgbToHex(r: number, g: number, b: number) {
    const to = (x:number) => x.toString(16).padStart(2,'0');
    return `#${to(r)}${to(g)}${to(b)}`;
}
function precomposeHex(fgHex: string, bgHex: string, alpha: number) {
    const [fr,fgc,fb] = hexToRgb(fgHex);
    const [br,bg,bb]  = hexToRgb(bgHex);
    const ar = Math.min(Math.max(alpha,0),1);
    const r = Math.round(ar*fr + (1-ar)*br);
    const g = Math.round(ar*fgc + (1-ar)*bg);
    const b = Math.round(ar*fb + (1-ar)*bb);
    return rgbToHex(r,g,b);
}
const compositeBase = computed(() => parentBackgroundColor.value || '#ffffff');
const eventSurfaceColor = computed(() => precomposeHex(baseHex.value, compositeBase.value, hcAlpha.value));
const timeTextColor  = computed(() => getTextColorBasedOnBackground(eventSurfaceColor.value));

// Stile
const headerBaseStyle = computed(() => ({ lineHeight: props.lineHeight, fontSize: props.fontSize }));
const headerTextStyle = computed(() => ({ ...headerBaseStyle.value, color: timeTextColor.value }));
const timeTextStyle   = computed(() => ({ ...headerBaseStyle.value, color: timeTextColor.value }));
const projectStatusDotStyle = computed(() => ({
    backgroundColor: (props.event?.project?.status?.color || '') + '33',
    borderColor: props.event?.project?.status?.color || 'transparent'
}));
const containerStyle = computed(() => ({
    minHeight: `${(totalHeight.value - heightSubtraction.value) * zoom_factor.value}px`,
    backgroundColor: eventSurfaceColor.value,
    fontSize: props.fontSize,
    lineHeight: props.lineHeight
}));

// Höhe
const totalHeight = computed(() => {
    let h = 42;
    if (settings.value.project_management) h += 17;
    if (settings.value.repeating_events) h += 20;
    if (settings.value.work_shifts) h += 18;
    return h;
});
const heightSubtraction = computed(() => {
    let sub = 0;
    const e = props.event;
    if (settings.value.project_management && (!e.projectLeaders || e.projectLeaders.length < 1)) sub += 17;
    if (settings.value.repeating_events && (!e.is_series)) sub += 20;
    if (settings.value.work_shifts && (!e.shifts || e.shifts.length < 1)) sub += 18;
    return sub;
});

// Routen/Hrefs
const projectHref = computed(() => route('projects.tab', { project: props.event?.project?.id, projectTab: props.first_project_tab_id }));
const hrefs = {
    project: projectHref,
    shifts: computed(() => route('projects.tab', { project: props.event?.project?.id, projectTab: props.firstProjectShiftTabId })),
    shiftsCompact: computed(() => route('projects.tab', { project: props.event?.projectId, projectTab: props.firstProjectShiftTabId }))
};

// SubEvent Styles
const subEventBorderColor = computed(() => backgroundColorWithOpacity(getEventColor().value, hcPercent.value));
const getEventColor = () => computed(() =>
    settings.value.use_event_status_color ? (props.event?.eventStatus?.color) : props.event.eventType.hex_code
);
function subEventStyle(se:any) {
    const h = (totalHeight.value - subHeightSubtraction(se)) * zoom_factor.value + 10;
    return {
        height: `${h}px`,
        backgroundColor: backgroundColorWithOpacity(se.type.hex_code, hcPercent.value)
    };
}
function subHeightSubtraction(se:any) {
    let s = 0;
    if (settings.value.work_shifts && (!se.shifts || se.shifts.length < 1)) s += 18;
    return s;
}

// Multi-Edit
function toggleMultiEdit() {
    props.event.considerOnMultiEdit = !props.event.considerOnMultiEdit;
    emitMultiEditChange();
}
function emitMultiEditChange() {
    const e = props.event;
    emits('changedMultiEditCheckbox', e.id, e.considerOnMultiEdit, e.roomId, e.start, e.end);
}

// Aktionen
function SendEventToVerification() { router.post(route('events.sendToVerification', { event: props.event.id }), { preserveScroll: true, preserveState: true }); }
function cancelVerification() { router.post(route('event-verifications.cancel-verification', props.event.id), {}, { preserveScroll: true, preserveState: false }); }
function approveRequest() { router.post(route('event-verifications.approved-by-event', props.event.id), {}, { preserveScroll: true, preserveState: true }); }

// Math/Format Helpers
function convertToMathJax(fr:string) {
    const parts = fr.split(' ');
    if (parts.length === 1) return `${parts[0]}`;
    const whole = +parts[0] > 0 ? parts[0] : "";
    const [n, d] = parts[1].split('/');
    return `${whole}$\\frac{${n}}{${d}}$`;
}
function decimalToFraction(decimal:number) {
    let whole = Math.floor(decimal);
    decimal = decimal - whole;
    if ((decimal as any) === parseInt(decimal as any)) return decimal < 1 ? `${whole}` : `${parseInt(decimal as any)}/1`;
    let precision = getFirstDigitAfterDecimal(decimal) === 3 ? 3 : 1000;
    let top = Math.round(decimal * precision);
    let bottom = precision;
    let g = gcd(top, bottom);
    return `${whole} ${top / g}/${bottom / g}`;
}
function getFirstDigitAfterDecimal(n:number) {
    const dec = n.toString().split('.')[1];
    return dec && dec.length ? parseInt(dec[0]) : null;
}
function gcd(a:number, b:number){ return b ? gcd(b, a % b) : a; }

// Berechtigungen
const isCreator = computed(() => props.event.created_by?.id === user.value.id);
const isRoomAdmin = computed(() => {
    const room = (props.rooms as any[])?.find((r:any) => r.id === props.event.roomId);
    return !!room?.admins?.some((a:any) => a.id === user.value.id);
});

// Stabile Klassen/Styles-Bundles
const cls = {
    statusBanner: computed(() => 'w-full rounded-t-lg px-2 py-1 text-[10px] font-lexend select-none pointer-events-none text-white'),
    titleHeader: computed(() => (zoom_factor.value === 1 ? 'eventHeader font-bold' : 'font-bold')),
    timeTextBase: computed(() => (zoom_factor.value === 1 ? 'eventTime font-medium subpixel-antialiased' : 'font-medium subpixel-antialiased')),
    rightMenuWrapper: computed(() => [
        'invisible',
        'group-hover/singleEvent:visible',
        'flex',
        'w-full',
        'items-start',
        'justify-end',
        props.event.isPlanning ? 'pt-2' : ''
    ].filter(Boolean).join(' ')),
    miniHeader: computed(() => (zoom_factor.value === 1 ? 'eventHeader font-bold' : 'font-bold')),
    subEventHeader: computed(() => [
        zoom_factor.value === 1 ? 'eventHeader' : '',
        'font-bold',
        'flex',
        'justify-between',
        'w-36'
    ].join(' '))
};

const styles = {
    headerTextStyle,
    timeTextStyle,
    headerBaseStyle,
    repeatTextStyle: computed(() => ({ lineHeight: props.lineHeight, fontSize: props.fontSize })),
    projectStateDotBorder: computed(() => [props.event.projectStateColor, zoom_factor.value <= 0.8 ? 'border-2' : 'border-4'].filter(Boolean).join(' '))
};

const rootEl = ref<HTMLElement|null>(null);
onMounted(() => {
    if (rootEl.value) detectParentBackgroundColor(rootEl.value);
    if (rootEl.value) detectParentBackgroundColor(rootEl.value); // parentBackgroundColor für Mischfarbe
});
</script>
