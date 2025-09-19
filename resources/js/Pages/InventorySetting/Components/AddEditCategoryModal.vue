<template>
    <BaseModal @closed="$emit('close')" >
        <div>
            <ModalHeader
                :title="category ? $t('Edit category') : $t('Add category')"
                :description="category ? $t('') : $t('Add a new category to the inventory management system.')"
            />
        </div>

        <div>
            <form @submit.prevent="createOrUpdateCategory">
                <div class="grid grid-cols-1 gap-4 mb-8">
                    <div>
                        <div>
                            <BaseInput
                                id="name" v-model="categoryForm.name"
                                :label="$t('Category Name')"
                                required
                            />
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <TinyPageHeadline
                        :title="$t('Article properties')"
                        :description="$t('Add properties to the category that the items in this category should have.')"
                    />
                    <div class="my-4 flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead>
                                    <tr class="divide-x divide-gray-200">
                                        <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Type') }}</th>
                                        <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('Default Value') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="property in categoryForm.properties" :key="category?.id" class="divide-x divide-gray-200">
                                        <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
                                            <div class="flex items-center justify-between">
                                                {{ property?.name }}
                                                <button type="button" @click="removePropertyFromCategory(property)" class="text-red-600 hover:text-red-900">
                                                    <component :is="IconTrash" class="h-5 w-5" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize xsLight cursor-default">
                                            {{ $t(capitalizeFirstLetter(property?.type)) }}
                                        </td>
                                        <td class="text-sm whitespace-nowrap text-gray-500 sm:pr-0">
                                            <input v-if="property.type !== 'file' && property.type !== 'checkbox' && property.type !== 'room' && property.type !== 'manufacturer' && property.type !== 'selection'"
                                                   :type="property.type" v-model="property.defaultValue"
                                                   class="block w-full rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                                   :placeholder="$t('Default Value')"
                                            />

                                            <div v-if="property.type === 'checkbox'" class="px-3">
                                                <input type="checkbox" v-model="property.defaultValue"
                                                       class="input-checklist"/>
                                            </div>

                                            <Combobox v-if="property.type === 'room'" as="div" v-model="property.defaultValue"
                                                  @update:modelValue="roomQuery = ''">
                                                <div class="relative">
                                                    <ComboboxInput
                                                        class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-xs text-gray-900 placeholder:text-gray-400"
                                                        @change="roomQuery = $event.target.value" @blur="roomQuery = ''"
                                                        :display-value="(person) => property.defaultValue ? props.rooms?.find((room) => room.id === parseInt(property.defaultValue) )?.name : ''"/>
                                                    <ComboboxButton
                                                        class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                        <component :is="IconSelector" class="size-5 text-gray-400"
                                                                   aria-hidden="true"/>
                                                    </ComboboxButton>

                                                    <ComboboxOptions v-if="filteredRooms?.length > 0"
                                                                     class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-xs ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                        <ComboboxOption v-for="room in filteredRooms" :key="room.id"
                                                                        :value="room.id" as="template"
                                                                        v-slot="{ active, selected }">
                                                            <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                    <span
                                                                        :class="['block truncate', selected && 'font-semibold']">
                                                                      {{ room.name }}
                                                                    </span>
                                                                <span v-if="selected"
                                                                      :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                      <component :is="IconCheck" class="size-5"
                                                                                 aria-hidden="true"/>
                                                                    </span>
                                                            </li>
                                                        </ComboboxOption>
                                                    </ComboboxOptions>
                                                </div>
                                            </Combobox>

                                            <Combobox v-if="property.type === 'manufacturer'" as="div"
                                                  v-model="property.defaultValue" @update:modelValue="manufacturerQuery = ''">
                                                <div class="relative">
                                                    <ComboboxInput
                                                        class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-xs text-gray-900 placeholder:text-gray-400"
                                                        @change="manufacturerQuery = $event.target.value"
                                                        @blur="manufacturerQuery = ''"
                                                        :display-value="(person) => property.defaultValue ? props.manufacturers?.find((manufacturer) => manufacturer.id === parseInt(property.defaultValue) )?.name : ''"/>
                                                    <ComboboxButton
                                                        class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                        <component :is="IconSelector" class="size-5 text-gray-400"
                                                                   aria-hidden="true"/>
                                                    </ComboboxButton>

                                                    <ComboboxOptions v-if="filteredManufacturers?.length > 0"
                                                                     class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-xs ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                        <ComboboxOption v-for="manufacturer in filteredManufacturers"
                                                                        :key="manufacturer.id" :value="manufacturer.id" as="template"
                                                                        v-slot="{ active, selected }">
                                                            <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                    <span
                                                                        :class="['block truncate', selected && 'font-semibold']">
                                                                      {{ manufacturer.name }}
                                                                    </span>
                                                                <span v-if="selected"
                                                                      :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                      <component :is="IconCheck" class="size-5"
                                                                                 aria-hidden="true"/>
                                                                    </span>
                                                            </li>
                                                        </ComboboxOption>
                                                    </ComboboxOptions>
                                                </div>
                                            </Combobox>

                                            <div v-if="property.type === 'selection'" class="">
                                                <div class="mt-2 grid grid-cols-1">
                                                    <select id="location" name="location" v-model="property.defaultValue" class="block w-full rounded-md bg-white border-none text-xs py-1.5 cursor-pointer text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0">
                                                        <option v-for="value in property.select_values" :value="value" :key="value">{{ value }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="divide-x divide-gray-200">
                                        <td colspan="3" class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
                                            <PropertiesMenu white-menu-background has-no-offset>
                                                <template v-slot:button>
                                                    <div class="flex items-center gap-x-2 text-gray-400 font-lexend font-bold cursor-pointer hover:text-gray-600 duration-200 ease-in-out">
                                                        <component :is="IconLibraryPlus" class="h-5 w-5" aria-hidden="true" />
                                                        <span>
                                                            {{ $t('Add property') }}
                                                        </span>
                                                    </div>
                                                </template>
                                                <template v-slot:menu>
                                                    <div v-for="property in properties">
                                                        <div @click="addPropertyToCategory(property)" class="px-4 py-3 cursor-pointer hover:bg-gray-50 rounded-lg duration-200 ease-in-out">
                                                            <div class="xsDark">
                                                                {{ property.name }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </PropertiesMenu>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-start justify-between">
                        <TinyPageHeadline
                            :title="$t('Sub-Categories')"
                            :description="$t('Add sub-categories to the category.')"
                        />
                        <div class="flex items-center gap-x-2 text-gray-400 text-sm font-lexend font-bold cursor-pointer hover:text-gray-600 duration-200 ease-in-out" @click="addEmptySubCategory">
                            <component :is="IconCategoryPlus" class="size-4" aria-hidden="true" />
                            <span>
                                {{ $t('Add sub-category') }}
                            </span>
                        </div>
                    </div>

                    <div class="my-4">
                        <div class="w-full">
                            <Disclosure v-for="(subCategory, index) in subCategories" v-slot="{ open }" as="div" class="mt-1">
                                <DisclosureButton class="flex w-full justify-between bg-gray-50 px-2 py-3 rounded-lg text-left text-sm font-medium  hover:bg-gray-200 focus:outline-none focus-visible:ring focus-visible:ring-purple-500/75">
                                    <div class="flex items-center gap-x-2 justify-between w-full pr-5">
                                        <span v-if="subCategory.name">{{ subCategory.name }}</span>
                                        <span v-else>{{ $t('Sub-Category without name. Please add a name')}}</span>

                                        <div>
                                            <component :is="IconTrash" class="h-5 w-5 text-red-600 hover:text-red-900 cursor-pointer" @click="removeSubCategoryFromCategory(subCategory)" />
                                        </div>
                                    </div>
                                    <component :is="IconChevronUp"
                                        :class="open ? 'rotate-180 transform' : ''"
                                        class="h-5 w-5 text-artwork-buttons-context"
                                    />
                                </DisclosureButton>
                                <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                                    <div class="grid grid-cols-1 gap-4 mb-8">
                                        <div>
                                            <div>
                                                <BaseInput
                                                    :id="'subName' + index" v-model="subCategory.name"
                                                    :label="$t('Sub-Category Name')"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <TinyPageHeadline
                                            :title="$t('Article properties')"
                                            :description="$t('Add properties to the category that the items in this category should have.')"
                                        />
                                        <div class="my-4 flow-root">
                                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                                    <table class="min-w-full divide-y divide-gray-300">
                                                        <thead>
                                                        <tr class="divide-x divide-gray-200">
                                                            <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Type') }}</th>
                                                            <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('Default Value') }}</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 bg-white">
                                                        <tr v-for="property in subCategory.properties" :key="property?.id" class="divide-x divide-gray-200">
                                                            <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
                                                                <div class="flex items-center justify-between">
                                                                    {{ property?.name }}
                                                                    <button type="button" @click="removePropertyFromSubCategory(property, subCategory)" class="text-red-600 hover:text-red-900">
                                                                        <component :is="IconTrash" class="h-5 w-5" aria-hidden="true" />
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize xsLight cursor-default">
                                                                {{ $t(capitalizeFirstLetter(property?.type)) }}
                                                            </td>
                                                            <td class="text-sm whitespace-nowrap text-gray-500 sm:pr-0">
                                                                <input v-if="property.type !== 'file' && property.type !== 'checkbox' && property.type !== 'room' && property.type !== 'manufacturer' && property.type !== 'selection'"
                                                                       :type="property.type" v-model="property.defaultValue"
                                                                       class="block w-full rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                                                       :placeholder="$t('Default Value')"
                                                                />

                                                                <div v-if="property.type === 'checkbox'" class="px-3">
                                                                    <input type="checkbox" v-model="property.defaultValue"
                                                                           class="input-checklist"/>
                                                                </div>

                                                                <Combobox v-if="property.type === 'room'" as="div" v-model="property.defaultValue"
                                                                      @update:modelValue="roomQuery = ''">
                                                                    <div class="relative">
                                                                        <ComboboxInput
                                                                            class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-xs text-gray-900 placeholder:text-gray-400"
                                                                            @change="roomQuery = $event.target.value" @blur="roomQuery = ''"
                                                                            :display-value="(person) => property.defaultValue ? props.rooms?.find((room) => room.id === parseInt(property.defaultValue) )?.name : ''"/>
                                                                        <ComboboxButton
                                                                            class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                                            <component :is="IconSelector" class="size-5 text-gray-400"
                                                                                       aria-hidden="true"/>
                                                                        </ComboboxButton>

                                                                        <ComboboxOptions v-if="filteredRooms?.length > 0"
                                                                                         class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-xs ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                                            <ComboboxOption v-for="room in filteredRooms" :key="room.id"
                                                                                            :value="room.id" as="template"
                                                                                            v-slot="{ active, selected }">
                                                                                <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                                        <span
                                                                                            :class="['block truncate', selected && 'font-semibold']">
                                                                                          {{ room.name }}
                                                                                        </span>
                                                                                    <span v-if="selected"
                                                                                          :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                                          <component :is="IconCheck" class="size-5"
                                                                                                     aria-hidden="true"/>
                                                                                        </span>
                                                                                </li>
                                                                            </ComboboxOption>
                                                                        </ComboboxOptions>
                                                                    </div>
                                                                </Combobox>

                                                                <Combobox v-if="property.type === 'manufacturer'" as="div"
                                                                      v-model="property.defaultValue" @update:modelValue="manufacturerQuery = ''">
                                                                    <div class="relative">
                                                                        <ComboboxInput
                                                                            class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-xs text-gray-900 placeholder:text-gray-400"
                                                                            @change="manufacturerQuery = $event.target.value"
                                                                            @blur="manufacturerQuery = ''"
                                                                            :display-value="(person) => property.defaultValue ? props.manufacturers?.find((manufacturer) => manufacturer.id === parseInt(property.defaultValue) )?.name : ''"/>
                                                                        <ComboboxButton
                                                                            class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                                            <component :is="IconSelector" class="size-5 text-gray-400"
                                                                                       aria-hidden="true"/>
                                                                        </ComboboxButton>

                                                                        <ComboboxOptions v-if="filteredManufacturers?.length > 0"
                                                                                         class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-xs ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                                            <ComboboxOption v-for="manufacturer in filteredManufacturers"
                                                                                            :key="manufacturer.id" :value="manufacturer.id" as="template"
                                                                                            v-slot="{ active, selected }">
                                                                                <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                                        <span
                                                                                            :class="['block truncate', selected && 'font-semibold']">
                                                                                          {{ manufacturer.name }}
                                                                                        </span>
                                                                                    <span v-if="selected"
                                                                                          :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                                          <component :is="IconCheck" class="size-5"
                                                                                                     aria-hidden="true"/>
                                                                                        </span>
                                                                                </li>
                                                                            </ComboboxOption>
                                                                        </ComboboxOptions>
                                                                    </div>
                                                                </Combobox>

                                                                <div v-if="property.type === 'selection'" class="">
                                                                    <div class="mt-2 grid grid-cols-1">
                                                                        <select id="location" name="location" v-model="property.defaultValue" class="block w-full rounded-md bg-white border-none text-xs py-1.5 cursor-pointer text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0">
                                                                            <option v-for="value in property.select_values" :value="value" :key="value">{{ value }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr class="divide-x divide-gray-200">
                                                            <td colspan="3" class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
                                                                <PropertiesMenu white-menu-background has-no-offset>
                                                                    <template v-slot:button>
                                                                        <div class="flex items-center gap-x-2 text-gray-400 font-lexend font-bold cursor-pointer hover:text-gray-600 duration-200 ease-in-out">
                                                                            <component :is="IconLibraryPlus" class="h-5 w-5" aria-hidden="true" />
                                                                            <span>
                                                                                {{ $t('Add property') }}
                                                                            </span>
                                                                        </div>
                                                                    </template>
                                                                    <template v-slot:menu>
                                                                        <div v-for="property in properties">
                                                                            <div @click="addPropertyToSubCategory(property, subCategory)" class="px-4 py-3 cursor-pointer hover:bg-gray-50 rounded-lg duration-200 ease-in-out">
                                                                                <div class="xsDark">
                                                                                    {{ property.name }}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </PropertiesMenu>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </DisclosurePanel>
                            </Disclosure>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center my-3">
                    <FormButton type="submit" :text="category ? $t('Update') : $t('Create')" :disabled="categoryForm.processing || checkIfAllSubCategoriesHasName" :class="categoryForm.processing ? 'bg-gray-200 hover:bg-gray-300' : ''" />
                </div>
            </form>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {router, useForm} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import PropertiesMenu from "@/Components/Menu/PropertiesMenu.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {computed, onMounted, ref} from "vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {Disclosure, DisclosureButton, DisclosurePanel, Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions} from "@headlessui/vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCategoryPlus, IconCheck, IconChevronUp, IconLibraryPlus, IconSelector, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    category: {
        type: Object,
        required: false
    },
    properties: {
        type: Object,
        required: false
    },
    rooms: {
        type: Object,
        required: false
    },
    manufacturers: {
        type: Object,
        required: false
    }
})

