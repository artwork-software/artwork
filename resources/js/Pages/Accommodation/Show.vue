<template>
    <AppLayout :title="$t('Accommodation') + ': ' + accommodation.name">
        <div class="mt-5 mx-auto container pb-20">
            <div>
                <div class="mb-4">
                    <Link :href="route('accommodation.index')" class="inline-flex items-center text-sm font-medium text-artwork-buttons-hover hover:text-artwork-buttons-hover/80">
                        <component is="IconArrowLeft" class="h-4 w-4 mr-2" />
                        {{ $t('Back to accommodations overview') }}
                    </Link>
                </div>
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
                        <p class="text-sm text-zinc-600 mb-3">{{ $t('Manage room types and their costs for this accommodation.') }}</p>
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
                                                    icon="IconInfoCircle"
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
                                            <span class="text-sm font-medium text-gray-900">{{ $t(roomType.name) }}</span>
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
                                                    @focusout="updateAccommodation"
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
                                <component is="IconPlus" class="h-4 w-4 mr-2" />
                                {{ $t('Add room type') }}
                            </button>
                        </div>

                        <!-- Add Room Type Dropdown -->
                        <div v-if="showAddRoomType" class="mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                            <div class="flex items-center gap-4">
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
                                    <button
                                        type="button"
                                        @click="addRoomType"
                                        :disabled="!selectedNewRoomType"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-artwork-buttons-create hover:bg-artwork-buttons-create/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-buttons-create disabled:bg-gray-400"
                                    >
                                        {{ $t('Add') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showAddRoomType = false; selectedNewRoomType = null"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-buttons-create"
                                    >
                                        {{ $t('Cancel') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="selectedRoomTypes.length === 0" class="mt-4 text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                        <component is="IconHome" class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('No room types') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $t('Get started by adding a room type to this accommodation.') }}</p>
                        <div class="mt-6">
                            <button
                                type="button"
                                @click="showAddRoomType = true"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-artwork-buttons-create hover:bg-artwork-buttons-create/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-buttons-create"
                            >
                                <component is="IconPlus" class="h-4 w-4 mr-2" />
                                {{ $t('Add room type') }}
                            </button>
                        </div>
                    </div>
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
import {useForm, Link} from "@inertiajs/vue3";
import {computed, defineAsyncComponent, ref, watch} from "vue";
import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import ArtworkSingleContact from "@/Artwork/Contact/ArtworkSingleContact.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

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
    room_types: props.accommodation.room_types ?? [],
    room_type_costs: {}
})


const selectedRoomTypes = ref([])
const selectedRoomTypeIds = computed(() => selectedRoomTypes.value.map(rt => rt.id))
const roomTypeCosts = ref({})
const showAddRoomType = ref(false)
const selectedNewRoomType = ref(null)

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

const addRoomType = () => {
    if (selectedNewRoomType.value) {
        selectedRoomTypes.value.push(selectedNewRoomType.value)
        roomTypeCosts.value[selectedNewRoomType.value.id] = 0.00
        selectedNewRoomType.value = null
        showAddRoomType.value = false
        updateAccommodation()
    }
}

const removeRoomType = (roomTypeId) => {
    selectedRoomTypes.value = selectedRoomTypes.value.filter(rt => rt.id !== roomTypeId)
    delete roomTypeCosts.value[roomTypeId]
    updateAccommodation()
}

const updateAccommodation = () => {
    accommodationForm.room_types = selectedRoomTypeIds.value;
    accommodationForm.room_type_costs = roomTypeCosts.value;
    accommodationForm.patch(route('accommodation.update', props.accommodation.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toastTexts.title = 'Accommodation updated successfully'
            toastTexts.description = 'The changes have been saved successfully.'
            toastTexts.type = 'success'
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

</script>

<style scoped>

</style>
