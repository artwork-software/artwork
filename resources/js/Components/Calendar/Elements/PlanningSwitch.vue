<template>

    <SwitchIconTooltip
        v-model="enablePlanning"
        :tooltip-text="$t('When active, newly created appointments are automatically planning appointments')"
        size="md"
        @change="$emit('update:planning', enablePlanning)"
        :icon="IconCalendarCog"
    />
</template>

<script setup>
import {IconCalendarCog} from "@tabler/icons-vue";
import {Switch} from "@headlessui/vue";
import {ref, watch} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";

const props = defineProps({
    planning: {
        type: Boolean,
        required: true
    }
})

const emit = defineEmits(['update:planning'])
const enablePlanning = ref(props.planning)

// add watch on props.planning
watch(() => props.planning, (value) => {
    enablePlanning.value = value
})
</script>

<style scoped>
</style>