const categoryForm = useForm({
    id: props.category ? props.category.id : null,
    name: props.category ? props.category.name : '',
    properties: [],
    subcategories: props.category ? props.category.subcategories : []
})

const subCategories = ref([]);
const roomQuery = ref('');
const manufacturerQuery = ref('');

const emit = defineEmits(["close"]);

const filteredRooms = computed(() =>
    roomQuery.value === ''
        ? props.rooms
        : props.rooms?.filter((room) => {
            return room.name.toLowerCase().includes(roomQuery.value.toLowerCase())
        }),
);

const filteredManufacturers = computed(() =>
    manufacturerQuery.value === ''
        ? props.manufacturers
        : props.manufacturers?.filter((manufacturer) => {
            return manufacturer.name.toLowerCase().includes(manufacturerQuery.value.toLowerCase())
        }),
);

const addEmptySubCategory = () => {
    subCategories.value.push({
        id: null,
        name: '',
        properties: []
    });
};

const addPropertyToCategory = (property) => {
    if (!categoryForm.properties) {
        categoryForm.properties = [];
    }

    if (!categoryForm.properties.some(p => p.id === property.id)) {
        categoryForm.properties.push({
            id: property.id,
            name: property.name,
            type: property.type,
            select_values: property.select_values,
            defaultValue: property.type === 'checkbox' ? false : ''
        });
    }
};

