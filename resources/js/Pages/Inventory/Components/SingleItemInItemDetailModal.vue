<template>
    <div class="grid grid-cols-1 md:grid-cols-2 rounded-lg group">
        <div class="flex items-start justify-between gap-x-3 rounded-l-lg px-3 py-2" :style="{
            backgroundColor: backgroundColorWithOpacity(event.eventInfo?.event_type?.hex_code),
            color: textColorWithDarken(event.eventInfo?.event_type?.hex_code)
            }">
            <div class="text-sm">
                <div>
                    {{ event.eventInfo.name ?? event.eventInfo.project_name }}
                </div>
                <div class="text-text-subtle text-xs">
                    {{ $t('Project') }}: {{ event.eventInfo.project_name ?? $t('No Project') }}
                </div>
            </div>
            <span class="stock-badge bg-surface-sunken">
                {{ event.quantity }}
                <span v-if="event.overbooked > 0" class="text-danger">
                    / {{ event.overbooked }}
                </span>
            </span>
        </div>
        <div class="flex justify-between bg-surface-sunken pl-2 rounded-r-lg">
            <div class="flex items-center gap-x-2">
                <img :src="event.user.profile_photo_url" class="h-8 w-8 object-cover rounded-full">
                <div class="xsLight">
                    {{ event.user.name }}
                </div>
            </div>
            <div class="flex items-center justify-end gap-x-2 bg-surface-sunken rounded-r-lg px-3">
                <IconEditCircle class="h-5 w-5 text-accent-600 hidden group-hover:block cursor-pointer" @click="showAssignedItemToEventModal = true" />
                <IconCircleX @click="removeEvent" class="h-5 w-5 text-danger hidden group-hover:block cursor-pointer" />
            </div>
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

const backgroundColorWithOpacity = (color, percent = 15) => {
    if (!color) return `rgb(255, 255, 255, ${percent}%)`;
    return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
}
const textColorWithDarken = (color, percent = 75) => {
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
}

</script>

<style scoped>

</style>