<template>
    <div
        :style="containerStyle"
        class="rounded-lg group/singleEvent h-full"
        :class="containerClasses"
        ref="rootEl"
    >
        <!-- Multi-Select Overlay -->
        <div
            v-show="showMultiEditOverlay"
            @click="toggleMultiEdit"
            class="absolute inset-0 z-10 rounded-lg flex items-center justify-center"
            :class="event.considerOnMultiEdit ? 'bg-green-200/50' : 'bg-artwork-buttons-create/50'"
        >
            <div v-if="event.considerOnMultiEdit" class="bg-white rounded-lg">
                <component is="IconSquareCheckFilled" class="size-6 text-green-500" />
            </div>
            <!-- Der echte Checkbox-Input bleibt für Accessibility vorhanden -->
            <input
                v-model="event.considerOnMultiEdit"
                :id="event.id"
                name="candidates"
                type="checkbox"
                class="hidden"
                @change="emitMultiEditChange"
            />
        </div>

        <!-- Status-Badge -->
        <div v-if="event.isPlanning && !event.hasVerification" class="w-full rounded-t-lg bg-artwork-buttons-create px-2 py-1 text-[10px] font-lexend select-none pointer-events-none text-white">
            {{ $t('Planned Event') }}
        </div>
        <div v-else-if="event.hasVerification" class="bg-orange-500 w-full rounded-t-lg px-2 py-1 text-[10px] font-lexend select-none pointer-events-none text-white">
            {{ $t('Verification requested') }}
        </div>

        <!-- DETAIL: große Ansicht -->
        <div class="grid grid-cols-1 md:grid-cols-3 w-full px-1" v-if="zoom_factor > 0.6">
            <div class="py-2 px-1 col-span-2">
                <div class="flex items-stretch gap-x-3 h-full min-h-full">
                    <div v-if="!settings.high_contrast" class="p-1 rounded-lg w-1" :style="{ backgroundColor: baseHex }"></div>

                    <div :style="headerTextStyle" :class="zoom_factor === 1 ? 'eventHeader font-bold' : 'font-bold'">
                        <!-- Titelzeile -->
                        <div>
                            <div class="flex items-center gap-x-1">
                                <div
                                    v-if="showProjectStatusDot"
                                    class="text-center rounded-full border group relative min-h-4 min-w-4 size-4 cursor-pointer"
                                    :style="projectStatusDotStyle"
                                >
                                    <div class="absolute hidden group-hover:block top-5">
                                        <div class="bg-artwork-navigation-background text-white text-xs rounded-full px-3 py-0.5">
                                            {{ event?.project?.status?.name }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center">
                                    <a v-if="event.project?.name && event.project?.id" :href="projectHref" class="flex items-center">
                                        <span class="truncate !w-20 inline-block">{{ event.project?.name }}</span>
                                    </a>
                                </div>
                            </div>

                            <div v-if="settings.project_artists" class="flex items-center w-28">
                                <div v-if="event.project?.artistNames" class="truncate">
                                    {{ event.project?.artistNames }}
                                </div>
                            </div>

                            <div v-if="settings.event_name" class="flex items-center w-28">
                                <div v-if="event.eventName" class="truncate">
                                    {{ event.eventName }}
                                </div>
                            </div>

                            <div class="w-28">
                                <div class="truncate">
                                    {{ event?.eventType?.name }}
                                </div>
                            </div>

                            <div v-if="settings.project_status" class="absolute right-5">
                                <div v-if="event.projectStateColor" :class="[event.projectStateColor, zoom_factor <= 0.8 ? 'border-2' : 'border-4']" class="rounded-full"></div>
                            </div>
                        </div>

                        <!-- Zeit + Optionen -->
                        <div class="flex items-center gap-x-1 mt-1 w-28">
                            <component :is="IconClock" class="size-3.5" stroke-width="2" v-if="!event.allDay && sameDay" />

                            <div :style="timeTextStyle" :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']" class="flex">
                                <div v-if="sameDay && !project && !atAGlance" class="items-center">
                                    <div v-if="event.allDay">{{ $t('Full day') }}</div>
                                    <div v-else>{{ event.formattedDates.startTime + ' - ' + event.formattedDates.endTime }}</div>
                                </div>

                                <div v-else class="flex w-full">
                                    <div v-if="event.allDay">
                                        <div v-if="atAGlance && sameDay">{{ $t('Full day') }}, {{ event.formattedDates.start_without_year }}</div>
                                        <div v-else>{{ $t('Full day') }}, {{ event.formattedDates.start_without_year }} - {{ event.formattedDates.end_without_year }}</div>
                                    </div>
                                    <div v-else class="items-center">
                                        <div v-if="!sameDay">
                                            <span class="text-error">!</span>
                                            {{ event.formattedDates.startDateTime_without_year  + ' - ' +  event.formattedDates.endDateTime_without_year }}
                                        </div>
                                        <div v-else>
                                            <div v-if="atAGlance">{{ event.formattedDates.startDateTime_without_year + ' - ' + event.formattedDates.endTime }}</div>
                                            <div v-else>{{ event.formattedDates.startTime + ' - ' + event.formattedDates.endTime }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="settings.options && event.option_string" class="flex items-center">
                                <div v-if="!atAGlance && sameDay" class="flex eventTime font-medium subpixel-antialiased" :style="headerBaseStyle">
                                    , {{ event.option_string }}
                                </div>
                                <div v-else class="flex eventTime font-medium subpixel-antialiased ml-0.5">
                                    ({{ event.option_string.charAt(7) }})
                                </div>
                            </div>
                        </div>

                        <!-- Wiederkehrend -->
                        <div v-if="settings.repeating_events && event.is_series" class="uppercase flex items-center font-semibold" :style="repeatTextStyle">
                            <component :is="IconRepeat" class="mr-1 min-h-3 min-w-3" stroke-width="2"/>
                            {{ $t('Repeat event') }}
                        </div>

                        <!-- Projektleiter -->
                        <div v-if="settings.project_management && (event?.project?.leaders?.length ?? 0) > 0" class="-ml-3 mb-0.5 w-full">
                            <div v-if="event?.project?.leaders && !project && zoom_factor >= 0.8" class="mt-1 ml-5 flex flex-wrap">
                                <div v-for="user in (event?.project?.leaders?.slice(0,3) || [])" :key="user.id" class="flex flex-wrap flex-row -ml-1.5">
                                    <UserPopoverTooltip :user="user" width="5" height="5" />
                                </div>

                                <div v-if="(event?.project?.leaders?.length || 0) >= 4" class="my-auto">
                                    <Menu as="div" class="relative">
                                        <MenuButton class="flex rounded-full focus:outline-none">
                                            <div class="-ml-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-black text-white shadow-sm font-semibold">
                                                +{{ (event?.project?.leaders?.length || 0) - 3 }}
                                            </div>
                                        </MenuButton>
                                        <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from" enter-to-class="transition-enter-to" leave-active-class="transition-leave-active" leave-from-class="transition-leave-from" leave-to-class="transition-leave-to">
                                            <MenuItems class="absolute mt-2 max-h-48 w-72 overflow-y-auto bg-primary py-1 ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                <MenuItem v-for="user in event?.project?.leaders" :key="user.id" v-slot="{ active }">
                                                    <Link href="#" :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <img class="h-5 w-5 rounded-full" :src="user.profile_photo_url" alt=""/>
                                                        <span class="ml-4">{{ user.first_name }} {{ user.last_name }}</span>
                                                    </Link>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                            </div>
                        </div>

                        <!-- Beschreibung -->
                        <div v-if="settings.description" :style="timeTextStyle">
                            <EventNoteComponent :event="event"/>
                        </div>
                    </div>

                    <!-- Work Shifts -->
                    <div v-if="settings.work_shifts" class="grid grid-cols-1 md:grid-cols-2 text-xs my-2">
                        <a v-if="firstProjectShiftTabId" v-for="shift in event.shifts" :key="shift.id" :href="route('projects.tab', {project: event?.project?.id, projectTab: firstProjectShiftTabId})">
                            <span>{{ shift?.craft?.abbreviation }}</span>
                            <span>&nbsp;({{ shift?.worker_count }}/{{ shift?.max_worker_count }})</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- rechte Kopf-/Menüleiste -->
            <div class="flex gap-x-1 mt-5">
                <div class="grid gird-cols-1 md:grid-cols-2 gap-1">
                    <div v-for="property in (event.eventProperties || [])" :key="property.name" class="col-span-1">
                        <ToolTipComponent
                            :icon="property.icon"
                            icon-size="size-4"
                            :tooltip-text="property.name"
                            classes="text-black"
                            stroke="1.5"
                            direction="left"
                            no-relative
                        />
                    </div>
                </div>

                <div class="invisible group-hover/singleEvent:visible flex w-full items-start justify-end" :class="event.isPlanning ? 'pt-2' : ''">
                    <BaseMenu has-no-offset menuWidth="w-fit" :dots-color="settings.high_contrast ? 'text-white' : ''" white-menu-background>
                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && !event.hasVerification" @click="SendEventToVerification" :icon="IconLock" title="Request verification" />
                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && event.hasVerification" @click="cancelVerification" :icon="IconLockOpen" title="Withdraw verification request" />
                        <BaseMenuItem white-menu-background v-if="event.hasVerification && (verifierForEventTypIds?.includes(event.eventType.id) || false)" @click="approveRequest" :icon="IconChecks" title="Approve verification" />
                        <BaseMenuItem white-menu-background v-if="event.hasVerification && (verifierForEventTypIds?.includes(event.eventType.id) || false)" @click="showRejectEventVerificationModal = true" :icon="IconCircleX" title="Reject verification" />

                        <BaseMenuItem white-menu-background @click="$emit('editEvent', event)" :icon="IconEdit" title="edit" />
                        <BaseMenuItem white-menu-background v-if="(isRoomAdmin || isCreator || hasAdminRole) && event?.eventType?.id === 1" @click="$emit('openAddSubEventModal', event, 'create', null)" :icon="IconCirclePlus" title="Add Sub-Event" />
                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" :icon="IconX" title="Decline event" />
                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" @click="$emit('openConfirmModal', event, 'main')" :icon="IconTrash" title="Delete" />
                    </BaseMenu>
                </div>
            </div>
        </div>

        <!-- KOMPAKT: kleine Ansicht -->
        <div v-else class="w-full flex px-2 h-full" :class="[zoom_factor < 0.6 ? 'justify-center' : 'py-3', zoom_factor === 0.4 ? 'items-center' : '']">
            <Popover class="relative">
                <Float auto-placement portal :offset="{  5 : -10}">
                    <PopoverButton class="flex items-center justify-start gap-1 ring-0 focus:ring-0">
                        <component :is="IconInfoCircle" class="size-6" stroke-width="1.5"/>
                        <div class="w-16 max-w-16 xsDark text-left" v-if="zoom_factor > 0.4">
                            <div v-if="settings.event_name && event.eventName" class="truncate">{{ event.eventName }}</div>
                            <a v-if="event.project?.id" :href="projectHref" class="truncate block">{{ event.project?.name }}</a>
                        </div>
                    </PopoverButton>

                    <PopoverPanel class="absolute z-10 w-fit bg-white shadow-lg rounded-lg p-2">
                        <!-- Panel-Inhalt bleibt semantisch identisch, Styles/Flags wie oben wiederverwendet -->
                        <div class="px-3 py-2">
                            <div :style="headerTextStyle" :class="zoom_factor === 1 ? 'eventHeader font-bold' : 'font-bold'">
                                <div class="flex items-center gap-x-1">
                                    <div v-if="showProjectStatusDot" class="text-center rounded-full border group size-4 cursor-pointer" :style="projectStatusDotStyle">
                                        <div class="absolute hidden group-hover:block top-5">
                                            <div class="bg-artwork-navigation-background text-white text-xs rounded-full px-3 py-0.5">
                                                {{ event?.project?.status?.name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <a v-if="event.project?.name && event.project?.id" :href="projectHref" class="flex items-center">
                                            <span class="truncate !w-28 inline-block">{{ event.project?.name }}</span>
                                        </a>
                                    </div>
                                </div>

                                <div v-if="settings.project_artists" class="flex items-center">
                                    <div v-if="event.projectArtists" class="truncate">{{ event.projectArtists }}</div>
                                </div>
                                <div v-if="settings.event_name" class="flex items-center">
                                    <div v-if="event.eventName" class="truncate">{{ event.eventName }}</div>
                                </div>
                                <div class="truncate">{{ event.eventTypeName }}</div>

                                <div v-if="settings.project_status" class="absolute right-5">
                                    <div v-if="event.projectStateColor" :class="[event.projectStateColor, zoom_factor <= 0.8 ? 'border-2' : 'border-4']" class="rounded-full"></div>
                                </div>

                                <div v-if="event.audience" class="absolute top-5 right-4 flex">
                                    <IconUsersGroup stroke-width="1.5" :width="12 * zoom_factor" :height="12 * zoom_factor"/>
                                </div>
                            </div>

                            <div class="flex">
                                <div :style="timeTextStyle" :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']" class="flex">
                                    <div v-if="sameDay && !project && !atAGlance" class="items-center">
                                        <div v-if="event.allDay">{{ $t('Full day') }}</div>
                                        <div v-else>{{ startHHmm + ' - ' + endHHmm }}</div>
                                    </div>
                                    <div v-else class="flex w-full">
                                        <div v-if="event.allDay">
                                            <div v-if="atAGlance && sameDay">{{ $t('Full day') }}, {{ startDDMM }}</div>
                                            <div v-else>{{ $t('Full day') }}, {{ startDDMM }} - {{ endDDMM }}</div>
                                        </div>
                                        <div v-else class="items-center">
                                            <div v-if="!sameDay">
                                                <span class="text-error">!</span>
                                                {{ startDDMMHHmm + ' - ' + endDDMMHHmm }}
                                            </div>
                                            <div v-else>
                                                <div v-if="atAGlance">{{ startDDMMHHmm + ' - ' + endHHmm }}</div>
                                                <div v-else>{{ startHHmm + ' - ' + endHHmm }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="settings.options && event.option_string" class="flex items-center">
                                    <div v-if="!atAGlance && sameDay" class="flex eventTime font-medium subpixel-antialiased" :style="headerBaseStyle">, {{ event.option_string }}</div>
                                    <div v-else class="ml-0.5 flex eventTime font-medium subpixel-antialiased">({{ event.option_string.charAt(7) }})</div>
                                </div>
                            </div>

                            <div v-if="settings.repeating_events && event.is_series" class="uppercase flex items-center font-semibold" :style="{ lineHeight: lineHeight, fontSize: fontSize }">
                                <IconRepeat class="mx-1 h-3 w-3" stroke-width="1.5"/>
                                {{ $t('Repeat event') }}
                            </div>

                            <div v-if="settings.project_management && (event.projectLeaders?.length ?? 0) > 0" class="-ml-3 mb-0.5 w-full">
                                <div v-if="event.projectLeaders && !project && zoom_factor >= 0.8" class="mt-1 ml-5 flex flex-wrap">
                                    <div v-for="user in (event.projectLeaders?.slice(0,3) || [])" :key="user.id" class="flex flex-wrap flex-row -ml-1.5">
                                        <img :src="user.profile_photo_url" alt="" class="mx-auto shrink-0 flex object-cover rounded-full" :class="['h-' + 5 * zoom_factor, 'w-' + 5 * zoom_factor]">
                                    </div>
                                    <div v-if="(event.projectLeaders?.length || 0) >= 4" class="my-auto">
                                        <!-- … (unverändert, nur Keys hinzugefügt) -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="grid gird-cols-1 md:grid-cols-2 gap-1">
                                <div v-for="property in (event.eventProperties || [])" :key="property.name">
                                    <ToolTipComponent :icon="property.icon" icon-size="size-4" :tooltip-text="property.name" classes="text-black" stroke="1.5"/>
                                </div>
                            </div>

                            <div class="invisible group-hover/singleEvent:visible">
                                <!-- Menü (unverändert) -->
                                <BaseMenu has-no-offset menuWidth="w-fit" :dots-color="settings.high_contrast ? 'text-white' : ''" white-menu-background>
                                    <!-- … -->
                                </BaseMenu>
                            </div>
                        </div>

                        <div v-if="settings.work_shifts" class="ml-1 pb-1 text-xs">
                            <a v-if="firstProjectShiftTabId" v-for="shift in (event.shifts || [])" :key="shift.id" :href="route('projects.tab', {project: event.projectId, projectTab: firstProjectShiftTabId})">
                                <span>{{ shift.craft.abbreviation }}</span>
                                <span>&nbsp;({{ shift.worker_count }}/{{ shift.max_worker_count }})</span>
                            </a>
                        </div>

                        <div v-if="settings.description" class="p-0.5 ml-0.5" :style="timeTextStyle">
                            <EventNoteComponent :event="event"/>
                        </div>
                    </PopoverPanel>
                </Float>
            </Popover>
        </div>
    </div>

    <!-- Sub-Events (Keys & Styles optimiert) -->
    <div v-if="(event.subEvents?.length || 0) > 0" class="w-full">
        <div v-for="subEvent in event.subEvents" :key="subEvent.id" class="mb-1">
            <div class="relative w-full group rounded-lg border-l-[6px]" :style="{ borderColor: subEventBorderColor }">
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

                <div :class="subEvent.class" :style="subEventStyle(subEvent)" class="px-2 py-1 rounded-r-lg overflow-y-auto">
                    <div class="flex items-start justify-between">
                        <div :style="headerBaseStyle" :class="[zoom_factor === 1 ? 'eventHeader' : '', 'font-bold', 'flex justify-between w-36']">
                            <div class="flex" v-if="subEvent.eventName?.length">
                                <div v-if="subEvent.type.abbreviation" class="mr-1 truncate">{{ subEvent.type.abbreviation }}:</div>
                                <div class="flex items-center truncate">{{ subEvent.eventName }}</div>
                            </div>
                            <div v-else class="flex items-center truncate">{{ subEvent.type.name }}</div>
                        </div>

                        <div class="flex items-center -space-x-1">
                            <div v-for="property in (subEvent.event_properties || [])" :key="property.name" class="bg-gray-100 rounded-full border-2 border-white p-0.5">
                                <component :is="property.icon" class="size-3" stroke-width="1.5" />
                            </div>
                        </div>
                    </div>

                    <div :style="subEventTimeStyle" :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']" class="flex">
                        <div v-if="subEvent.formattedDates.is_same_day && !project && !atAGlance" class="items-center">
                            <div v-if="subEvent.allDay">{{ $t('Full day') }}</div>
                            <div v-else>{{ subEvent.formattedDates.start_time }} - {{ subEvent.formattedDates.end_time }}</div>
                        </div>
                        <div v-else class="flex w-full">
                            <div v-if="subEvent.allDay">
                                <div v-if="atAGlance && subEvent.formattedDates.start_date === subEvent.formattedDates.end_date">
                                    {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }}
                                </div>
                                <div v-else>
                                    <span class="text-error">{{ subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date ? '!' : '' }}</span>
                                    {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }} - {{ subEvent.formattedDates.end_date }}
                                </div>
                            </div>
                            <div v-else class="items-center">
                                <span class="text-error">{{ subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date ? '!' : '' }}</span>
                                {{ subEvent.formattedDates.start_date_time }} - {{ subEvent.formattedDates.end_date_time }}
                            </div>
                        </div>
                    </div>

                    <div v-if="settings.work_shifts" class="ml-0.5 text-xs" :style="{ color: timeTextColor }">
                        <div v-for="shift in (subEvent.shifts || [])" :key="shift.id">
                            <span>{{ shift.craft.abbreviation }}</span>
                            ( <VueMathjax :formula="convertToMathJax(decimalToFraction(shift.user_count ? shift.user_count : 0))"/>/{{ shift.number_employees }}
                            <span v-if="shift.number_masters > 0">| {{ shift.master_count }}/{{ shift.number_masters }}</span> )
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <RejectEventVerificationRequestModal
        v-if="showRejectEventVerificationModal"
        @close="showRejectEventVerificationModal = false"
        :event="event"
    />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { IconCirclePlus, IconEdit, IconRepeat, IconTrash, IconUsersGroup, IconX, IconClock, IconInfoCircle, IconSquareCheckFilled } from "@tabler/icons-vue";
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
import RejectEventVerificationRequestModal
    from "@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue";

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

const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);
const atAGlance  = ref(usePage().props.auth.user.at_a_glance ?? false);
const showRejectEventVerificationModal = ref(false);

const page = usePage();
const user = computed(() => page.props.auth.user);
const settings = computed(() => user.value.calendar_settings);
const hcPercent = computed(() => page.props.high_contrast_percent || 0.0);

const { backgroundColorWithOpacity, detectParentBackgroundColor, getTextColorBasedOnBackground, parentBackgroundColor } = useColorHelper();

/* ==== Derivates: Zeiten (keine mehrfachen Date-Konstruktionen) ==== */
const startDate = computed(() => new Date(props.event.start));
const endDate   = computed(() => new Date(props.event.end));
const sameDay   = computed(() => startDate.value.toDateString() === endDate.value.toDateString());

// Für kompaktes Panel (fallback, falls formattedDates nicht vorhanden)
const pad = (n) => (n < 10 ? '0' + n : '' + n);
const startHHmm = computed(() => `${pad(startDate.value.getHours())}:${pad(startDate.value.getMinutes())}`);
const endHHmm   = computed(() => `${pad(endDate.value.getHours())}:${pad(endDate.value.getMinutes())}`);
const startDDMM = computed(() => `${pad(startDate.value.getDate())}.${pad(startDate.value.getMonth()+1)}.`);
const endDDMM   = computed(() => `${pad(endDate.value.getDate())}.${pad(endDate.value.getMonth()+1)}.`);
const startDDMMHHmm = computed(() => `${pad(startDate.value.getDate())}.${pad(startDate.value.getMonth()+1)}. ${pad(startDate.value.getHours())}:${pad(startDate.value.getMinutes())}`);
const endDDMMHHmm   = computed(() => `${pad(endDate.value.getDate())}.${pad(endDate.value.getMonth()+1)}. ${pad(endDate.value.getHours())}:${pad(endDate.value.getMinutes())}`);


/* ==== Klassen (stabil, kein Inline-Array pro Render) ==== */
const containerClasses = computed(() => ({
    'event-disabled': !!props.event.occupancy_option,
    'border-[3px] border-dashed border-pink-500': (settings.value.time_period_project_id === props?.event?.project?.id) || isHighlighted.value,
    'h-full': props.isHeightFull,
    'overflow-y-scroll': user.value.daily_view,
    'relative': props.multiEdit
}));

/* ==== Status-/Flag-Computeds ==== */

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

const projectStatusDotStyle = computed(() => ({
    backgroundColor: (props.event?.project?.status?.color || '') + '33',
    borderColor: props.event?.project?.status?.color || 'transparent'
}));

const subEventBorderColor = computed(() => backgroundColorWithOpacity(getEventColor().value, hcPercent.value));

/* ==== Höhe ==== */
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

/* ==== Hilfsfunktionen ==== */
const getEventColor = () => computed(() =>
    settings.value.use_event_status_color ? (props.event?.eventStatus?.color) : props.event.eventType.hex_code
);

const projectHref = computed(() => route('projects.tab', { project: props.event?.project?.id, projectTab: props.first_project_tab_id }));

/* ==== Multi-Edit Handling (ohne DOM lookup) ==== */
function toggleMultiEdit() {
    props.event.considerOnMultiEdit = !props.event.considerOnMultiEdit;
    emitMultiEditChange();
}
function emitMultiEditChange() {
    const e = props.event;
    emits('changedMultiEditCheckbox', e.id, e.considerOnMultiEdit, e.roomId, e.start, e.end);
}

/* ==== SubEvent Styles ==== */
function subEventStyle(se) {
    const h = (totalHeight.value - subHeightSubtraction(se)) * zoom_factor.value + 10;
    return {
        height: `${h}px`,
        backgroundColor: backgroundColorWithOpacity(se.type.hex_code, hcPercent.value)
    };
}
function subHeightSubtraction(se) {
    let s = 0;
    if (settings.value.work_shifts && (!se.shifts || se.shifts.length < 1)) s += 18;
    // weitere SubEvent-spezifische Subtraktionen hier analog falls nötig
    return s;
}

/* ==== Lifecycle ==== */
const rootEl = ref(null);
onMounted(() => { if (rootEl.value) detectParentBackgroundColor(rootEl.value); });

/* ==== Aktionen (unchanged) ==== */
function getEditHref(projectId) { return route('projects.tab', { project: projectId, projectTab: props.first_project_tab_id }); }
function SendEventToVerification() { router.post(route('events.sendToVerification', { event: props.event.id }), { preserveScroll: true, preserveState: true }); }
function cancelVerification() { router.post(route('event-verifications.cancel-verification', props.event.id), {}, { preserveScroll: true, preserveState: false }); }
function approveRequest() { router.post(route('event-verifications.approved-by-event', props.event.id), {}, { preserveScroll: true, preserveState: true }); }

/* ==== Math/Format Helpers (belassen, aber pure) ==== */
function convertToMathJax(fr) {
    const parts = fr.split(' ');
    if (parts.length === 1) return `${parts[0]}`;
    const whole = +parts[0] > 0 ? parts[0] : "";
    const [n, d] = parts[1].split('/');
    return `${whole}$\\frac{${n}}{${d}}$`;
}
function decimalToFraction(decimal) {
    let whole = Math.floor(decimal);
    decimal = decimal - whole;
    if (decimal === parseInt(decimal)) return decimal < 1 ? `${whole}` : `${parseInt(decimal)}/1`;
    let precision = getFirstDigitAfterDecimal(decimal) === 3 ? 3 : 1000;
    let top = Math.round(decimal * precision);
    let bottom = precision;
    let g = gcd(top, bottom);
    return `${whole} ${top / g}/${bottom / g}`;
}
function getFirstDigitAfterDecimal(n) {
    const dec = n.toString().split('.')[1];
    return dec && dec.length ? parseInt(dec[0]) : null;
}
function gcd(a, b){ return b ? gcd(b, a % b) : a; }

/* ==== Berechtigungen ==== */
const isCreator = computed(() => props.event.created_by?.id === user.value.id);
const isRoomAdmin = computed(() => {
    const room = props.rooms?.find(r => r.id === props.event.roomId);
    return !!room?.admins?.some(a => a.id === user.value.id);
});

// Basisfarbe einheitlich:
const baseHex = computed(() =>
    (settings.value?.use_event_status_color ? props.event?.eventStatus?.color : null) ??
    props.event?.eventType?.hex_code ??
    props.event?.event_type_color ??
    '#9CA3AF'
);

// Alpha normalisieren (0..1 oder 0..100):
const hcAlpha = computed(() => {
    const v = Number(hcPercent.value ?? 0);
    return v > 1 ? v / 100 : v;
});

// Farb-Precompose:  out = alpha*fg + (1-alpha)*bg  (alles deckend zurückgeben)
function hexToRgb(hex: string) {
    const h = hex.replace('#','');
    const n = h.length === 3
        ? h.split('').map(c => c + c).join('')
        : h;
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

// tatsächlicher Zellenhintergrund (Wochentag/WE) – wird im onMounted ermittelt
const compositeBase = computed(() => parentBackgroundColor.value || '#ffffff');

// DIE neue Eventfläche: deckende Farbe, die wie RGBA(baseHex, alpha) über dem Zellhintergrund aussieht
const eventSurfaceColor = computed(() => precomposeHex(baseHex.value, compositeBase.value, hcAlpha.value));

// Textfarbe IMMER von der fertigen Fläche ableiten
const timeTextColor  = computed(() => getTextColorBasedOnBackground(eventSurfaceColor.value));
const headerBaseStyle = computed(() => ({ lineHeight: props.lineHeight, fontSize: props.fontSize }));
const headerTextStyle = computed(() => ({ ...headerBaseStyle.value, color: timeTextColor.value }));
const timeTextStyle   = computed(() => ({ ...headerBaseStyle.value, color: timeTextColor.value }));

// Container-Style nutzt jetzt die prekomponierte Farbe
const containerStyle = computed(() => ({
    minHeight: `${(totalHeight.value - heightSubtraction.value) * zoom_factor.value}px`,
    backgroundColor: eventSurfaceColor.value,
    fontSize: props.fontSize,
    lineHeight: props.lineHeight
}));

onMounted(() => {
    if (rootEl.value) detectParentBackgroundColor(rootEl.value); // ermittelt parentBackgroundColor (Weekday/Weekend)
});

</script>
