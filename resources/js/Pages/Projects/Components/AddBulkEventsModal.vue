<template>
    <BaseModal v-if="true" @closed="$emit('closed')" modal-size="w-[95vw] max-w-screen-2xl">
        <div class="modal-header">
            <div class="flex items-center gap-x-4">
                <div class="model-title">
                    {{ $t('Events') }}
                </div>
                <div class="model-title !text-gray-400 text-md">
                    ({{ project.name}})
                </div>
            </div>
        </div>
        <div>
            <BulkBody
                :rooms="rooms"
                :event-types="event_types"
                :project="project"
                :can-edit-component="true"
                is-in-modal
                @closed="$emit('closed')"
                :event-statuses="eventStatuses"
            />
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import BulkBody from "@/Pages/Projects/Components/BulkComponents/BulkBody.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    event_types: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: true
    }
});
</script>
