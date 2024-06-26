<template>
    <BaseModal v-if="true" @closed="$emit('closed')" >
        <h1 class="headline1">
            {{ $t('All bookings on {0} from "{1}"', [day.full_day, item.name]) }}
        </h1>
        <p class="mt-2 text-sm xsLight">
            {{ $t('All events on which "{0}" was booked on "{1}" are listed here. There are a total of {2} event(s).', [item.name, day.full_day, calculateTotalEventCountByDay(day)])}}
        </p>

       <div class="my-5">
           <div v-if="calculateTotalEventCountByDay(day) > 0" v-for="event in item.events" class="mb-1">
               <div v-if="event.date === day.full_day">
                   <SingleItemInItemDetailModal :day="day.full_day" :item="item" :event="event" />
               </div>
           </div>
           <div v-else>
               <AlertComponent type="info" show-icon icon-size="h-4 w-4" :text="$t('There are no events on this day.')" />
           </div>
       </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import SingleItemInItemDetailModal from "@/Pages/Inventory/Components/SingleItemInItemDetailModal.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
const emits = defineEmits(['closed'])
const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    day: {
        type: Object,
        required: true
    }
});

const calculateTotalEventCountByDay = (day) => {
    return props.item.events.filter(event => event.date === day.full_day).length;
}

</script>

<style scoped>

</style>