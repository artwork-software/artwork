<template>
    <InventorySettingsHeader>

        <div class="mb-10">
            <div class="mb-5">
                <TinyPageHeadline
                    :title="$t('Categories')"
                    :description="$t('Edit and create categories for your inventory.')"
                />
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
                                <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">{{ category?.name }}</td>
                                <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize">
                                    {{ category.subcategories.map((category) => category.name).join(', ') }}
                                </td>
                                <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize">
                                    {{ category.properties.map((property) => property.name).join(', ') }}
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

            <pre>
                categories {{ categories }}
            </pre>
        </div>
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
})

</script>

<style scoped>

</style>