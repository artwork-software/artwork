<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import SingleArticleStatus from "@/Pages/InventorySetting/Components/SingleArticleStatus.vue";

const props = defineProps({
    statuses: {
        type: Object,
        required: true
    }
})
</script>

<template>
    <InventorySettingsHeader>
        <div class="mb-10 card white p-5">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <TinyPageHeadline
                        :title="$t('Status Settings')"
                        :description="$t('Manage your article status settings here.')"
                    />
                </div>
            </div>


            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-white/15">
                            <thead>
                            <tr class="divide-x divide-gray-200 dark:divide-white/10">
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0 dark:text-white">Name</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $t('Color') }}
                                </th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0 dark:text-white">
                                    {{ $t('Actions') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                            <tr v-for="status in statuses" :key="status.id" class="divide-x divide-gray-200 dark:divide-white/10">
                                <SingleArticleStatus :status="status" />
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </InventorySettingsHeader>
</template>

<style scoped>

</style>
