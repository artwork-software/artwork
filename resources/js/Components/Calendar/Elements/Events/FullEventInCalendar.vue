<template>
    <div
        :style="{
      minHeight: isHeightFull ? '100%' : (totalHeight - heightSubtraction(event)) * zoom_factor + 'px',
      backgroundColor: backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent),
      fontsize: fontSize,
      lineHeight: lineHeight
    }"
        class="group/singleEvent h-full rounded-lg border border-black/5 transition-[border,background-color] duration-150"
        :class="[
      event.occupancy_option ? 'event-disabled' : '',
      usePage().props.auth.user.calendar_settings.time_period_project_id === event?.project?.id || isHighlighted ? 'border-[3px] border-dashed border-pink-500' : '',
      isHeightFull ? 'h-full' : '',
      usePage().props.auth.user.daily_view ? 'overflow-y-auto' : '',
      multiEdit ? 'relative' : ''
    ]"
    >
        <!-- Multi-Edit Overlay -->
        <div
            v-if="checkIfMultiEditIsEnabled"
            @click="clickOnCheckBox"
            class="absolute inset-0 z-10 rounded-lg hidden group-hover/singleEvent:flex items-center justify-center"
            :class="event.considerOnMultiEdit ? '!flex bg-green-200/50' : 'bg-artwork-buttons-create/35'"
        >
            <div v-if="event.considerOnMultiEdit" class="bg-white rounded-md p-0.5">
                <component :is="IconSquareCheckFilled" class="size-6 text-green-600" />
            </div>

            <!-- wirkliche Checkbox bleibt, nur optisch versteckt -->
            <input
                v-model="event.considerOnMultiEdit"
                aria-describedby="candidates-description"
                name="candidates"
                type="checkbox"
                :id="event.id"
                class="input-checklist hidden"
                @change="changeMultiEditCheckbox(event.id, event.considerOnMultiEdit, event.roomId, event.start, event.end)"
            />
        </div>

        <!-- Status-Leiste oben (kompakt) -->
        <div v-if="event.isPlanning && !event.hasVerification" class="w-full rounded-t-lg bg-artwork-buttons-create px-2 py-1 text-[10px] font-lexend text-white select-none">
            {{ $t('Planned Event') }}
        </div>
        <div v-else-if="event.hasVerification" class="w-full rounded-t-lg bg-orange-500 px-2 py-1 text-[10px] font-lexend text-white select-none">
            {{ $t('Verification requested') }}
        </div>

        <!-- Projektgruppen-Balken (nur wenn display_project_groups aktiv UND Projekt einer Gruppe zugeordnet ist) -->
        <div
            v-if="usePage().props.auth.user.calendar_settings.display_project_groups && event.project?.isInGroup && event.project?.group && event.project?.group.length > 0 && !event.project?.isGroup"
            class="w-full rounded-t-lg px-2 py-1 border-b border-black/15"
            :style="{
                backgroundColor: event.project.group[0].color ? event.project.group[0].color + '40' : 'transparent'
            }"
        >
            <div class="flex items-center gap-1.5 min-w-0">
                <a
                    :href="getEditHref(event.project.group[0].id)"
                    class="block w-full min-w-0 hover:text-artwork-buttons-hover hover:underline underline-offset-2 transition ease-in-out duration-200"
                    @mouseenter="showProjectGroupTooltipHandler"
                    @mouseleave="hideProjectGroupTooltip"
                >
                    <span ref="projectGroupNameSpan" class="block w-full truncate font-semibold text-xs text-black">
                        {{ event.project.group[0].name }}
                    </span>
                    <Teleport to="body">
                        <div
                            v-if="isProjectGroupNameTruncated && showProjectGroupNameTooltip"
                            class="fixed z-[9999] pointer-events-none"
                            :style="{ top: projectGroupTooltipPosition.top + 'px', left: projectGroupTooltipPosition.left + 'px' }"
                        >
                            <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                {{ event.project.group[0].name }}
                            </div>
                        </div>
                    </Teleport>
                </a>
            </div>
        </div>

        <!-- Projektname: eigener Balken oberhalb des Events -->
        <div
            v-if="event.project?.name && event.project?.id"
            class="w-full px-2 py-1 border-b border-black/15"
            :style="{
                backgroundColor: event.project?.isGroup && event.project?.color ? event.project.color + '40' : 'transparent'
            }"
        >
            <div
                class="flex items-center gap-1.5 min-w-0"
                :style="{
                  color: event.project?.isGroup ? 'black' : getTextColorBasedOnBackground(
                    backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)
                  )
                }"
            >
                <!-- Projekt-Status Punkt (fixe Größe) -->
                <div
                    v-if="usePage().props.auth.user.calendar_settings.project_status && event.project?.status"
                    class="group relative shrink-0 flex-none size-3.5 min-w-3.5 min-h-3.5 rounded-full border"
                    :style="{ backgroundColor: event?.project?.status?.color + '33', borderColor: event?.project?.status?.color }"
                    title=""
                >
                    <div class="absolute hidden group-hover:block top-5 z-99">
                        <div class="rounded-full bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white">
                            {{ event?.project?.status?.name }}
                        </div>
                    </div>
                </div>

                <!-- Projektname als Link, einzeilig mit Tooltip bei Trunkierung -->
                <a
                    :href="getEditHref(event.project?.id)"
                    class="relative group flex-1 min-w-0 hover:text-artwork-buttons-hover hover:underline underline-offset-2 transition ease-in-out duration-200"
                    @mouseenter="showTooltip"
                    @mouseleave="hideTooltip"
                >
                    <span ref="projectNameSpan" class="block w-full truncate font-semibold text-xs">
                        {{ event.project?.name }}
                    </span>
                    <Teleport to="body">
                        <div
                            v-if="isNameTruncated && showProjectNameTooltip"
                            class="fixed z-[9999] pointer-events-none"
                            :style="{ top: tooltipPosition.top + 'px', left: tooltipPosition.left + 'px' }"
                        >
                            <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                {{ event.project?.name }}
                            </div>
                        </div>
                    </Teleport>
                </a>
            </div>
        </div>
        <!-- CONTENT: Detailliert ab mittlerem Zoom -->
        <div v-if="zoom_factor > 0.6" class="grid grid-cols-1 md:grid-cols-3 gap-x-3 px-2.5 py-2">
            <!-- Linke 2/3 Spalte -->
            <div class="col-span-2">
                <div class="flex items-start gap-2">
                    <!-- Schmaler Typ-Streifen -->
                    <div
                        v-if="!usePage().props.auth.user.calendar_settings.high_contrast"
                        class="w-[4px] rounded-sm mt-[2px] self-stretch"
                        :style="{ backgroundColor: getColorBasedOnUserSettings }"
                    ></div>

                    <!-- Text-Block -->
                    <div
                        class="min-w-0 flex-1"
                        :style="{
                          color: getTextColorBasedOnBackground(
                            backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)
                          )
                        }"
                    >
                        <!-- Kopfzeile -->
                        <div class="relative space-y-0.5">
                            <div v-if="false" class="flex items-center gap-1.5 min-w-0">
                                <!-- Projekt-Status Punkt -->
                                <div
                                    v-if="usePage().props.auth.user.calendar_settings.project_status && event.project?.status"
                                    class="group relative shrink-0 flex-none size-3.5 min-w-3.5 min-h-3.5 rounded-full border"
                                    :style="{ backgroundColor: event?.project?.status?.color + '33', borderColor: event?.project?.status?.color }"
                                    title=""
                                >
                                    <div class="absolute hidden group-hover:block top-5 z-99">
                                        <div class="rounded-full bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white">
                                            {{ event?.project?.status?.name }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Projektname (Link) -->
                                <a
                                    v-if="event.project?.name && event.project?.id"
                                    :href="getEditHref(event.project?.id)"
                                    class="relative group flex-1 min-w-0 hover:text-artwork-buttons-hover hover:underline underline-offset-2 transition ease-in-out duration-200"
                                >
                                  <span ref="projectNameSpan" :class="['block w-full font-semibold', isProjectNameLong ? 'text-[13px] leading-4 two-line-clamp' : 'truncate text-sm']">
                                    {{ event.project?.name }}
                                  </span>
                                  <div v-if="isNameTruncated" class="absolute hidden group-hover:block top-5 left-0 z-50 w-42">
                                    <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white">
                                      {{ event.project?.name }}
                                    </div>
                                  </div>
                                </a>
                            </div>

                            <!-- Artists -->
                            <div v-if="usePage().props.auth.user.calendar_settings.project_artists && event.project?.artistNames" class="truncate text-xs/5 opacity-90">
                                {{ event.project?.artistNames }}
                            </div>

                            <!-- Eventname -->
                            <div
                                v-if="usePage().props.auth.user.calendar_settings.event_name && event.eventName"
                                class="relative truncate text-xs/4 font-semibold"
                                @mouseenter="showEventNameTooltipHandler"
                                @mouseleave="hideEventNameTooltip"
                            >
                                <span ref="eventNameSpan" class="block w-full truncate">
                                    {{ event.eventName }}
                                </span>
                                <Teleport to="body">
                                    <div
                                        v-if="isEventNameTruncated && showEventNameTooltipFlag"
                                        class="fixed z-[9999] pointer-events-none"
                                        :style="{ top: eventNameTooltipPosition.top + 'px', left: eventNameTooltipPosition.left + 'px' }"
                                    >
                                        <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                            {{ event.eventName }}
                                        </div>
                                    </div>
                                </Teleport>
                            </div>

                            <!-- Eventtyp + Projekt-State-Indicator rechts -->
                            <div class="flex items-center justify-between">
                                <div
                                    class="truncate text-xs/5 opacity-90 min-w-0 flex-1"
                                    @mouseenter="showEventTypeTooltipHandler"
                                    @mouseleave="hideEventTypeTooltip"
                                >
                                    <span ref="eventTypeSpan" class="block w-full truncate">
                                        {{ event?.eventType?.name }}
                                    </span>
                                    <Teleport to="body">
                                        <div
                                            v-if="isEventTypeTruncated && showEventTypeTooltipFlag"
                                            class="fixed z-[9999] pointer-events-none"
                                            :style="{ top: eventTypeTooltipPosition.top + 'px', left: eventTypeTooltipPosition.left + 'px' }"
                                        >
                                            <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                                {{ event?.eventType?.name }}
                                            </div>
                                        </div>
                                    </Teleport>
                                </div>
                                <div v-if="usePage().props.auth.user.calendar_settings.project_status && event.projectStateColor" class="ml-2">
                                    <div :class="[event.projectStateColor, zoom_factor <= 0.8 ? 'border-2' : 'border-4']" class="rounded-full"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Zeit/Optionen Zeile -->
                        <div class="mt-0.5 flex items-center gap-1.5 text-xs/5" :class="[(new Date(event.start).toDateString() === new Date(event.end).toDateString()) && !project && !atAGlance ? 'flex-nowrap' : 'flex-wrap']">
                            <component
                                :is="IconRepeat"
                                v-if="usePage().props.auth.user.calendar_settings.repeating_events && event.is_series"
                                class="size-3.5 shrink-0"
                                stroke-width="2"
                                :style="{
                                    color: getTextColorBasedOnBackground(
                                        backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)
                                    )
                                }"
                            />
                            <component
                                :is="IconClock"
                                v-if="!event.allDay && new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                                class="size-3.5 shrink-0"
                                stroke-width="2"
                                :style="{
                                    color: getTextColorBasedOnBackground(
                                        backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)
                                    )
                                }"
                            />
                            <div
                                class="subpixel-antialiased"
                                :class="[zoom_factor === 1 ? 'eventTime' : '', (new Date(event.start).toDateString() === new Date(event.end).toDateString()) && !project && !atAGlance ? 'whitespace-nowrap' : '']"
                                :style="{
                                      color: getTextColorBasedOnBackground(
                                        backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent)
                                      )
                                    }"
                            >
                                <!-- gleicher Tag -->
                                <template v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString() && !project && !atAGlance">
                                    <span v-if="event.allDay">{{ $t('Full day') }}</span>
                                    <span v-else>{{ event.formattedDates.startTime + ' - ' + event.formattedDates.endTime }}</span>
                                </template>

                                <!-- mehrtägig -->
                                <template v-else>
                                  <span v-if="event.allDay">
                                    <template v-if="atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()">
                                      {{ $t('Full day') }}, {{ event.formattedDates.start_without_year }}
                                    </template>
                                    <template v-else>
                                      {{ $t('Full day') }}, {{ event.formattedDates.start_without_year }} - {{ event.formattedDates.end_without_year }}
                                    </template>
                                  </span>
                                                    <span v-else>
                                    <template v-if="new Date(event.start).toDateString() !== new Date(event.end).toDateString()">
                                      <span class="text-error pr-0.5">!</span>
                                      {{ event.formattedDates.startDateTime_without_year  + ' - ' +  event.formattedDates.endDateTime_without_year }}
                                    </template>
                                    <template v-else>
                                      <template v-if="atAGlance">
                                        {{ event.formattedDates.startDateTime_without_year + ' - ' + event.formattedDates.endTime }}
                                      </template>
                                      <template v-else>
                                        {{ event.formattedDates.startTime + ' - ' + event.formattedDates.endTime }}
                                      </template>
                                    </template>
                                  </span>
                                </template>
                            </div>

                            <!-- Options -->
                            <div v-if="event.option_string && usePage().props.auth.user.calendar_settings.options" class=" text-xs/5">
                                <span
                                    v-if="!atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                                    class="eventTime font-medium subpixel-antialiased"
                                    :style="{ lineHeight: lineHeight, fontSize: fontSize }"
                                >
                                  , {{ event.option_string }}
                                </span>
                                                <span v-else class="eventTime font-medium subpixel-antialiased ml-0.5">
                                  ({{ event.option_string.charAt(7) }})
                                </span>
                            </div>
                        </div>


                        <!-- Projektleiter -->
                        <div
                            v-if="usePage().props.auth.user.calendar_settings.project_management && event?.project?.leaders?.length > 0"
                            class="mt-2 -ml-1.5"
                        >
                            <div v-if="event?.project?.leaders && !project && zoom_factor >= 0.8" class="ml-2 flex flex-wrap items-center gap-1">
                                <UserPopoverTooltip
                                    v-for="user in event?.project?.leaders?.slice(0,3)"
                                    :key="'leader-'+user.id"
                                    :user="user"
                                    width="5"
                                    height="5"
                                />
                                <div v-if="event?.project?.leaders.length >= 4" class="ml-1">
                                    <Menu as="div" class="relative">
                                        <MenuButton class="rounded-full focus:outline-none">
                                            <div class="flex h-5 w-5 items-center justify-center rounded-full bg-black text-[11px] font-semibold text-white">
                                                +{{ event?.project?.leaders.length - 3 }}
                                            </div>
                                        </MenuButton>
                                        <transition
                                            enter-active-class="transition ease-out duration-150"
                                            enter-from-class="opacity-0 scale-95"
                                            enter-to-class="opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100 scale-100"
                                            leave-to-class="opacity-0 scale-95"
                                        >
                                            <MenuItems class="absolute mt-2 w-72 origin-top-right rounded-lg bg-primary py-1 ring-1 ring-black/5 focus:outline-none">
                                                <MenuItem v-for="user in event?.project?.leaders" :key="'menu-'+user.id" v-slot="{ active }">
                                                    <Link
                                                        href="#"
                                                        :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']"
                                                    >
                                                        <img class="h-5 w-5 rounded-full" :src="user.profile_photo_url" alt="" />
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
                        <div
                            v-if="usePage().props.auth.user.calendar_settings.description"
                            class="mt-2"
                            :style="{
                color: getTextColorBasedOnBackground(
                  backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent)
                )
              }"
                        >
                            <EventNoteComponent :event="event" />
                        </div>
                    </div>

                    <!-- Schichten kompakt rechts daneben -->
                    <div
                        v-if="usePage().props.auth.user.calendar_settings.work_shifts"
                        class="grid grid-cols-1 md:grid-cols-2 gap-x-2 gap-y-0.5 text-xs pt-0.5"
                    >
                        <a
                            v-if="firstProjectShiftTabId"
                            v-for="shift in event.shifts"
                            :key="shift.id"
                            :href="route('projects.tab', { project: event?.project?.id, projectTab: firstProjectShiftTabId })"
                            class="hover:underline underline-offset-2"
                        >
                            <span class="font-medium">{{ shift?.craft?.abbreviation }}</span>
                            <span class="opacity-80"> ({{ shift?.worker_count }}/{{ shift?.max_worker_count }})</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Rechte 1/3 Spalte: Properties + Aktionen -->
            <div class="pt-1 flex flex-col justify-start items-end">
                <!-- Kontext-Menü -->
                <div class="opacity-0 group-hover/singleEvent:opacity-100 transition-opacity duration-150">
                    <BaseMenu has-no-offset :dots-color="$page.props.auth.user.calendar_settings.high_contrast ? 'text-white' : ''" white-menu-background class="cursor-pointer">
                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && !event.hasVerification" @click="SendEventToVerification" :icon="IconLock" title="Request verification" />
                        <BaseMenuItem white-menu-background v-if="event?.isPlanning && event.hasVerification" @click="cancelVerification" :icon="IconLockOpen" title="Withdraw verification request" />
                        <BaseMenuItem white-menu-background v-if="event.hasVerification && verifierForEventTypIds?.includes(event.eventType.id)" @click="approveRequest" :icon="IconChecks" title="Approve verification" />
                        <BaseMenuItem white-menu-background v-if="event.hasVerification && verifierForEventTypIds?.includes(event.eventType.id)" @click="showRejectEventVerificationModal = true" :icon="IconCircleX" title="Reject verification" />

                        <BaseMenuItem white-menu-background @click="$emit('editEvent', event)" :icon="IconEdit" title="edit" />
                        <BaseMenuItem
                            white-menu-background
                            v-if="(isRoomAdmin || isCreator || hasAdminRole) && event?.eventType?.id === 1"
                            @click="$emit('openAddSubEventModal', event, 'create', null)"
                            :icon="IconCirclePlus"
                            title="Add Sub-Event"
                        />
                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" @click="$emit('showDeclineEventModal', event)" :icon="IconX" title="Decline event" />
                        <BaseMenuItem white-menu-background v-if="(isRoomAdmin || isCreator || hasAdminRole) && (event.is_series || event.series_id)" @click="deleteSeriesEvents" :icon="IconTrash" title="Delete all series events" />
                        <BaseMenuItem white-menu-background v-if="(isRoomAdmin || isCreator || hasAdminRole) && (event.is_series || event.series_id)" @click="showEditSeriesModal = true" :icon="IconEdit" title="Edit all series events" />
                        <BaseMenuItem white-menu-background v-if="(can('can edit planning calendar') || hasAdminRole) && !event.isPlanning" @click="showConvertToPlanningModal = true" :icon="IconCalendarPlus" title="Convert to planned event" />
                        <BaseMenuItem white-menu-background v-if="isRoomAdmin || isCreator || hasAdminRole" @click="$emit('openConfirmModal', event, 'main')" :icon="IconTrash" title="Delete" />
                        <BaseMenuItem white-menu-background v-if="event.hasTimelines" @click="showCreateTimelinePresetModal = true" :icon="IconDeviceFloppy" title="Save timeline as preset" />
                        <BaseMenuItem white-menu-background @click="showSearchTimelinePresetModal = true" :icon="IconFileImport" title="Import timeline preset" />
                    </BaseMenu>
                </div>

                <!-- Properties als Icons -->
                <div class="grid grid-cols-5 md:grid-cols-2 gap-2">
                    <div v-for="property in event.eventProperties" :key="property.id" class="col-span-1 group/property relative">
                        <PropertyIcon
                            :name="property.icon"
                            class="size-3.5 opacity-90"
                            :style="{
                                color: getTextColorBasedOnBackground(
                                    backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)
                                )
                            }"
                        />
                        <div class="absolute hidden group-hover/property:block bottom-full left-1/2 -translate-x-1/2 mb-1 z-50 whitespace-nowrap">
                            <div class="rounded-lg bg-artwork-navigation-background px-3 py-1 text-xs text-white">
                                {{ property.name }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Icon -->
                <div
                    v-if="usePage().props.auth.user.calendar_settings.show_timeline"
                    class="mt-2 cursor-pointer"
                    @click="openTimelineModal"
                >
                    <component
                        :is="IconTimeline"
                        class="size-5"
                        stroke-width="1.5"
                        :style="{
                            color: event.hasTimelines
                                ? getTextColorBasedOnBackground(
                                    backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)
                                  )
                                : (usePage().props.auth.user.calendar_settings.high_contrast
                                    ? getTextColorBasedOnBackground(
                                        backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)
                                      ) + '80'
                                    : undefined)
                        }"
                        :class="!event.hasTimelines && !usePage().props.auth.user.calendar_settings.high_contrast ? 'text-gray-400' : ''"
                    />
                </div>
            </div>

        </div>

        <!-- CONTENT: Kompakte Ansicht bei kleinem Zoom mit Info-Icon -->
        <div
            v-else
            class="flex h-full items-center gap-2 px-2.5 py-2"
            :class="[zoom_factor < 0.6 ? 'justify-center' : '']"
        >
            <div class="flex items-center gap-1.5">
                <component
                    :is="IconInfoCircle"
                    class="size-6 cursor-pointer flex-shrink-0"
                    stroke-width="1.5"
                    @click.stop="toggleSmallZoomTooltip"
                />
                <div class="w-16 max-w-16 text-left" v-if="zoom_factor > 0.4">
                    <div v-if="usePage().props.auth.user.calendar_settings.event_name && event.eventName" class="truncate text-xs">
                        {{ event.eventName }}
                    </div>
                    <a
                        v-if="event.project && event.project?.id"
                        :href="getEditHref(event.project?.id)"
                        class="block truncate text-xs hover:underline underline-offset-2"
                    >
                        {{ event.project?.name }}
                    </a>
                </div>
            </div>

            <!-- Teleport: Vollständiges Event-Div als Tooltip -->
            <Teleport to="body">
                <div
                    v-if="showSmallZoomTooltip"
                    class="fixed z-[9999]"
                    :style="{ top: smallZoomTooltipPosition.top + 'px', left: smallZoomTooltipPosition.left + 'px' }"
                >
                    <div
                        class="w-[280px] rounded-lg shadow-xl ring-1 ring-black/10 bg-white"
                        @click.stop
                    >
                        <!-- Vollständiges Event-Rendering wie bei normalem Zoom -->
                        <div
                            :style="{
                                backgroundColor: backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent),
                            }"
                            class="rounded-lg border border-black/5"
                        >
                            <!-- Status-Leiste oben -->
                            <div v-if="event.isPlanning && !event.hasVerification" class="w-full rounded-t-lg bg-artwork-buttons-create px-2 py-1 text-[10px] font-lexend text-white select-none">
                                {{ $t('Planned Event') }}
                            </div>
                            <div v-else-if="event.hasVerification" class="w-full rounded-t-lg bg-orange-500 px-2 py-1 text-[10px] font-lexend text-white select-none">
                                {{ $t('Verification requested') }}
                            </div>

                            <!-- Projektgruppen-Balken -->
                            <div
                                v-if="usePage().props.auth.user.calendar_settings.display_project_groups && event.project?.isInGroup && event.project?.group && event.project?.group.length > 0 && !event.project?.isGroup"
                                class="w-full px-2 py-1 border-b border-black/15"
                                :style="{ backgroundColor: event.project.group[0].color ? event.project.group[0].color + '40' : 'transparent' }"
                            >
                                <a
                                    :href="getEditHref(event.project.group[0].id)"
                                    class="block w-full min-w-0 hover:text-artwork-buttons-hover hover:underline underline-offset-2"
                                    @mouseenter="showProjectGroupTooltipHandler"
                                    @mouseleave="hideProjectGroupTooltip"
                                >
                                    <span ref="projectGroupNameSpan" class="block w-full truncate font-semibold text-xs text-black">
                                        {{ event.project.group[0].name }}
                                    </span>
                                </a>
                            </div>

                            <!-- Projektname-Balken -->
                            <div
                                v-if="event.project?.name && event.project?.id"
                                class="w-full px-2 py-1 border-b border-black/15"
                                :style="{ backgroundColor: event.project?.isGroup && event.project?.color ? event.project.color + '40' : 'transparent' }"
                            >
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <div
                                        v-if="usePage().props.auth.user.calendar_settings.project_status && event.project?.status"
                                        class="shrink-0 flex-none size-3.5 rounded-full border"
                                        :style="{ backgroundColor: event?.project?.status?.color + '33', borderColor: event?.project?.status?.color }"
                                    ></div>
                                    <a
                                        :href="getEditHref(event.project?.id)"
                                        class="flex-1 min-w-0 hover:text-artwork-buttons-hover hover:underline underline-offset-2"
                                    >
                                        <span class="block w-full truncate font-semibold text-xs">{{ event.project?.name }}</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-3 px-2.5 py-2">
                                <div class="col-span-2">
                                    <div class="flex items-start gap-2">
                                        <div
                                            v-if="!usePage().props.auth.user.calendar_settings.high_contrast"
                                            class="w-[4px] rounded-sm mt-[2px] self-stretch"
                                            :style="{ backgroundColor: getColorBasedOnUserSettings }"
                                        ></div>
                                        <div
                                            class="min-w-0 flex-1"
                                            :style="{ color: getTextColorBasedOnBackground(backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)) }"
                                        >
                                            <div class="relative space-y-0.5">
                                                <!-- Artists -->
                                                <div v-if="usePage().props.auth.user.calendar_settings.project_artists && event.project?.artistNames" class="truncate text-xs/5 opacity-90">
                                                    {{ event.project?.artistNames }}
                                                </div>
                                                <!-- Eventname -->
                                                <div v-if="usePage().props.auth.user.calendar_settings.event_name && event.eventName" class="truncate text-xs/4 font-semibold">
                                                    {{ event.eventName }}
                                                </div>
                                                <!-- Eventtyp -->
                                                <div class="flex items-center justify-between">
                                                    <div
                                                        class="truncate text-xs/5 opacity-90 min-w-0 flex-1"
                                                        @mouseenter="showEventTypeTooltipHandler"
                                                        @mouseleave="hideEventTypeTooltip"
                                                    >
                                                        <span ref="eventTypeSpan" class="block w-full truncate">
                                                            {{ event?.eventType?.name }}
                                                        </span>
                                                    </div>
                                                    <div v-if="usePage().props.auth.user.calendar_settings.project_status && event.projectStateColor" class="ml-2">
                                                        <div :class="[event.projectStateColor, 'border-2']" class="rounded-full"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Zeit -->
                                            <div class="mt-0.5 flex items-center gap-1.5 text-xs/5 flex-wrap">
                                                <component
                                                    :is="IconRepeat"
                                                    v-if="usePage().props.auth.user.calendar_settings.repeating_events && event.is_series"
                                                    class="size-3.5 shrink-0"
                                                    stroke-width="2"
                                                />
                                                <component
                                                    :is="IconClock"
                                                    v-if="!event.allDay && new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                                                    class="size-3.5 shrink-0"
                                                    stroke-width="2"
                                                />
                                                <div class="subpixel-antialiased">
                                                    <template v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString()">
                                                        <span v-if="event.allDay">{{ $t('Full day') }}</span>
                                                        <span v-else>{{ event.formattedDates.startTime + ' - ' + event.formattedDates.endTime }}</span>
                                                    </template>
                                                    <template v-else>
                                                        <span v-if="event.allDay">
                                                            {{ $t('Full day') }}, {{ event.formattedDates.start_without_year }} - {{ event.formattedDates.end_without_year }}
                                                        </span>
                                                        <span v-else>
                                                            <span class="text-error pr-0.5">!</span>
                                                            {{ event.formattedDates.startDateTime_without_year + ' - ' + event.formattedDates.endDateTime_without_year }}
                                                        </span>
                                                    </template>
                                                </div>
                                                <div v-if="event.option_string && usePage().props.auth.user.calendar_settings.options" class="text-xs/5">
                                                    , {{ event.option_string }}
                                                </div>
                                            </div>

                                            <!-- Projektleiter -->
                                            <div v-if="usePage().props.auth.user.calendar_settings.project_management && event?.project?.leaders?.length > 0" class="mt-2 -ml-1.5">
                                                <div class="ml-2 flex flex-wrap items-center gap-1">
                                                    <UserPopoverTooltip
                                                        v-for="user in event?.project?.leaders?.slice(0,3)"
                                                        :key="'tooltip-leader-'+user.id"
                                                        :user="user"
                                                        width="5"
                                                        height="5"
                                                    />
                                                    <div v-if="event?.project?.leaders.length >= 4" class="ml-1 text-xs">
                                                        +{{ event?.project?.leaders.length - 3 }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Beschreibung -->
                                            <div v-if="usePage().props.auth.user.calendar_settings.description" class="mt-2">
                                                <EventNoteComponent :event="event" />
                                            </div>
                                        </div>

                                        <!-- Schichten -->
                                        <div v-if="usePage().props.auth.user.calendar_settings.work_shifts" class="grid grid-cols-1 gap-y-0.5 text-xs pt-0.5">
                                            <a
                                                v-if="firstProjectShiftTabId"
                                                v-for="shift in event.shifts"
                                                :key="'tooltip-shift-'+shift.id"
                                                :href="route('projects.tab', { project: event?.project?.id, projectTab: firstProjectShiftTabId })"
                                                class="hover:underline underline-offset-2"
                                            >
                                                <span class="font-medium">{{ shift?.craft?.abbreviation }}</span>
                                                <span class="opacity-80"> ({{ shift?.worker_count }}/{{ shift?.max_worker_count }})</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rechte Spalte: Properties -->
                                <div class="pt-1 flex flex-col justify-start items-end">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="property in event.eventProperties" :key="'tooltip-prop-'+property.id" class="col-span-1">
                                            <PropertyIcon
                                                :name="property.icon"
                                                class="size-3.5 opacity-90"
                                                :style="{ color: getTextColorBasedOnBackground(backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)) }"
                                            />
                                        </div>
                                    </div>
                                    <!-- Timeline Icon -->
                                    <div
                                        v-if="usePage().props.auth.user.calendar_settings.show_timeline"
                                        class="mt-2 cursor-pointer"
                                        @click.stop="openTimelineModal"
                                    >
                                        <component
                                            :is="IconTimeline"
                                            class="size-5"
                                            stroke-width="1.5"
                                            :class="event.hasTimelines ? '' : 'text-gray-400'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schließen-Button -->
                        <button
                            @click.stop="showSmallZoomTooltip = false"
                            class="absolute -top-2 -right-2 bg-white rounded-full p-1 shadow-md hover:bg-gray-100"
                        >
                            <component :is="IconX" class="size-4" />
                        </button>
                    </div>
                </div>
            </Teleport>
        </div>

        <!-- SUB-EVENTS -->
        <div v-if="event.subEvents?.length > 0" class="space-y-1.5 border-t border-black/5 px-2.5 py-2">
            <div v-for="subEvent in event.subEvents" :key="'sub-'+subEvent.id" class="rounded-lg">
                <div
                    class="relative rounded-lg border-l-[6px]"
                    :style="{ borderColor: backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent) }"
                >
                    <!-- Hover-Aktionen -->
                    <div class="absolute inset-0 hidden items-center justify-center rounded-lg bg-indigo-500/40 group-hover/singleEvent:flex">
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="$emit('editSubEvent', subEvent, 'edit', event)"
                                type="button"
                                class="rounded-full bg-indigo-600 p-1.5 text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                aria-label="Edit sub-event"
                            >
                                <IconEdit class="h-4 w-4" stroke-width="1.5" />
                            </button>
                            <button
                                v-if="isRoomAdmin || isCreator || hasAdminRole"
                                @click="$emit('openConfirmModal', subEvent, 'sub')"
                                type="button"
                                class="rounded-full bg-red-600 p-1.5 text-white hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                                aria-label="Delete sub-event"
                            >
                                <IconTrash class="h-4 w-4" stroke-width="1.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Sub-Event Card -->
                    <div
                        :class="[subEvent.class]"
                        :style="{
              height: (totalHeight - heightSubtraction(subEvent)) * zoom_factor + 10 + 'px',
              backgroundColor: backgroundColorWithOpacity(subEvent.type.hex_code, usePage().props.high_contrast_percent)
            }"
                        class="rounded-r-lg px-2 py-1.5"
                    >
                        <!-- Kopf -->
                        <div class="flex items-start justify-between gap-2">
                            <div :style="{ lineHeight: lineHeight, fontSize: fontSize }" class="w-40 min-w-0 md:w-56 font-semibold">
                                <div v-if="subEvent.eventName?.length > 0" class="flex min-w-0">
                                    <div v-if="subEvent.type.abbreviation" class="mr-1 truncate opacity-80">{{ subEvent.type.abbreviation }}:</div>
                                    <div class="truncate">{{ subEvent.eventName }}</div>
                                </div>
                                <div v-else class="truncate">{{ subEvent.type.name }}</div>
                            </div>

                            <!-- Eigenschaften-Icons -->
                            <div class="flex items-center -space-x-1">
                                <div
                                    v-for="property in subEvent.event_properties"
                                    :key="'subp-'+property.id"
                                    class="rounded-full border-2 border-white bg-white/90 p-0.5"
                                >
                                    <component :is="property.icon" class="size-3" stroke-width="1.5" />
                                </div>
                            </div>
                        </div>

                        <!-- Zeit -->
                        <div :style="{ lineHeight: lineHeight, fontSize: fontSize }" class="mt-1 text-[0.95em] font-medium subpixel-antialiased">
                            <template v-if="subEvent.formattedDates.is_same_day && !project && !atAGlance">
                                <span v-if="subEvent.allDay">{{ $t('Full day') }}</span>
                                <span v-else>{{ subEvent.formattedDates.start_time }} - {{ subEvent.formattedDates.end_time }}</span>
                            </template>
                            <template v-else>
                <span v-if="subEvent.allDay">
                  <template v-if="atAGlance && subEvent.formattedDates.start_date === subEvent.formattedDates.end_date">
                    {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }}
                  </template>
                  <template v-else>
                    <span class="text-error" v-if="subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date">!</span>
                    {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }} – {{ subEvent.formattedDates.end_date }}
                  </template>
                </span>
                                <span v-else>
                  <span class="text-error" v-if="subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date">!</span>
                  {{ subEvent.formattedDates.start_date_time }} – {{ subEvent.formattedDates.end_date_time }}
                </span>
                            </template>
                        </div>

                        <!-- Schichten -->
                        <div
                            v-if="usePage().props.auth.user.calendar_settings.work_shifts"
                            class="mt-1 text-xs"
                            :style="{ color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent)) }"
                        >
                            <div v-for="shift in subEvent.shifts" :key="'subs-'+shift.id">
                                <span class="font-medium">{{ shift.craft.abbreviation }}</span>
                                (
                                <VueMathjax :formula="convertToMathJax(decimalToFraction(shift.user_count ? shift.user_count : 0))" />/{{ shift.number_employees }}
                                <span v-if="shift.number_masters > 0"> | {{ shift.master_count }}/{{ shift.number_masters }}</span>
                                )
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verifikation ablehnen Modal -->
        <RejectEventVerificationRequestModal
            v-if="showRejectEventVerificationModal"
            @close="showRejectEventVerificationModal = false"
            :event="event"
        />

        <!-- Convert to Planning Modal -->
        <ConvertToPlanningModal
            v-if="showConvertToPlanningModal"
            @close="showConvertToPlanningModal = false"
            @convert="convertToPlanning"
        />

        <!-- Delete Series Events Modal -->
        <ConfirmDeleteModal
            v-if="showDeleteSeriesModal"
            :title="$t('Delete all series events')"
            :description="$t('Do you really want to delete all events of this series?')"
            @closed="closeDeleteSeriesModal"
            @delete="confirmDeleteSeriesEvents"
        />

        <!-- Edit Series Events Modal -->
        <EditSeriesEventsModal
            v-if="showEditSeriesModal"
            :event="event"
            :rooms="rooms"
            @close="showEditSeriesModal = false"
        />

        <!-- Timeline Modal -->
        <AddEditTimelineModal
            v-if="showTimelineModal"
            :event="event"
            :timelineToEdit="timelineData"
            @close="closeTimelineModal"
        />

        <!-- Timeline Preset Modals -->
        <CreateTimelinePresetFormEvent
            v-if="showCreateTimelinePresetModal"
            :event="event"
            @close="showCreateTimelinePresetModal = false"
        />

        <SearchTimelinePresetModal
            v-if="showSearchTimelinePresetModal"
            :event="event"
            @close="showSearchTimelinePresetModal = false"
        />
    </div>
