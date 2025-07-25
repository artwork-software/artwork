<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="property ? $t('Edit article property') : $t('Add article property')"
            />
        </div>

        <div>
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
                                    <component is="IconChevronDown" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4" :class="open ? 'rotate-180' : '' " aria-hidden="true" />
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                        <ListboxOption as="template" v-for="type in types" :key="type.type" :value="type" v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900', 'relative cursor-default py-2 pr-9 pl-3 select-none']">
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ $t(type.name) }}</span>

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

                    <div v-if="selectedType.type === 'selection'" class="col-span-full">
                        <div v-for="(value, index) in propertyForm.select_values" :key="index" class="flex gap-3 items-center mb-2">
                            <BaseInput
                                :id="'select_value' + index"
                                v-model="propertyForm.select_values[index]"
                                :label="$t('Selection value {index}', {index: index + 1})"
                            />
                            <button type="button" @click="propertyForm.select_values.splice(index, 1)" class="text-red-500 hover:text-red-700">
                                <component is="IconX" class="size-5" aria-hidden="true" />
                            </button>

                        </div>

                        <div class="flex items-center justify-end">
                            <button type="button" @click="propertyForm.select_values.push('')" class="text-gray-500 text-xs hover:text-gray-700 flex items-center font-lexend">
                                <component is="IconPlus" class="size-4" aria-hidden="true" />
                                {{ $t('Selection Add value') }}
                            </button>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="is_filterable" aria-describedby="is_filterable-description" v-model="propertyForm.is_filterable" name="is_filterable" type="checkbox" class="input-checklist" />
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="is_filterable" class="font-medium text-gray-900">Filterbar</label>
                                <p id="is_filterable-description" class="text-gray-500">
                                    Soll nach dieser Eigenschaft gefiltert werden können?
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="show_in_list" aria-describedby="show_in_list-description" v-model="propertyForm.show_in_list" name="show_in_list" type="checkbox" class="input-checklist" />
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="show_in_list" class="font-medium text-gray-900">In Artikelübersicht</label>
                                <p id="show_in_list-description" class="text-gray-500">
                                    Soll diese Eigenschaft in der Artikelübersicht angezeigt werden?
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="is_required" aria-describedby="is_required-description" v-model="propertyForm.is_required" name="is_required" type="checkbox" class="input-checklist" />
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="is_required" class="font-medium text-gray-900">Wert verpflichtend</label>
                                <p id="is_required-description" class="text-gray-500">
                                    Muss dieser Wert bei der Artikelanlage angegeben werden?
                                </p>
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
    { name: 'Datetime', type: 'datetime' },
    { name: 'Checkbox', type: 'checkbox' },
    { name: 'Selection', type: 'selection' },
    //{ name: 'Upload', type: 'file' },
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
