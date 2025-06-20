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
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                            <tr class="divide-x divide-gray-200">
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">Tooltip Text</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Type') }}</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Filterable') }}</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('In article overview') }}</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">{{ $t('Required field') }}</th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="property in properties.data" :key="property?.id" class="divide-x divide-gray-200">
                                <SinglePropertyInSettings :property="property" />
                            </tr>
                            </tbody>
                        </table>
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

</style>