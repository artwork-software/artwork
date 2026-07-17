<template>
    <ArtworkBaseModal
        title="Select Work Time Pattern"
        description="Choose a work time pattern for the user."
        modal-size="sm:max-w-3xl"
        @close="$emit('close')">

        <div class="space-y-4">
            <BaseInput
                v-model="searchInput"
                type="text"
                label="Search patterns..."
                is-small
                id="searchInput"/>

            <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3">
                <p class="text-xs font-medium text-gray-700 mb-2">
                    {{ $t('Validity period') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <BaseInput
                        v-model="valid_from"
                        :label="$t('Valid from')"
                        without-translation
                        type="date"
                        id="valid_from" />
                    <BaseInput
                        v-model="valid_until"
                        :label="$t('Valid until')"
                        without-translation
                        type="date"
                        id="valid_until" />
                </div>
                <p class="text-[11px] text-gray-500 mt-2">
                    {{ $t('If no dates are set, the hours apply from today indefinitely as the daily target for all future days.') }}
                </p>
            </div>

            <p class="text-[11px] text-gray-500">
                {{ $t('The daily values are working hours (daily target), not start times.') }}
            </p>

            <div v-if="filteredPatterns.length === 0" class="py-8 text-center text-sm text-gray-500">
                {{ $t('No work time patterns found.') }}
            </div>

            <ul v-else role="list" class="space-y-3 max-h-[45vh] overflow-y-auto pr-1">
                <li v-for="workTime in filteredPatterns"
                    :key="workTime.id"
                    @click="emitWorkTimePattern(workTime)"
                    class="group cursor-pointer rounded-lg border p-4 transition duration-150 ease-in-out"
                    :class="workTime.id === selectedPatternId
                        ? 'border-blue-300 bg-blue-50/50'
                        : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'">

                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 font-lexend flex items-center gap-2">
                                {{ workTime.name }}
                                <span v-if="workTime.id === selectedPatternId"
                                      class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700">
                                    <component :is="IconCheck" class="size-3" stroke-width="2" />
                                    {{ $t('Currently selected') }}
                                </span>
                            </p>
                            <p v-if="workTime.description" class="text-xs text-gray-500 mt-0.5">
                                {{ workTime.description }}
                            </p>
                        </div>
                        <span class="shrink-0 text-xs font-medium transition"
                              :class="workTime.id === selectedPatternId
                                ? 'text-blue-400'
                                : 'text-blue-600 opacity-0 group-hover:opacity-100'">
                            {{ workTime.id === selectedPatternId ? '' : $t('Select') }} &rarr;
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-4 sm:grid-cols-8 gap-1.5">
                        <div v-for="day in weekDays" :key="day.key"
                             class="rounded-md border border-gray-100 bg-gray-50/60 group-hover:bg-white px-1.5 py-1.5 text-center">
                            <p class="text-[10px] text-gray-500">{{ $t(day.short) }}</p>
                            <p class="text-xs font-semibold text-gray-900">{{ workTime[day.key] ?? '-' }}</p>
                        </div>
                        <div class="rounded-md bg-blue-50 border border-blue-100 px-1.5 py-1.5 text-center">
                            <p class="text-[10px] text-blue-600">&Sigma;</p>
                            <p class="text-xs font-semibold text-blue-800">{{ workTime.full_work_time_in_hours }}</p>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {computed, ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCheck} from "@tabler/icons-vue";

const props = defineProps({
    workTimePatterns: {
        type: Object,
        required: true
    },
    selectedPatternId: {
        type: [Number, null],
        required: false,
        default: null
    },
})

const emit = defineEmits(['close', 'selectPattern']);

const searchInput = ref('');
const valid_from = ref(null);
const valid_until = ref(null);

const weekDays = [
    { key: 'monday', short: 'Mon' },
    { key: 'tuesday', short: 'Tue' },
    { key: 'wednesday', short: 'Wed' },
    { key: 'thursday', short: 'Thu' },
    { key: 'friday', short: 'Fri' },
    { key: 'saturday', short: 'Sat' },
    { key: 'sunday', short: 'Sun' },
];

const filteredPatterns = computed(() => {
    const search = searchInput.value.toLowerCase();
    return props.workTimePatterns.filter(pattern =>
        pattern.name.toLowerCase().includes(search) ||
        (pattern.description ?? '').toLowerCase().includes(search)
    );
});

const emitWorkTimePattern = (workTime) => {
    emit('selectPattern', {
        workTimePattern: workTime,
        valid_from: valid_from.value,
        valid_until: valid_until.value
    });
    emit('close');
}

</script>

<style scoped>

</style>
