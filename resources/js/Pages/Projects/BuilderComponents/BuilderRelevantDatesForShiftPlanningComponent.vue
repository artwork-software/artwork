<template>
    <div class="flex -space-x-1 overflow-hidden">
        <span
            v-for="eventType in project.shift_relevant_event_types"
            class="w-8 h-8 rounded-full ring-2 ring-white flex items-center justify-center text-xs text-white uppercase"
            :style="{ backgroundColor: eventType.hex_code, borderColor: eventType.hex_code }"
            >
                {{ eventType.abbreviation }}
        </span>
    </div>
</template>

<script setup>

import TagComponent from "@/Layouts/Components/TagComponent.vue";

import {useColorHelper} from "@/Composeables/UseColorHelper.js";

const {backgroundColorWithOpacityOld} = useColorHelper()

const props = defineProps({
    project: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>