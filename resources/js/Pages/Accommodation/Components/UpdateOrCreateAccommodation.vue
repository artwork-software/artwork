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
                    <p class="text-sm text-zinc-600 mb-3">{{ $t('Manage room types and their costs for this accommodation.') }}</p>
                </div>

                <div v-if="showRoomTypeError" class="mb-4 text-sm text-red-600">
                    {{ $t('At least one room type must be selected.') }}
                </div>

                <!-- Room Types Table -->
                <div v-if="selectedRoomTypes.length > 0" class="mt-4">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $t('Room type') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center gap-2">
                                            {{ $t('Cost per night') }}
                                            <ToolTipComponent
                                                :icon="IconInfoCircle"
                                                icon-size="h-4 w-4"
                                                :tooltip-text="$t('Cost per night tooltip')"
                                                direction="top"
                                            />
                                        </div>
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">{{ $t('Actions') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="roomType in selectedRoomTypes" :key="roomType.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ isPredefinedRoomType(roomType.name) ? $t(roomType.name) : roomType.name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-32">
                                            <BaseInput
                                                :id="`cost_${roomType.id}`"
                                                type="number"
                                                :step="0.01"
                                                :max="50000"
                                                v-model="roomTypeCosts[roomType.id]"
                                                placeholder="0.00"
                                                no-margin-top
                                                @focusout="updateAccommodationOnFocusout"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            type="button"
                                            @click="removeRoomType(roomType.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            {{ $t('Remove') }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Room Type Section -->
                <div class="mt-4">
                    <div v-if="!showAddRoomType" class="flex justify-start">
                        <button
                            type="button"
                            @click="showAddRoomType = true"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-artwork-buttons-create bg-artwork-buttons-create/10 hover:bg-artwork-buttons-create/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-buttons-create"
                        >
                            <component :is="IconPlus" class="h-4 w-4 mr-2" />
                            {{ $t('Add room type') }}
                        </button>
                    </div>

                    <!-- Add Room Type Dropdown -->
                    <div v-if="showAddRoomType" class="mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <div class="flex items-end gap-4 mb-4">
                            <div class="flex-1">
                                <ArtworkBaseListbox
                                    v-model="selectedNewRoomType"
                                    :items="availableRoomTypes"
                                    use-translations
                                    placeholder="Select a room type"
                                    label="Room type"
                                />
                            </div>
                            <div class="flex items-end gap-2">
                                <BaseUIButton
                                    type="button"
                                    @click="addRoomType"
                                    :disabled="!selectedNewRoomType"
                                    :label="$t('Add')"
                                    is-add-button
                                />
                                <BaseUIButton
                                    type="button"
                                    @click="showAddRoomType = false; selectedNewRoomType = null"
                                    is-cancel-button
                                    :label="$t('Cancel')"
                                />
                            </div>
                        </div>

                        <!-- Create New Room Type Section -->
                        <div class="border-t pt-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">{{ $t('Create an individual room type') }}</h3>
                            <div class="flex gap-2">
                                <BaseInput
                                    id="new_room_type"
                                    v-model="newRoomTypeName"
                                    :placeholder="$t('Enter room type name')"
                                    class="flex-1"
                                />
                                <BaseUIButton
                                    :label="creatingRoomType ? $t('Creating...') : $t('Add')"
                                    type="button"
                                    is-add-button
                                    @click="createNewRoomType"
                                    :disabled="!newRoomTypeName.trim() || creatingRoomType"
                                />
                            </div>
                            <div v-if="showNewRoomTypeNameRequiredError" class="mt-1 text-sm text-red-600">
                                {{ $t('Room type name is required.') }}
                            </div>
                            <div v-if="showNewRoomTypeError" class="mt-1 text-sm text-red-600">
                                {{ $t('An error occurred while creating the room type.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="selectedRoomTypes.length === 0" class="mt-4 text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                    <component :is="IconHome" class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('No room types') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $t('Get started by adding a room type to this accommodation.') }}</p>
                    <div class="mt-6">
                        <BaseUIButton
                            type="button"
                            @click="showAddRoomType = true"
                            is-add-button
                            :label="$t('Add room type')"
                        />
                    </div>
                </div>
            </div>

            <div v-if="!accommodationForm.id">
                <BaseAlertComponent message="After creating the accommodation, you can add contacts to it." use-translation type="info" class="mt-4" />
            </div>

            <div class="flex items-center justify-between mt-5">
                <BaseUIButton type="submit" :label="accommodation.id ? $t('Update') : $t('Create')" is-add-button />
                <BaseUIButton type="button" @click="$emit('close')" is-cancel-button :label="$t('Cancel')"/>
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
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconHome, IconInfoCircle, IconPlus} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

// Predefined room types from TypOfRoom enum - only these should be translated
const predefinedRoomTypes = [
    'appartement', 'bungalow', 'duplex_room', 'single_room', 'double_room',
    'family_room', 'holiday_home', 'junior_suite', 'maisonette', 'multi_bed_room',
    'penthouse', 'dormitory', 'standard_room', 'studio', 'suite', 'twin_room',
    'chambre_de_hotel', 'chambre_du_salon'
];

const isPredefinedRoomType = (name) => predefinedRoomTypes.includes(name);

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
    room_types: props.accommodation.room_types ?? [],
    room_type_costs: {}
})

