<template>
    <div class="flex items-center px-2 py-1.5 gap-x-1 justify-between group cursor-default">
        <div class="text-xs">
            <div v-if="time.start && time.end">{{ time.start }} - {{ time.end }}</div>
            <div v-if="time.start && !time.end">Ab {{ time.start }}</div>
            <div v-if="!time.start && time.end">Bis {{ time.end }}</div>
            {{ time.description }}
        </div>
        <component :is="IconTrash" @click="showConfirmDeleteModal = true" stroke-width="1.5" class="invisible min-h-5 min-w-5 h-5 w-5 hover:text-red-600 group-hover:visible transition-colors duration-300 ease-in-out cursor-pointer"/>
    </div>

    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        :title="$t('Delete time')"
        :description="$t('Would you like to delete the time?')"
        @closed="showConfirmDeleteModal = false"
        @delete="deleteTime"
    />
</template>

<script setup>

import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import {IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    time: {
        type: Object,
        required: true
    }
})

const showConfirmDeleteModal = ref(false);


const deleteTime = () => {
    router.delete(route('timeline-preset.time.destroy', {presetTimelineTime: props.time.id}), {
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
