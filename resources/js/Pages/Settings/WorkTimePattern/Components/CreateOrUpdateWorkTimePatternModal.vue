<template>
    <ArtworkBaseModal
        :title="workTimePattern.id ? 'Edit Work Time Pattern' : 'Create Work Time Pattern'"
        :description="workTimePattern.id ? 'Edit the work time pattern details.' : 'Create a new work time pattern.'"
        @close="$emit('close')">


        <div v-if="workTimePattern.id">
            <BaseAlertComponent message="You are currently editing a working time pattern. If this pattern is assigned to users, the users' working time will also be edited." use-translation type="warning" />
        </div>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                 <div>
                     <BaseInput
                        v-model="workPatternForm.name"
                        label="Name"
                        required
                        id="name" />
                     <p v-if="workPatternForm.errors.name" class="text-red-500 mt-0.5 text-xs"></p>
                 </div>

                <div>
                    <BaseTextarea
                        v-model="workPatternForm.description"
                        label="Description"
                        id="description" />
                    <p v-if="workPatternForm.errors.description" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ $t('Daily Target Hours') }}</h3>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.monday"
                        label="Monday"
                        type="time"
                        :step="1"
                        id="monday" />
                    <p v-if="workPatternForm.errors.monday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.tuesday"
                        label="Tuesday"
                        type="time"
                        :step="1"
                        id="tuesday" />
                    <p v-if="workPatternForm.errors.tuesday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.wednesday"
                        label="Wednesday"
                        type="time"
                        :step="1"
                        id="wednesday" />
                    <p v-if="workPatternForm.errors.wednesday" class="text-red-500 mt-0.5 text-xs"></p>

                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.thursday"
                        label="Thursday"
                        type="time"
                        :step="1"
                        id="thursday" />
                    <p v-if="workPatternForm.errors.thursday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.friday"
                        label="Friday"
                        type="time"
                        :step="1"
                        id="friday" />
                    <p v-if="workPatternForm.errors.friday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.saturday"
                        label="Saturday"
                        type="time"
                        :step="1"
                        id="saturday" />
                    <p v-if="workPatternForm.errors.saturday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.sunday"
                        label="Sunday"
                        type="time"
                        :step="1"
                        id="sunday" />
                    <p v-if="workPatternForm.errors.sunday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>
            </div>

            <div class="flex items-center justify-between mt-10">
                <ArtworkBaseModalButton
                    type="button"
                    @click="$emit('close')"
                    variant="danger">
                    {{ $t('Cancel') }}
                </ArtworkBaseModalButton>


                <ArtworkBaseModalButton
                    type="submit"
                    variant="primary">
                    {{ $t('Save') }}
                </ArtworkBaseModalButton>
            </div>
        </form>


    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

const props = defineProps({
    workTimePattern: {
        type: Object,
        default: () => ({
            id: null,
            name: '',
            description: '',
            monday: '00:00',
            tuesday: '00:00',
            wednesday: '00:00',
            thursday: '00:00',
            friday: '00:00',
            saturday: '00:00',
            sunday: '00:00',
        })
    },
})

const emits = defineEmits(['close']);
const workPatternForm = useForm({
    id: props.workTimePattern?.id,
    name: props.workTimePattern.name,
    description: props.workTimePattern.description,
    monday: props.workTimePattern.monday,
    tuesday: props.workTimePattern.tuesday,
    wednesday: props.workTimePattern.wednesday,
    thursday: props.workTimePattern.thursday,
    friday: props.workTimePattern.friday,
    saturday: props.workTimePattern.saturday,
    sunday: props.workTimePattern.sunday,
});
const submit = () => {

    if (props.workTimePattern.id) {
        workPatternForm.patch(route('shift.work-time-pattern.update', props.workTimePattern.id), {
            onSuccess: () => {
                emits('close');
                workPatternForm.reset();
            },
            onError: (errors) => {
                console.error(errors);
            }
        });
    } else {
        workPatternForm.post(route('shift.work-time-pattern.store'), {
            onSuccess: () => {
                emits('close');
                workPatternForm.reset();
            },
            onError: (errors) => {
                console.error(errors);
            }
        });
    }



};

</script>

<style scoped>

</style>
