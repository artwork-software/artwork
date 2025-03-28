<template>
    <aside class="border-r border-gray-200 py-6 pr-8 h-full font-lexend">
        <h2 class="sr-only">Filters</h2>
        <div class="">
            <div class="divide-y divide-gray-200">
                <Link preserve-scroll :href="route('inventory.index')" class="group flex items-center justify-between py-4">
                    <span class=" text-sm font-bold tracking-tight" :class="route().current('inventory.index') ? 'text-artwork-buttons-create' : ''">{{ $t('All Products') }}</span>
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 ring-1 ring-blue-500/10 ring-inset">{{ props.articlesCount }}</span>
                </Link>
                <div v-for="category in props.categories" :key="category.id" class="py-5 first:pt-0 last:pb-0">
                    <Link preserve-scroll class="flex items-center justify-between " :href="route('inventory.category.show', category.id)" :class="[route().current('inventory.category.show', category.id) ? 'text-artwork-buttons-create font-semibold' : '']">
                        <div class="first-letter:capitalize text-sm font-bold tracking-tight">
                            <div>
                                {{ category.name }}
                            </div>
                        </div>
                        <span v-if="category?.subcategories?.length > 0">
                            <component is="IconChevronDown" class="size-5" stroke-width="1" aria-hidden="true"  :class="[category.id === currentCategory?.id ? 'rotate-180' : '']" />
                        </span>
                    </Link>

                    <div v-if="category.id === currentCategory?.id" :class="currentCategory?.subcategories?.length > 0 ? 'mt-4' : ''">
                        <div v-for="subCategory in currentCategory.subcategories" :key="category.id" class="first:pt-0 last:pb-0">
                            <Link preserve-scroll :href="route('inventory.sub.category.show', {
                                            inventoryCategory: category.id,
                                            inventorySubCategory: subCategory.id
                                        })" class="flex items-center justify-between" :class="[route().current('inventory.sub.category.show', {
                                            inventoryCategory: category.id,
                                            inventorySubCategory: subCategory.id
                                        }) ? 'text-artwork-buttons-create font-semibold' : '']">
                                <div class="first-letter:capitalize text-xs px-3 py-1 flex items-center gap-x-0.5">
                                    <component is="IconPointFilled" class="size-4" stroke-width="1" aria-hidden="true" />
                                    <span class="first-letter:capitalize">
                                        {{ subCategory.name }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    articlesCount: {
        type: Number,
        required: true
    },
    currentCategory: {
        type: Object,
        required: false,
        default: []
    }
})

</script>

<style scoped>

</style>