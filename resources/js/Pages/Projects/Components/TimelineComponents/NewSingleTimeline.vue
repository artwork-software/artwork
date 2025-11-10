<template>
    <div :id="'timeline-container-' + event.id + '-' + timeLineForm.id"
         class="flex flex-col relative h-full mb-2"
         :class="{'cursor-pointer': can('can plan shifts') || hasAdminRole()}"
         v-if="timeLineForm.start !== null && timeLineForm.end !== null">
        <div class="text-xs bg-zinc-100 border border-zinc-200 shadow-sm p-2 h-full rounded-lg" :class="time.clicked || editDescription ? '' : 'group flex justify-between'" >
            <div>
                <div v-if="time.clicked" class="mb-3 mx-1">
                    <SwitchGroup as="div" class="flex items-center">
                        <SwitchLabel as="span" class="mr-3 text-xs" :class="automaticMode ? 'font-bold' : 'text-gray-400'">
                            {{ $t('Automatic mode')}}
                        </SwitchLabel>
                        <Switch v-model="automaticMode" :class="[automaticMode ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-3 w-6 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                            <span aria-hidden="true" :class="[!automaticMode  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 text-xs" :class="!automaticMode? 'font-bold' : 'text-gray-400'">
                            {{ $t('Manual mode') }}
                        </SwitchLabel>
                    </SwitchGroup>
                </div>
                <div class="cursor-pointer">
                    <div v-if="!time.clicked" @click="canEditComponent ? openCloseTimeEditor(true) : ''">
                        <div v-if="time.start_or_end && time.start === time.end && time.start_date === time.end_date">
                            <p class="text-xs">{{ $t('From') }} {{ time.start }}</p>
                        </div>
                        <div v-else-if="!time.start_or_end && time.start === time.end && time.start_date === time.end_date">
                            <p class="text-xs">{{ $t('Until') }} {{ time.end }}</p>
                        </div>
                        <div v-else-if="time.start_date !== time.end_date">
                            <p class="text-xs">{{ time.formatted_dates.start_date }} {{ time.start }} - {{ time.formatted_dates.end_date }} {{ time.end }}</p>
                        </div>
                        <div v-else>
                            <p class="text-xs">{{ time.start }} - {{ time.end }}</p>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div v-if="!automaticMode">
                            <BaseInput type="date" :id="'start_date_' + timeLineForm.id" v-model="timeLineForm.start_date" label="Start date" is-dark is-small />
                        </div>
                        <div v-if="!automaticMode">
                            <BaseInput type="date" :id="'end_date_' + timeLineForm.id" v-model="timeLineForm.end_date" label="End date" is-dark is-small />
                        </div>
                        <div>
                            <BaseInput type="time" :id="'start_' + timeLineForm.id" v-model="timeLineForm.start" label="Start-Time" is-dark is-small />
                        </div>
                        <div>
                            <BaseInput type="time" :id="'start_' + timeLineForm.id" v-model="timeLineForm.end" label="End-Time" is-dark is-small />
                        </div>
                    </div>
                </div>
                <div class="mt-2"  @click="openCloseDescriptionEditor(true)">
                    <div v-if="!editDescription">
                        <div v-if="!timeLineForm.description">
                            <IconNote  class="h-5 w-5 cursor-pointer text-artwork-buttons-context hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out" stroke-width="2" />
                        </div>
                        <div v-else>
                            <p class="text-xs" v-html="timeLineForm.description"></p>
                        </div>
                    </div>
                    <div v-else class="py-3">
                        <BaseTextarea is-dark :id="'editTimeLineDescription_' + timeLineForm.id" v-model="timeLineForm.description" label="Comment" />
                    </div>
                </div>
                <div class="text-xs text-artwork-messages-error mt-2" v-if="helpText">
                    {{ helpText }}
                </div>
                <div v-if="time.clicked || editDescription" class="pt-4 flex items-center justify-between">
                    <!-- tiny cancel button -->
                    <button class="bg-gray-800 text-white text-[9px] rounded-lg px-2.5 py-0.5" @click="resetForm">{{ $t('Cancel') }}</button>
                    <button class="bg-artwork-buttons-create text-white text-[9px] rounded-lg px-2.5 py-0.5" @click="saveTimeline">{{ $t('Save') }}</button>
                </div>


            </div>
            <div class="invisible group-hover:visible" v-if="!time.clicked || editDescription">
                <BaseMenu v-if="canEditComponent" white-menu-background has-no-offset>
                    <BaseMenuItem white-menu-background title="Edit" :icon="IconEdit" @click="openCloseTimeEditor(true)" />
                    <BaseMenuItem white-menu-background title="Delete" :icon="IconTrash" @click="deleteTime" />
                </BaseMenu>
            </div>
        </div>

    </div>
</template>

<script setup>

// permission
import {usePermission} from "@/Composeables/Permission.js";
import {router, useForm, usePage} from "@inertiajs/vue3";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
const {hasAdminRole, can} = usePermission(usePage().props)
import {IconCircleCheck, IconEdit, IconNote, IconTrash} from '@tabler/icons-vue';
import timeline from "@/Pages/Projects/Components/Timeline.vue";
import {nextTick, ref, watch} from "vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

const props = defineProps({
    time: {
        type: Object,
        required: true
    },
    event: {
        type: Object,
        required: true
    },
    canEditComponent: {
        type: Boolean,
        required: false,
        default: false
    }
})

const $t = useTranslation()
const emits = defineEmits([
    'wantsFreshPlacements'
]);

const currentHeight = ref(0)

const editDescription = ref(false)
const helpText = ref('')
const automaticMode = ref(true)

const timeLineForm = useForm({
    id: props.time.id,
    start: props.time.start,
    end: props.time.end,
    start_date: props.time.start_date,
    end_date: props.time.end_date,
    description: props.time.description,
    formatted_dates: props.time.formatted_dates
})

const saveTimeline = () => {
    helpText.value = ''
    if (timeLineForm.isDirty) {

        // if automatic mode is on, set the end date to the start date
        if (automaticMode.value) {
            timeLineForm.end_date = timeLineForm.start_date
        }

        // end date and start date must be the same or end date must be after start date
        if (timeLineForm.start_date > timeLineForm.end_date) {
            helpText.value = $t('End date must be after start date or the same')
            return
        }

        timeLineForm.patch(route('update.timeline', timeLineForm.id), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                timeLineForm.clicked = false
                editDescription.value = false
                emits('wantsFreshPlacements')
            }
        })
    } else {
        props.time.clicked = false
        openCloseDescriptionEditor(false)
    }
}

