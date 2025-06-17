<template>
    <aside class="h-full font-lexend">
        <h2 class="sr-only">Filters</h2>
        <BaseCard>
            <div class="p-5">
                <div class="">
                    <div class="space-y-4">
                        <WhiteInnerCard>
                            <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
                                <div class="p-1 rounded-lg w-1 bg-gray-500"></div>
                                <Link preserve-scroll :href="route('inventory.index')" class="group flex items-center justify-between w-full">
                                    <span class=" text-sm font-bold tracking-tight" :class="route().current('inventory.index') ? 'text-artwork-buttons-create' : ''">{{ $t('All articles') }}</span>
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 ring-1 ring-blue-500/10 ring-inset">{{ props.articlesCount }}</span>
                                </Link>
                            </div>

                        </WhiteInnerCard>
                        <div v-for="category in props.categories" :key="category.id" class="first:pt-0 last:pb-0">
                            <WhiteInnerCard>
                                <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
                                    <div class="p-1 rounded-lg w-1 bg-artwork-buttons-create"></div>
                                    <div class="w-full">
                                        <Link preserve-scroll class="flex items-center w-full justify-between" :href="route('inventory.category.show', category.id)" :class="[route().current('inventory.category.show', category.id) ? 'text-artwork-buttons-create font-semibold' : '']">
                                            <div class="first-letter:capitalize text-sm font-bold tracking-tight max-w-64">
                                                <div>
                                                    {{ category.name }}
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-x-2">
                                                <span v-if="category?.subcategories?.length > 0">
                                                    <component is="IconChevronDown" class="size-5" stroke-width="1" aria-hidden="true"  :class="[category.id === currentCategory?.id ? 'rotate-180' : '']" />
                                                </span>
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 ring-1 ring-blue-500/10 ring-inset">{{ category.articles?.length || 0 }}</span>
                                            </div>
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
                                                    <div class="first-letter:capitalize text-xs pl-2 py-1 flex items-center justify-between w-full">
                                                        <div class="flex items-center gap-x-0.5">
                                                            <component is="IconPointFilled" class="size-4" stroke-width="1" aria-hidden="true" />
                                                            <span class="first-letter:capitalize">
                                                                {{ subCategory.name }}
                                                            </span>
                                                        </div>
                                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 ring-1 ring-blue-500/10 ring-inset">{{ subCategory.articles?.length }}</span>
                                                    </div>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </WhiteInnerCard>
                        </div>
                    </div>
                </div>
            </div>
        </BaseCard>
    </aside>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";

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
