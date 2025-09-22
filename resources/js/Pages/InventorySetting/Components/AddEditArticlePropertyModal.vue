<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="property ? $t('Edit article property') : $t('Add article property')"
            />
        </div>

        <div class="artwork">
            <form @submit.prevent="addEditProperty">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-full">
                        <BaseInput
                            id="name" v-model="propertyForm.name"
                            :label="$t('Name')"
                        />
                    </div>

                    <div class="col-span-full">
                        <BaseTextarea
                            id="description"
                            v-model="propertyForm.tooltip_text"
                            :label="$t('Tooltip text')"
                        />
                    </div>

                    <div class="col-span-full" v-if="(property?.type !== 'room') && (property?.type !== 'manufacturer')">
                        <Listbox as="div" v-model="selectedType" v-slot="{ open }">
                            <ListboxLabel class="block text-sm/6 font-medium text-gray-900">
                                {{ $t('Select a data type') }}
                            </ListboxLabel>
                            <div class="relative mt-2">
                                <ListboxButton class="menu-button">
                                    <div class="col-start-1 row-start-1 xsDark truncate pr-6">{{ selectedType?.name ? $t(selectedType?.name) : '' }}</div>
                                    <component :is="IconChevronDown" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4" :class="open ? 'rotate-180' : '' " aria-hidden="true" />
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                        <ListboxOption as="template" v-for="type in types" :key="type.type" :value="type" v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900', 'relative cursor-default py-2 pr-9 pl-3 select-none']">
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ $t(type.name) }}</span>

                                                <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                    <component :is="IconCheck" class="size-5" aria-hidden="true" />
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>

                    <div v-if="selectedType.type === 'selection'" class="col-span-full">
                        <div v-for="(value, index) in propertyForm.select_values" :key="index" class="flex gap-3 items-center mb-2">
                            <BaseInput
                                :id="'select_value' + index"
                                v-model="propertyForm.select_values[index]"
                                :label="$t('Selection value {index}', {index: index + 1})"
                            />
                            <button type="button" @click="propertyForm.select_values.splice(index, 1)" class="text-red-500 hover:text-red-700">
                                <component :is="IconX" class="size-5" aria-hidden="true" />
                            </button>

                        </div>

                        <div class="flex items-center justify-end">
                            <button type="button" @click="propertyForm.select_values.push('')" class="text-gray-500 text-xs hover:text-gray-700 flex items-center font-lexend">
                                <component :is="IconPlus" class="size-4" aria-hidden="true" />
                                {{ $t('Selection Add value') }}
                            </button>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="is_filterable" aria-describedby="is_filterable-description" v-model="propertyForm.is_filterable" name="is_filterable" type="checkbox" class="aw-checklist-input" />
                                    <svg class="aw-input-svg" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <div class="flex items-center">
                                    <label for="is_filterable" class="font-medium text-gray-900">Filterbar</label>
                                    <ToolTipComponent
                                        class="ml-2"
                                        :icon="IconInfoCircle"
                                        icon-size="size-4"
                                        tooltip-text="Soll nach dieser Eigenschaft gefiltert werden können?"
                                        direction="right"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="show_in_list" aria-describedby="show_in_list-description" v-model="propertyForm.show_in_list" name="show_in_list" type="checkbox" class="aw-checklist-input" />
                                    <svg class="aw-input-svg" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <div class="flex items-center">
                                    <label for="show_in_list" class="font-medium text-gray-900">In Artikelübersicht</label>
                                    <ToolTipComponent
                                        class="ml-2"
                                        :icon="IconInfoCircle"
                                        icon-size="size-4"
                                        tooltip-text="Soll diese Eigenschaft in der Artikelübersicht angezeigt werden?"
                                        direction="right"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="is_required" aria-describedby="is_required-description" v-model="propertyForm.is_required" name="is_required" type="checkbox" class="aw-checklist-input" />
                                    <svg class="aw-input-svg" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <div class="flex items-center">
                                    <label for="is_required" class="font-medium text-gray-900">Wert verpflichtend</label>
                                    <ToolTipComponent
                                        class="ml-2"
                                        :icon="IconInfoCircle"
                                        icon-size="size-4"
                                        tooltip-text="Muss dieser Wert bei der Artikelanlage angegeben werden?"
                                        direction="right"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="across_articles" aria-describedby="across_articles-description" v-model="propertyForm.across_articles" name="across_articles" type="checkbox" class="aw-checklist-input" />
                                    <svg class="aw-input-svg" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <div class="flex items-center">
                                    <label for="across_articles" class="font-medium text-gray-900">
                                        {{$t('across all articles')}}
                                    </label>
                                    <ToolTipComponent
                                        class="ml-2"
                                        :icon="IconInfoCircle"
                                        icon-size="size-4"
                                        :tooltip-text="$t('Only relevant for single inventory capable articles. A property with this active will always be set for all articles and is made for properties that are the same for all articles.')"
                                        direction="right"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="individual_value" aria-describedby="individual_value-description" v-model="propertyForm.individual_value" name="individual_value" type="checkbox" class="aw-checklist-input" />
                                    <svg class="aw-input-svg" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <div class="flex items-center">
                                    <label for="individual_value" class="font-medium text-gray-900">{{$t('individual value')}}</label>
                                    <ToolTipComponent
                                        class="ml-2"
                                        :icon="IconInfoCircle"
                                        icon-size="size-4"
                                        :tooltip-text="$t('If active, this property will not be duplicated when duplicating an article and individual values can not be edited with multiedit-functions. This is useful for properties like serial numbers that should be unique for each article.')"
                                        direction="right"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center my-10">
                    <FormButton type="submit" :text="property ? $t('Update') : $t('Create')" :disabled="propertyForm.processing || checkIfPropertyHasValues" :class="propertyForm.processing || checkIfPropertyHasValues ? 'bg-gray-200 hover:bg-gray-300' : ''" />
                </div>
            </form>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm} from "@inertiajs/vue3";
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {computed, ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconCheck, IconChevronDown, IconInfoCircle, IconPlus} from "@tabler/icons-vue";

