<template>
    <div class="flex -space-x-1 overflow-hidden print:space-x-1">
        <span v-for="eventType in project.shift_relevant_event_types"
            class="w-8 h-8 rounded-full ring-2 ring-white flex items-center justify-center text-xs text-white uppercase print:w-fit"
            :style="{ backgroundColor: eventType.hex_code, borderColor: eventType.hex_code }">
                <span class="block print:hidden">{{ eventType.abbreviation }}</span>
                <span class="hidden print:block mx-3">{{ eventType.name }}</span>
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