<template>
    <AppLayout>
        <!-- Tagesansicht -->
        <!--<div v-else-if="isDaily && !atAGlance">
            <AsyncDailyViewCalendar
                :multi-edit="multiEdit"
                :rooms="rooms"
                :days="days"
                :calendarData="newCalendarData"
                :project="project"
                :eventStatuses="eventStatuses"
                :eventTypes="eventTypes"
                :eventsWithoutRoom="eventsWithoutRoom"
                :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                :firstProjectShiftTabId="firstProjectShiftTabId"
                :first-project-tab-id="first_project_tab_id"
                :verifierForEventTypIds="verifierForEventTypIds"
                :is-planning="isPlanning"
                @edit-event="showEditEventModel"
                @edit-sub-event="openAddSubEventModal"
                @open-add-sub-event-modal="openAddSubEventModal"
                @open-confirm-modal="openDeleteEventModal"
                @show-decline-event-modal="openDeclineEventModal"
                @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
            />
        </div>-->

        <!-- At-a-glance -->
        <!--<div class="mt-[4.5rem] w-max" v-else>
            <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-30 top-[64px] rounded-lg mb-3">
                <div v-for="(room, rIdx) in newCalendarData" :key="room.id ?? room.roomId ?? rIdx">
                    <div :style="{ minWidth: cellWidthPx, maxWidth: cellWidthPx, width: cellWidthPx }" class="flex items-center h-full truncate">
                        <SingleRoomInHeader :room="room" is-light />
                    </div>
                </div>
            </div>

            <div class="flex gap-0.5">
                <div v-for="(room, rIdx) in newCalendarData" :key="'agl-'+(room.id ?? room.roomId ?? rIdx)">
                    <div v-for="(events, k) in room.content" :key="events?.date ?? k" class="flex flex-col">
                        <div
                            v-for="(event, index) in (events?.events ?? [])"
                            :key="event.id ?? `${events?.date}-${index}`"
                            :style="{ minWidth: cellWidthPx, maxWidth: cellWidthPx, width: cellWidthPx }"
                            class="mb-0.5 h-full"
                            :id="'scroll_container-' + (events?.date ?? k)"
                        >
                            <div class="py-0.5">
                                <AsyncSingleEventInCalendar
                                    :event="event"
                                    :multi-edit="multiEdit"
                                    :font-size="textStyle.fontSize"
                                    :line-height="textStyle.lineHeight"
                                    :rooms="rooms"
                                    :has-admin-role="hasAdminRole()"
                                    :width="cardWidthNum"
                                    :first_project_tab_id="first_project_tab_id"
                                    :firstProjectShiftTabId="firstProjectShiftTabId"
                                    :verifierForEventTypIds="verifierForEventTypIds"
                                    :is-planning="isPlanning"
                                    @edit-event="showEditEventModel"
                                    @edit-sub-event="openAddSubEventModal"
                                    @open-add-sub-event-modal="openAddSubEventModal"
                                    @open-confirm-modal="openDeleteEventModal"
                                    @show-decline-event-modal="openDeclineEventModal"
                                    @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    -->

        <!-- Hinweis, wenn Projektzeitraum aktiv & leer -->
        <!--<div v-if="!checkIfAnyRoomHasAnEventOrShift && settings.use_project_time_period">
            <div class="mt-24 ml-4">
                <div class="border-l-4 border-red-400 bg-red-50 p-4 w-fit">
                    <div class="flex">
                        <div class="shrink-0">
                            <IconExclamationCircle class="h-5 w-5 text-red-400" stroke-width="2" aria-hidden="true" />
                        </div>
                        <div class="ml-1">
                            <p class="text-sm text-red-700 font-bold">
                                {{ $t('The selected project has no dates') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>-->
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({})
</script>

<style scoped>

</style>