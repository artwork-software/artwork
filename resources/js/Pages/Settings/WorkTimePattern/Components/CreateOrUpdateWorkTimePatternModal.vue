<template>
    <ArtworkBaseModal
        :title="workTimePattern.id ? 'Edit Work Time Pattern' : 'Create Work Time Pattern'"
        :description="workTimePattern.id ? 'Edit the work time pattern details.' : 'Create a new work time pattern.'"
        @close="$emit('close')">


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
                    <BaseInput
                        v-model="workPatternForm.monday"
                        label="Monday"
                        type="time"
                        id="monday" />
                    <p v-if="workPatternForm.errors.monday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.tuesday"
                        label="Tuesday"
                        type="time"
                        id="tuesday" />
                    <p v-if="workPatternForm.errors.tuesday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.wednesday"
                        label="Wednesday"
                        type="time"
                        id="wednesday" />
                    <p v-if="workPatternForm.errors.wednesday" class="text-red-500 mt-0.5 text-xs"></p>

                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.thursday"
                        label="Thursday"
                        type="time"
                        id="thursday" />
                    <p v-if="workPatternForm.errors.thursday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.friday"
                        label="Friday"
                        type="time"
                        id="friday" />
                    <p v-if="workPatternForm.errors.friday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.saturday"
                        label="Saturday"
                        type="time"
                        id="saturday" />
                    <p v-if="workPatternForm.errors.saturday" class="text-red-500 mt-0.5 text-xs"></p>
                </div>

                <div>
                    <BaseInput
                        v-model="workPatternForm.sunday"
                        label="Sunday"
                        type="time"
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