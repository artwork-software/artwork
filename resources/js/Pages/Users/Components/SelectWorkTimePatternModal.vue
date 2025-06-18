<template>
    <ArtworkBaseModal
        title="Select Work Time Pattern"
        description="Choose a work time pattern for the user."
        @close="$emit('close')">

        <div class="p-4 space-y-4">
            <BaseInput
                v-model="searchInput"
                type="text"
                label="Search patterns..."
                id="searchInput"/>


            <div class="mt-5">
                <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="workTime in filteredPatterns" :key="workTime.id" @click="$emit('selectPattern', workTime)" class="flex justify-between gap-x-6 px-3 py-5 cursor-pointer hover:bg-gray-50">
                        <div class="flex min-w-0 gap-x-4">
                            <div class="min-w-0 flex-auto">
                                <p class="text-sm/6 font-semibold text-gray-900">
                                    {{ workTime.name }}
                                </p>
                                <p class="mt-1 flex text-xs/5 text-gray-500">
                                    {{ workTime.description }}
                                </p>
                                <p class="mt-1 flex text-xs/5 text-gray-500 space-x-2 divide-x divide-gray-200">
                                    <span><b>{{ $t('Monday')}}</b>: {{ workTime.monday }} Std.</span>
                                    <span class="pl-2"><b>{{ $t('Tuesday')}}</b>: {{ workTime.tuesday }} Std.</span>
                                    <span class="pl-2"><b>{{ $t('Wednesday')}}</b>: {{ workTime.wednesday }} Std.</span>
                                    <span class="pl-2"><b>{{ $t('Thursday')}}</b>: {{ workTime.thursday }} Std.</span>
                                </p>
                                <p class="mt-1 flex text-xs/5 text-gray-500 space-x-2 divide-x divide-gray-200">
                                    <span><b>{{ $t('Friday')}}</b>: {{ workTime.friday }} Std.</span>
                                    <span class="pl-2"><b>{{ $t('Saturday')}}</b>: {{ workTime.saturday }} Std.</span>
                                    <span class="pl-2"><b>{{ $t('Sunday')}}</b>: {{ workTime.sunday }} Std.</span>
                                    <span class="pl-2"><b>{{ $t('Total hours')}}</b>: {{ workTime.full_work_time_in_hours }} Std.</span>
                                </p>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {computed, ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    workTimePatterns: {
        type: Object,
        required: true
    },
})

const emit = defineEmits(['close', 'selectPattern']);

const searchInput = ref('');

const filteredPatterns = computed(() => {
    return props.workTimePatterns.filter(pattern =>
        pattern.name.toLowerCase().includes(searchInput.value.toLowerCase())
    );
});

</script>

<style scoped>

</style>