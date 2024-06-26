<template>
<BaseModal v-if="true" @closed="$emit('closed')">
    <h1 class="headline1" v-if="!booking">
        {{ item.name }} zu {{ event.alwaysEventName ?? event.name }} hinzufügen
    </h1>
    <h1 class="headline1" v-else>
        {{ item.name }} in {{ event.alwaysEventName ?? event.name }} bearbeiten
    </h1>


    <form @submit.prevent="submit" class="py-5">
        <NumberInputComponent required v-model="dropItemForm.quantity" label="Anzahl" id="quality" />


        <div class="flex items-end justify-between">
            <div class="xsLight cursor-pointer"
                 @click="$emit('closed')">
                {{ $t('No, not really') }}
            </div>
            <FormButton class="mt-5" type="submit" text="Buchen" />
        </div>
    </form>

</BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

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
    },
    booking: {
        type: Object,
        required: false
    },
    quantity: {
        type: String,
        required: false
    }
})
const emits = defineEmits(['closed'])
const dropItemForm = useForm({
    quantity: props.quantity || '',
    date: props.day
})

const submit = () => {
    if(props.booking) {
        dropItemForm.patch(route('inventory.updateEvent', {
            craftInventoryItemEvent: props.booking.booking_id
        }), {
            preserveScroll: true,
            onSuccess: () => {
                dropItemForm.reset()
                emits('closed')
            }
        })
    } else {
        dropItemForm.post(route('inventory.dropItemToEvent', {
            event: props.event.id,
            item: props.item.id
        }), {
            preserveScroll: true,
            onSuccess: () => {
                dropItemForm.reset()
                emits('closed')
            }
        })
    }
}
</script>

<style scoped>

</style>