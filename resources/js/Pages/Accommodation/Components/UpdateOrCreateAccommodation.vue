<template>
    <ArtworkBaseModal :title="accommodation.id ? 'Update accommodation' : 'Add new accommodation'" :description="accommodation.id ? 'Fill in the details below to edit the accommodation.' : 'Fill in the details below to add a new accommodation.'" @close="$emit('close')">
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

            <div v-if="!accommodationForm.id">
                <BaseAlertComponent message="After creating the accommodation, you can add contacts to it." use-translation type="info" class="mt-4" />
            </div>

            <div class="flex items-center justify-between mt-5">
                <ArtworkBaseModalButton size="md" variant="primary" type="submit">
                    {{ accommodation.id ? $t('Update') : $t('Create') }}
                </ArtworkBaseModalButton>
                <ArtworkBaseModalButton size="md" variant="danger" type="button" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </ArtworkBaseModalButton>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {computed, ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {ListboxButton, ListboxOption, ListboxOptions, Listbox} from "@headlessui/vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";

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
            note: '',
            room_types: []
        })
    },
    roomTypes: {
        type: Object,
        required: false,
        default: () => []
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

const updateOrCreateAccommodation = () => {

    accommodationForm.room_types = selectedRoomTypeIds;

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
