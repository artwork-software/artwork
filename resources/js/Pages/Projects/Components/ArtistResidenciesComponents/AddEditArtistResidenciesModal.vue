<template>
    <BaseModal @closed="$emit('close')" modal-size="max-w-4xl" full-modal>
       <div class="px-6 py-3">
           <ModalHeader
               :title="artistResidency.id ? $t('Edit artist residency') : $t('Add artist residency')"
               :description="artistResidency.id ? $t('Edit the artist residency for this project.') : $t('Add a new artist residency for this project.')"
           />
       </div>

        <form @submit.prevent="createOrUpdateArtistResidency" v-if="usePage().props.accommodations.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 px-6">
                <div class="col-span-2">
                    <!-- Name -->
                    <TextInputComponent
                        v-model="artistResidency.name"
                        :label="$t('Artist name')"
                        id="name"
                        no-margin-top
                        required
                    />
                </div>
                <div class="col-span-2">
                    <!-- Name -->
                    <TextInputComponent
                        v-model="artistResidency.civil_name"
                        :label="$t('Civil name')"
                        id="civil_name"
                        no-margin-top
                    />
                </div>
                <div class="col-span-2">
                    <!-- Name -->
                    <TextInputComponent
                        v-model="artistResidency.phone_number"
                        :label="$t('phone number')"
                        id="phone_number"
                    />
                </div>
                <div class="col-span-2">
                    <!-- Name -->
                    <TextInputComponent
                        v-model="artistResidency.position"
                        label="Position"
                        id="position"
                    />
                </div>
                <div class="col-span-2">
                    <Listbox as="div" v-model="selectedAccommodation">
                        <ListboxLabel class="xsLight">{{ $t('Accommodation') }}</ListboxLabel>
                        <div class="relative mt-0.5">
                            <ListboxButton class="relative h-12 w-full cursor-default rounded-lg bg-white min-h-10 py-1.5 pl-3 pr-10 text-left text-gray-900 ring-2 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create sm:text-sm sm:leading-6">
                                <span class="block truncate">{{ selectedAccommodation?.name }}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                              <component is="IconCaretUpDown" stroke-width="1.5" class="size-5 text-gray-400" aria-hidden="true" />
                            </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="accommodation in usePage().props.accommodations" :key="accommodation.id" :value="accommodation" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ accommodation.name }}</span>

                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                        <component is="IconCheck" class="size-5" aria-hidden="true" />
                                      </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
                <div class="col-span-2">
                    <Listbox as="div" v-model="selectedRoomType">
                        <ListboxLabel class="xsLight">{{ $t('Room type') }}</ListboxLabel>
                        <div class="relative mt-0.5">
                            <ListboxButton class="relative h-12 w-full cursor-default rounded-lg bg-white min-h-10 py-1.5 pl-3 pr-10 text-left text-gray-900 ring-2 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create sm:text-sm sm:leading-6">
                                <span class="block truncate">{{ $t(selectedRoomType) }}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                              <component is="IconCaretUpDown" stroke-width="1.5" class="size-5 text-gray-400" aria-hidden="true" />
                            </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="roomType in usePage().props.roomTypes" :key="roomType" :value="roomType" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ $t(roomType) }}</span>

                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                        <component is="IconCheck" class="size-5" aria-hidden="true" />
                                      </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 px-6 bg-gray-100 my-5 py-5">
                <div class="col-span-2 flex items-center gap-4">
                    <div class="w-full">
                        <DateInputComponent
                            v-model="artistResidency.arrival_date"
                            :label="$t('Arrival date')"
                            id="arrival_date"
                        />
                    </div>
                    <div class="w-full">
                        <TimeInputComponent
                            v-model="artistResidency.arrival_time"
                            :label="$t('Arrival time')"
                            id="arrival_time"
                        />
                    </div>
                </div>
                <div class="col-span-2 flex items-center gap-4">
                    <div class="w-full">
                        <DateInputComponent
                            v-model="artistResidency.departure_date"
                            :label="$t('Date departure')"
                            id="departure_date"
                        />
                    </div>
                    <div class="w-full">
                        <TimeInputComponent
                            v-model="artistResidency.departure_time"
                            :label="$t('Departure time')"
                            id="departure_time"
                        />
                    </div>
                </div>
                <div class="col-span-1">
                    <!-- Name -->
                    <NumberInputComponent
                        v-model="artistResidency.cost_per_night"
                        :label="$t('Cost per night')"
                        id="cost_per_night"
                        :step="0.01"
                        :max="50000"
                    />
                </div>
                <div>

                </div>
                <div class="col-span-1">
                    <div class="flex items-center">
                        <h4 class="xsLight">{{ $t('No. of overnight stays') }}</h4>
                        <ToolTipComponent
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4 ml-2"
                            :tooltip-text="$t('The number of nights is calculated from the arrival and departure dates.')"
                            direction="bottom"
                        />
                    </div>
                    <div class="mt-3">
                        <h4 class="xsDark">{{ calculateTotalNights() }}</h4>
                    </div>
                </div>
                <div class="col-span-1">
                    <div class="lex items-center justify-center">
                        <h4 class="xsLight">{{ $t('Costs for overnight stays') }}</h4>
                    </div>
                    <div class="mt-3">
                        <h4 class="xsDark"><span class="underline decoration-double underline-offset-2">{{ calculateTotalCost }} €</span></h4>
                    </div>
                </div>
                <div>
                    <!-- Name -->
                    <NumberInputComponent
                        v-model="artistResidency.daily_allowance"
                        :label="$t('Daily allowance')"
                        id="daily_allowance"
                        :step="0.01"
                        :max="50000"
                    />
                </div>
                <div>
                    <!-- Name -->
                    <NumberInputComponent
                        v-model="artistResidency.additional_daily_allowance"
                        :label="$t('Additional daily allowance')"
                        id="additional_daily_allowance"
                        :step="1"
                        :max="50000"
                    />
                </div>
                <div class="col-span-1">
                    <div class="flex items-center">
                        <h4 class="xsLight">{{ $t('Daily allowance entitlement') }}</h4>
                        <ToolTipComponent
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4 ml-2"
                            :tooltip-text="$t('Daily allowance entitlement is calculated from the number of overnight stays and the daily allowance.')"
                            direction="bottom"
                        />
                    </div>
                    <div class="mt-3">
                        <h4 class="xsDark">{{ calculateTotalNights() + Math.floor(artistResidency.additional_daily_allowance)}}</h4>
                    </div>
                </div>

                <div class="">
                    <div class="lex items-center justify-center">
                        <h4 class="xsLight">{{ $t('Daily allowance') }}</h4>
                    </div>
                    <div class="mt-3">
                        <h4 class="xsDark"><span class="underline decoration-double underline-offset-2">{{ calculateTotalDailyAllowance }} €</span></h4>
                    </div>
                </div>
            </div>

            <div class="col-span-full px-6">
                <TextareaComponent
                    v-model="artistResidency.description"
                    :label="$t('Description')"
                    id="description"
                    />
            </div>

            <div class="my-5 flex items-center justify-center w-full col-span-full">
                <FormButton type="submit" class="bg-artwork-buttons-create" :text="artistResidency.id ? $t('Save') : $t('Create')" />
            </div>
        </form>

        <div v-else class="bg-red-500/10 mx-10 my-10 px-4 py-5 rounded-lg border border-red-200 -mt-5">
            <div>
                <h4 class="font-bold text-sm text-red-500 mb-1">{{ $t('Attention')}}</h4>
            </div>
            <AlertComponent  :text="$t('You must first create at least one accommodation in the user administration under “Addresses” before you can maintain an artist residency')" type="error" />
        </div>


    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {computed, reactive, ref} from "vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";


