<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <TinyPageHeadline
                    :title="$t('Work Time Pattern')"
                    :description="$t('Select a work time pattern for the user or enter custom working times.')"
                />

                <div v-if="selectedWorkTimePattern" class="mt-1">
                    <p class="text-sm font-lexend text-gray-500">
                        {{ $t('The working time pattern “{0}” is currently selected. This means that working times cannot be edited. Remove the working time pattern to enter your own times.', [selectedWorkTimePattern.name]) }}
                    </p>
                    <div class="mt-2 cursor-pointer text-artwork-buttons-create hover:text-artwork-buttons-default flex items-center gap-x-1 text-sm font-lexend" @click="removePattern">
                        <component is="IconRepeat" class="size-5 text-gray-500"/>
                        {{ $t('Click here to remove the current work time pattern.') }}
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <GlassyIconButton text="Select Work Time Pattern" icon="IconClockSearch" @click.stop="showSelectWorkTimePatternModal = true"/>
            </div>
        </div>

        <div class="mt-5" v-if="!isSelectingPattern">
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <BaseInput
                            v-model="workTimeForm.monday"
                            :disabled="isSelectingPattern"
                            label="Monday"
                            type="time"
                            id="monday" />
                        <p v-if="workTimeForm.errors.monday" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>

                    <div>
                        <BaseInput
                            v-model="workTimeForm.tuesday"
                            :disabled="isSelectingPattern"
                            label="Tuesday"
                            type="time"
                            id="tuesday" />
                        <p v-if="workTimeForm.errors.tuesday" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>

                    <div>
                        <BaseInput
                            v-model="workTimeForm.wednesday"
                            :disabled="isSelectingPattern"
                            label="Wednesday"
                            type="time"
                            id="wednesday" />
                        <p v-if="workTimeForm.errors.wednesday" class="text-red-500 mt-0.5 text-xs"></p>

                    </div>

                    <div>
                        <BaseInput
                            v-model="workTimeForm.thursday"
                            :disabled="isSelectingPattern"
                            label="Thursday"
                            type="time"
                            id="thursday" />
                        <p v-if="workTimeForm.errors.thursday" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>

                    <div>
                        <BaseInput
                            v-model="workTimeForm.friday"
                            :disabled="isSelectingPattern"
                            label="Friday"
                            type="time"
                            id="friday" />
                        <p v-if="workTimeForm.errors.friday" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>

                    <div>
                        <BaseInput
                            v-model="workTimeForm.saturday"
                            :disabled="isSelectingPattern"
                            label="Saturday"
                            type="time"
                            id="saturday" />
                        <p v-if="workTimeForm.errors.saturday" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>

                    <div>
                        <BaseInput
                            v-model="workTimeForm.sunday"
                            :disabled="isSelectingPattern"
                            label="Sunday"
                            type="time"
                            id="sunday" />
                        <p v-if="workTimeForm.errors.sunday" class="text-red-500 mt-0.5 text-xs"></p>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-5">
                    <div>
                        <ArtworkBaseModalButton type="submit">
                            <span v-if="!workTimeForm.processing">{{ $t('Save') }}</span>
                            <span v-else>{{ $t('Saving...') }}</span>
                        </ArtworkBaseModalButton>
                    </div>
                </div>
            </form>
        </div>

        <div v-else class="mt-5">
            <div>
                <p class="text-sm font-lexend text-gray-500">
                    {{ $t('The working time pattern “{0}” is currently selected. This means that working times cannot be edited.', [selectedWorkTimePattern.name]) }}
                </p>
            </div>
            <p class="mt-1 flex text-sm text-gray-500 space-x-2 divide-x divide-gray-200 font-lexend">
                <span><b>{{ $t('Monday')}}</b>: {{ workTime.monday }} Std.</span>
                <span class="pl-2"><b>{{ $t('Tuesday')}}</b>: {{ workTime.tuesday }} Std.</span>
                <span class="pl-2"><b>{{ $t('Wednesday')}}</b>: {{ workTime.wednesday }} Std.</span>
                <span class="pl-2"><b>{{ $t('Thursday')}}</b>: {{ workTime.thursday }} Std.</span>
                <span class="pl-2"><b>{{ $t('Friday')}}</b>: {{ workTime.friday }} Std.</span>
                <span class="pl-2"><b>{{ $t('Saturday')}}</b>: {{ workTime.saturday }} Std.</span>
                <span class="pl-2"><b>{{ $t('Sunday')}}</b>: {{ workTime.sunday }} Std.</span>
                <span class="pl-2"><b>{{ $t('Sum')}}</b>: {{ workTime.full_work_time_in_hours }} Std.</span>
            </p>
        </div>

        <SelectWorkTimePatternModal
            :work-time-patterns="workTimePatterns"
            v-if="showSelectWorkTimePatternModal"
            @close="showSelectWorkTimePatternModal = false"
            @select-pattern="selectPattern"
        />
    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import SelectWorkTimePatternModal from "@/Pages/Users/Components/SelectWorkTimePatternModal.vue";
import {computed, ref} from "vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    workTime: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            work_time_pattern_id: null,
            monday: null,
            tuesday: null,
            wednesday: null,
            thursday: null,
            friday: null,
            saturday: null,
            sunday: null
        })
    },
    currentTab: {
        type: String,
        required: true
    },
    workTimePatterns: {
        type: Object,
        required: true
    }
})

const workTimeForm = useForm({
    id: props.workTime?.id || null,
    work_time_pattern_id: props.workTime?.work_time_pattern_id || null,
    monday: props.workTime?.monday || '00:00',
    tuesday: props.workTime?.tuesday || '00:00',
    wednesday: props.workTime?.wednesday || '00:00',
    thursday: props.workTime?.thursday || '00:00',
    friday: props.workTime?.friday || '00:00',
    saturday: props.workTime?.saturday || '00:00',
    sunday: props.workTime?.sunday || '00:00'
})

const showSelectWorkTimePatternModal = ref(false)

const selectPattern = (workTimePattern) => {
    workTimeForm.work_time_pattern_id = workTimePattern.id;
    workTimeForm.monday = workTimePattern.monday;
    workTimeForm.tuesday = workTimePattern.tuesday;
    workTimeForm.wednesday = workTimePattern.wednesday;
    workTimeForm.thursday = workTimePattern.thursday;
    workTimeForm.friday = workTimePattern.friday;
    workTimeForm.saturday = workTimePattern.saturday;
    workTimeForm.sunday = workTimePattern.sunday;

    showSelectWorkTimePatternModal.value = false;

    submit();
}

const removePattern = () => {
    workTimeForm.work_time_pattern_id = null;
    workTimeForm.monday = '00:00';
    workTimeForm.tuesday = '00:00';
    workTimeForm.wednesday = '00:00';
    workTimeForm.thursday = '00:00';
    workTimeForm.friday = '00:00';
    workTimeForm.saturday = '00:00';
    workTimeForm.sunday = '00:00';

    submit();
}

const selectedWorkTimePattern = computed(() => {
    return props.workTimePatterns.find(pattern => pattern.id === workTimeForm.work_time_pattern_id);
});

const isSelectingPattern = computed(() => {
    return workTimeForm.work_time_pattern_id !== null;
});

const submit = () => {
    workTimeForm.patch(route('shift.work-time-pattern.update-user', props.userToEdit.id), {
        onSuccess: () => {
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
}
</script>

<style scoped>

</style>