</template>

<script setup>
import { computed, defineAsyncComponent, onMounted, onBeforeUnmount, ref, watch, nextTick } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
    IconCalendarPlus,
    IconChecks,
    IconCirclePlus,
    IconCircleX,
    IconClock,
    IconDeviceFloppy,
    IconEdit,
    IconFileImport,
    IconInfoCircle,
    IconLock,
    IconLockOpen,
    IconRepeat,
    IconSquareCheckFilled,
    IconTimeline,
    IconTrash,
    IconUsersGroup,
    IconX,
} from "@tabler/icons-vue";
import { Menu, MenuButton, MenuItem, MenuItems, Popover, PopoverButton, PopoverPanel } from "@headlessui/vue";
import VueMathjax from "vue-mathjax-next";
import { useI18n } from "vue-i18n";
import { useColorHelper } from "@/Composeables/UseColorHelper.js";
import { can } from "laravel-permission-to-vuejs";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ConvertToPlanningModal from "@/Components/Modals/ConvertToPlanningModal.vue";
import EventNoteComponent from "@/Layouts/Components/EventNoteComponent.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import { Float } from "@headlessui-float/vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import EditSeriesEventsModal from "@/Components/Calendar/Elements/Events/EditSeriesEventsModal.vue";

const { t } = useI18n(), $t = t;
const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);
const atAGlance = ref(usePage().props.auth.user.at_a_glance ?? false);
const showRejectEventVerificationModal = ref(false);
const showConvertToPlanningModal = ref(false);
const showDeleteSeriesModal = ref(false);
const showEditSeriesModal = ref(false);
const showTimelineModal = ref(false);
const timelineData = ref([]);
const showCreateTimelinePresetModal = ref(false);
const showSearchTimelinePresetModal = ref(false);

