<template>
    <ArtworkBaseModal
        title="Search article categories"
        description="Select a category to view and add articles."
        @close="$emit('close')"
    >

        <Listbox as="div" v-model="selectedCategory">
            <ListboxLabel class="xsDark">
                {{ $t('Select Category') }}
            </ListboxLabel>
            <div class="relative mt-2">
                <ListboxButton class="menu-button bg-white">
                    <div class="col-start-1 row-start-1 truncate pr-6">
                        {{ selectedCategory?.name ?? $t('Please select a Category') }}
                    </div>
                    <component :is="IconChevronUp"
                               class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4"
                               aria-hidden="true"/>
                </ListboxButton>

                <transition leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <ListboxOptions
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                        <ListboxOption as="template" v-for="category in categories" :key="category.id"
                                       :value="category" v-slot="{ active, selected }">
                            <li :class="[active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900', 'relative cursor-default py-2 pr-9 pl-3 select-none']">
                                            <span
                                                :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{
                                                    category.name
                                                }}</span>

                                <span v-if="selected"
                                      :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <component :is="IconCheck" class="size-5" aria-hidden="true"/>
                                            </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>


        <div class="my-5">
            <div class="flex items-center justify-between py-2 px-3 rounded-lg cursor-pointer border-dashed hover:bg-gray-50" v-for="(article, index) in selectedCategory?.articles" :key="index">
                <div @click="$emit('add-article', article)">
                    <h2 class="text-sm font-bold">{{ article.name }}</h2>
                    <p class="text-xs">{{ article.description }}</p>
                    <p class="text-xs">
                        <span class="font-bold">{{ $t('Category') }}:</span>
                        {{ article.category.name }}
                        <span v-if="article.sub_category" class="font-bold">{{ $t('Sub-Category' )}}: </span>
                        <span v-if="article.sub_category">{{ article.sub_category.name }}</span>
                    </p>
                </div>
            </div>
        </div>


    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {onMounted, ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCheck, IconChevronUp} from "@tabler/icons-vue";

const props = defineProps({})

const emits = defineEmits(['close', 'add-article'])

const selectedCategory = ref(null)
const categories = ref([])
onMounted(() => {
    // Fetch categories from API or props
    fetchCategories()
})

const fetchCategories = async () => {
    try {
        const response = await axios.get(route('inventory.categories.get-all'))
        categories.value = response.data.categories
    } catch (error) {
        console.error('Error fetching categories:', error)
    }
}
</script>

<style scoped>

</style>