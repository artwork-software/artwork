<template>
    <InventorySettingsHeader
        :title="$t('Categories')"
        :description="$t('Edit and create categories for your inventory.')"
    >
        <template #actions>
            <button class="ui-button-add" @click="showAddEditCategoryModal = true">
                <component :is="IconPlus" stroke-width="1" class="size-5" />
                {{ $t('Create Category') }}
            </button>
        </template>

        <div class="mb-10 card white p-5">

            <div class="my-8 flow-root">
                <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="table-container ">
                            <div class="overflow-x-auto w-full">
                                <div class="inline-flex w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg xl:max-w-xl 2xl:max-w-full">
                                    <table class="min-w-0 w-full divide-y divide-gray-300 flex-grow overflow-hidden">
                                        <thead>
                                        <tr class="divide-x divide-gray-200">
                                            <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Sub-Categories') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Properties') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="category in categories.data" :key="category?.id" class="divide-x divide-gray-200">
                                            <SingleCategoryInSettings :category="category" :properties="properties" :rooms="rooms" :manufacturers="manufacturers" :show-actions="false" />
                                        </tr>
                                        </tbody>
                                    </table>

                                    <!-- Fixed Actions Column -->
                                    <div class="fixed-actions-column">
                                        <table class="h-full divide-y divide-gray-300">
                                            <thead>
                                            <tr>
                                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0 bg-white">{{ $t('Actions') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="category in categories.data" :key="category?.id">
                                                <SingleCategoryInSettings :category="category" :properties="properties" :rooms="rooms" :manufacturers="manufacturers" :show-only-actions="true" />
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                :rooms="rooms"
                :manufacturers="manufacturers"
                v-if="showAddEditCategoryModal"
                @close="showAddEditCategoryModal = false"
            />

        </div>
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import AddEditCategoryModal from "@/Pages/InventorySetting/Components/AddEditCategoryModal.vue";
import {ref} from "vue";
import SingleCategoryInSettings from "@/Pages/InventorySetting/Components/SingleCategoryInSettings.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {IconPlus} from "@tabler/icons-vue";

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    properties: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    manufacturers: {
        type: Object,
        required: true
    }
})

const showAddEditCategoryModal = ref(false)

</script>

<style scoped>
.table-container {
    position: relative;
    overflow: hidden;
    width: 100%;
}

/* Add a shadow to the fixed actions column */
.fixed-actions-column {
    position: sticky;
    top: 0;
    right: 0;
    height: 100%;
    background-color: white;
    box-shadow: -4px 0 6px -2px rgba(0, 0, 0, 0.05);
    border-left: 1px solid #e5e7eb;
    z-index: 10;
    width: 100px;
    min-width: 100px;
    flex-shrink: 0; /* Prevent the column from shrinking */
    margin-left: auto; /* Push to the right edge in flex container */
}

/* Ensure the actions column has a consistent width */
.actions-column {
    width: 100px;
    min-width: 100px;
}
</style>