const addPropertyToSubCategory = (property, subCategory) => {
    if (!subCategory.properties) {
        subCategory.properties = [];
    }

    if (!subCategory.properties.some(p => p.id === property.id)) {
        subCategory.properties.push({
            id: property.id,
            name: property.name,
            type: property.type,
            select_values: property.select_values,
            defaultValue: property.type === 'checkbox' ? false : ''
        });
    }
};

onMounted(() => {
    if (props.category) {
        categoryForm.properties = props.category.properties.map(property => {
            let defaultValue = property.pivot.value ?? '';

            // Convert string checkbox values to booleans
            if (property.type === 'checkbox') {
                defaultValue = defaultValue === 'true' ? true :
                              defaultValue === 'false' ? false :
                              defaultValue === '' ? false : defaultValue;
            }

            return {
                id: property.id,
                name: property.name,
                type: property.type,
                select_values: property.select_values,
                defaultValue: defaultValue
            }
        });

        // add sub categories to subCategories from props.category if they exist with property values and default values
        subCategories.value = props.category.subcategories.map(subCategory => {
            return {
                id: subCategory.id,
                name: subCategory.name,
                properties: subCategory.properties ? subCategory.properties.map(property => {
                    let defaultValue = property.pivot.value ?? '';

                    // Convert string checkbox values to booleans
                    if (property.type === 'checkbox') {
                        defaultValue = defaultValue === 'true' ? true :
                                      defaultValue === 'false' ? false :
                                      defaultValue === '' ? false : defaultValue;
                    }

                    return {
                        id: property.id,
                        name: property.name,
                        type: property.type,
                        select_values: property.select_values,
                        defaultValue: defaultValue
                    }
                }) : []
            }
        });
    }
})

