<template>
    <div class="flex items-center gap-4 mb-3 text-gray-400 text-sm print:xsDark mt-5">
        <div v-if="multiEdit" class="w-8 h-4 block px-4">

        </div>
        <div class="font-bold" v-if="usePage().props.event_status_module" :style="getColumnSize(1)">
            {{ $t('Event Status') }}
        </div>
        <div class="font-bold" :style="getColumnSize(2)">
            {{ $t('Event type') }}
        </div>
        <div class="font-bold" :style="getColumnSize(3)">
            {{ $t('Event name') }}
        </div>
        <div class="font-bold" :style="getColumnSize(4)">
            {{ $t('Room') }}
        </div>
        <div class="font-bold print:col-span-2" :style="getColumnSize(5)">
            {{ $t('Day') }}
        </div>
        <div class="font-bold col-span-1" :style="getColumnSize(6)">
            <SwitchGroup as="div" class="flex items-center" v-if="isInModal">
                <Switch v-model="localValue"
                        @change="$emit('update:modelValue', localValue)"
                        :class="[localValue ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex h-4 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-0 focus:ring-indigo-600 focus:ring-offset-2']">
                    <span aria-hidden="true"
                          :class="[localValue ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <SwitchLabel as="span" class="ml-3">
                    <div>
                        {{ $t('Period') }}
                    </div>
                </SwitchLabel>
            </SwitchGroup>
            <div v-else class="flex items-center gap-x-4">
                {{ $t('Period') }}
                <ToolTipDefault :tooltip-text="$t('If the start and end times are identical or the end time is before the start time, the end date is set to the next day; if no time is specified, the event is categorised as a full day.')" top class="print:hidden"/>
            </div>
        </div>
        <div class="font-bold print:hidden">
        </div>
    </div>
</template>

<script setup>
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ref, watch} from "vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import {usePage} from "@inertiajs/vue3";

// Emit Event
const emit = defineEmits(['update:modelValue']);

// Props für v-model Unterstützung
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true
    },
    isInModal: {
        type: Boolean,
        required: false,
        default: false
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    }
});

const getColumnSize = (column) => {
    return {
        minWidth: usePage().props.auth.user.bulk_column_size[column] + 'px',
        width: usePage().props.auth.user.bulk_column_size[column] + 'px',
        maxWidth: usePage().props.auth.user.bulk_column_size[column] + 'px'
    }
}

// Lokaler Wert, um den Zustand des Switches zu halten
const localValue = ref(props.modelValue);

// Watcher um Änderungen an modelValue zu berücksichtigen
watch(() => props.modelValue, (newValue) => {
    localValue.value = newValue;
});

// Wenn localValue geändert wird, das update:modelValue Event emittieren
watch(localValue, (newValue) => {
    emit('update:modelValue', newValue);
});
</script>

<style scoped>
/* Dein vorhandenes CSS */
</style>
