<template>
    <ShiftHeader>
        <div class="ml-5">
            <ShiftPresetTabs />
        </div>

        <div class="ml-5 mt-8">
            <div class="flex items-center justify-between border-b border-dashed pb-3">
                <TinyPageHeadline
                    :title="$t('Timeline Presets')"
                    :description="$t('Create and manage timeline presets for your shifts.')"
                />
                <div>
                    <AddButtonSmall :text="$t('Create new timeline preset')" @click="showAddEditTimelinePresetModal = true" />
                </div>
            </div>
            <div class="my-5">
                <div class="grid grid-cols-1 md:grid-cols-11 gap-4">
                    <SingleTimelinePreset v-for="timelinePreset in timelinePresets" :timeline-preset="timelinePreset" />
                </div>
            </div>
        </div>



        <AddEditTimelinePresetModal v-if="showAddEditTimelinePresetModal" @close="showAddEditTimelinePresetModal = false" />
    </ShiftHeader>
</template>

<script setup>

import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import ShiftPresetTabs from "@/Pages/Shifts/Components/ShiftPresetTabs.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import {computed, ref} from "vue";
import AddEditTimelinePresetModal from "@/Pages/TimelinePreset/Components/AddEditTimelinePresetModal.vue";
import SingleTimelinePreset from "@/Pages/TimelinePreset/Components/SingleTimelinePreset.vue";

const props = defineProps({
    timelinePresets: {
        type: Object,
        required: true
    }
})

const showAddEditTimelinePresetModal = ref(false);

</script>

<style scoped>

</style>