<template>

    <div class="w-full hidden group-hover:flex border-2 border-dashed rounded-lg items-center justify-center btn-group-border-hover cursor-pointer" :class="eventsLength > 0 ? 'h-8' : 'h-full'" @click="showAddShiftModal = true">
        <component :is="IconCircleDashedPlus" class="h-6 w-6 text-gray-300 btn-group-hover" stroke-width="2"/>
    </div>

    <AddShiftModal v-if="showAddShiftModal"
                   :crafts="$page.props.crafts"
                   :event="null"
                   :currentUserCrafts="$page.props.currentUserCrafts"
                   :buffer="null"
                   :shift-qualifications="$page.props.shiftQualifications"
                   @closed="closeAddShiftModal"
                   :shift-time-presets="$page.props.shiftTimePresets"
                   :room="room"
                   :day="day.without_format"
    />
</template>

<script setup>

import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import {ref} from "vue";
import {IconCircleDashedPlus} from "@tabler/icons-vue";

const props = defineProps({
    eventsLength: {
        type: Number,
        required: true
    },
    room: {
        type: Number,
        required: true
    },
    day: {
        type: Object,
        required: true
    }
})

const showAddShiftModal = ref(false)

const closeAddShiftModal = () => {
    showAddShiftModal.value = false
}

</script>

<style scoped>

</style>