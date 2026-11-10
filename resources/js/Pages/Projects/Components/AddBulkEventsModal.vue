<template>
    <ArtworkBaseModal
        title="Events"
        :description="project?.name ?? ''"
        modal-size="sm:w-[95vw] sm:max-w-[96rem]"
        @close="$emit('closed')"
    >
        <BulkBody
            :rooms="rooms"
            :event-types="event_types"
            :project="project"
            :can-edit-component="true"
            is-in-modal
            :event-statuses="eventStatuses"
            @closed="$emit('closed')"
        />
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BulkBody from "@/Pages/Projects/Components/BulkComponents/BulkBody.vue";

defineOptions({name: 'AddBulkEventsModal'});

defineProps({
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

defineEmits(['closed']);
</script>
