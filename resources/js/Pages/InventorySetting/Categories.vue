<template>
    <InventorySettingsHeader>
        <div class="mb-10">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <TinyPageHeadline
                        :title="$t('Categories')"
                        :description="$t('Edit and create categories for your inventory.')"
                    />
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                    <button @click="showAddEditCategoryModal = true" type="button" class="block rounded-md bg-artwork-buttons-create px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-artwork-buttons-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        {{ $t('Create Category') }}
                    </button>
                </div>
            </div>

            <div class="my-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                            <tr class="divide-x divide-gray-200">
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Sub-Categories') }}</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Properties') }}</th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">Aktionen</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="category in categories.data" :key="category?.id" class="divide-x divide-gray-200">
                                <SingleCategoryInSettings :category="category" :properties="properties" />
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <BasePaginator
                property-name="categories"
                :entities="categories"
            />

            <AddEditCategoryModal
                :category="null"
                :properties="properties"
                v-if="showAddEditCategoryModal"
                @close="showAddEditCategoryModal = false"
            />

        </div>
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import AddEditCategoryModal from "@/Pages/InventorySetting/Components/AddEditCategoryModal.vue";
import {ref} from "vue";
import SingleCategoryInSettings from "@/Pages/InventorySetting/Components/SingleCategoryInSettings.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    properties: {
        type: Object,
        required: true
    }
})

const showAddEditCategoryModal = ref(false)

</script>

<style scoped>

</style>