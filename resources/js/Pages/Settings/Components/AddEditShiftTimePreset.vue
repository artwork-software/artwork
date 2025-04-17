<script setup>

import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import Input from "@/Jetstream/Input.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const emit = defineEmits(['closed'])

const props = defineProps({
    timePreset: {
        type: Object,
        required: false
    },
})

const newTimePreset = useForm({
    name: props.timePreset ? props.timePreset.name : '',
    start_time: props.timePreset ? props.timePreset.start_time : '',
    end_time: props.timePreset ? props.timePreset.end_time : '',
    id: props.timePreset ? props.timePreset.id : null,
    break_time: props.timePreset ? props.timePreset.break_time : 0,
})

const saveTimePreset = () => {
    if (newTimePreset.id) {
        newTimePreset.patch(route('shift-time-preset.update', newTimePreset.id), {
            preserveScroll: true,
            onFinish: () => emit('closed')
        })
    } else {
        newTimePreset.post(route('shift-time-preset.store'), {
            preserveScroll: true,
            onFinish: () => emit('closed')
        })
    }
}
</script>

<template>
    <BaseModal v-if="true" @closed="$emit('closed')">
        <form @submit.prevent="saveTimePreset">

            <ModalHeader
                :title="newTimePreset.id ? $t('Edit Time Preset') : $t('Create Time Preset')"
            />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-2">
                    <BaseInput
                           :label="$t('Name')"
                           v-model="newTimePreset.name"
                           id="name"
                           required
                    />
                </div>
                <div>
                    <BaseInput type="time"
                           :label="$t('Start-Time')"
                           v-model="newTimePreset.start_time"
                           id="start_time"
                           required
                    />
                </div>
                <div>
                    <BaseInput type="time"
                        :label="$t('End-Time')"
                        v-model="newTimePreset.end_time"
                        id="end_time"
                        required
                    />
                </div>
                <div class="col-span-2">
                    <BaseInput type="number"
                        :label="$t('Length of break in minutes*')"
                        v-model="newTimePreset.break_time"
                        required
                        :min="0"
                        :max="1000"
                        id="break_time"
                    />
                </div>
            </div>
            <div class="flex items-center justify-between mt-10">
                <div>
                    <button type="button" @click="$emit('closed')" class="text-secondary underline cursor-pointer text-xs">{{ $t('No, not really') }}</button>
                </div>
                <div>
                    <AddButtonSmall type="submit" :text="props.timePreset ? $t('Edit') : $t('Create')" />
                </div>
            </div>
        </form>
    </BaseModal>
</template>

<style scoped>

</style>
