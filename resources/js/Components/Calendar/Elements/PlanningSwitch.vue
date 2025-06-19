<template>
    <Switch v-model="enablePlanning" @update:model-value="$emit('update:planning', enablePlanning)" :class="[enablePlanning ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none z-50']">
        <span :class="[enablePlanning ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
            <span :class="[enablePlanning ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-50', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                <ToolTipComponent
                    direction="bottom"
                    :tooltip-text="$t('When active, newly created appointments are automatically planning appointments')"
                    icon="IconCalendarCog"
                    icon-size="h-4 w-4"
                />
            </span>
            <span :class="[enablePlanning ? 'opacity-100 duration-200 ease-in z-50' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                <ToolTipComponent
                    direction="bottom"
                    :tooltip-text="$t('When active, newly created appointments are automatically planning appointments')"
                    icon="IconCalendarCog"
                    icon-size="h-4 w-4"
                />
            </span>
        </span>
    </Switch>
</template>

<script setup>
import {IconCalendarCog} from "@tabler/icons-vue";
import {Switch} from "@headlessui/vue";
import {ref, watch} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

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
