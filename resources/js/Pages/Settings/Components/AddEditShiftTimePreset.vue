<script setup>

import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import Input from "@/Jetstream/Input.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";

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

            <h3 class="headline2">
                <span v-if="newTimePreset.id">{{ $t('Edit Time Preset')}}</span>
                <span v-else>{{ $t('Create Time Preset')}}</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 my-10">
                <div class="col-span-2">
                    <label for="name" class="text-base">{{ $t('Name') }}</label>
                    <input type="text"
                           :placeholder="$t('Name')"
                           v-model="newTimePreset.name"
                           class="w-full h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"
                           required
                    />
                </div>
                <div>
                    <label for="name" class="text-base">{{ $t('Start-Time')}}</label>
                    <input type="time"
                           :placeholder="$t('Start-Time')"
                           v-model="newTimePreset.start_time"
                           class="w-full h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"
                           required
                    />
                </div>
                <div>
                    <label for="name" class="text-base">{{ $t('End-Time') }}</label>
                    <input type="time"
                           :placeholder="$t('Start-Time')"
                           v-model="newTimePreset.end_time"
                           class="w-full h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"
                           required
                    />
                </div>
                <div class="col-span-2">
                    <label for="name" class="text-base">{{ $t('Break time') }}</label>
                    <input type="number"
                           :placeholder="$t('Length of break in minutes*')"
                           v-model="newTimePreset.break_time"
                           class="w-full h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"
                           required
                           min="0"
                           max="1000"
                    />
                </div>
            </div>
            <div class="flex items-center justify-between">
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