const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    artist_residency: {
        type: Object,
        required: false,
        defaults: null
    }
})

const formatDate = (date) => {
    if(date) {
        let utcDate = new Date(date)
        let localDate = new Date(utcDate.getTime() - utcDate.getTimezoneOffset() * 60000);
        return localDate ? localDate.toISOString().split('T')[0] : null;
    }else {
        return ''
    }
}

const emit = defineEmits(['close'])
const selectedAccommodation = ref(usePage().props.accommodations.find(accommodation => accommodation.id === props.artist_residency?.accommodation_id) || usePage().props.accommodations[0])
const selectedRoomType = ref(props.artist_residency?.type_of_room || usePage().props.roomTypes[0])

const artistResidency = useForm({
    id: props.artist_residency ? props.artist_residency.id : null,
    name: props.artist_residency ? props.artist_residency.name : '',
    civil_name: props.artist_residency ? props.artist_residency.civil_name : '',
    phone_number: props.artist_residency ? props.artist_residency.phone_number : '',
    position: props.artist_residency ? props.artist_residency.position : '',
    accommodation_id: null,
    project_id: props.project.id,
    arrival_date: props.artist_residency ? formatDate(props.artist_residency.arrival_date) : '',
    arrival_time: props.artist_residency ? props.artist_residency.arrival_time : '',
    departure_date: props.artist_residency ? formatDate(props.artist_residency.departure_date) : '',
    departure_time: props.artist_residency ? props.artist_residency.departure_time : '',
    type_of_room: null,
    cost_per_night: props.artist_residency ? props.artist_residency.cost_per_night : 0.00,
    daily_allowance: props.artist_residency ? props.artist_residency.daily_allowance : 0.00,
    additional_daily_allowance: props.artist_residency ? props.artist_residency.additional_daily_allowance : 0.00,
    description: props.artist_residency ? props.artist_residency.description : '',
    days: 0,
})

const calculateTotalNights = () => {
    const arrivalDate = new Date(artistResidency.arrival_date)
    const departureDate = new Date(artistResidency.departure_date)

    const timeDifference = departureDate.getTime() - arrivalDate.getTime()
    const daysDifference = timeDifference / (1000 * 3600 * 24)

    if(isNaN(daysDifference)) {
        return 0
    }

    return daysDifference
}

const calculateTotalCost = computed(() => {
    return (artistResidency.cost_per_night * calculateTotalNights()).toFixed(2)
})

const calculateTotalDailyAllowance = computed(() => {

    return (Math.floor(artistResidency.daily_allowance) * (calculateTotalNights() + Math.floor(artistResidency.additional_daily_allowance)))
})



const formattedDepartureDate = computed(() => {
    let utcDate = new Date(artistResidency.departure_date)
    let localDate = null;
    if(artistResidency.departure_date){
        localDate = new Date(utcDate.getTime() - utcDate.getTimezoneOffset() * 60000);
    }
    return localDate ? localDate.toISOString().split('T')[0] : null;
})

const createOrUpdateArtistResidency = () => {

    if(!selectedAccommodation.value || !selectedRoomType.value){
        return
    }

    artistResidency.days = calculateTotalNights();
    artistResidency.type_of_room = selectedRoomType.value;
    artistResidency.accommodation_id = selectedAccommodation.value.id;

    if(artistResidency.id){
        artistResidency.patch(route('artist-residencies.update', {artistResidency: artistResidency.id}), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close', true)
            }
        })
    } else {
        artistResidency.post(route('artist-residencies.store', {project: props.project.id}),{
            preserveScroll: true,
            onSuccess: () => {
                emit('close', true)
            }
        })
    }
}
</script>

<style scoped>

</style>
