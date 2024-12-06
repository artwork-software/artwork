<template>
    <SwitchGroup as="div" class="flex items-center gap-x-2">
        <SwitchLabel as="span" class="text-xs cursor-pointer">
            <span :class="[!timespanExport ? 'font-bold' : 'text-gray-600', 'text-xs']">
                {{ $t('Export by project') }}
            </span>
        </SwitchLabel>
        <Switch v-model="timespanExport"
                @update:model-value="(desiresTimespanExport) => emits.call(this, 'on-update', desiresTimespanExport)"
                :class="[timespanExport ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
            <span aria-hidden="true" :class="[timespanExport ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
        </Switch>
        <SwitchLabel as="span" class="text-xs cursor-pointer">
            <span :class="[timespanExport ? 'font-bold' : 'text-gray-600', 'text-xs']">
                {{ $t('Export by period') }}
            </span>
        </SwitchLabel>
    </SwitchGroup>
</template>

<script setup>
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ref} from "vue";

const emits = defineEmits(['on-update']),
    timespanExport = ref(false);
</script>
