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
                    <h2 class="text-md font-semibold mb-2">{{ $t('Room types')}} <span class="text-red-500">*</span></h2>
                    <p class="text-sm text-zinc-600 mb-3">{{ $t('Select the room types that are available at this accommodation.') }} {{ $t('At least one room type must be selected.') }}</p>
                </div>
                <ArtworkBaseListbox
                    v-model="selectedRoomTypes"
                    :items="roomTypes"
                    multiple
                    use-translations
                    placeholder=""
                    :selectedFormatter="(items) => `${items.length} ausgewählt`"
                    :error="showRoomTypeError"
                />
                <div v-if="showRoomTypeError" class="mt-1 text-sm text-red-600">
                    {{ $t('At least one room type must be selected.') }}
                </div>

                <!-- Custom Room Type Creation -->
                <div class="mt-4 p-4 bg-gray-50 rounded-lg border">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">{{ $t('Create an individual room type') }}</h3>
                    <div class="flex gap-2">
                        <BaseInput
                            id="new_room_type"
                            v-model="newRoomTypeName"
                            :placeholder="$t('Enter room type name')"
                            class="flex-1"
                        />
                        <ArtworkBaseModalButton
                            size="sm"
                            variant="secondary"
                            type="button"
                            @click="createNewRoomType"
                            :disabled="!newRoomTypeName.trim() || creatingRoomType"
                        >
                            {{ creatingRoomType ? $t('Creating...') : $t('Add') }}
                        </ArtworkBaseModalButton>
                    </div>
                    <div v-if="showNewRoomTypeNameRequiredError" class="mt-1 text-sm text-red-600">
                        {{ $t('Room type name is required.') }}
                    </div>
                    <div v-if="showNewRoomTypeError" class="mt-1 text-sm text-red-600">
                        {{ $t('An error occurred while creating the room type.') }}
                    </div>
                </div>
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
import {useForm, router} from "@inertiajs/vue3";
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
const showRoomTypeError = ref(false)
const newRoomTypeName = ref('')
const showNewRoomTypeError = ref(false)
const showNewRoomTypeNameRequiredError = ref(false)
const creatingRoomType = ref(false)
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

// Clear error when room types are selected
watch(
    () => selectedRoomTypes.value.length,
    (newLength) => {
        if (newLength > 0 && showRoomTypeError.value) {
            showRoomTypeError.value = false;
        }
    }
)

const updateOrCreateAccommodation = () => {
    // Clear previous error
    showRoomTypeError.value = false;

    // Validate room types
    if (selectedRoomTypes.value.length === 0) {
        showRoomTypeError.value = true;
        return;
    }

    accommodationForm.room_types = selectedRoomTypeIds.value;

    if (accommodationForm.id) {
        accommodationForm.patch(route('accommodation.update', accommodationForm.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                //accommodationForm.reset();
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
                //accommodationForm.reset();
                emits('close');
            },
            onError: () => {
                console.log('Error creating accommodation');
            }
        });
    }
}

const createNewRoomType = () => {
    // Clear previous errors
    showNewRoomTypeNameRequiredError.value = false;
    showNewRoomTypeError.value = false;

    if (!newRoomTypeName.value.trim()) {
        showNewRoomTypeNameRequiredError.value = true;
        return;
    }

    creatingRoomType.value = true;

    router.post(route('accommodation-room-types.store'), {
        name: newRoomTypeName.value.trim()
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // The controller returns JSON, so we need to handle it appropriately
            // Since we're using router.post with a JSON response, the data will be in the page response
            if (page && page.props && page.props.flash && page.props.flash.room_type) {
                const roomType = page.props.flash.room_type;

                // Add new room type to available options
                props.roomTypes.push(roomType);

                // Automatically select the new room type
                selectedRoomTypes.value.push(roomType);
            }

            // Clear the input field
            newRoomTypeName.value = '';

            // Clear any validation errors since we now have room types selected
            showRoomTypeError.value = false;

            creatingRoomType.value = false;
        },
        onError: (errors) => {
            if (errors.name) {
                // Server validation error for name field
                showNewRoomTypeNameRequiredError.value = true;
            } else {
                // General error
                showNewRoomTypeError.value = true;
            }
            creatingRoomType.value = false;
        },
        onFinish: () => {
            creatingRoomType.value = false;
        }
    });
}

</script>

<style scoped>

</style>
