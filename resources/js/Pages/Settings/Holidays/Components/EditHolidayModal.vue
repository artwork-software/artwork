<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <div class="mb-5">
                <h2 class="headline2 my-2">{{ holidayToEdit.name }} {{ $t('edit')}}</h2>
                <p class="xsLight max-w-3xl">
                    {{ $t('Edit the holiday by filling in the fields. You can rename the holiday, change the date and adjust the federal states and color.') }}
                </p>
            </div>
            <div class="my-5">
                <h3 class="headline3">{{ $t('Select states & color') }}</h3>
                <p class="xsLight w-full my-2">
                    <!-- text für selbst angelegte Feiertage und deren bundesländer -->
                    {{ $t('Select the federal states that should apply to this public holiday. You can select as many federal states as you like. You can then specify a color in which the public holiday should be displayed in your calendar. You do not have to select a federal state if the public holiday applies throughout Germany.') }}
                </p>
                <div class="flex items-center gap-4 w-full">
                    <Listbox as="div" class="relative w-full" v-model="customHolidayForm.selectedSubdivisions" multiple>
                        <ListboxButton class="menu-button">
                            <div class="flex items-center justify-between w-full">
                                <div class="xsLight">
                                    {{ $t('Select federal states') }}
                                </div>
                                <div>
                                    <component :is="IconChevronDown" class="h-5 w-5" aria-hidden="true" />
                                </div>
                            </div>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                <ListboxOption as="template" v-for="subdivision in subDivisions" :key="subdivision.id" :value="subdivision" v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ subdivision.name }}</span>

                                        <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                            <component :is="IconCheck" class="h-5 w-5" aria-hidden="true" />
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>

                    <ColorPickerComponent @update-color="updateColorCustomHoliday" :color="customHolidayForm.color" />
                </div>
                <div class="w-full">
                    <div class="flex items-center flex-wrap gap-2 mt-4 w-full">
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

                </div>
                <div>
                    <BaseInput type="date" id="start" v-model="customHolidayForm.date" label="Start-Time*" />
                    <div class="text-red-500 text-xs mt-1" v-show="showErrorMessageDate">
                        {{ $t('Please select a date') }}
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
                        <div class="ml-3 text-sm/6">
                            <label for="treatAsSpecialDay" class="text-sm font-medium">{{ $t('Treat as special day') }}</label>
                        </div>
                    </div>
                </div>
                <div class="col-span-2 flex items-center justify-end">
                    <AddButtonBig :text="$t('Save')" class="w-fit" @click="storeCustomHoliday" />
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import {useForm} from "@inertiajs/vue3";
import {computed, ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";

const props = defineProps({
    holidayToEdit: {
        type: Object,
        required: true
    },
    subDivisions: {
        type: Object,
        required: true
    }
})

const emits = defineEmits(['close'])
const showErrorMessageDate = ref(false);

const customHolidayForm = useForm({
    name: props.holidayToEdit.name,
    date: props.holidayToEdit.date,
    end_date: props.holidayToEdit.end_date,
    color: props.holidayToEdit.color,
    yearly: props.holidayToEdit.yearly,
    treatAsSpecialDay: props.holidayToEdit.treatAsSpecialDay || false,
    selectedSubdivisions: props.holidayToEdit.subdivisions,
})

const storeCustomHoliday = () => {
    customHolidayForm.patch(route('holidays.update', props.holidayToEdit.id), {
        preserveScroll: true,
        onSuccess: () => {
            emits('close', true);
        }
    });
}

const updateColorCustomHoliday = (color) => {
    customHolidayForm.color = color;
}

const removeSubDivisionFormCustomHoliday = (id) => {
    customHolidayForm.selectedSubdivisions = customHolidayForm.selectedSubdivisions.filter(subdivision => subdivision.id !== id);
}

// computed filter for subDivisions to remove already selected subdivisions
const subDivisions = computed(() => {
    return props.subDivisions.filter(subdivision => !customHolidayForm.selectedSubdivisions.some(selectedSubdivision => selectedSubdivision.id === subdivision.id));
})

</script>

<style scoped>

</style>