const selectedRoomTypes = ref([])
const selectedRoomTypeIds = computed(() => selectedRoomTypes.value.map(rt => rt.id))
const roomTypeCosts = ref({})
const showRoomTypeError = ref(false)
const showAddRoomType = ref(false)
const selectedNewRoomType = ref(null)
const newRoomTypeName = ref('')
const showNewRoomTypeError = ref(false)
const showNewRoomTypeNameRequiredError = ref(false)
const creatingRoomType = ref(false)

// Available room types that are not yet selected
const availableRoomTypes = computed(() => {
    const selectedIds = new Set(selectedRoomTypeIds.value)
    return props.roomTypes.filter(rt => !selectedIds.has(rt.id))
})
watch(
    () => [props.accommodation?.id, props.roomTypes], // neu laden, wenn Unterkunft oder die Liste wechselt
    () => {
        const accTypeIds = new Set(
            (props.accommodation?.room_types ?? []).map((rt) => rt.id)
        )
        selectedRoomTypes.value = (props.roomTypes ?? []).filter((rt) =>
            accTypeIds.has(rt.id)
        )

        // Initialize costs from existing pivot data
        const newCosts = {}
        props.accommodation?.room_types?.forEach((rt) => {
            newCosts[rt.id] = rt.pivot?.cost_per_night || 0.00
        })
        roomTypeCosts.value = newCosts
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

const addRoomType = () => {
    if (selectedNewRoomType.value) {
        selectedRoomTypes.value.push(selectedNewRoomType.value)
        roomTypeCosts.value[selectedNewRoomType.value.id] = 0.00
        selectedNewRoomType.value = null
        showAddRoomType.value = false
    }
}

const removeRoomType = (roomTypeId) => {
    selectedRoomTypes.value = selectedRoomTypes.value.filter(rt => rt.id !== roomTypeId)
    delete roomTypeCosts.value[roomTypeId]
}

const updateAccommodationOnFocusout = () => {
    // Only auto-save when editing an existing accommodation, not when creating
    if (accommodationForm.id) {
        accommodationForm.room_types = selectedRoomTypeIds.value;
        accommodationForm.room_type_costs = roomTypeCosts.value;

        accommodationForm.patch(route('accommodation.update', accommodationForm.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Silent success - no notification needed for auto-save
            },
            onError: () => {
                console.log('Error auto-saving accommodation');
            }
        });
    }
}

const updateOrCreateAccommodation = () => {
    // Clear previous error
    showRoomTypeError.value = false;

    // Validate room types
    if (selectedRoomTypes.value.length === 0) {
        showRoomTypeError.value = true;
        return;
    }

    accommodationForm.room_types = selectedRoomTypeIds.value;
    accommodationForm.room_type_costs = roomTypeCosts.value;

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
