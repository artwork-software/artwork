<template>
    <AppLayout :title="$t('Accommodation') + ': ' + accommodation.name">
        <div class="mt-5 mx-auto container pb-20">
            <div>
                <PageTitle :title="$t('Accommodation') + ': ' + accommodation.name" description="Bearbeite deine Unterkunft" />

            </div>

            <div>
                <div class="grid gird-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="col-span-full">
                        <BaseInput id="name" v-model="accommodationForm.name" label="Accommodation Name" @focusout="updateAccommodation" />
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="email" type="email" v-model="accommodationForm.email" label="Email" @focusout="updateAccommodation" />
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="phone_number" v-model="accommodationForm.phone_number" label="Phone number" @focusout="updateAccommodation" />
                    </div>
                    <div class="col-span-full">
                        <BaseInput id="street" v-model="accommodationForm.street" label="Street" @focusout="updateAccommodation" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput id="zip_code" v-model="accommodationForm.zip_code" label="Zip code" @focusout="updateAccommodation" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput id="location" v-model="accommodationForm.location" label="Location" @focusout="updateAccommodation" />
                    </div>
                    <div class="col-span-full">
                        <BaseTextarea id="note" v-model="accommodationForm.note" label="Note" @focusout="updateAccommodation" />
                    </div>
                </div>

                <div class="mt-10">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <PageTitle :title="$t('Contacts')" :description="$t('You can view and edit all accommodation contacts here')" />
                            <ArtworkBaseButton size="sm" variant="primary" type="button" @click="showCreateOrUpdateContactModal = true">
                                {{ $t('Add Contact') }}
                            </ArtworkBaseButton>
                        </div>


                        <div v-if="accommodation.contacts.length > 0">
                            <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
                                <li v-for="contact in accommodation.contacts" :key="contact.id" class="overflow-hidden rounded-xl border border-gray-200 shadow-glass">
                                    <ArtworkSingleContact :contact="contact" />
                                </li>
                            </ul>
                        </div>

                        <div v-else>
                            <BaseAlertComponent message="No contacts found for this accommodation." use-translation type="info" class="mt-4" />
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <GeneralCreateOrUpdateContactModal
            model="accommodation"
            :model-id="accommodation.id"
            title="Add accommodation contact"
            description="Add a new contact to the accommodation"
            v-if="showCreateOrUpdateContactModal"
            @close="showCreateOrUpdateContactModal = false"
        />

        <NotificationToast v-model:show="toastVisible" :type="toastTexts.type" :title="toastTexts.title" :description="toastTexts.description"/>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import PageTitle from "@/Artwork/Titles/PageTitle.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {useForm} from "@inertiajs/vue3";
import {defineAsyncComponent, ref} from "vue";
import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ArtworkSingleContact from "@/Artwork/Contact/ArtworkSingleContact.vue";

const props = defineProps({
    accommodation: {
        type: Object,
        required: true,
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
const showCreateOrUpdateContactModal = ref(false)
const toastVisible = ref(false)

const GeneralCreateOrUpdateContactModal = defineAsyncComponent({
    loader: () => import('@/Components/Modals/GeneralCreateOrUpdateContactModal.vue'),
    delay: 200,
    timeout: 3000,
})

const NotificationToast = defineAsyncComponent({
    loader: () => import('@/Artwork/Feedback/NotificationToast.vue'),
    delay: 200,
    timeout: 3000,
})

const toastTexts = {
    title: '',
    description: '',
    type: 'success'
}
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


const updateAccommodation = () => {
    if ( accommodationForm.isDirty ) {
        accommodationForm.patch(route('accommodation.update', props.accommodation.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toastTexts.title = 'Accommodation updated successfully'
                toastTexts.description = 'The changes have been saved successfully.'
                toastVisible.value = true
            },
            onError: () => {
                toastTexts.title = 'Error updating accommodation'
                toastTexts.description = 'There was an error updating the accommodation.'
                toastTexts.type = 'danger'
                toastVisible.value = true
            }
        });
    }
}

</script>

<style scoped>

</style>