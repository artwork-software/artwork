<template>
    <SwitchIconTooltip
        v-model="newGridLayout"
        :tooltip-text="gridLayout ? $t('List') : $t('Grid')"
        size="md"
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
    }
})

const emit = defineEmits(["update:gridLayout"]);

const newGridLayout = ref(props.gridLayout);
</script>

<style scoped>

</style>