const checkIfAllSubCategoriesHasName = computed(() => {
    return subCategories.value.some(subCategory => !subCategory.name);
})

const removePropertyFromCategory = (property) => {
    categoryForm.properties = categoryForm.properties.filter(p => p.id !== property.id);
};

const removePropertyFromSubCategory = (property, subCategory) => {
    subCategory.properties = subCategory.properties.filter(p => p.id !== property.id);
};

const capitalizeFirstLetter = (val) => {
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}

const createOrUpdateCategory = () => {
    // Convert boolean checkbox values to strings for category properties
    if (categoryForm.properties && categoryForm.properties.length > 0) {
        categoryForm.properties.forEach(property => {
            if (property.type === 'checkbox' && typeof property.defaultValue === 'boolean') {
                property.defaultValue = property.defaultValue ? 'true' : 'false';
            }
        });
    }

    // Convert boolean checkbox values to strings for subcategory properties
    const processedSubCategories = subCategories.value.map(subCategory => {
        const processedSubCategory = {...subCategory};
        if (processedSubCategory.properties && processedSubCategory.properties.length > 0) {
            processedSubCategory.properties = processedSubCategory.properties.map(property => {
                const processedProperty = {...property};
                if (processedProperty.type === 'checkbox' && typeof processedProperty.defaultValue === 'boolean') {
                    processedProperty.defaultValue = processedProperty.defaultValue ? 'true' : 'false';
                }
                return processedProperty;
            });
        }
        return processedSubCategory;
    });

    categoryForm.subcategories = processedSubCategories;

    if (categoryForm.id) {
        categoryForm.patch(route('inventory-management.settings.categories.update', {inventoryCategory: categoryForm.id}), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            }
        });
    } else {
        categoryForm.post(route('inventory-management.settings.categories.create'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            }
        });
    }
}

const removeSubCategoryFromCategory = (subCategory) => {
    if ( subCategory.id) {
        router.delete(route('inventory-management.settings.categories.subcategories.delete', {inventorySubCategory: subCategory.id}), {
            preserveScroll: true,
            onSuccess: () => {
                subCategories.value = subCategories.value.filter(sub => sub.id !== subCategory.id);
            }
        });
    } else {
        subCategories.value = subCategories.value.filter(sub => sub.id !== subCategory.id);
    }
}
</script>

<style scoped>

</style>