const emits = defineEmits([
    "editEvent",
    "editSubEvent",
    "openAddSubEventModal",
    "openConfirmModal",
    "showDeclineEventModal",
    "changedMultiEditCheckbox",
]);

const RejectEventVerificationRequestModal = defineAsyncComponent({
    loader: () => import("@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue"),
    delay: 200,
    timeout: 3000,
});

const AddEditTimelineModal = defineAsyncComponent({
    loader: () => import("@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue"),
    delay: 200,
    timeout: 3000,
});

const CreateTimelinePresetFormEvent = defineAsyncComponent({
    loader: () => import("@/Pages/Projects/Components/TimelineComponents/CreateTimelinePresetFormEvent.vue"),
    delay: 200,
    timeout: 3000,
});

const SearchTimelinePresetModal = defineAsyncComponent({
    loader: () => import("@/Pages/Projects/Components/TimelineComponents/SearchTimelinePresetModal.vue"),
    delay: 200,
    timeout: 3000,
});

const props = defineProps({
    event: { type: Object, required: true },
    multiEdit: { type: Boolean, default: false },
    fontSize: { type: String, default: "0.875rem" },
    lineHeight: { type: String, default: "1.25rem" },
    first_project_tab_id: { type: Number, default: 1 },
    project: { type: Object, default: null },
    hasAdminRole: { type: Boolean, default: false },
    rooms: { type: Object, required: true },
    width: { type: Number, required: true },
    firstProjectShiftTabId: { type: [Number, String], default: null },
    isHeightFull: { type: Boolean, default: false },
    isInDailyView: { type: Boolean, default: false },
    verifierForEventTypIds: { type: Array, default: [] },
    isPlanning: { type: Boolean, default: false },
});

