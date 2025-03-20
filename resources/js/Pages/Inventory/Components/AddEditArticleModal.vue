<template>
    <BaseModal @closed="$emit('close')" modal-size="max-w-3xl" full-modal>
        <div class="px-6 py-4">
            <ModalHeader
                :title="article ? $t('Edit article') : $t('Add Article')"
            />
        </div>

        <form>
            <div class="px-6 pb-4">
                <div @click="addImage" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center hover:border-gray-400 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                    <component is="IconPhotoPlus" class="mx-auto size-12 text-gray-400" aria-hidden="true" />
                    <span class="mt-2 block text-sm font-semibold text-gray-900">Upload Images</span>
                    <input type="file" accept="image/*"  class="sr-only" ref="articleImageInput" multiple @input="articleForm.images = $event.target.files"/>
                </div>
                <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200">
                    <li class="flex items-center justify-between py-4 pr-5 pl-4 text-sm/6" v-for="image in articleForm.images" :key="image.id">
                        <div class="flex w-0 flex-1 items-center">
                            <component is="IconPhoto" class="size-5 shrink-0 text-gray-400" aria-hidden="true" />
                            <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                <span class="truncate font-medium">{{ image.name }}</span>
                            </div>
                        </div>
                        <div class="ml-4 shrink-0">
                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Remove</a>
                        </div>
                    </li>
                </ul>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-full">
                        <TextInputComponent
                            id="name" v-model="articleForm.name"
                            :label="$t('Name')"
                        />
                    </div>

                    <div class="col-span-full">
                        <TextareaComponent
                            id="description"
                            v-model="articleForm.description"
                            :label="$t('Description')"
                        />
                    </div>

                    <div class="col-span-full">
                        <NumberInputComponent
                            id="quantity" v-model="articleForm.quantity"
                            :label="$t('Quantity')"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-6 mb-5">

                <div class="mb-5">
                    <Listbox as="div" v-model="selectedCategory">
                        <ListboxLabel class="xsDark">
                            {{ $t('Select Category') }}
                        </ListboxLabel>
                        <div class="relative mt-2">
                            <ListboxButton class="menu-button">
                                <div class="col-start-1 row-start-1 truncate pr-6">{{ selectedCategory?.name ?? $t('Please select a Category') }}</div>
                                <component is="IconChevronUp" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4" aria-hidden="true" />
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                    <ListboxOption as="template" v-for="category in categories" :key="category.id" :value="category" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900', 'relative cursor-default py-2 pr-9 pl-3 select-none']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ category.name }}</span>

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

                <div class="pb-4" v-if="selectedCategory && selectedCategory.subcategories.length > 0">
                    <Listbox as="div" v-model="selectedSubCategory">
                        <ListboxLabel class="xsDark">
                            {{ $t('Select Sub-Category') }}
                        </ListboxLabel>
                        <div class="relative mt-2">
                            <ListboxButton class="menu-button">
                                <div class="col-start-1 row-start-1 truncate pr-6">{{ selectedSubCategory?.name ?? $t('Please select a Sub-Category') }}</div>
                                <component is="IconChevronUp" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4" aria-hidden="true" />
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                    <ListboxOption as="template" v-for="category in selectedCategory.subcategories" :key="category.id" :value="category" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900', 'relative cursor-default py-2 pr-9 pl-3 select-none']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ category.name }}</span>

                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <component is="IconCheck" class="size-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>

                    <div class="flex items-center justify-end mt-3" v-if="selectedSubCategory">
                        <div class="text-xs text-artwork-buttons-create underline underline-offset-4 hover:text-artwork-buttons-hover duration-200 ease-in-out cursor-pointer" @click="selectedSubCategory = null">{{ $t('Remove the sub-category assignment')}}</div>
                    </div>
                </div>
            </div>

            <!-- category properties -->
            <div class="my-8 flow-root px-6 pb-4" v-if="articleForm.properties.length > 0">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                            <tr class="divide-x divide-gray-200">
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Type') }}</th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('Value') }}</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="property in articleForm.properties" :key="property?.id" class="divide-x divide-gray-200">
                                <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
                                    <div class="flex items-center justify-between">
                                        {{ property?.name }}
                                        <ToolTipComponent
                                            v-if="property?.tooltip_text"
                                            :tooltip-text="property?.tooltip_text"
                                            icon="IconInfoCircle"
                                            direction="top"
                                            tooltip-width="w-48 break-words"
                                        />
                                    </div>
                                </td>
                                <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize xsLight cursor-default">
                                    {{ $t(capitalizeFirstLetter(property?.type)) }}
                                </td>
                                <td class="text-sm whitespace-nowrap text-gray-500 sm:pr-0">
                                    <input v-if="property.type !== 'file'"
                                           :type="property.type" v-model="property.value"
                                           class="block w-full rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                           :placeholder="$t('Value')"
                                    />
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm} from "@inertiajs/vue3";
import {computed, ref, watch} from "vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const props = defineProps({
    article: {
        type: Object,
        required: false,
        default: null
    },
    categories: {
        type: Object,
        required: false
    }
})

const emits = defineEmits(["close"]);
const articleImageInput = ref(null);
const selectedCategory = ref(null);
const selectedSubCategory = ref(null);

const articleForm = useForm({
    name: props.article ? props.article.name : "",
    description: props.article ? props.article.description : "",
    inventory_category_id: null,
    inventory_sub_category_id: null,
    quantity: props.article ? props.article.quantity : 0,
    images: [],
    properties: []
})

const addImage = () => {
    articleImageInput.value.click();
}

const uploadImages = (file) => {
    articleForm.images = file.files;
}

const capitalizeFirstLetter = (val) => {
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}


watch(selectedCategory, (value) => {
    if (!value || !Array.isArray(value.properties)) return;

    articleForm.inventory_category_id = value.id;
    selectedSubCategory.value = null;
    // Setze die Eigenschaften basierend auf der ausgewählten Kategorie
    articleForm.properties = value.properties.map(prop => ({
        id: prop.id,
        name: prop.name,
        tooltip_text: prop.tooltip_text,
        type: prop.type,
        value: prop.pivot?.value ?? ''
    }));
});

watch(selectedSubCategory, (value) => {
    if (!value || !Array.isArray(value.properties)) return;

    articleForm.inventory_sub_category_id = value.id;

    // Füge Eigenschaften hinzu, die nicht bereits in der Kategorie enthalten sind
    const existingPropertyIds = new Set(articleForm.properties.map(prop => prop.id));

    const newProperties = value.properties
        .filter(prop => !existingPropertyIds.has(prop.id)) // Verhindert doppelte Einträge
        .map(prop => ({
            id: prop.id,
            name: prop.name,
            tooltip_text: prop.tooltip_text,
            type: prop.type,
            value: ''
        }));

    articleForm.properties.push(...newProperties);
});
</script>

<style scoped>

</style>