<template>
    <InventorySettingsHeader>

        <div class="">
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
                    <button type="button" class="block rounded-md bg-artwork-buttons-create px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-artwork-buttons-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
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
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">Filterbar</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">In Artikelübersicht</th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">Aktionen</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="property in properties.data" :key="property?.id" class="divide-x divide-gray-200">
                                <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">{{ property?.name }}</td>
                                <td class="p-4 text-sm whitespace-nowrap text-gray-500">{{ $t(property?.type) }}</td>
                                <td class="p-4 text-sm whitespace-nowrap text-gray-500">
                                    <span v-if="property?.is_filterable" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset">{{ $t('Yes') }}</span>
                                    <span v-else class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset">{{ $t('No') }}</span>
                                </td>
                                <td class="p-4 text-sm whitespace-nowrap text-gray-500">
                                    <span v-if="property?.show_in_list" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset">{{ $t('Yes') }}</span>
                                    <span v-else class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset">{{ $t('No') }}</span>
                                </td>
                                <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0">
                                    <div class="flex items-center gap-x-4">
                                        <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover">
                                            <component is="IconEdit" class="h-5 w-5" aria-hidden="true" />
                                        </button>
                                        <button type="button" class="text-red-600 hover:text-red-900">
                                            <component is="IconTrash" class="h-5 w-5" aria-hidden="true" />
                                        </button>

                                    </div>
                                </td>
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
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

const props = defineProps({
    properties: {
        type: Object,
        required: true
    }
})

</script>

<style scoped>

</style>