const isHighlighted = computed(() => {
    const highlightEventId = usePage().props.urlParameters.highlightEventId;
    return highlightEventId && parseInt(highlightEventId) === parseInt(props.event.id);
});

// Consider project name long when it exceeds a reasonable character threshold
const isProjectNameLong = computed(() => {
    const name = props.event?.project?.name ?? '';
    return name.length > 24; // threshold can be adjusted if desired
});

// Runtime detection: show tooltip only when text is actually truncated
const projectNameSpan = ref(null);
const isNameTruncated = ref(false);
const showProjectNameTooltip = ref(false);
const tooltipPosition = ref({ top: 0, left: 0 });

// Project group name tooltip
const projectGroupNameSpan = ref(null);
const isProjectGroupNameTruncated = ref(false);
const showProjectGroupNameTooltip = ref(false);
const projectGroupTooltipPosition = ref({ top: 0, left: 0 });

// Event type tooltip
const eventTypeSpan = ref(null);
const isEventTypeTruncated = ref(false);
const showEventTypeTooltipFlag = ref(false);
const eventTypeTooltipPosition = ref({ top: 0, left: 0 });

// Event name tooltip
const eventNameSpan = ref(null);
const isEventNameTruncated = ref(false);
const showEventNameTooltipFlag = ref(false);
const eventNameTooltipPosition = ref({ top: 0, left: 0 });