const props = defineProps({
    property: {
        type: Object,
        required: false
    }
})

const emits = defineEmits(['close'])

const types = [
    { name: 'Text', type: 'text' },
    { name: 'Number', type: 'number' },
    { name: 'Date', type: 'date' },
    { name: 'Time', type: 'time' },
    { name: 'Datetime', type: 'datetime-local' },
    { name: 'Checkbox', type: 'checkbox' },
    { name: 'Selection', type: 'selection' },

]

const selectedType = ref(props.property ? (types.find(type => type.type === props.property.type) || { type: props.property.type, name: props.property.type }) : types[0])

const propertyForm = useForm({
    id: props.property ? props.property.id : null,
    name: props.property ? props.property.name : '',
    tooltip_text: props.property ? props.property.tooltip_text : '',
    type: props.property ? props.property.type : types[0].type,
    is_filterable: props.property ? props.property.is_filterable : false,
    show_in_list: props.property ? props.property.show_in_list : false,
    is_required: props.property ? props.property.is_required : false,
    across_articles: props.property ? props.property.across_articles : false,
    individual_value: props.property ? props.property.individual_value : false,
    select_values: props.property ? props.property.select_values : [],
})

const addEditProperty = () => {
    // For special properties like 'room' and 'manufacturer', preserve the original type
    if (props.property && (props.property.type === 'room' || props.property.type === 'manufacturer')) {
        propertyForm.type = props.property.type
    } else {
        propertyForm.type = selectedType?.value?.type
    }

    if (props.property) {
        propertyForm.patch(route('inventory-management.settings.properties.update', {inventoryArticleProperty: props.property.id}), {
            preserveScroll: true,
            onSuccess: () => {
                emits('close')
            }
        })
    } else {
        propertyForm.post(route('inventory-management.settings.properties.create'), {
            preserveScroll: true,
            onSuccess: () => {
                emits('close')
            }
        })
    }
}

const checkIfPropertyHasValues = computed(() => {
    // Skip validation for special types
    if (props.property && (props.property.type === 'room' || props.property.type === 'manufacturer')) {
        // Only check if name is set for special properties
        return propertyForm.name === ''
    }

    // if type is selection and select_values is empty return true
    if (selectedType.value?.type === 'selection' && (propertyForm.select_values.length === 0 || propertyForm.select_values[0] === '')) {
        return true
    }

    // check if name is set
    if (propertyForm.name === '') {
        return true
    }

    return false
})
</script>

<style scoped>

</style>
