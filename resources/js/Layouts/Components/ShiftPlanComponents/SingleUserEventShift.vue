<template>
    <div>
        <div ref="sidebarTagComponent"
             class="text-secondaryHover xsWhiteBold p-1 flex justify-between items-center rounded-t-lg"
             :style="{ backgroundColor: backgroundColorWithOpacity(eventType?.hex_code), color: getTextColorBasedOnParent(eventType?.hex_code) }">
            <a :href="project?.id ? route('projects.tab', {project: project.id, projectTab: firstProjectShiftTabId}) : '#'" class="w-40 truncate cursor-pointer hover:text-gray-300 transition-all duration-150 ease-in-out">
                {{ eventType?.abbreviation }}: {{ event.projectName ?? project?.name }}
            </a>
            <div v-if="shift.is_committed">
                <IconLock stroke-width="1.5" class="h-5 w-5 text-white"/>
            </div>
        </div>
        <div class="flex flex-col bg-backgroundGray rounded-b-lg px-1 pt-1">
            <div class="flex flex-row justify-between border-b-2 border-dashed border-gray-400 pb-1">
                <span class="text-sm font-bold">{{ shift.start }} - {{ shift.end }}, {{ event.room?.name }}</span>
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
</template>
<script setup>
import {IconCalendarMonth, IconLock} from "@tabler/icons-vue";
import {router} from "@inertiajs/vue3";
import ShiftNoteComponent from "@/Layouts/Components/ShiftNoteComponent.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";
import {ref} from "vue";

const {backgroundColorWithOpacity, isDarkColor} = ColorHelper.methods;

const props = defineProps({
    type: {
        type: String,
        required: true
    },
    event: {
        type: Object,
        required: true
    },
    shift: {
        type: Object,
        required: true
    },
    project: {
        type: Object,
        required: false
    },
    eventType: {
        type: Object,
        required: true
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

const hasColleaguesOnShift = (shift) => {
    return shift.users.length > 0 || shift.freelancer.length > 0 || shift.service_provider.length > 0;
};

const parentBackgroundColor = ref(null);

const detectParentBackgroundColor = (element) => {
    if (!element || !element.parentElement) {
        this.parentBackgroundColor = 'rgba(255, 255, 255, 1)';
        return;
    }

    const parentElement = element.parentElement;
    const computedStyle = window.getComputedStyle(parentElement);
    const bgColor = computedStyle.backgroundColor;

    if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
        parentBackgroundColor.value = bgColor;
    } else {
        detectParentBackgroundColor(parentElement);
    }
};

const checkExplicitBackgroundColor = (color) => {
    const rgbToHex = (r, g, b) => {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).toUpperCase();
    };

    const rgbMatch = color.match(/\d+/g);
    if (rgbMatch) {
        const [r, g, b] = rgbMatch.map(Number);
        const hexColor = rgbToHex(r, g, b);

        if (hexColor === "#000000") return "#FFFFFF";
        if (hexColor === "#FFFFFF") return "#000000";
    }
    return null;
};

const getTextColorBasedOnParent = (color) => {
    const explicitTextColor = checkExplicitBackgroundColor(backgroundColorWithOpacity(color));
    if (explicitTextColor) {
        return explicitTextColor;
    }
    if (parentBackgroundColor.value) {
        const isDark = isDarkColor(parentBackgroundColor.value);
        return isDark ? '#FFFFFF' : '#000000';
    }
    return '#000000';
};
</script>
