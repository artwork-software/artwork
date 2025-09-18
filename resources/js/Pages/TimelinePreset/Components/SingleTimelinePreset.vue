<template>
    <div>
        <div class="rounded-lg w-full bg-gray-500 px-4 py-2 mb-2 flex items-center justify-between shadow-md">
            <div class="text-sm text-white truncate">
                {{ timelinePreset.name }}
            </div>
            <div class="flex items-center gap-x-2">
                <component :is="IconWand" class="h-4 w-4 rounded-full text-white hover:text-gray-300 transition-colors duration-300 ease-in-out cursor-pointer hidden" stroke-width="1.5"/>
                <BaseMenu white-icon dots-size="h-4 w-4" has-no-offset>
                    <BaseMenuItem title="Edit" :icon="IconEdit" @click="showEditTimelinePresetModal = true" />
                    <BaseMenuItem title="Duplicate" :icon="IconCopy" @click="copyTimelinePreset" />
                    <BaseMenuItem title="Delete" :icon="IconTrash" @click="showConfirmDeleteModal = true" />
                </BaseMenu>
            </div>
        </div>
        <div class="rounded-lg w-full bg-gray-100 px-4 py-2">
            <div class="divide-y divide-dashed h-72 overflow-x-auto">
                <div v-for="time in timelinePreset.times">
                    <SingleTimesInPreset :time="time" />

                </div>
            </div>
        </div>
    </div>

    <AddEditTimelinePresetModal :preset-to-edit="timelinePreset" v-if="showEditTimelinePresetModal" @close="showEditTimelinePresetModal = false" />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        :title="$t('Delete timeline preset')"
        :description="$t('Would you like to delete the timeline preset?')"
        @closed="showConfirmDeleteModal = false"
        @delete="deleteTimelinePreset"
    />
</template>

<script setup>

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import AddEditTimelinePresetModal from "@/Pages/TimelinePreset/Components/AddEditTimelinePresetModal.vue";
import {ref} from "vue";
import SingleTimesInPreset from "@/Pages/TimelinePreset/Components/SingleTimesInPreset.vue";
import {router} from "@inertiajs/vue3";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {IconCopy, IconEdit, IconTrash, IconWand} from "@tabler/icons-vue";

const props = defineProps({
    timelinePreset: {
        type: Object,
        required: true
    }
})

const showEditTimelinePresetModal = ref(false);
const showConfirmDeleteModal = ref(false);
const copyTimelinePreset = () => {
    router.post(route('timeline-preset.duplicate', {shiftPresetTimeline: props.timelinePreset.id}))
}

const deleteTimelinePreset = () => {
    router.delete(route('timeline-preset.destroy', {shiftPresetTimeline: props.timelinePreset.id}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showConfirmDeleteModal.value = false;
        }
    })
}
</script>

<style scoped>

</style>