const checkTruncation = () => {
    const el = projectNameSpan.value;
    if (!el) { isNameTruncated.value = false; return; }
    const truncated = el.scrollWidth > el.clientWidth || el.scrollHeight > el.clientHeight;
    isNameTruncated.value = truncated;
};

const checkProjectGroupNameTruncation = () => {
    const el = projectGroupNameSpan.value;
    if (!el) { isProjectGroupNameTruncated.value = false; return; }
    isProjectGroupNameTruncated.value = el.scrollWidth > el.clientWidth || el.scrollHeight > el.clientHeight;
};

const showProjectGroupTooltipHandler = (e) => {
    checkProjectGroupNameTruncation();
    if (!isProjectGroupNameTruncated.value) return;
    const rect = e.target.getBoundingClientRect();
    projectGroupTooltipPosition.value = { top: rect.bottom + 4, left: rect.left };
    showProjectGroupNameTooltip.value = true;
};

const hideProjectGroupTooltip = () => {
    showProjectGroupNameTooltip.value = false;
};

const checkEventNameTruncation = () => {
    const el = eventNameSpan.value;
    if (!el) { isEventNameTruncated.value = false; return; }
    isEventNameTruncated.value = el.scrollWidth > el.clientWidth || el.scrollHeight > el.clientHeight;
};

