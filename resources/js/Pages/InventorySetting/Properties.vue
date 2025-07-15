<template>
    <InventorySettingsHeader>

        <div class="card white p-5">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold text-gray-900">
                        {{ $t('Properties') }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">
                        {{ $t('Define global settings for inventory planning.') }}
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                    <button @click="showAddEditPropertyModal = true" type="button" class="block rounded-md bg-artwork-buttons-create px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-artwork-buttons-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        {{ $t('Create Property') }}
                    </button>
                </div>
            </div>
            <div class="my-8 flow-root">
                <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="table-container">
                            <div class="overflow-x-auto">
                                <div class="inline-flex w-full">
                                    <table class="min-w-full divide-y divide-gray-300 flex-grow">
                                        <thead>
                                        <tr class="divide-x divide-gray-200">
                                            <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('Name') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Tooltip Text') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Type') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Filterable') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('In article overview') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Required field') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="property in properties.data" :key="property?.id" class="divide-x divide-gray-200">
                                            <SinglePropertyInSettings :property="property" :show-actions="false" />
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
                                            <tr v-for="property in properties.data" :key="property?.id">
                                                <SinglePropertyInSettings :property="property" :show-only-actions="true" />
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
                property-name="properties"
                :entities="properties"
            />
        </div>

        <AddEditArticlePropertyModal
            v-if="showAddEditPropertyModal"
            @close="showAddEditPropertyModal = false"
            :property="null"
            />
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {ref} from "vue";
import AddEditArticlePropertyModal from "@/Pages/InventorySetting/Components/AddEditArticlePropertyModal.vue";
import SinglePropertyInSettings from "@/Pages/InventorySetting/Components/SinglePropertyInSettings.vue";

const props = defineProps({
    properties: {
        type: Object,
        required: true
    }
})

const showAddEditPropertyModal = ref(false);

</script>

<style scoped>
.table-container {
    position: relative;
    overflow: hidden;
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
    margin-left: auto; /* Push to the right edge in flex container */
}

/* Ensure the actions column has a consistent width */
.actions-column {
    width: 100px;
    min-width: 100px;
}
</style>
