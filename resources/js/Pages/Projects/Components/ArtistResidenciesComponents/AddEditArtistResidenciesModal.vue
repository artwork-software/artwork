<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        modal-size="max-w-7xl"
        full-modal
        :title="artistResidency.id ? $t('Edit artist residency') : $t('Add artist residency')"
        :description="artistResidency.id ? $t('Edit the artist residency for this project.') : $t('Add a new artist residency for this project.')"
    >
        <form @submit.prevent="createOrUpdateArtistResidency" v-if="usePage().props.accommodations.length > 0">
            <div class="px-6 pb-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- FORM -->
                    <div class="md:col-span-2 space-y-6">
                        <!-- Artist -->
                        <section class="rounded-xl border border-zinc-200 bg-white shadow-sm">
                            <header class="flex items-center justify-between px-4 pt-4">
                                <h3 class="text-sm font-semibold text-zinc-900 pl-3 border-l-4 border-artwork-buttons-hover">
                                    {{ $t('Artist') }}
                                </h3>
                                <span class="inline-flex items-center rounded-full border border-artwork-navigation-color/30 bg-artwork-navigation-color/10 px-2 py-0.5 text-[11px] font-medium text-artwork-buttons-hover">
                  {{ project?.name }}
                </span>
                            </header>
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <BaseInput v-model="artistResidency.name" :label="$t('Artist name')" id="name" no-margin-top required />
                                    <BaseInput v-model="artistResidency.civil_name" :label="$t('Civil name')" id="civil_name" no-margin-top />
                                    <BaseInput v-model="artistResidency.phone_number" :label="$t('phone number')" id="phone_number" />
                                    <BaseInput v-model="artistResidency.position" label="Position" id="position" />
                                </div>
                            </div>
                        </section>

                        <!-- Accommodation & Room -->
                        <section class="rounded-xl border border-zinc-200 bg-white shadow-sm">
                            <header class="px-4 pt-4">
                                <h3 class="text-sm font-semibold text-zinc-900 pl-3 border-l-4 border-artwork-navigation-color">
                                    {{ $t('Accommodation & room') }}
                                </h3>
                            </header>
                            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Accommodation -->
                                <div>
                                    <Listbox as="div" v-model="selectedAccommodation">
                                        <ListboxLabel class="xsLight">{{ $t('Accommodation') }}</ListboxLabel>
                                        <div class="relative mt-1">
                                            <ListboxButton
                                                class="relative h-11 w-full cursor-default rounded-lg bg-white py-1.5 pl-3 pr-10 text-left text-gray-900 ring-1 ring-inset ring-zinc-200 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-hover sm:text-sm"
                                            >
                                                <span class="block truncate">{{ selectedAccommodation?.name }}</span>
                                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                          <component is="IconCaretUpDown" stroke-width="1.5" class="size-5 text-gray-400" aria-hidden="true" />
                        </span>
                                            </ListboxButton>

                                            <ListboxOptions
                                                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-md ring-1 ring-black/5 focus:outline-none sm:text-sm"
                                            >
                                                <ListboxOption
                                                    as="template"
                                                    v-for="accommodation in usePage().props.accommodations"
                                                    :key="accommodation.id"
                                                    :value="accommodation"
                                                    v-slot="{ selected }"
                                                >
                                                    <li class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900">
                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                              {{ accommodation.name }}
                            </span>
                                                        <span
                                                            v-if="selected"
                                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-artwork-buttons-hover"
                                                        >
                              <component is="IconCheck" class="size-5" aria-hidden="true" />
                            </span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </div>
                                    </Listbox>
                                </div>

                                <!-- Room type -->
                                <div>
                                    <Listbox as="div" v-model="selectedRoomType">
                                        <ListboxLabel class="xsLight">{{ $t('Room type') }}</ListboxLabel>
                                        <div class="relative mt-1">
                                            <ListboxButton
                                                class="relative h-11 w-full cursor-default rounded-lg bg-white py-1.5 pl-3 pr-10 text-left text-gray-900 ring-1 ring-inset ring-zinc-200 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-hover sm:text-sm"
                                            >
                                                <span class="block truncate">{{ $t(selectedRoomType) }}</span>
                                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                          <component is="IconCaretUpDown" stroke-width="1.5" class="size-5 text-gray-400" aria-hidden="true" />
                        </span>
                                            </ListboxButton>

                                            <ListboxOptions
                                                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-md ring-1 ring-black/5 focus:outline-none sm:text-sm"
                                            >
                                                <ListboxOption
                                                    as="template"
                                                    v-for="roomType in usePage().props.roomTypes"
                                                    :key="roomType"
                                                    :value="roomType"
                                                    v-slot="{ selected }"
                                                >
                                                    <li class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900">
                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                              {{ $t(roomType) }}
                            </span>
                                                        <span
                                                            v-if="selected"
                                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-artwork-buttons-hover"
                                                        >
                              <component is="IconCheck" class="size-5" aria-hidden="true" />
                            </span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </div>
                                    </Listbox>
                                </div>
                            </div>
                        </section>

                        <!-- Travel & costs -->
                        <section class="rounded-xl border border-zinc-200 bg-white shadow-sm">
                            <header class="px-4 pt-4">
                                <h3 class="text-sm font-semibold text-zinc-900 pl-3 border-l-4 border-artwork-buttons-hover">
                                    {{ $t('Travel & costs') }}
                                </h3>
                            </header>

                            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Arrival -->
                                <div class="flex items-end gap-4">
                                    <BaseInput type="date" v-model="artistResidency.arrival_date" :label="$t('Arrival date')" id="arrival_date" class="w-full" />
                                    <BaseInput type="time" v-model="artistResidency.arrival_time" :label="$t('Arrival time')" id="arrival_time" class="w-full" />
                                </div>

                                <!-- Departure -->
                                <div class="flex items-end gap-4">
                                    <BaseInput type="date" v-model="artistResidency.departure_date" :label="$t('Date departure')" id="departure_date" class="w-full" />
                                    <BaseInput type="time" v-model="artistResidency.departure_time" :label="$t('Departure time')" id="departure_time" class="w-full" />
                                </div>

                                <!-- Costs -->
                                <BaseInput
                                    type="number"
                                    v-model="artistResidency.cost_per_night"
                                    :label="$t('Cost per night')"
                                    id="cost_per_night"
                                    :step="0.01"
                                    :max="50000"
                                />
                                <div></div>

                                <BaseInput
                                    type="number"
                                    v-model="artistResidency.daily_allowance"
                                    :label="$t('Daily allowance')"
                                    id="daily_allowance"
                                    :step="0.01"
                                    :max="50000"
                                />
                                <BaseInput
                                    type="number"
                                    v-model="artistResidency.additional_daily_allowance"
                                    :label="$t('Additional daily allowance')"
                                    id="additional_daily_allowance"
                                    :step="1"
                                    :max="50000"
                                />
                            </div>

                            <!-- KPIs (mobile only) -->
                            <div class="p-4 md:hidden">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                        <div class="flex items-center">
                                            <h4 class="xsLight">{{ $t('No. of overnight stays') }}</h4>
                                            <ToolTipComponent
                                                icon="IconInfoCircle"
                                                icon-size="h-4 w-4 ml-2"
                                                :tooltip-text="$t('The number of nights is calculated from the arrival and departure dates.')"
                                                direction="bottom"
                                            />
                                        </div>
                                        <div class="mt-2 xsDark tabular-nums">{{ calculateTotalNights() }}</div>
                                    </div>

                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                        <h4 class="xsLight">{{ $t('Costs for overnight stays') }}</h4>
                                        <div class="mt-2 xsDark tabular-nums">
                                            <span class="underline decoration-double underline-offset-2">{{ calculateTotalCost }} €</span>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                        <div class="flex items-center">
                                            <h4 class="xsLight">{{ $t('Daily allowance entitlement') }}</h4>
                                            <ToolTipComponent
                                                icon="IconInfoCircle"
                                                icon-size="h-4 w-4 ml-2"
                                                :tooltip-text="$t('Daily allowance entitlement is calculated from the number of overnight stays and the daily allowance.')"
                                                direction="bottom"
                                            />
                                        </div>
                                        <div class="mt-2 xsDark tabular-nums">
                                            {{ calculateTotalNights() + Math.floor(artistResidency.additional_daily_allowance) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Description -->
                        <section class="rounded-xl border border-zinc-200 bg-white shadow-sm">
                            <div class="p-4">
                                <BaseTextarea v-model="artistResidency.description" :label="$t('Description')" id="description" />
                            </div>
                        </section>
                    </div>

                    <!-- SUMMARY -->
                    <aside class="md:col-span-1">
                        <div class="md:sticky md:top-4 rounded-xl border border-zinc-200 bg-white shadow-sm p-4">
                            <h4 class="text-sm font-semibold text-zinc-900 pl-3 border-l-4 border-artwork-navigation-color">
                                {{ $t('Summary') }}
                            </h4>

                            <dl class="mt-3 space-y-3">
                                <div class="flex items-center justify-between">
                                    <dt class="text-sm text-zinc-600">{{ $t('No. of overnight stays') }}</dt>
                                    <dd>
                                        <CountUp
                                            :value="Number(calculateTotalNights())"
                                            :decimals="0"
                                            class="tabular-nums text-sm font-semibold text-zinc-900"
                                        />
                                    </dd>
                                </div>

                                <div class="flex items-center justify-between">
                                    <dt class="text-sm text-zinc-600">{{ $t('Costs for overnight stays') }}</dt>
                                    <dd>
                                        <CountUp
                                            :value="Number(calculateTotalCost)"
                                            :decimals="2"
                                            suffix=" €"
                                            locale="de-DE"
                                            class="tabular-nums text-sm font-semibold text-zinc-900"
                                        />
                                    </dd>
                                </div>

                                <div class="flex items-center justify-between">
                                    <dt class="text-sm text-zinc-600">{{ $t('Daily allowance entitlement') }}</dt>
                                    <dd>
                                        <CountUp
                                            :value="Number(calculateTotalNights() + Math.floor(artistResidency.additional_daily_allowance))"
                                            :decimals="0"
                                            class="tabular-nums text-sm font-semibold text-zinc-900"
                                        />
                                    </dd>
                                </div>

                                <div class="flex items-center justify-between">
                                    <dt class="text-sm text-zinc-600">{{ $t('Daily allowance') }}</dt>
                                    <CountUp
                                        :value="Number(calculateTotalDailyAllowance)"
                                        :decimals="2"
                                        suffix=" €"
                                        locale="de-DE"
                                        class="tabular-nums text-sm font-semibold text-zinc-900"
                                    />
                                </div>
                            </dl>

                            <p class="mt-3 text-[11px] text-zinc-500">
                                {{ $t('Values update automatically when dates or rates change.') }}
                            </p>
                        </div>
                        <!-- SUBMIT -->
                        <div class="my-6 flex w-full items-center justify-center">
                            <ArtworkBaseModalButton
                                type="submit"
                                :loading="artistResidency.processing"
                                class="w-full"
                                :disabled="artistResidency.processing"
                                variant="primary"
                            >
                                {{ artistResidency.id ? $t('Save') : $t('Create') }}
                            </ArtworkBaseModalButton>
                        </div>

                    </aside>
                </div>
            </div>


        </form>

        <!-- EMPTY STATE -->
        <div v-else class="mx-10 my-10 -mt-5 rounded-xl border border-red-200 bg-red-500/10 px-4 py-5">
            <h4 class="mb-1 text-sm font-bold text-red-500">
                {{ $t('Attention') }}
            </h4>
            <AlertComponent
                :text="$t('You must first create at least one accommodation in the user administration under “Addresses” before you can maintain an artist residency')"
                type="error"
            />
        </div>
    </ArtworkBaseModal>
</template>



<script setup>

import {useForm, usePage} from "@inertiajs/vue3";
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {computed, ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import CountUp from "@/Artwork/Visual/CountUp.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";


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
.tabular-nums { font-variant-numeric: tabular-nums; }

/* kleine, sanfte Fade/Slide-Animation */
.fade-up-enter-active, .fade-up-leave-active { transition: all .15s ease; }
.fade-up-enter-from { opacity: 0; transform: translateY(2px); }
.fade-up-enter-to { opacity: 1; transform: translateY(0); }
.fade-up-leave-from { opacity: 1; transform: translateY(0); }
.fade-up-leave-to { opacity: 0; transform: translateY(-2px); }
</style>
