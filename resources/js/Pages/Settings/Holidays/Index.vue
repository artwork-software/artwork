<template>
    <AppLayout :title="$t('Public holidays & school vacations via interface')">
        <EventSettingHeader>
            <!-- Intro / Hero -->
            <section class="">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                    <!-- API Import Card -->
                    <div class="bg-white rounded-2xl border border-gray-200/70 shadow-sm">
                        <div class="p-6 sm:p-8">
                            <BasePageTitle
                                title="Public holidays & school vacations via interface"
                                description="With this interface, you can select the public holidays and school vacations for the federal states that are relevant to you. This means you only receive the data you really need and it is automatically entered into your calendar. You can update your calendar at any time to ensure that it always contains the latest public holidays and school vacations for your selected federal states. This keeps you informed at all times and allows you to plan with ease and precision."
                            />

                            <transition
                                enter-active-class="duration-300 ease-out"
                                enter-from-class="transform opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="duration-200 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="transform opacity-0"
                            >
                                <div
                                    v-show="showAPIHolidaySaved"
                                    class="mt-4 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg w-full sm:w-2/3"
                                >
                                    {{ $t('Saved. The changes have been successfully applied.') }}
                                </div>
                            </transition>

                            <!-- Auswahl & Farbe -->
                            <div class="mt-8">
                                <BasePageTitle
                                    title="Select states & color"
                                    description="Select the federal states for which you want to import the public holidays and school vacations into your calendar. You can select as many federal states as you like. You can then specify a color in which the public holidays and school vacations should be displayed in your calendar."
                                />

                                <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-4">
                                    <Listbox as="div" class="relative w-full sm:max-w-md">
                                        <ListboxButton class="menu-button">
                                            <span class="flex items-center justify-between w-full">
                                                {{ $t('Select federal states') }}
                                            </span>
                                            <component :is="IconChevronDown" class="h-5 w-5" aria-hidden="true" />
                                        </ListboxButton>
                                        <transition
                                            leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0"
                                        >
                                            <ListboxOptions
                                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg border border-gray-200 ring-opacity-5 focus:outline-none sm:text-sm"
                                            >
                                                <ListboxOption
                                                    as="template"
                                                    v-for="subdivision in computedSubDivisions"
                                                    :key="subdivision.id"
                                                >
                                                    <li
                                                        @click="addSubDivisionToForm(subdivision)"
                                                        class="relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-gray-50"
                                                    >
                                                        <span class="block truncate">{{ subdivision.name }}</span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </transition>
                                    </Listbox>

                                    <ColorPickerComponent
                                        @update-color="updateColor"
                                        :color="holidayForm.color"
                                    />
                                </div>

                                <!-- Chips -->
                                <div class="mt-4" v-if="holidayForm.selectedSubdivisions.length > 0">
                                    <div class="flex items-center flex-wrap gap-2 w-full">
                                        <div
                                            v-for="selectedSubdivision in holidayForm.selectedSubdivisions"
                                            :key="selectedSubdivision.id"
                                            class="break-keep"
                                        >
                                            <div
                                                class="px-2 py-1 bg-tagBg rounded-full min-w-fit text-tagText text-xs cursor-pointer border border-tagBg hover:bg-red-600/20 hover:border-red-500/40 hover:text-red-600 transition-colors"
                                                @click="removeSubDivisionFormForm(selectedSubdivision.id)"
                                            >
                                                {{ selectedSubdivision.name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Was importieren -->
                            <div class="mt-8">
                                <BasePageTitle
                                    title="What data would you like to import?"
                                    description="Select whether you only want to import the public holidays or also the school vacations into your calendar."
                                />

                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <BaseCheckbox
                                        id="public_holidays"
                                        v-model="holidayForm.public_holidays"
                                        :label="$t('Holidays')"
                                        :description="$t('Import the public holidays for the federal states you have selected into your calendar.')"
                                    />
                                    <BaseCheckbox
                                        id="school_holidays"
                                        v-model="holidayForm.school_holidays"
                                        :label="$t('School vacations')"
                                        :description="$t('Import the school vacations for the federal states you have selected into your calendar.')"
                                    />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-8 flex items-center justify-between">
                                <div v-if="holidayForm.isDirty">
                                    <p>
                                        <span class="text-xs text-red-500">
                                            {{ $t('Changes have been made. Please save!') }}
                                        </span>
                                    </p>
                                </div>

                                <BaseUIButton
                                    is-add-button
                                    use-translation
                                    label="Save & Import"
                                    @click="submitHolidayForm"
                                />
                            </div>

                            <!-- Hinweis -->
                            <AlertComponent
                                type="error"
                                classes="mt-6 font-bold"
                                :text="$t('Each time you run the interface again, all previously imported public holidays and school vacations are removed from your calendar and replaced with the latest data. This ensures that only the latest information from your selected federal states is in your calendar, without old entries.')"
                            />
                        </div>
                    </div>

                    <!-- Manuell erstellen Card -->
                    <div class="bg-white rounded-2xl border border-gray-200/70 shadow-sm">
                        <div class="p-6 sm:p-8">
                            <div class="mb-2">
                                <BasePageTitle
                                    title="Create holidays & school vacations yourself"
                                    description="Here you have the option of creating public holidays and school vacations yourself. These are then displayed in your calendar and you can edit them individually. For example, you can also create your own public holidays or school vacations that are not included in the official calendars."
                                />
                            </div>

                            <transition
                                enter-active-class="duration-300 ease-out"
                                enter-from-class="transform opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="duration-200 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="transform opacity-0"
                            >
                                <div
                                    v-show="showCustomHolidaySaved"
                                    class="mt-4 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg"
                                >
                                    {{ $t('Saved. The changes have been successfully applied.') }}
                                </div>
                            </transition>

                            <!-- Auswahl & Farbe -->
                            <div class="mt-6">
                                <BasePageTitle
                                    title="Select states & color"
                                    description="Select the federal states that should apply to this public holiday. You can select as many federal states as you like. You can then specify a color in which the public holiday should be displayed in your calendar. You do not have to select a federal state if the public holiday applies throughout Germany."
                                />
                                <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-4">
                                    <Listbox
                                        as="div"
                                        class="relative w-full sm:max-w-md"
                                        v-model="customHolidayForm.selectedSubdivisions"
                                        multiple
                                    >
                                        <ListboxButton class="menu-button">
                                            <span>{{ $t('Select federal states') }}</span>
                                            <component :is="IconChevronDown" class="h-5 w-5" aria-hidden="true" />
                                        </ListboxButton>
                                        <transition
                                            leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0"
                                        >
                                            <ListboxOptions
                                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg border border-gray-200 ring-opacity-5 focus:outline-none sm:text-sm"
                                            >
                                                <ListboxOption
                                                    as="template"
                                                    v-for="subdivision in subdivisions"
                                                    :key="subdivision.id"
                                                    :value="subdivision"
                                                    v-slot="{ active, selected }"
                                                >
                                                    <li
                                                        :class="[
                                                            active ? 'bg-indigo-600 text-white' : 'text-gray-900',
                                                            'relative cursor-pointer select-none py-2 pl-3 pr-9'
                                                        ]"
                                                    >
                                                        <span
                                                            :class="[
                                                                selected ? 'font-semibold' : 'font-normal',
                                                                'block truncate'
                                                            ]"
                                                        >
                                                            {{ subdivision.name }}
                                                        </span>
                                                        <span
                                                            v-if="selected"
                                                            :class="[
                                                                active ? 'text-white' : 'text-indigo-600',
                                                                'absolute inset-y-0 right-0 flex items-center pr-4'
                                                            ]"
                                                        >
                                                            <component :is="IconCheck" class="h-5 w-5" aria-hidden="true" />
                                                        </span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </transition>
                                    </Listbox>

                                    <ColorPickerComponent
                                        @update-color="updateColorCustomHoliday"
                                        :color="customHolidayForm.color"
                                    />
                                </div>

                                <!-- Chips -->
                                <div class="mt-4" v-if="customHolidayForm.selectedSubdivisions.length > 0">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <div
                                            v-for="selectedSubdivision in customHolidayForm.selectedSubdivisions"
                                            :key="selectedSubdivision.id"
                                            class="break-keep"
                                        >
                                            <div
                                                class="px-2 py-1 bg-tagBg rounded-full min-w-fit text-tagText text-xs cursor-pointer border border-tagBg hover:bg-red-600/20 hover:border-red-500/40 hover:text-red-600 transition-colors"
                                                @click="removeSubDivisionFormCustomHoliday(selectedSubdivision.id)"
                                            >
                                                {{ selectedSubdivision.name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Formular -->
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
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

                                <div class="md:col-span-2">
                                    <SwitchGroup as="div" class="flex items-center cursor-pointer">
                                        <SwitchLabel
                                            as="span"
                                            class="mr-3 text-sm"
                                            :class="customHolidayForm.yearly ? 'font-bold' : 'text-gray-400'"
                                        >
                                            {{ $t('Repeat the holiday annually') }}
                                        </SwitchLabel>
                                        <Switch
                                            v-model="customHolidayForm.yearly"
                                            :class="[
                                                customHolidayForm.yearly ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create',
                                                'relative inline-flex h-3 w-6 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
                                            ]"
                                        >
                                            <span
                                                aria-hidden="true"
                                                :class="[
                                                    !customHolidayForm.yearly ? 'translate-x-3' : 'translate-x-0',
                                                    'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                ]"
                                            />
                                        </Switch>
                                        <SwitchLabel
                                            as="span"
                                            class="ml-3 text-sm"
                                            :class="!customHolidayForm.yearly ? 'font-bold' : 'text-gray-400'"
                                        >
                                            {{ $t('One-off public holiday') }}
                                        </SwitchLabel>
                                    </SwitchGroup>
                                </div>

                                <div class="md:col-span-2">
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
                                            <label for="treatAsSpecialDay" class="text-sm font-medium">
                                                {{ $t('Treat as special day') }}
                                            </label>
                                            <ToolTipComponent
                                                :icon="IconInfoCircle"
                                                icon-size="h-4 w-4 ml-2"
                                                :tooltip-text="$t('A holiday, which is treated as a special day, is not counted as a normal working day for the shifts. This means that artwork will set the needed workhours for this day to zero regardless of the working-contract. Thus every worked hour on this day will be counted as overtime.')"
                                                direction="bottom"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-2 flex items-center justify-end">
                                    <BaseUIButton
                                        is-add-button
                                        use-translation
                                        label="Add holiday"
                                        @click="storeCustomHoliday"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tabelle / Liste -->
            <section class="mt-10">
                <div class="bg-white rounded-2xl border border-gray-200/70 shadow-sm">
                    <div class="p-4 sm:p-6 lg:p-8">
                        <div class="flex items-center justify-between mb-4">
                            <transition
                                enter-active-class="duration-300 ease-out"
                                enter-from-class="transform opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="duration-200 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="transform opacity-0"
                            >
                                <div
                                    v-show="showHolidayUpdatedSuccess"
                                    class="text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg w-full sm:w-1/2 lg:w-1/3"
                                >
                                    {{ $t('Saved. The changes have been successfully applied.') }}
                                </div>
                            </transition>

                            <div v-if="Object.keys(changedHolidays).length > 0" class="flex items-center gap-2">
                                <span class="text-sm text-red-500">
                                    {{ $t('You have unsaved changes') }}
                                </span>
                                <AddButtonBig :text="$t('Save Changes')" class="w-fit" @click="saveChanges" />
                            </div>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr class="text-left">
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-sm font-semibold text-gray-900 sm:pl-6">
                                        {{ $t('Name') }}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-900 capitalize">
                                        {{ $t('from') }}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-900 capitalize">
                                        {{ $t('until')}}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-900">
                                        {{ $t('Federal states') }}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-900">
                                        {{ $t('About interface') }}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-900">
                                        {{ $t('Repeat annually')}}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-900">
                                            <span class="inline-flex items-center">
                                                {{ $t('Special Day')}}
                                                <ToolTipComponent
                                                    :icon="IconInfoCircle"
                                                    icon-size="h-4 w-4 ml-2"
                                                    :tooltip-text="$t('A holiday, which is treated as a special day, is not counted as a normal working day for the shifts. This means that artwork will set the needed workhours for this day to zero regardless of the working-contract. Thus every worked hour on this day will be counted as overtime.')"
                                                    direction="bottom"
                                                />
                                            </span>
                                    </th>
                                    <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6"></th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="holiday in holidays.data" :key="holiday.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        {{ holiday.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                        {{ holiday.casted_date.date }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                        {{ holiday.casted_date.end_date }}
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-700">
                                        <div v-if="holiday.subdivisions.length > 0" class="max-w-xl">
                                            {{ holiday.subdivisions.map((person) => person.name).join(', ') }}
                                        </div>
                                        <div v-else>
                                            {{ $t('Germany-wide')}}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                        {{ holiday.from_api ? $t('Yes') : $t('No') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                        {{ holiday.yearly ? $t('Yes') : $t('No') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <input
                                            type="checkbox"
                                            :checked="getHolidayTreatAsSpecialDay(holiday)"
                                            @change="updateTreatAsSpecialDay(holiday.id, $event.target.checked)"
                                            class="input-checklist"
                                        />
                                    </td>
                                    <td class="whitespace-nowrap py-4 pl-3 pr-4 sm:pr-6">
                                        <div class="flex items-center justify-end gap-3">
                                            <ToolTipComponent
                                                v-if="!holiday.from_api"
                                                @click="editHoliday(holiday)"
                                                :icon="IconEdit"
                                                :tooltip-text="$t('Edit')"
                                                icon-size="h-5 w-5"
                                                class="cursor-pointer text-gray-500 hover:text-artwork-buttons-hover transition-colors"
                                                aria-hidden="true"
                                            />
                                            <ToolTipComponent
                                                v-if="!holiday.from_api"
                                                @click="deleteHolidayModal(holiday.id)"
                                                :icon="IconTrash"
                                                :tooltip-text="$t('Delete')"
                                                icon-size="h-5 w-5"
                                                class="cursor-pointer text-gray-500 hover:text-red-600 transition-colors"
                                                aria-hidden="true"
                                            />
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <BasePaginator
                            :entities="holidays"
                            property-name="holidays"
                            :emit-update-entities-per-page="true"
                            @update-page="updatePage"
                            @update-entities-per-page="changeEntitiesPerPage"
                            class="mt-6"
                        />
                    </div>
                </div>
            </section>

            <!-- Modals -->
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
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions, Switch, SwitchGroup, SwitchLabel
} from "@headlessui/vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import {router, useForm} from "@inertiajs/vue3";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import EditHolidayModal from "@/Pages/Settings/Holidays/Components/EditHolidayModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCheck, IconChevronDown, IconEdit, IconInfoCircle, IconTrash} from "@tabler/icons-vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    holidays: { type: Object, required: true },
    subdivisions: { type: Object, required: true },
    settings: { type: Object, required: true }
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
    selectedSubdivisions: reactive(
        props.subdivisions.filter(subdivision => props.settings.subdivisions.includes(subdivision.id))
    ),
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

const updateColor = (color) => { holidayForm.color = color }
const updateColorCustomHoliday = (color) => { customHolidayForm.color = color }

const submitHolidayForm = () => {
    holidayForm.post(route('holiday.api.call'), {
        preserveScroll: true,
        onSuccess: () => {
            showAPIHolidaySaved.value = true;
            setTimeout(() => { showAPIHolidaySaved.value = false }, 5000)
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
}

const removeSubDivisionFormForm = (id) => {
    showDeleteModal.value = true;
    subDivisionsToDelete.value = id;
}

const computedSubDivisions = computed(() => {
    return props.subdivisions.filter(subdivision => {
        return !holidayForm.selectedSubdivisions.find(sub => sub.id === subdivision.id) &&
            subdivision.name.toLowerCase().includes(query.value.toLowerCase())
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
        { preserveScroll: true, preserveState: true }
    );
}

const storeCustomHoliday = () => {
    // add check if date is set
    showErrorMessageDate.value = customHolidayForm.date === null;
    if (customHolidayForm.date === null) return;

    customHolidayForm.post(route('holiday.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            customHolidayForm.reset();
            showCustomHolidaySaved.value = true;
            setTimeout(() => { showCustomHolidaySaved.value = false }, 5000)
            applyFiltersAndSort();
        }
    })
}

const closeEditHolidayModal = (bool) => {
    showEditHolidayModal.value = false;
    holidayToEdit.value = null;

    if (bool) {
        showHolidayUpdatedSuccess.value = true;
        setTimeout(() => { showHolidayUpdatedSuccess.value = false }, 5000)
    }
}

const editHoliday = (holiday) => {
    holidayToEdit.value = holiday;
    showEditHolidayModal.value = true;
}

const getHolidayTreatAsSpecialDay = (holiday) => {
    if (changedHolidays.value.hasOwnProperty(holiday.id)) {
        return changedHolidays.value[holiday.id];
    }
    return holiday.treatAsSpecialDay;
}

const updateTreatAsSpecialDay = (id, checked) => {
    changedHolidays.value[id] = checked;
}

const saveChanges = () => {
    if (Object.keys(changedHolidays.value).length === 0) return;

    router.post(route('holiday.batch-update'), {
        holidays: changedHolidays.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            changedHolidays.value = {};
            showHolidayUpdatedSuccess.value = true;
            setTimeout(() => { showHolidayUpdatedSuccess.value = false }, 5000);
            applyFiltersAndSort(false);
        }
    });
}
</script>

<style scoped>
/* Optional: kleine optische Verfeinerungen für kompaktere Tabellenabstände auf Mobile */
@media (max-width: 640px) {
    table thead th { white-space: nowrap; }
}
</style>
