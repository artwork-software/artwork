<template>
    <SwitchIconTooltip
        v-model="newGridLayout"
        :tooltip-text="gridLayout ? $t('Switch to list view') : $t('Switch to grid view')"
        size="md"
        :on-band="onBand"
        @update:modelValue="$emit('update:gridLayout', newGridLayout)"
        :icon="!gridLayout ? IconLayoutList : IconLayoutGrid"
    />
</template>

<script setup>

import {Switch} from "@headlessui/vue";
import {ref} from "vue";
import {IconLayoutGrid, IconLayoutList} from "@tabler/icons-vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";

const props = defineProps({
    gridLayout: {
        type: Boolean,
        required: true
    },
    /** Sitzt der Switch im dunklen Toolbar-Band? Wird an SwitchIconTooltip durchgereicht. */
    onBand: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(["update:gridLayout"]);

const newGridLayout = ref(props.gridLayout);
</script>

<style scoped>

</style>
