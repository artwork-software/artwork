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

                <div>
                    <div class="my-4">
                        <h2 class="text-md font-semibold mb-2">{{ $t('Room types')}}</h2>
                        <p class="text-sm text-zinc-600 mb-3">{{ $t('Select the room types that are available at this accommodation.') }}</p>
                    </div>
                    <ArtworkBaseListbox
                        v-model="selectedRoomTypes"
                        :items="roomTypes"
                        multiple
                        use-translations
                        placeholder=""
                        :selectedFormatter="(items) => `${items.length} ausgewählt`"
                    />
                </div>

                <div class="mt-10">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <PageTitle :title="$t('Contacts')" :description="$t('You can view and edit all accommodation contacts here')" />
                            <ArtworkBaseModalButton size="sm" variant="primary" type="button" @click="showCreateOrUpdateContactModal = true">
                                {{ $t('Add Contact') }}
                            </ArtworkBaseModalButton>
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
import {computed, defineAsyncComponent, ref, watch} from "vue";
import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ArtworkSingleContact from "@/Artwork/Contact/ArtworkSingleContact.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";

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
            note: '',
            room_types: [],
        })
    },
    roomTypes: {
        type: Object,
        required: false,
        default: () => []
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
    room_types: props.accommodation.room_types ?? []
})


const selectedRoomTypes = ref([])
const selectedRoomTypeIds = computed(() => selectedRoomTypes.value.map(rt => rt.id))
watch(
    () => [props.accommodation?.id, props.roomTypes], // neu laden, wenn Unterkunft oder die Liste wechselt
    () => {
        const accTypeIds = new Set(
            (props.accommodation?.room_types ?? []).map((rt) => rt.id)
        )
        selectedRoomTypes.value = (props.roomTypes ?? []).filter((rt) =>
            accTypeIds.has(rt.id)
        )
    },
    { immediate: true }
)

const updateAccommodation = () => {
    if ( accommodationForm.isDirty ) {
        accommodationForm.room_types = selectedRoomTypeIds;
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
