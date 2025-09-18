<template>
    <nav class="flex mb-5 font-lexend" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-2">
            <li>
                <div>
                    <Link preserve-scroll :href="route('inventory.index')" class="text-gray-400 hover:text-gray-500 flex items-center">
                        <component :is="IconHome" class="size-4 shrink-0" aria-hidden="true" />
                        <span class="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700 first-letter:capitalize">{{ $t('All Products') }}</span>
                    </Link>
                </div>
            </li>
            <li v-if="currentCategory?.id">
                <div class="flex items-center">
                    <component :is="IconPointFilled" class="size-4 shrink-0 text-gray-300" aria-hidden="true" />
                    <Link preserve-scroll :href="route('inventory.category.show', currentCategory?.id)"  class="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700 first-letter:capitalize">{{ currentCategory.name }}</Link>
                </div>
            </li>
            <li v-if="currentSubCategory?.id">
                <div class="flex items-center">
                    <component :is="IconPointFilled" class="size-4 shrink-0 text-gray-300" aria-hidden="true" />
                    <Link  preserve-scroll :href="route('inventory.sub.category.show', { inventoryCategory: currentCategory.id, inventorySubCategory: currentSubCategory.id})" class="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700 first-letter:capitalize">
                        {{ currentSubCategory.name }}
                    </Link>
                </div>
            </li>
        </ol>
    </nav>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";
import {IconHome, IconPointFilled} from "@tabler/icons-vue";

const props = defineProps({
    currentCategory: {
        type: Object,
        required: false
    },
    currentSubCategory: {
        type: Object,
        required: false
    }
})

</script>

<style scoped>

</style>