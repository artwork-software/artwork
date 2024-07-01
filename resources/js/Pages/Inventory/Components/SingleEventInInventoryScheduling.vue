<template>
   <div class="text-sm"
        :id="event.id"
        @dragover="onDragOver"
        @drop="onDrop">
       <div class="py-1.5 px-2 border flex items-center" :style="{
        backgroundColor: backgroundColorWithOpacity(event.event_type.hex_code),
        color: textColorWithDarken(event.event_type.hex_code),
        border: textColorWithDarken(event.event_type.hex_code)
        }"
        :class="isLastEvent ? 'rounded-b-lg' : ''">
           <div v-if="multiEdit">
               <div class="flex items-center mr-2">
                   <input @change="removeCheckedState" :checked="event.checked" id="comments" aria-describedby="comments-description" name="comments" type="checkbox" class="focus:ring-transparent text-green-600 ring-transparent h-4 w-4 border-gray-300" />
               </div>
           </div>
           <div class="flex items-start text-xs gap-x-3 justify-between w-full">
               {{ event.eventName ?? event.title }}
              <div>
                   <span class="text-[9px]" v-if="!event.allDay">
                   {{ event.timesWithoutDates.start }} - {{ event.timesWithoutDates.end }}
               </span>
                  <span v-else class="text-xs">
                   {{ $t('All day') }}
               </span>
              </div>
           </div>
       </div>
   </div>
    <AssignedItemToEventModal :day="day" :event="event" @closed="showAssignedItemToEventModal = false" v-if="showAssignedItemToEventModal" :item="ItemDragElement" />
</template>

<script setup>

import AssignedItemToEventModal from "@/Pages/Inventory/Components/AssignedItemToEventModal.vue";
import {ref, watch} from "vue";

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    isLastEvent: {
        type: Boolean,
        required: false,
        default: false
    },
    day: {
        type: String,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    },
    selectedEventIds: {
        type: Array,
        required: true,
    }
})

// computed add check state to event
// 1. if event is in selectedEventIds, add checked: true
// 2. if event is not in selectedEventIds, add checked: false
props.event.checked = props.selectedEventIds.includes(props.event.id);

const showAssignedItemToEventModal = ref(false);
const ItemDragElement = ref(null);
const emits = defineEmits(['update:selectedEventIds']);

const backgroundColorWithOpacity = (color, percent = 15) => {
    if (!color) return `rgb(255, 255, 255, ${percent}%)`;
    return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
}
const textColorWithDarken = (color, percent = 75) => {
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
}

const onDragOver = (event) => {
    event.preventDefault();
}
const onDrop = (event) =>  {
    event.preventDefault();

    const droppedItem = JSON.parse(event.dataTransfer.getData('application/json'));
    console.log('dropped', droppedItem);
    ItemDragElement.value = droppedItem;
    showAssignedItemToEventModal.value = true;

}

const removeCheckedState = () => {
    props.event.checked = !props.event.checked;
    emits('update:selectedEventIds', props.event.id);
}

// watch for changes in selectedEventIds
watch(() => props.selectedEventIds, () => {
    props.event.checked = props.selectedEventIds.includes(props.event.id);
}, {deep: true})

</script>

<style scoped>

</style>