const showTooltip = (e) => {
    if (!isNameTruncated.value) return;
    const rect = e.target.getBoundingClientRect();
    tooltipPosition.value = { top: rect.bottom + 4, left: rect.left };
    showProjectNameTooltip.value = true;
};

const hideTooltip = () => {
    showProjectNameTooltip.value = false;
};

const showEventNameTooltipHandler = (e) => {
    checkEventNameTruncation();
    if (!isEventNameTruncated.value) return;
    const rect = e.target.getBoundingClientRect();
    eventNameTooltipPosition.value = { top: rect.bottom + 4, left: rect.left };
    showEventNameTooltipFlag.value = true;
};

const hideEventNameTooltip = () => {
    showEventNameTooltipFlag.value = false;
};

const checkEventTypeTruncation = () => {
    const el = eventTypeSpan.value;
    if (!el) { isEventTypeTruncated.value = false; return; }
    isEventTypeTruncated.value = el.scrollWidth > el.clientWidth || el.scrollHeight > el.clientHeight;
};

const showEventTypeTooltipHandler = (e) => {
    checkEventTypeTruncation();
    if (!isEventTypeTruncated.value) return;
    const rect = e.target.getBoundingClientRect();
    eventTypeTooltipPosition.value = { top: rect.bottom + 4, left: rect.left };
    showEventTypeTooltipFlag.value = true;
};

