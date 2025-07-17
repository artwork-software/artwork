<template>
    <AppLayout>
        <EventSettingHeader>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-24">
                <div>
                    <div>
                        <h2 class="headline2 my-2">{{ $t('Public holidays & school vacations via interface') }}</h2>
                        <p class="xsLight max-w-3xl">
                            {{ $t('With this interface, you can select the public holidays and school vacations for the federal states that are relevant to you. This means you only receive the data you really need and it is automatically entered into your calendar. You can update your calendar at any time to ensure that it always contains the latest public holidays and school vacations for your selected federal states. This keeps you informed at all times and allows you to plan with ease and precision.') }}
                        </p>
                    </div>
                    <transition
                        enter-active-class="duration-300 ease-out"
                        enter-from-class="transform opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="duration-200 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="transform opacity-0"
                    >
                        <div class="my-3 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg w-1/2" v-show="showAPIHolidaySaved">
                            {{ $t('Saved. The changes have been successfully applied.') }}
                        </div>
                    </transition>

                    <div class="mt-5">
                        <h3 class="headline3">{{ $t('Select states & color') }}</h3>
                        <p class="xsLight w-1/2 my-2">
                            {{ $t('Select the federal states for which you want to import the public holidays and school vacations into your calendar. You can select as many federal states as you like. You can then specify a color in which the public holidays and school vacations should be displayed in your calendar.') }}
                        </p>
                        <div class="flex items-center gap-4 w-full mt-3">
                            <Listbox as="div" class="relative w-96">
                                <ListboxButton class="menu-button">
                                    <span class="flex items-center justify-between w-full">
                                       {{ $t('Select federal states') }}
                                    </span>
                                    <component is="IconChevronDown" class="h-5 w-5" aria-hidden="true" />
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg border border-gray-300 ring-opacity-5 focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" v-for="subdivision in computedSubDivisions" :key="subdivision.id">
                                            <li @click="addSubDivisionToForm(subdivision)" :class="['relative cursor-default select-none py-2 pl-3 pr-9']">
                                                <span :class="['block truncate']">{{ subdivision.name }}</span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>

                            <ColorPickerComponent @update-color="updateColor" :color="holidayForm.color" />
                        </div>
                        <div class="w-full" v-if="holidayForm.selectedSubdivisions.length > 0">
                            <div class="flex items-center flex-wrap gap-2 my-4 w-1/2">
                                <div v-for="selectedSubdivision in holidayForm.selectedSubdivisions" :key="selectedSubdivision.id" class="break-keep">
                                    <div class="px-2 py-1 bg-tagBg rounded-full min-w-fit text-tagText text-xs cursor-pointer hover:bg-red-600/20 hover:border-red-500/40 hover:text-red-600 transition-colors duration-300 ease-in-out border border-tagBg"
                                         @click="removeSubDivisionFormForm(selectedSubdivision.id)">
                                        {{ selectedSubdivision.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-2/3 mt-5">
                        <h3 class="headline3">
                            {{ $t('What data would you like to import?') }}
                        </h3>
                        <p class="xsLight my-2">
                            {{ $t('Select whether you only want to import the public holidays or also the school vacations into your calendar.') }}
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-4">
                            <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input id="comments" v-model="holidayForm.public_holidays" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist" />
                                </div>
                                <div class="ml-3 text-sm/6">
                                    <label for="comments" class="headline3">{{ $t('Holidays') }}</label>
                                    <p id="comments-description" class="xsLight">
                                        {{ $t('Import the public holidays for the federal states you have selected into your calendar.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input v-model="holidayForm.school_holidays" id="candidates" aria-describedby="candidates-description" name="candidates" type="checkbox" class="input-checklist" />
                                </div>
                                <div class="ml-3 text-sm/6">
                                    <label for="candidates" class="headline3">{{ $t('School vacations') }}</label>
                                    <p id="candidates-description" class="xsLight">
                                        {{ $t('Import the school vacations for the federal states you have selected into your calendar.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between w-2/3 my-10">
                        <div>
                            <div v-if="holidayForm.isDirty">
                                <p>
                                    <span class="text-xs text-red-500">{{ $t('Changes have been made. Please save!') }}</span>
                                </p>
                            </div>
                        </div>
                        <AddButtonBig :text="$t('Save & Import')" class="w-fit" @click="submitHolidayForm"/>
                    </div>


                    <AlertComponent type="error" classes="max-w-3xl font-bold mt-3" :text="$t('Each time you run the interface again, all previously imported public holidays and school vacations are removed from your calendar and replaced with the latest data. This ensures that only the latest information from your selected federal states is in your calendar, without old entries.')" />
                </div>
                <div>
                    <div class="mb-5">
                        <h2 class="headline2 my-2">{{ $t('Create holidays & school vacations yourself') }}</h2>
                        <p class="xsLight max-w-3xl">
                            {{ $t('Here you have the option of creating public holidays and school vacations yourself. These are then displayed in your calendar and you can edit them individually. For example, you can also create your own public holidays or school vacations that are not included in the official calendars.') }}
                        </p>
                    </div>
                    <transition
                        enter-active-class="duration-300 ease-out"
                        enter-from-class="transform opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="duration-200 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="transform opacity-0"
                    >
                        <div class="my-3 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg" v-show="showCustomHolidaySaved">
                            {{ $t('Saved. The changes have been successfully applied.') }}
                        </div>
                    </transition>
                    <div class="my-4">
                        <h3 class="headline3">{{ $t('Select states & color') }}</h3>
                        <p class="xsLight w-1/2 my-2">
                            <!-- text für selbst angelegte Feiertage und deren bundesländer -->
                            {{ $t('Select the federal states that should apply to this public holiday. You can select as many federal states as you like. You can then specify a color in which the public holiday should be displayed in your calendar. You do not have to select a federal state if the public holiday applies throughout Germany.') }}
                        </p>
                        <div class="flex items-center gap-4 w-full mt-3">
                            <Listbox as="div" class="relative w-96" v-model="customHolidayForm.selectedSubdivisions" multiple>
                                <ListboxButton class="menu-button">
                                    <span>
                                        {{ $t('Select federal states') }}
                                    </span>
                                    <component is="IconChevronDown" class="h-5 w-5" aria-hidden="true" />
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg border border-gray-300 ring-opacity-5 focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" v-for="subdivision in subdivisions" :key="subdivision.id" :value="subdivision" v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ subdivision.name }}</span>

                                                <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                    <component is="IconCheck" class="h-5 w-5" aria-hidden="true" />
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>

                            <ColorPickerComponent @update-color="updateColorCustomHoliday" :color="customHolidayForm.color" />
                        </div>
                        <div class="w-full" v-if="customHolidayForm.selectedSubdivisions.length > 0">
                            <div class="flex items-center flex-wrap gap-2 mt-4 w-1/2">
                                <div v-for="selectedSubdivision in customHolidayForm.selectedSubdivisions" :key="selectedSubdivision.id" class="break-keep">
                                    <div class="px-2 py-1 bg-tagBg rounded-full min-w-fit text-tagText text-xs cursor-pointer hover:bg-red-600/20 hover:border-red-500/40 hover:text-red-600 transition-colors duration-300 ease-in-out border border-tagBg"
                                         @click="removeSubDivisionFormCustomHoliday(selectedSubdivision.id)">
                                        {{ selectedSubdivision.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <BaseInput id="title" v-model="customHolidayForm.name" label="Name" />
                            <div class="text-red-500 text-xs mt-1" v-show="customHolidayForm.errors.name">
                                {{ customHolidayForm.errors.name }}
                            </div>
                        </div>
                        <div>
                            <BaseInput type="date" id="start" v-model="customHolidayForm.date" label="Start-Time*" />
                            <div class="text-red-500 text-xs mt-1" v-show="customHolidayForm.errors.date">
                                {{ customHolidayForm.errors.date }}
                            </div>
                        </div>
                        <div>
                            <BaseInput type="date" id="end" v-model="customHolidayForm.end_date" label="End-Time" />
                        </div>
                        <div class="col-span-2">
                            <SwitchGroup as="div" class="flex items-center cursor-pointer">
                                <SwitchLabel as="span" class="mr-3 text-sm" :class="customHolidayForm.yearly ? 'font-bold' : 'text-gray-400'">
                                    {{ $t('Repeat the holiday annually') }}
                                </SwitchLabel>
                                <Switch v-model="customHolidayForm.yearly" :class="[customHolidayForm.yearly ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-3 w-6 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                                    <span aria-hidden="true" :class="[!customHolidayForm.yearly  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                </Switch>
                                <SwitchLabel as="span" class="ml-3 text-sm" :class="!customHolidayForm.yearly? 'font-bold' : 'text-gray-400'">
                                    {{ $t('One-off public holiday') }}
                                </SwitchLabel>
                            </SwitchGroup>
                        </div>
                        <div class="col-span-2">
                            <div class="relative flex items-start mt-4">
                                <div class="flex h-6 items-center">
                                    <input
                                        id="treatAsSpecialDay"
                                        v-model="customHolidayForm.treatAsSpecialDay"
                                        type="checkbox"
                                        class="input-checklist"
                                    />
                                </div>
                                <div class="ml-3 text-sm/6 flex">
                                    <label for="treatAsSpecialDay" class="text-sm font-medium">{{ $t('Treat as special day') }}</label>
                                    <ToolTipComponent
                                        icon="IconInfoCircle"
                                        icon-size="h-4 w-4 ml-2"
                                        :tooltip-text="$t('A holiday, which is treated as a special day, is not counted as a normal working day for the shifts. This means that artwork will set the needed workhours for this day to zero regardless of the working-contract. Thus every worked hour on this day will be counted as overtime.')"
                                        direction="bottom"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="col-span-2 flex items-center justify-end">
                            <AddButtonBig :text="$t('Add holiday')" class="w-fit" @click="storeCustomHoliday" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between mb-4">
                        <transition
                            enter-active-class="duration-300 ease-out"
                            enter-from-class="transform opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="duration-200 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="transform opacity-0"
                        >
                            <div class="my-3 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg w-1/3" v-show="showHolidayUpdatedSuccess">
                                {{ $t('Saved. The changes have been successfully applied.') }}
                            </div>
                        </transition>

                        <div v-if="Object.keys(changedHolidays).length > 0" class="flex items-center space-x-2">
                            <span class="text-sm text-red-500">{{ $t('You have unsaved changes') }}</span>
                            <AddButtonBig :text="$t('Save Changes')" class="w-fit" @click="saveChanges" />
                        </div>
                    </div>
                    <div class="mt-8 flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead>
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('Name') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 capitalize">{{ $t('from') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 capitalize">{{ $t('until')}}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Federal states') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('About interface') }}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Repeat annually')}}</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 flex">{{ $t('Special Day')}}
                                            <ToolTipComponent
                                            icon="IconInfoCircle"
                                            icon-size="h-4 w-4 ml-2"
                                            :tooltip-text="$t('A holiday, which is treated as a special day, is not counted as a normal working day for the shifts. This means that artwork will set the needed workhours for this day to zero regardless of the working-contract. Thus every worked hour on this day will be counted as overtime.')"
                                            direction="bottom"
                                            />
                                        </th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0"></th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                    <tr v-for="holiday in holidays.data" :key="holiday.id">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ holiday.name }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ holiday.casted_date.date }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ holiday.casted_date.end_date }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <div v-if="holiday.subdivisions.length > 0">
                                                {{ holiday.subdivisions.map((person) => person.name).join(', ') }}
                                            </div>
                                            <div v-else>
                                                {{ $t('Germany-wide')}}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ holiday.from_api ? $t('Yes') : $t('No') }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ holiday.yearly ? $t('Yes') : $t('No') }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <input
                                                type="checkbox"
                                                :checked="getHolidayTreatAsSpecialDay(holiday)"
                                                @change="updateTreatAsSpecialDay(holiday.id, $event.target.checked)"
                                                class="input-checklist"
                                            />
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 text-right text-sm font-medium pr-3 flex items-center justify-end space-x-4">
                                            <ToolTipComponent
                                                v-if="!holiday.from_api"
                                                @click="editHoliday(holiday)"
                                                icon="IconEdit"
                                                :tooltip-text="$t('Edit')"
                                                icon-size="h-5 w-5"
                                                class="cursor-pointer text-gray-500 hover:text-artwork-buttons-hover transition-colors duration-300 ease-in-out"
                                                aria-hidden="true"
                                            />
                                            <ToolTipComponent
                                                v-if="!holiday.from_api"
                                                @click="deleteHolidayModal(holiday.id)"
                                                icon="IconTrash"
                                                :tooltip-text="$t('Delete')"
                                                icon-size="h-5 w-5"
                                                class="cursor-pointer text-gray-500 hover:text-red-600 transition-colors duration-300 ease-in-out"
                                                aria-hidden="true"
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <BasePaginator
                        :entities="holidays"
                        property-name="holidays"
                        :emit-update-entities-per-page="true"
                        @update-page="updatePage"
                        @update-entities-per-page="changeEntitiesPerPage"
                        class="mt-5"
                    />
                </div>


            </div>

            <ConfirmDeleteModal
                v-if="showDeleteModal"
                :title="$t('Delete state')"
                :description="$t('Do you really want to delete the state?')"
                @delete="confirmDeleteSubdivision"
                @closed="closeDeleteModal"
            />

            <ConfirmDeleteModal
                v-if="showDeleteHolidayModal"
                :title="$t('Delete holiday')"
                :description="$t('Do you really want to delete the holiday?')"
                @delete="deleteHoliday"
                @closed="closeDeleteHolidayModal"
                />


            <EditHolidayModal
                v-if="showEditHolidayModal"
                @close="closeEditHolidayModal"
                :holiday-to-edit="holidayToEdit"
                :sub-divisions="subdivisions"
            />
        </EventSettingHeader>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {computed, reactive, ref} from "vue";
import {
    Combobox, ComboboxButton,
    ComboboxInput,
    ComboboxLabel, ComboboxOption, ComboboxOptions,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions, Switch, SwitchGroup, SwitchLabel
} from "@headlessui/vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import {router, useForm} from "@inertiajs/vue3";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import EditHolidayModal from "@/Pages/Settings/Holidays/Components/EditHolidayModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    holidays: {
        type: Object,
        required: true
    },
    subdivisions: {
        type: Object,
        required: true
    },
    settings: {
        type: Object,
        required: true
    }
})

const query = ref('')
const page = ref(route().params.page ?? 1)
const perPage = ref(route().params.entitiesPerPage ?? 10);
const showCustomHolidaySaved = ref(false);
const showAPIHolidaySaved = ref(false);
const subDivisionsToDelete = ref(null);
const showDeleteModal = ref(false);

const showDeleteHolidayModal = ref(false);
const holidayToDelete = ref(null);

const showEditHolidayModal = ref(false);
const holidayToEdit = ref(null);

const showHolidayUpdatedSuccess = ref(false);
const showErrorMessageDate = ref(false);
const changedHolidays = ref({});

const holidayForm = useForm({
    color: '#1c77d7',
    public_holidays: props.settings.public_holidays ?? false,
    school_holidays: props.settings.school_holidays ?? false,
    selectedSubdivisions: reactive(props.subdivisions.filter(subdivision => props.settings.subdivisions.includes(subdivision.id))),
})

const customHolidayForm = useForm({
    color: '#1c77d7',
    selectedSubdivisions: reactive([]),
    name: '',
    date: '',
    end_date: '',
    from_api: false,
    yearly: false,
    treatAsSpecialDay: false,
})


const updateColor = (color) => {
    holidayForm.color = color;
}

const updateColorCustomHoliday = (color) => {
    customHolidayForm.color = color;
}

const submitHolidayForm = () => {
    holidayForm.post(route('holiday.api.call'), {
        preserveScroll: true,
        onSuccess: () => {
            showAPIHolidaySaved.value = true;
            setTimeout(() => {
                showAPIHolidaySaved.value = false;
            }, 5000)
            applyFiltersAndSort();
        }
    })
}

const addSubDivisionToForm = (subdivision) => {
    if (holidayForm.selectedSubdivisions.find(sub => sub.id === subdivision.id)) {
        holidayForm.selectedSubdivisions = holidayForm.selectedSubdivisions.filter(sub => sub.id !== subdivision.id)
    } else {
        holidayForm.selectedSubdivisions.push(subdivision)
    }

    console.log(holidayForm.selectedSubdivisions)
}

const removeSubDivisionFormForm = (id) => {
    showDeleteModal.value = true;
    subDivisionsToDelete.value = id;
}

const computedSubDivisions = computed(() => {
    return props.subdivisions.filter(subdivision => {
        return !holidayForm.selectedSubdivisions.find(sub => sub.id === subdivision.id) && subdivision.name.toLowerCase().includes(query.value.toLowerCase())
    })
})

const removeSubDivisionFormCustomHoliday = (id) => {
    customHolidayForm.selectedSubdivisions = customHolidayForm.selectedSubdivisions.filter(sub => sub.id !== id)
}

const confirmDeleteSubdivision = () => {
    holidayForm.selectedSubdivisions = holidayForm.selectedSubdivisions.filter(sub => sub.id !== subDivisionsToDelete.value)
    showDeleteModal.value = false;
    subDivisionsToDelete.value = null;
}

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    subDivisionsToDelete.value = null;
}

const updatePage = (pageNumber, entitiesPerPage) => {
    page.value = pageNumber;
    perPage.value = entitiesPerPage;
    applyFiltersAndSort(false);
}

const deleteHolidayModal = (id) => {
    holidayToDelete.value = id;
    showDeleteHolidayModal.value = true;
}

const deleteHoliday = () => {
    customHolidayForm.delete(route('holiday.delete', holidayToDelete.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showDeleteHolidayModal.value = false;
            holidayToDelete.value = null;
            applyFiltersAndSort(false);
        }
    })
}

const closeDeleteHolidayModal = () => {
    showDeleteHolidayModal.value = false;
    holidayToDelete.value = null;
}

const changeEntitiesPerPage = (entitiesPerPage) => {
    perPage.value = entitiesPerPage;
    applyFiltersAndSort();
}
const applyFiltersAndSort = (resetPage = true) => {
    router.get(
        route('holiday.management'),
        {
            page: resetPage ? 1 : page.value,
            entitiesPerPage: perPage.value,
            query: route().params.query,
        },
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
}

const storeCustomHoliday = () => {

    // add check if date is set
    showErrorMessageDate.value = customHolidayForm.date === null;
    if (customHolidayForm.date === null) {
        return;
    }

    customHolidayForm.post(route('holiday.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            customHolidayForm.reset();
            showCustomHolidaySaved.value = true;
            setTimeout(() => {
                showCustomHolidaySaved.value = false;
            }, 5000)
            applyFiltersAndSort();
        }
    })
}

const closeEditHolidayModal = (bool) => {
    showEditHolidayModal.value = false;
    holidayToEdit.value = null;

    if(bool) {
        showHolidayUpdatedSuccess.value = true;
        setTimeout(() => {
            showHolidayUpdatedSuccess.value = false;
        }, 5000)
    }
}

const editHoliday = (holiday) => {
    holidayToEdit.value = holiday;
    showEditHolidayModal.value = true;
}

const getHolidayTreatAsSpecialDay = (holiday) => {
    // If the holiday has been changed, return the changed value
    if (changedHolidays.value.hasOwnProperty(holiday.id)) {
        return changedHolidays.value[holiday.id];
    }
    // Otherwise return the original value
    return holiday.treatAsSpecialDay;
}

const updateTreatAsSpecialDay = (id, checked) => {
    // Store the change locally
    changedHolidays.value[id] = checked;
}

const saveChanges = () => {
    // Only send if there are changes
    if (Object.keys(changedHolidays.value).length === 0) {
        return;
    }

    // Send the changes to the server
    router.post(route('holiday.batch-update'), {
        holidays: changedHolidays.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Clear the changes
            changedHolidays.value = {};

            // Show success message
            showHolidayUpdatedSuccess.value = true;
            setTimeout(() => {
                showHolidayUpdatedSuccess.value = false;
            }, 5000);

            // Refresh the data
            applyFiltersAndSort(false);
        }
    });
}
</script>

<style scoped>

</style>
