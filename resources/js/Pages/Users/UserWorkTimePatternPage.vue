<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <div class="flex items-center justify-end">
            <GlassyIconButton text="Select Work Time Pattern" icon="IconClockSearch" @click.stop="showSelectWorkTimePatternModal = true"/>
        </div>

        <div class="mt-5">

            <div v-if="isSelectingPattern">

                <BaseAlertComponent
                    :message="$t('The working time pattern “{0}” is currently selected. This means that working times cannot be edited. Remove the working time pattern to enter your own times. Click here to remove.', [selectedWorkTimePattern.name])"
                    type="info"
                    class="mb-4 cursor-pointer"
                    @click="workTimeForm.work_time_pattern_id = null"
                />
            </div>


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