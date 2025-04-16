<template>
    <ArtworkBaseModal title="Add new accommodation" description="Fill in the details below to add a new accommodation." @close="$emit('close')">
        <form @submit.prevent="updateOrCreateAccommodation">
            <div class="grid gird-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-full">
                    <BaseInput id="name" v-model="accommodationForm.name" label="Accommodation Name" />
                </div>
                <div class="col-span-full">
                    <BaseInput id="email" type="email" v-model="accommodationForm.email" label="Email" />
                </div>
                <div class="col-span-full">
                    <BaseInput id="phone_number" v-model="accommodationForm.phone_number" label="Phone number" />
                </div>
                <div class="col-span-full">
                    <BaseInput id="street" v-model="accommodationForm.street" label="Street" />
                </div>
                <div class="col-span-1">
                    <BaseInput id="zip_code" v-model="accommodationForm.zip_code" label="Zip code" />
                </div>
                <div class="col-span-1">
                    <BaseInput id="location" v-model="accommodationForm.location" label="Location" />
                </div>
                <div class="col-span-full">
                    <BaseTextarea id="note" v-model="accommodationForm.note" label="Note" />
                </div>
            </div>

            <div v-if="!accommodationForm.id">
                <BaseAlertComponent message="After creating the accommodation, you can add contacts to it." use-translation type="info" class="mt-4" />
            </div>

            <div class="flex items-center justify-between mt-5">
                <ArtworkBaseButton size="md" variant="primary" type="submit">
                    {{ $t('Create') }}
                </ArtworkBaseButton>
                <ArtworkBaseButton size="md" variant="danger" type="button" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </ArtworkBaseButton>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {useForm} from "@inertiajs/vue3";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

const props = defineProps({
    accommodation: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            email: '',
            phone_number: '',
            street: '',
            zip_code: '',
            location: '',
            note: ''
        })
    }
})

const emits = defineEmits(['close'])

const accommodationForm = useForm({
    id: props.accommodation.id,
    name: props.accommodation.name,
    email: props.accommodation.email,
    phone_number: props.accommodation.phone_number,
    street: props.accommodation.street,
    zip_code: props.accommodation.zip_code,
    location: props.accommodation.location,
    note: props.accommodation.note,
})


const updateOrCreateAccommodation = () => {
    if (accommodationForm.id) {
        accommodationForm.patch(route('accommodation.update', accommodationForm.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                accommodationForm.reset();
                emits('close');
            },
            onError: () => {
                console.log('Error updating accommodation');
            }
        });
    } else {
        accommodationForm.post(route('accommodation.store'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                accommodationForm.reset();
                emits('close');
            },
            onError: () => {
                console.log('Error creating accommodation');
            }
        });
    }
}

</script>

<style scoped>

</style>