const hideEventTypeTooltip = () => {
    showEventTypeTooltipFlag.value = false;
};

// Small zoom tooltip state
const showSmallZoomTooltip = ref(false);
const smallZoomTooltipPosition = ref({ top: 0, left: 0 });

const toggleSmallZoomTooltip = (e) => {
    if (showSmallZoomTooltip.value) {
        showSmallZoomTooltip.value = false;
        return;
    }
    const rect = e.target.getBoundingClientRect();
    // Position tooltip to the right of the icon, or adjust if near edge
    let left = rect.right + 8;
    let top = rect.top;

    // Adjust if tooltip would go off-screen
    if (left + 300 > window.innerWidth) {
        left = rect.left - 300 - 8;
    }
    if (top + 200 > window.innerHeight) {
        top = window.innerHeight - 220;
    }

    smallZoomTooltipPosition.value = { top, left };
    showSmallZoomTooltip.value = true;
};

// Close tooltip when clicking outside
const handleClickOutside = (e) => {
    if (showSmallZoomTooltip.value) {
        showSmallZoomTooltip.value = false;
    }
};

onMounted(() => {
    nextTick(checkTruncation);
    nextTick(checkEventNameTruncation);
    nextTick(checkProjectGroupNameTruncation);
    window.addEventListener('resize', checkTruncation);
    window.addEventListener('resize', checkEventNameTruncation);
    window.addEventListener('resize', checkProjectGroupNameTruncation);
    nextTick(checkEventTypeTruncation);
    window.addEventListener('resize', checkEventTypeTruncation);
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', checkTruncation);
    window.removeEventListener('resize', checkEventNameTruncation);
    window.removeEventListener('resize', checkProjectGroupNameTruncation);
    window.removeEventListener('resize', checkEventTypeTruncation);
    document.removeEventListener('click', handleClickOutside);
});

