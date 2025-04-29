<template>
    <div :style="{ minHeight: totalHeight - heightSubtraction(event) * zoom_factor + 'px', backgroundColor: backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent), fontsize: fontSize, lineHeight: lineHeight }"
        class="rounded-lg group/singleEvent"
        :class="[event.occupancy_option ? 'event-disabled' : '', usePage().props.auth.user.calendar_settings.time_period_project_id === event?.project?.id || isHighlighted ? 'border-[3px] border-dashed border-pink-500' : '', isHeightFull ? 'h-full' : '', usePage().props.auth.user.daily_view ? 'overflow-y-scroll' : '', multiEdit ? 'relative' : '']">
        <div v-if="checkIfMultiEditIsEnabled" @click="clickOnCheckBox"
             class="absolute w-full h-full z-10 rounded-lg group-hover/singleEvent:block flex justify-center align-middle items-center"
             :class="event.considerOnMultiEdit ? 'block bg-green-200/50' : 'hidden bg-artwork-buttons-create/50'">
            <div v-if="event.considerOnMultiEdit" class="flex items-center h-full justify-center align-middle">
                <div class="bg-white rounded-lg">
                    <component is="IconSquareCheckFilled" class="size-6 text-green-500" />
                </div>
            </div>
            <div class="justify-center items-center h-full gap-2 hidden">
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input v-model="event.considerOnMultiEdit"
                               aria-describedby="candidates-description"
                               name="candidates" type="checkbox"
                               :id="event.id"
                               class="input-checklist hidden"
                               @change="changeMultiEditCheckbox(
                                   event.id,
                                   event.considerOnMultiEdit,
                                   event.roomId,
                                   event.start,
                                   event.end
                               )"/>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="event.isPlanning && !event.hasVerification" class="w-full rounded-t-lg bg-artwork-buttons-create px-2 py-1 text-[10px] font-lexend select-none pointer-events-none text-white">
            {{ $t('Planned Event') }}
        </div>
        <div v-else-if="event.hasVerification" class="bg-orange-500 w-full rounded-t-lg px-2 py-1 text-[10px] font-lexend select-none pointer-events-none text-white">
            {{ $t('Verification requested') }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 w-full px-1" v-if="zoom_factor > 0.6">
            <div class="py-2 px-1 col-span-2">
                <div class="px-2" :class="usePage().props.auth.user.calendar_settings.high_contrast ? '' : 'border-l-4'" :style="{borderColor: getColorBasedOnUserSettings}">
                    <div :style="{lineHeight: lineHeight,fontSize: fontSize, color: getTextColorBasedOnBackground(backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent))}"
                        :class="[zoom_factor === 1 ? 'eventHeader' : '', 'font-bold']" class="">
                        <div>
                            <div class="flex items-center gap-x-1">
                                <div  v-if="usePage().props.auth.user.calendar_settings.project_status && event.project?.status" class="text-center rounded-full border group min-h-4 min-w-4 size-4 cursor-pointer" :style="{backgroundColor: event?.project?.status?.color + '33', borderColor: event?.project?.status?.color}">
                                    <div class="absolute hidden group-hover:block top-5">
                                        <div class="bg-artwork-navigation-background text-white text-xs rounded-full px-3 py-0.5">
                                            {{ event?.project?.status?.name }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <a v-if="event.project?.name && event.project?.id" :href="getEditHref(event.project?.id)" class="flex items-center">
                                        <span class="truncate !w-20 inline-block">{{ event.project?.name }}</span>
                                    </a>
                                </div>
                            </div>
                            <div v-if="usePage().props.auth.user.calendar_settings.project_artists" class="flex items-center w-28">
                                <div v-if="event.project && event.project?.artistNames"
                                     class=" truncate">
                                    {{ event.project?.artistNames }}
                                </div>
                            </div>
                            <div v-if="usePage().props.auth.user.calendar_settings.event_name"
                                 class="flex items-center w-28">
                                <div v-if="event.eventName"
                                     class="truncate">
                                    {{ event.eventName }}
                                </div>
                            </div>
                            <div class="w-28">
                                <div class=" truncate">
                                    {{ event?.eventType?.name }}
                                </div>
                            </div>
                            <div v-if="usePage().props.auth.user.calendar_settings.project_status" class="absolute right-5">
                                <div v-if="event.projectStateColor"
                                     :class="[event.projectStateColor,zoom_factor <= 0.8 ? 'border-2' : 'border-4']"
                                     class="rounded-full">
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-x-1 mt-1 w-28">
                            <component is="IconClock" class="size-3.5" stroke-width="2" v-if="!event.allDay && new Date(event.start).toDateString() === new Date(event.end).toDateString()"/>
                            <!-- Time -->
                            <div class="flex"
                                 :style="{lineHeight: lineHeight, fontSize: fontSize, color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent))}"
                                 :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']">
                                <div
                                    v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString() && !project && !atAGlance"
                                    class="items-center">
                                    <div v-if="event.allDay">
                                        {{ $t('Full day') }}
                                    </div>
                                    <div v-else>
                                        {{
                                            event.formattedDates.startTime + ' - ' + event.formattedDates.endTime
                                        }}
                                    </div>
                                </div>
                                <div class="flex w-full" v-else>
                                    <div v-if="event.allDay">
                                        <div
                                            v-if="atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()">
                                            {{ $t('Full day') }}, {{ event.formattedDates.start_without_year }}
                                        </div>
                                        <div v-else>
                                            {{ $t('Full day') }}, {{ event.formattedDates.start_without_year }} - {{ event.formattedDates.end_without_year }}
                                        </div>
                                    </div>
                                    <div v-else class="items-center">
                                        <div v-if="new Date(event.start).toDateString() !== new Date(event.end).toDateString()">
                                <span class="text-error">
                                    {{
                                        new Date(event.start).toDateString() !== new Date(event.end).toDateString() ? '!' : ''
                                    }}
                                </span>
                                            {{
                                                event.formattedDates.startDateTime_without_year  + ' - ' +  event.formattedDates.endDateTime_without_year
                                            }}
                                        </div>
                                        <div v-else>
                                            <div v-if="atAGlance">
                                                {{
                                                    event.formattedDates.startDateTime_without_year + ' - ' + event.formattedDates.endTime
                                                }}
                                            </div>
                                            <div v-else>
                                                {{
                                                    event.formattedDates.startTime + ' - ' + event.formattedDates.endTime
                                                }}
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="event.option_string && usePage().props.auth.user.calendar_settings.options"
                                 class="flex items-center">
                                <div
                                    v-if="!atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                                    class="flex eventTime font-medium subpixel-antialiased"
                                    :style="{lineHeight: lineHeight,fontSize: fontSize}">
                                    , {{ event.option_string }}
                                </div>
                                <div class="flex eventTime font-medium subpixel-antialiased ml-0.5" v-else>
                                    ({{ event.option_string.charAt(7) }})
                                </div>
                            </div>
                        </div>
                        <!-- repeating Event -->
                        <div :style="{lineHeight: lineHeight,fontSize: fontSize * 0.5}"
                             :class="[zoom_factor === 1 ? 'eventText' : '', 'font-semibold']"
                             v-if="usePage().props.auth.user.calendar_settings.repeating_events && event.is_series"
                             class="uppercase flex items-center">
                            <component is="IconRepeat" class="mr-1 min-h-3 min-w-3" stroke-width="2"/>
                            {{ $t('Repeat event') }}
                        </div>
                        <!-- User-Icons -->
                        <div class="-ml-3 mb-0.5 w-full" v-if="usePage().props.auth.user.calendar_settings.project_management && event?.project?.leaders?.length > 0">
                            <div v-if="event?.project?.leaders && !project && zoom_factor >= 0.8"
                                 class="mt-1 ml-5 flex flex-wrap">
                                <div class="flex flex-wrap flex-row -ml-1.5"
                                     v-for="user in event?.project?.leaders?.slice(0,3)">

                                    <UserPopoverTooltip :user="user" width="5" height="5" />
                                    <!--<img :src="user.profile_photo_url" alt=""
                                         class="mx-auto shrink-0 flex object-cover rounded-full"
                                         :class="['h-' + 5 * zoom_factor, 'w-' + 5 * zoom_factor]">-->
                                </div>
                                <div v-if="event?.project?.leaders.length >= 4" class="my-auto">
                                    <Menu as="div" class="relative">
                                        <MenuButton class="flex rounded-full focus:outline-none">
                                            <div
                                                :class="'h-5 w-5'"
                                                class="-ml-1.5 flex-shrink-0 flex items-center my-auto font-semibold rounded-full shadow-sm text-white bg-black">
                                                <p class="">
                                                    +{{ event?.project?.leaders.length - 3 }}
                                                </p>
                                            </div>
                                        </MenuButton>
                                        <transition enter-active-class="transition-enter-active"
                                                    enter-from-class="transition-enter-from"
                                                    enter-to-class="transition-enter-to"
                                                    leave-active-class="transition-leave-active"
                                                    leave-from-class="transition-leave-from"
                                                    leave-to-class="transition-leave-to">
                                            <MenuItems
                                                class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                <MenuItem v-for="user in event?.project?.leaders" v-slot="{ active }">
                                                    <Link href="#"
                                                          :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <img :class="'h-5 w-5'"
                                                             class="rounded-full"
                                                             :src="user.profile_photo_url"
                                                             alt=""/>
                                                        <span class="ml-4">

                                                    {{ user.first_name }} {{ user.last_name }}
                                                </span>
                                                    </Link>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="usePage().props.auth.user.calendar_settings.work_shifts" class="grid grid-cols-1 md:grid-cols-2 text-xs my-2">
                        <a v-if="firstProjectShiftTabId" :href="route('projects.tab', {project: event?.project?.id, projectTab: firstProjectShiftTabId})" v-for="(shift) in event.shifts" :key="shift.id">
                            <span>{{ shift.craft.abbreviation }}</span>
                            <span>
                                &nbsp;({{ shift.worker_count }}/{{ shift.max_worker_count }})
                            </span>
                        </a>
                    </div>
                    <div v-if="usePage().props.auth.user.calendar_settings.description" :style="{lineHeight: lineHeight, fontSize: fontSize, color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent))}" class="">
                        <EventNoteComponent :event="event"/>
                    </div>
                </div>
            </div>
            <div class="flex gap-x-1 mt-5">
                <div class="grid gird-cols-1 md:grid-cols-2 gap-1">
                    <div v-for="property in event.eventProperties" class="col-span-1">
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

                <div class="invisible group-hover/singleEvent:visible flex items-start justify-end w-full" :class="event.isPlanning ? 'pt-2' : ''">
                    <BaseMenu has-no-offset menuWidth="w-fit" :dots-color="$page.props.auth.user.calendar_settings.high_contrast ? 'text-white' : ''">
                        <MenuItem v-if="event?.isPlanning && !event.hasVerification" v-slot="{ active }">
                            <div @click="SendEventToVerification"
                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <component is="IconLock" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                {{ $t('Request verification') }}
                            </div>
                        </MenuItem>
                        <MenuItem v-if="event?.isPlanning && event.hasVerification" v-slot="{ active }">
                            <div @click="cancelVerification"
                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <component is="IconLockOpen" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                {{ $t('Withdraw verification request')}}
                            </div>
                        </MenuItem>
                        <MenuItem v-if="event.hasVerification && verifierForEventTypIds?.includes(event.eventType.id)" v-slot="{ active }">
                            <div @click="approveRequest"
                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <component is="IconChecks" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                {{ $t('Approve verification') }}
                            </div>
                        </MenuItem>
                        <MenuItem v-if="event.hasVerification && verifierForEventTypIds?.includes(event.eventType.id)" v-slot="{ active }">
                            <div @click="showRejectEventVerificationModal = true"
                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <component is="IconCircleX" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                {{ $t('Reject verification') }}
                            </div>
                        </MenuItem>
                        <MenuItem v-if="(isRoomAdmin || isCreator || hasAdminRole)" v-slot="{ active }">
                            <div @click="$emit('editEvent', event)"
                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <IconEdit class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                {{ $t('edit')}}
                            </div>
                        </MenuItem>
                        <MenuItem v-if="(isRoomAdmin || isCreator || hasAdminRole) && event?.eventType?.id === 1" v-slot="{ active }">
                            <div
                                @click="$emit('openAddSubEventModal', event, 'create', null)"
                                :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <IconCirclePlus stroke-width="1.5" stroke="currentColor" class="inline w-6 h-6 mr-2"/>
                                {{$t('Add Sub-Event')}}
                            </div>
                        </MenuItem>
                        <MenuItem v-if="isRoomAdmin || isCreator || hasAdminRole" v-slot="{ active }">
                            <div
                                @click="$emit('showDeclineEventModal', event)"
                                :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <IconX stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4 mr-2"/>
                                {{$t('Decline event')}}
                            </div>
                        </MenuItem>
                        <MenuItem v-if="isRoomAdmin || isCreator || hasAdminRole" v-slot="{ active }">
                            <div @click="$emit('openConfirmModal', event, 'main')"
                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                <IconTrash stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4 mr-2"/>
                                {{$t('Delete')}}
                            </div>
                        </MenuItem>
                    </BaseMenu>
                </div>
            </div>
        </div>
        <div v-else class="w-full flex px-2 h-full" :class="[zoom_factor < 0.6 ? 'justify-center' : 'py-3', zoom_factor === 0.4 ? 'items-center' : '']">
            <!-- Info Icon -->
            <Popover class="relative">
                <Float auto-placement portal :offset="{  5 : -10}">
                    <PopoverButton class="flex items-center justify-start gap-1 ring-0 focus:ring-0">
                        <component is="IconInfoCircle" class="size-6 " stroke-width="1.5"/>
                        <div class="w-16 max-w-16 xsDark text-left" v-if="zoom_factor > 0.4">
                            <div v-if="usePage().props.auth.user.calendar_settings.event_name && event.eventName" class="truncate">
                                {{ event.eventName }}
                            </div>
                            <a v-if="event.project && event.project?.id" :href="getEditHref(event.project?.id)" class="truncate block">
                                {{ event.project?.name }}
                            </a>
                        </div>
                    </PopoverButton>

                    <PopoverPanel class="absolute z-10 w-fit bg-white shadow-lg rounded-lg p-2">
                        <div class="px-3 py-2">
                            <div :style="{lineHeight: lineHeight,fontSize: fontSize, color: getTextColorBasedOnBackground(backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent))}"
                                 :class="[zoom_factor === 1 ? 'eventHeader' : '', 'font-bold']" class="">
                                <div class="flex items-center gap-x-1">
                                    <div  v-if="usePage().props.auth.user.calendar_settings.project_status && event.project?.status" class="text-center rounded-full border group size-4 cursor-pointer" :style="{backgroundColor: event?.project?.status?.color + '33', borderColor: event?.project?.status?.color}">
                                        <div class="absolute hidden group-hover:block top-5">
                                            <div class="bg-artwork-navigation-background text-white text-xs rounded-full px-3 py-0.5">
                                                {{ event?.project?.status?.name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <a v-if="event.project?.name && event.project?.id" :href="getEditHref(event.project?.id)" class="flex items-center">
                                            <span class="truncate !w-28 inline-block">{{ event.project?.name }}</span>
                                        </a>
                                    </div>
                                </div>
                                <div v-if="usePage().props.auth.user.calendar_settings.project_artists"
                                     class="flex items-center">
                                    <div v-if="event.projectArtists"
                                         class=" truncate">
                                        {{ event.projectArtists }}
                                    </div>
                                </div>
                                <div v-if="usePage().props.auth.user.calendar_settings.event_name"
                                     class="flex items-center">
                                    <div v-if="event.eventName"
                                         class="truncate">
                                        {{ event.eventName }}
                                    </div>
                                </div>
                                <div class="">
                                    <div class=" truncate">
                                        {{ event.eventTypeName }}
                                    </div>
                                </div>
                                <div v-if="usePage().props.auth.user.calendar_settings.project_status" class="absolute right-5">
                                    <div v-if="event.projectStateColor"
                                         :class="[event.projectStateColor,zoom_factor <= 0.8 ? 'border-2' : 'border-4']"
                                         class="rounded-full">
                                    </div>
                                </div>
                                <!-- Icon -->
                                <div v-if="event.audience"
                                     class="flex absolute top-5 right-4">
                                    <IconUsersGroup stroke-width="1.5" :width="12 * zoom_factor" :height="12 * zoom_factor"/>
                                </div>
                            </div>
                            <div class="flex">
                                <!-- Time -->
                                <div class="flex"
                                     :style="{lineHeight: lineHeight, fontSize: fontSize, color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent))}"
                                     :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']">
                                    <div
                                        v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString() && !project && !atAGlance"
                                        class="items-center">
                                        <div v-if="event.allDay">
                                            {{ $t('Full day') }}
                                        </div>
                                        <div v-else>
                                            {{
                                                new Date(event.start).format("HH:mm") + ' - ' + new Date(event.end).format("HH:mm")
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex w-full" v-else>
                                        <div v-if="event.allDay">
                                            <div
                                                v-if="atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()">
                                                {{ $t('Full day') }}, {{ new Date(event.start).format("DD.MM.") }}
                                            </div>
                                            <div v-else>
                                                {{ $t('Full day') }}, {{ new Date(event.start).format("DD.MM.") }} - {{
                                                    new Date(event.end).format("DD.MM.")
                                                }}
                                            </div>
                                        </div>
                                        <div v-else class="items-center">
                                            <div v-if="new Date(event.start).toDateString() !== new Date(event.end).toDateString()">
                                                <span class="text-error">
                                                    {{
                                                        new Date(event.start).toDateString() !== new Date(event.end).toDateString() ? '!' : ''
                                                    }}
                                                </span>
                                                {{
                                                    new Date(event.start).format("DD.MM. HH:mm") + ' - ' + new Date(event.end).format("DD.MM. HH:mm")
                                                }}
                                            </div>
                                            <div v-else>
                                                <div v-if="atAGlance">
                                                    {{
                                                        new Date(event.start).format("DD.MM. HH:mm") + ' - ' + new Date(event.end).format("HH:mm")
                                                    }}
                                                </div>
                                                <div v-else>
                                                    {{
                                                        new Date(event.start).format("HH:mm") + ' - ' + new Date(event.end).format("HH:mm")
                                                    }}
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="event.option_string && usePage().props.auth.user.calendar_settings.options"
                                     class="flex items-center">
                                    <div
                                        v-if="!atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                                        class="flex eventTime font-medium subpixel-antialiased"
                                        :style="{lineHeight: lineHeight,fontSize: fontSize}">
                                        , {{ event.option_string }}
                                    </div>
                                    <div class="flex eventTime font-medium subpixel-antialiased ml-0.5" v-else>
                                        ({{ event.option_string.charAt(7) }})
                                    </div>
                                </div>
                            </div>
                            <!-- repeating Event -->
                            <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                                 :class="[zoom_factor === 1 ? 'eventText' : '', 'font-semibold']"
                                 v-if="usePage().props.auth.user.calendar_settings.repeating_events && event.is_series"
                                 class="uppercase flex items-center">
                                <IconRepeat class="mx-1 h-3 w-3" stroke-width="1.5"/>
                                {{ $t('Repeat event') }}
                            </div>
                            <!-- User-Icons -->
                            <div class="-ml-3 mb-0.5 w-full"
                                 v-if="usePage().props.auth.user.calendar_settings.project_management && event.projectLeaders?.length > 0">
                                <div v-if="event.projectLeaders && !project && zoom_factor >= 0.8"
                                     class="mt-1 ml-5 flex flex-wrap">
                                    <div class="flex flex-wrap flex-row -ml-1.5"
                                         v-for="user in event.projectLeaders?.slice(0,3)">
                                        <img :src="user.profile_photo_url" alt=""
                                             class="mx-auto shrink-0 flex object-cover rounded-full"
                                             :class="['h-' + 5 * zoom_factor, 'w-' + 5 * zoom_factor]">
                                    </div>
                                    <div v-if="event.projectLeaders.length >= 4" class="my-auto">
                                        <Menu as="div" class="relative">
                                            <MenuButton class="flex rounded-full focus:outline-none">
                                                <div
                                                    :class="'h-5 w-5'"
                                                    class="-ml-1.5 flex-shrink-0 flex items-center my-auto font-semibold rounded-full shadow-sm text-white bg-black">
                                                    <p class="">
                                                        +{{ event.projectLeaders.length - 3 }}
                                                    </p>
                                                </div>
                                            </MenuButton>
                                            <transition enter-active-class="transition-enter-active"
                                                        enter-from-class="transition-enter-from"
                                                        enter-to-class="transition-enter-to"
                                                        leave-active-class="transition-leave-active"
                                                        leave-from-class="transition-leave-from"
                                                        leave-to-class="transition-leave-to">
                                                <MenuItems
                                                    class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="user in event.projectLeaders" v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <img :class="'h-5 w-5'"
                                                                 class="rounded-full"
                                                                 :src="user.profile_photo_url"
                                                                 alt=""/>
                                                            <span class="ml-4">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                                        </Link>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="grid gird-cols-1 md:grid-cols-2 gap-1">
                                <div v-for="property in event.eventProperties" class="col-span-1">
                                    <ToolTipComponent
                                        :icon="property.icon"
                                        icon-size="size-4"
                                        :tooltip-text="property.name"
                                        classes="text-black"
                                        stroke="1.5"
                                    />
                                </div>
                            </div>
                            <div class="invisible group-hover/singleEvent:visible">
                                <BaseMenu has-no-offset menuWidth="w-fit" :dots-color="$page.props.auth.user.calendar_settings.high_contrast ? 'text-white' : ''">
                                    <MenuItem v-if="event?.isPlanning && !event.hasVerification" v-slot="{ active }">
                                        <div @click="SendEventToVerification"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <component is="IconLock" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                            {{ $t('Request verification') }}
                                        </div>
                                    </MenuItem>
                                    <MenuItem v-if="event?.isPlanning && event.hasVerification" v-slot="{ active }">
                                        <div @click="cancelVerification"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <component is="IconLockOpen" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                            {{ $t('Withdraw verification request')}}
                                        </div>
                                    </MenuItem>
                                    <MenuItem v-if="event.hasVerification && verifierForEventTypIds?.includes(event.eventType.id)" v-slot="{ active }">
                                        <div @click="approveRequest"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <component is="IconChecks" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                            {{ $t('Approve verification') }}
                                        </div>
                                    </MenuItem>
                                    <MenuItem v-if="event.hasVerification && verifierForEventTypIds?.includes(event.eventType.id)" v-slot="{ active }">
                                        <div @click="showRejectEventVerificationModal = true"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <component is="IconCircleX" class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                            {{ $t('Reject verification') }}
                                        </div>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <div @click="$emit('editEvent', event)"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <IconEdit class="inline h-4 w-4 mr-2" stroke-width="1.5"/>
                                            {{ $t('edit')}}
                                        </div>
                                    </MenuItem>
                                    <MenuItem v-if="(isRoomAdmin || isCreator || hasAdminRole) && event.eventType.id === 1" v-slot="{ active }">
                                        <div
                                            @click="$emit('openAddSubEventModal', event, 'create', null)"
                                            :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <IconCirclePlus stroke-width="1.5" stroke="currentColor" class="inline w-6 h-6 mr-2"/>
                                            {{$t('Add Sub-Event')}}
                                        </div>
                                    </MenuItem>
                                    <MenuItem v-if="isRoomAdmin || isCreator || hasAdminRole" v-slot="{ active }">
                                        <div
                                            @click="$emit('showDeclineEventModal', event)"
                                            :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <IconX stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4 mr-2"/>
                                            {{$t('Decline event')}}
                                        </div>
                                    </MenuItem>
                                    <MenuItem v-if="isRoomAdmin || isCreator || hasAdminRole" v-slot="{ active }">
                                        <div @click="$emit('openConfirmModal', event, 'main')"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                            <IconTrash stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4 mr-2"/>
                                            {{$t('Delete')}}
                                        </div>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </div>
                        <div v-if="usePage().props.auth.user.calendar_settings.work_shifts" class="ml-1 pb-1 text-xs">
                            <a v-if="firstProjectShiftTabId" :href="route('projects.tab', {project: event.projectId, projectTab: firstProjectShiftTabId})" v-for="shift in event.shifts">
                                <span>{{ shift.craft.abbreviation }}</span>
                                <span>
                                    &nbsp;({{ shift.worker_count }}/{{ shift.max_worker_count }})
                                </span>
                            </a>
                        </div>
                        <div v-if="usePage().props.auth.user.calendar_settings.description"
                             :style="{lineHeight: lineHeight, fontSize: fontSize, color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent))}"
                             class="p-0.5 ml-0.5">
                            <EventNoteComponent :event="event"/>
                        </div>
                    </PopoverPanel>
                </Float>
            </Popover>

        </div>
    </div>
    <div v-if="event.subEvents?.length > 0" class="w-full">
        <div v-for="subEvent in event.subEvents" class="mb-1">
            <div class="w-full relative group rounded-lg border-l-[6px]" :style="{borderColor: backgroundColorWithOpacity(getColorBasedOnUserSettings, usePage().props.high_contrast_percent)}">
                <div class="bg-indigo-500/50 hidden absolute w-full h-full rounded-lg group-hover:block justify-center align-middle items-center">
                    <div class="flex justify-center items-center h-full gap-2">
                        <button @click="$emit('editSubEvent', subEvent, 'edit', event)" type="button"
                                class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <IconEdit class="h-4 w-4" stroke-width="1.5"/>
                        </button>
                        <button v-if="isRoomAdmin || isCreator || hasAdminRole"
                                @click="$emit('openConfirmModal', subEvent, 'sub')" type="button"
                                class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            <IconTrash stroke-width="1.5"
                                       stroke="currentColor" class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
                <div :class="[subEvent.class]"
                     :style="{ height: (totalHeight - heightSubtraction(subEvent)) * zoom_factor + 10 + 'px', backgroundColor: backgroundColorWithOpacity(subEvent.type.hex_code, usePage().props.high_contrast_percent) }"
                     class="px-2 py-1 rounded-r-lg overflow-y-auto">
                    <div class="flex items-start justify-between">
                        <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                             :class="[zoom_factor === 1 ? 'eventHeader' : '', 'font-bold']"
                             class="flex justify-between w-36">
                            <div class="flex" v-if="subEvent.eventName?.length > 0">
                                <div v-if="subEvent.type.abbreviation" class="mr-1 truncate">
                                    {{ subEvent.type.abbreviation }}:
                                </div>
                                <div class="flex items-center truncate">
                                    {{ subEvent.eventName }}
                                </div>
                            </div>
                            <div v-else class="flex items-center truncate">
                                {{ subEvent.type.name }}
                            </div>
                            <!-- Icons -->
                        </div>
                        <div class="flex items-center -space-x-1">
                            <div v-for="property in subEvent.event_properties" class="bg-gray-100 rounded-full border-2 border-white p-0.5" >
                                <component :is="property.icon" class="size-3" stroke-width="1.5" />
                            </div>
                        </div>
                    </div>
                    <!-- Time -->
                    <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                         :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']"
                         class="flex">
                        <div v-if="subEvent.formattedDates.is_same_day && !project && !atAGlance"
                            class="items-center">
                            <div v-if="subEvent.allDay">
                                {{ $t('Full day') }}
                            </div>
                            <div v-else>
                                {{ subEvent.formattedDates.start_time }} - {{ subEvent.formattedDates.end_time }}
                            </div>
                        </div>
                        <div class="flex w-full" v-else>
                            <div v-if="subEvent.allDay">
                                <div v-if="atAGlance && subEvent.formattedDates.start_date === subEvent.formattedDates.end_date">
                                    {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }}
                                </div>
                                <div v-else>
                                    <span class="text-error">
                                        {{ subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date ? '!' : '' }}
                                    </span>
                                        {{ $t('Full day') }}, {{ subEvent.formattedDates.start_date }} - {{ subEvent.formattedDates.end_date }}
                                </div>

                            </div>
                            <div v-else class="items-center">
                                <span class="text-error">
                                    {{ subEvent.formattedDates.start_date !== subEvent.formattedDates.end_date ? '!' : '' }}
                                </span>
                                {{ subEvent.formattedDates.start_date_time  }} - {{ subEvent.formattedDates.end_date_time }}
                            </div>
                        </div>
                    </div>
                    <div v-if="usePage().props.auth.user.calendar_settings.work_shifts" class="ml-0.5 text-xs"
                         :style="{color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.event_type_color, usePage().props.high_contrast_percent))}">
                        <div v-for="shift in subEvent.shifts">
                            <span>{{ shift.craft.abbreviation }}</span>
                            (
                                <VueMathjax :formula="convertToMathJax(decimalToFraction(shift.user_count ? shift.user_count : 0))"/>/{{ shift.number_employees }}
                                <span v-if="shift.number_masters > 0">| {{ shift.master_count }}/{{ shift.number_masters }}</span>
                            )
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

<script setup>
import {computed, defineAsyncComponent, inject, onMounted, ref} from "vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import {IconCirclePlus, IconEdit, IconRepeat, IconTrash, IconUsersGroup, IconX} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import {Menu, MenuButton, MenuItem, MenuItems, Popover, PopoverButton, PopoverPanel} from "@headlessui/vue";
import VueMathjax from "vue-mathjax-next";
import {useI18n} from "vue-i18n";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import EventNoteComponent from "@/Layouts/Components/EventNoteComponent.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {Float} from "@headlessui-float/vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
const {t} = useI18n(), $t = t;
const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);
const atAGlance = ref(usePage().props.auth.user.at_a_glance ?? false);
const showRejectEventVerificationModal = ref(false);

const emits = defineEmits([
    'editEvent',
    'editSubEvent',
    'openAddSubEventModal',
    'openConfirmModal',
    'showDeclineEventModal',
    'changedMultiEditCheckbox'
]);

const RejectEventVerificationRequestModal = defineAsyncComponent({
    loader: () => import('@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue'),
    delay: 200,
    timeout: 3000,
})

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    },
    fontSize: {
        type: String,
        required: false,
        default: '0.875rem'
    },
    lineHeight: {
        type: String,
        required: false,
        default: '1.25rem'
    },
    first_project_tab_id: {
        type: Number,
        required: false,
        default: 1
    },
    project: {
        type: Object,
        required: false,
        default: null
    },
    hasAdminRole: {
        type: Boolean,
        required: false,
        default: false
    },
    rooms: {
        type: Object,
        required: true
    },
    width: {
        type: Number,
        required: true
    },
    firstProjectShiftTabId: {
        type: [Number, String],
        required: false,
        default: null
    },
    isHeightFull: {
        type: Boolean,
        required: false,
        default: false
    },
    isInDailyView: {
        type: Boolean,
        required: false,
        default: false
    },
    verifierForEventTypIds: {
        type: Array,
        required: false,
        default: []
    },
    isPlanning: {
        type: Boolean,
        required: false,
        default: false
    }
});

const isHighlighted = computed(() => {
    const highlightEventId = usePage().props.urlParameters.highlightEventId
    return highlightEventId && parseInt(highlightEventId) === parseInt(props.event.id);
})

const element = ref(null);
const changeMultiEditCheckbox = (eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd) => {
    emits.call(this, 'changedMultiEditCheckbox', eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd);
};

const isRoomAdmin = computed(() => {
    return props.rooms?.find(room => room.id === props.event.roomId)?.admins.some(admin => admin.id === usePage().props.auth.user.id) || false;
});

const checkIfMultiEditIsEnabled = computed(() => {
    const { isPlanning, multiEdit, zoom_factor, event } = props;

    if (multiEdit) {
        if (isPlanning) {
            return event.hasVerification || event.isPlanning;
        }
        if (zoom_factor > 0.4) {
            return true;
        }

        return true;
    }

    return false;
});


const isCreator = computed(() => {
    return props.event.created_by.id === usePage().props.auth.user.id
});

const roomCanBeBookedByEveryone = computed(() => {
    return props.rooms?.find(room => room.id === props.event.roomId).everyone_can_book
});

const {
    backgroundColorWithOpacity,
    detectParentBackgroundColor,
    getTextColorBasedOnBackground,
    parentBackgroundColor
} = useColorHelper();

const textColorWithDarken = computed(() => {
    const percent = 75;
    const color = getColorBasedOnUserSettings.value;
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
});

const getColorBasedOnUserSettings = computed(() => {
    return usePage().props.auth.user.calendar_settings.use_event_status_color ? props.event?.eventStatus?.color : props.event.eventType.hex_code;
});

const totalHeight = computed(() => {
    let height = 42;
    // ProjectStatus is in same row as name -> no extra height needed
    if (usePage().props.auth.user.calendar_settings.project_status) height += 0;
    //Options are in same row as time -> no extra height needed
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

const clickOnCheckBox = () => {
    const checkBox = document.getElementById(props.event.id)

    checkBox.click();
}

const convertToMathJax = (fraction) => {
    const parts = fraction.split(' ');

    if (parts.length === 1) {
        return `${parts[0]}`;
    } else {
        const wholePart = parts[0] > 0
            ? parts[0]
            : "";
        const fractionParts = parts[1].split('/');
        const numerator = fractionParts[0];
        const denominator = fractionParts[1];
        return `${wholePart}$\\frac{${numerator}}{${denominator}}$`;
    }
};

const decimalToFraction = (decimal) => {
    let wholePart = Math.floor(decimal);
    decimal = decimal - wholePart;

    if (decimal === parseInt(decimal)) {
        if (decimal < 1) {
            return `${wholePart}`;
        }
        return `${parseInt(decimal)}/1`;
    } else {
        let precision = getFirstDigitAfterDecimal(decimal) === 3 ? 3 : 1000; // The desired precision for the fraction
        let top = Math.round(decimal * precision);
        let bottom = precision;

        let x = gcd(top, bottom);
        return `${wholePart} ${top / x}/${bottom / x}`;
    }
};

const getFirstDigitAfterDecimal = (number) => {
    const decimalPart = number.toString().split('.')[1];
    if (decimalPart && decimalPart.length > 0) {
        return parseInt(decimalPart[0]);
    }
    return null; // Return null if there is no decimal part
};

const gcd = (a, b) => {
    return (b) ? gcd(b, a % b) : a;
};

onMounted(() => {
    if (element.value) {
        detectParentBackgroundColor(element.value);
    }
});

const getEditHref = (projectId) => {
    return route('projects.tab', {project: projectId, projectTab: props.first_project_tab_id});
};

const SendEventToVerification = () => {
    router.post(route('events.sendToVerification', {event: props.event.id}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {

        }
    });
}

const cancelVerification = () => {
    router.post(route('event-verifications.cancel-verification', props.event.id), {}, {
        preserveScroll: true,
        preserveState: false,
    })
}

const approveRequest = () => {
    router.post(route('event-verifications.approved-by-event', props.event.id), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}




</script>