const openCloseDescriptionEditor = (bool) => {
    editDescription.value = bool
    nextTick(() => {
        document.getElementById('editTimeLineDescription_' + timeLineForm.id).focus();
    })
    addHeightToTimeline()
}

const openCloseTimeEditor = (bool) => {
    props.time.clicked = bool
    nextTick(() => {
        if (automaticMode.value){
            document.getElementById('start_' + timeLineForm.id).focus();
        } else {
            document.getElementById('start_date_' + timeLineForm.id).focus();
        }
    })
    addHeightToTimeline()
}

const addHeightToTimeline = () => {
    const container = document.getElementById('timeline-container-' + props.event.id + '-' + timeLineForm.id)

    if (container){
        if (editDescription.value || props.time.clicked){
            currentHeight.value = parseInt(container.style.height)
            container.style.height = 'auto'
        } else {
            container.style.height = currentHeight.value + 'px'
        }
    }
}

const resetForm = () => {
    editDescription.value = false
    props.time.clicked = false

    addHeightToTimeline()
}

const deleteTime = () => {
    router.delete(
        route('delete.timeline.row', props.time.id),
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                emits('wantsFreshPlacements')
            }
        }
    )
};

// watch on automatic mode
watch(automaticMode, () => {
    nextTick(() => {
        if(automaticMode.value){
            document.getElementById('start_' + timeLineForm.id).focus();
        } else {
            document.getElementById('start_date_' + timeLineForm.id).focus();
        }
    })
})

</script>

<style scoped>

</style>