// Re-check when name or width/zoom changes
watch(() => [props.event?.project?.name, props.width, zoom_factor.value], () => nextTick(checkTruncation));
watch(() => [props.event?.eventName, props.width, zoom_factor.value], () => nextTick(checkEventNameTruncation));
watch(() => [props.event?.project?.group, props.width, zoom_factor.value], () => nextTick(checkProjectGroupNameTruncation));
watch(() => [props.event?.eventType?.name, props.width, zoom_factor.value], () => nextTick(checkEventTypeTruncation));

const element = ref(null);
const changeMultiEditCheckbox = (eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd) => {
    emits.call(this, "changedMultiEditCheckbox", eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd);
};

const isRoomAdmin = computed(() => {
    return props.rooms?.find((room) => room.id === props.event.roomId)?.admins.some((admin) => admin.id === usePage().props.auth.user.id) || false;
});

const checkIfMultiEditIsEnabled = computed(() => {
    const { isPlanning, multiEdit, zoom_factor, event } = props;

    if (multiEdit) {
        if (isPlanning) {
            return event.hasVerification || event.isPlanning;
        }
        if (zoom_factor > 0.4) return true;
        return true;
    }
    return false;
});

const isCreator = computed(() => props.event.created_by.id === usePage().props.auth.user.id);

const roomCanBeBookedByEveryone = computed(() => {
    return props.rooms?.find((room) => room.id === props.event.roomId).everyone_can_book;
});

const { backgroundColorWithOpacity, detectParentBackgroundColor, getTextColorBasedOnBackground } = useColorHelper();

const textColorWithDarken = computed(() => {
    const percent = 75;
    const color = getColorBasedOnUserSettings.value;
    if (!color) return "rgb(180, 180, 180)";
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
});

const getColorBasedOnUserSettings = computed(() => {
    const settings = usePage().props.auth.user.calendar_settings;
    if (settings.use_main_category_color) {
        if (!props.event?.project) {
            return '#9E9E9E'; // grey for events without project
        }
        if (props.event.project.mainCategoryColor) {
            return props.event.project.mainCategoryColor;
        }
        return '#3A3A3A'; // anthracite for project without main category
    }
    if (settings.use_event_status_color) {
        return props.event?.eventStatus?.color ?? props.event.eventType.hex_code;
    }
    return props.event.eventType.hex_code;
});

const totalHeight = computed(() => {
    let height = 42;
    if (usePage().props.auth.user.calendar_settings.project_status) height += 0;
    if (usePage().props.auth.user.calendar_settings.options) height += 0;
    if (usePage().props.auth.user.calendar_settings.project_management) height += 17;
    if (usePage().props.auth.user.calendar_settings.repeating_events) height += 20;
    if (usePage().props.auth.user.calendar_settings.work_shifts) height += 18;
    return height;
});

const heightSubtraction = (event) => {
    let heightSubtraction = 0;
    if (usePage().props.auth.user.calendar_settings.project_management && (!event.projectLeaders || event.projectLeaders?.length < 1)) {
        heightSubtraction += 17;
    }
    if (usePage().props.auth.user.calendar_settings.repeating_events && (!event.is_series || event.is_series === false)) {
        heightSubtraction += 20;
    }
    if (usePage().props.auth.user.calendar_settings.work_shifts && (!event.shifts || event.shifts?.length < 1)) {
        heightSubtraction += 18;
    }
    return heightSubtraction;
};

onMounted(() => {
    if (element.value) detectParentBackgroundColor(element.value);
});

const getEditHref = (projectId) => route("projects.tab", { project: projectId, projectTab: props.first_project_tab_id });

const SendEventToVerification = () => {
    router.post(route("events.sendToVerification", { event: props.event.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {},
    });
};

const cancelVerification = () => {
    router.post(route("event-verifications.cancel-verification", props.event.id), {}, { preserveScroll: true, preserveState: false });
};

const approveRequest = () => {
    router.post(route("event-verifications.approved-by-event", props.event.id), {}, { preserveScroll: true, preserveState: true });
};

const convertToPlanning = () => {
    router.post(route("events.convertToPlanning", props.event.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showConvertToPlanningModal.value = false;
        },
    });
};

const deleteSeriesEvents = () => {
    showDeleteSeriesModal.value = true;
};

const confirmDeleteSeriesEvents = () => {
    router.delete(route("events.series.delete", props.event.id), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            showDeleteSeriesModal.value = false;
        }
    });
};

const closeDeleteSeriesModal = () => {
    showDeleteSeriesModal.value = false;
};

const openTimelineModal = async () => {
    try {
        const response = await axios.get(route('events.timelines', { event: props.event.id }));
        timelineData.value = response.data.timelines || [];
        showTimelineModal.value = true;
    } catch (error) {
        console.error('Error loading timelines:', error);
    }
};

const closeTimelineModal = () => {
    showTimelineModal.value = false;
    timelineData.value = [];
};
</script>

<style scoped>
/* Barrierearme Fokussierung für interaktive Elemente (wenn Tailwind focus:ring nicht überall greift) */
a:focus-visible,
button:focus-visible {
    outline: 2px solid rgba(59, 130, 246, 0.7); /* blau */
    outline-offset: 2px;
}

/* Mini-Fix: verhindert zu harte Text-Überläufe in sehr schmalen Kacheln */
.eventTime,
.eventHeader {
    word-break: break-word;
}
.two-line-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2; /* future-proof */
    -webkit-box-orient: vertical;
    overflow: hidden;
    white-space: normal;
    word-break: break-word;
}
</style>
