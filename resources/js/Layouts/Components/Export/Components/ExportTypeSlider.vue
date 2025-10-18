<template>

    <SwitchDualLabel
        v-model="timespanExport"
        :left-label="$t('Export by project')"
        :right-label="$t('Export by period')"
        size="md"
        icon="IconGeometry"
        :disabled="false"
        @update:model-value="(desiresTimespanExport) => emits.call(this, 'on-update', desiresTimespanExport)"
    />
</template>

<script setup>
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ref} from "vue";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";

const emits = defineEmits(['on-update']),
    timespanExport = ref(false);
</script>
