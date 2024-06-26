<template>
    <div class="grid grid-cols-1 md:grid-cols-3 px-4 py-2 bg-gray-100 rounded-lg group">
        <div class="flex items-start gap-x-3">
            <div class="text-sm">
                <div>
                    {{ event.eventInfo.name ?? event.eventInfo.project_name }}
                </div>
                <div class="xxsLight">
                    {{ $t('Project') }}: {{ event.eventInfo.project_name ?? $t('No Project') }}
                </div>
            </div>
            <span class="rounded-full text-[9px] bg-gray-200 px-2">
                {{ event.quantity }}
                <span v-if="item.count < event.quantity" class="text-red-500">
                    / {{ event.quantity - item.count }}
                </span>
            </span>
        </div>
        <div class="flex items-center">
            <div class="flex items-center gap-x-2">
                <img :src="event.user.profile_photo_url" class="h-8 w-8 object-cover rounded-full">
                <div class="xsLight">
                    {{ event.user.name }}
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-x-2 ">
            <IconEditCircle class="h-5 w-5 text-blue-500 hidden group-hover:block cursor-pointer" @click="showAssignedItemToEventModal = true" />
            <IconCircleX @click="removeEvent" class="h-5 w-5 text-red-500 hidden group-hover:block cursor-pointer" />
        </div>
    </div>

    <ConfirmDeleteModal
        v-if="showDeleteModal"
        @delete="deleteEvent"
        @closed="showDeleteModal = false"
        :title="'Buchung entfernen'"
        :description="'Möchtest du das Event wirklich aus der Buchung von ' + item.name + ' löschen?'"
    />
    <AssignedItemToEventModal
        :day="day"
        :event="event.eventInfo"
        :item="item"
        :booking="event"
        :quantity="event.quantity.toString()"
        v-if="showAssignedItemToEventModal"
        @closed="showAssignedItemToEventModal = false" />
</template>

<script setup>

import {IconCircleX, IconEditCircle} from "@tabler/icons-vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import AssignedItemToEventModal from "@/Pages/Inventory/Components/AssignedItemToEventModal.vue";

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    event: {
        type: Object,
        required: true
    },
    day: {
        type: String,
        required: true
    }
})

const showDeleteModal = ref(false);
const showAssignedItemToEventModal = ref(false);

const removeEvent = () => {
    showDeleteModal.value = true;
}

const deleteEvent = () => {
    router.delete(route('inventory.events.destroy', {craftInventoryItemEvent: props.event.booking_id}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
}

</script>

<style scoped>

</style>