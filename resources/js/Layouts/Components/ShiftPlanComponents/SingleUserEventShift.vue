<template>
    <div>
        <div ref="sidebarTagComponent" class="text-secondaryHover xsWhiteBold p-1 flex flex-wrap justify-between items-center rounded-t-lg w-full truncate h-[28px]"
             :style="{ backgroundColor: eventType ? backgroundColorWithOpacity(eventType?.hex_code, percentage) : '#e8e8e8', color: eventType ? getTextColorBasedOnBackground(backgroundColorWithOpacity(eventType?.hex_code, percentage)) : '#000000' }">
            <a v-if="project && eventType" :href="project?.id ? route('projects.tab', {project: project.id, projectTab: firstProjectShiftTabId}) : '#'" class="w-40 truncate cursor-pointer hover:text-gray-300 transition-all duration-150 ease-in-out">
                {{ eventType?.abbreviation }}: {{ project?.name }}
            </a>
            <div v-else>
                <span class="w-40 truncate">
                    {{ $t('Universal Shift')}}
                </span>
            </div>
            <div v-if="shift.is_committed">
                <IconLock stroke-width="1.5" class="h-5 w-5 text-white"/>
            </div>
            <button type="button" @click="showRequestWorkTimeChangeModal = true" v-if="userToEditId === usePage().props.auth.user.id && type === 'user'">
                <Component is="IconClockEdit" class="h-5 w-5 hover:text-blue-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5"/>
            </button>
        </div>
        <div class="flex flex-col bg-backgroundGray rounded-b-lg px-1 pt-1">
            <div class="flex flex-row justify-between border-b-2 border-dashed border-gray-400 pb-1">
                <span class="text-sm font-bold">{{ shift.start }} - {{ shift.end }}, {{ shift?.room?.name ?? shift?.event?.room?.name }}</span>
                <IconCalendarMonth v-if="project" class="w-5 h-5 cursor-pointer" @click="toggleProjectTimePeriodAndRedirect"/>
            </div>

            <div class="border-b-2 border-dashed border-gray-400 pb-1 pt-0.5">
                <template v-if="hasColleaguesOnShift(shift)">
                    <span class="text-sm font-bold">{{ $t('Colleagues') }}</span>
                    <ul class="text-sm font-bold text-gray-700">
                        <template v-for="user in shift.users">
                            <li v-if="(type === 'user' && user.id !== userToEditId) || type !== 'user'"
                                class="flex flex-row items-center gap-x-1">
                                <UserPopoverTooltip :user="user"
                                                    height="5"
                                                    width="5"
                                                    :use-slot-instead-of-icon="true"
                                                    :dont-translate-popover-position="true">
                                    {{user.first_name}},&nbsp;{{ user.last_name }}
                                </UserPopoverTooltip>
                            </li>
                        </template>
                        <template v-for="freelancer in shift.freelancer">
                            <li v-if="(type === 'freelancer' && freelancer.id !== userToEditId) || type !== 'freelancer'"
                                class="flex flex-row items-center gap-x-1">
                                <UserPopoverTooltip :user="freelancer"
                                                    height="5"
                                                    width="5"
                                                    :use-slot-instead-of-icon="true"
                                                    :dont-translate-popover-position="true">
                                    {{freelancer.first_name}},&nbsp;{{ freelancer.last_name }}
                                </UserPopoverTooltip>
                            </li>
                        </template>
                        <template v-for="service_provider in shift.service_provider">
                            <li v-if="(type === 'service_provider' && service_provider.id !== userToEditId) || type !== 'service_provider'"
                                class="flex flex-row items-center gap-x-1">
                                <UserPopoverTooltip :user="service_provider"
                                                    height="5"
                                                    width="5"
                                                    :use-slot-instead-of-icon="true"
                                                    :dont-translate-popover-position="true">
                                    {{service_provider.provider_name}}
                                </UserPopoverTooltip>
                            </li>
                        </template>
                    </ul>
                </template>
                <span class="text-sm font-bold" v-else>
                    {{ $t('No colleagues') }}
                </span>
            </div>
            <div class="w-full text-xs">
                <ShiftNoteComponent :shift="shift" />
            </div>
        </div>
    </div>

    <RequestWorkTimeChangeModal
        :user="shift.users.find(user => user.id === userToEditId)"
        :shift="shift"
        v-if="showRequestWorkTimeChangeModal"
        @close="showRequestWorkTimeChangeModal = false"
    />
</template>
<script setup>
import {IconCalendarMonth, IconLock} from "@tabler/icons-vue";
import {router} from "@inertiajs/vue3";
import ShiftNoteComponent from "@/Layouts/Components/ShiftNoteComponent.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

import {usePage} from "@inertiajs/vue3";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import RequestWorkTimeChangeModal from "@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue";
import {ref} from "vue";
import Button from "@/Jetstream/Button.vue";
const percentage = usePage().props.high_contrast_percent;
const {
    backgroundColorWithOpacity,
    getTextColorBasedOnBackground,
} = useColorHelper();

const props = defineProps({
    type: {
        type: String,
        required: true,
        default: null
    },
    event: {
        type: [Object, null],
        required: true,
        default: []
    },
    shift: {
        type: Object,
        required: true
    },
    project: {
        type: [Object, null],
        required: false,
        default: []
    },
    eventType: {
        type: [Object, null],
        required: true,
        default: []
    },
    firstProjectShiftTabId: {
        type: Number,
        required: true
    },
    userToEditId: {
        type: Number,
        required: true
    }
});

const toggleProjectTimePeriodAndRedirect = () => {
    if (props.project?.id) {
        router.patch(
            route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
            {
                use_project_time_period: true,
                project_id: props.project.id
            }
        );
    }
};

const showRequestWorkTimeChangeModal = ref(false);

const hasColleaguesOnShift = (shift) => {
    // The user where the shift is to be displayed is always automatically in the users array, which is why users is always greater than 0
    return shift.users?.length > 1 || shift.freelancer?.length > 0 || shift.serviceProvider?.length > 0;
};

</script>
