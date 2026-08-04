<template>
    <aside class="h-full">
        <h2 class="sr-only">Filters</h2>
        <BaseCard>
            <div class="">
                <div class="">
                    <div class="space-y-4">
                        <WhiteInnerCard>
                            <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
                                <div class="p-1 rounded-lg w-1 bg-text-subtle"></div>
                                <Link preserve-scroll :href="route('inventory.index', linkQuery)" class="group flex items-center justify-between w-full">
                                    <span class=" text-sm font-bold tracking-tight" :class="route().current('inventory.index') ? 'text-artwork-buttons-create' : ''">{{ $t('All articles') }}</span>
                                    <span class="inline-flex items-center rounded-md bg-accent-50 px-2 py-1 text-xs font-medium text-accent-600 ring-1 ring-accent-600 ring-inset">{{ props.articlesCount }}</span>
                                </Link>
                            </div>

                        </WhiteInnerCard>
                        <div v-for="category in visibleCategories" :key="category.id" class="first:pt-0 last:pb-0">
                            <WhiteInnerCard>
                                <div class="flex items-stretch gap-x-3 min-w-full w-full h-full p-4">
                                    <div class="p-1 rounded-lg w-1 bg-artwork-buttons-create"></div>
                                    <div class="w-full">
                                        <Link preserve-scroll class="flex items-center w-full justify-between" :href="route('inventory.category.show', { inventoryCategory: category.id, ...linkQuery })" :class="[route().current('inventory.category.show', category.id) ? 'text-artwork-buttons-create font-semibold' : '']">
                                            <div class="first-letter:capitalize text-sm font-bold tracking-tight max-w-64">
                                                <div>
                                                    {{ category.name }}
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-x-2">
                                                <span v-if="category?.subcategories?.length > 0">
                                                    <component :is="IconChevronDown" class="size-5" stroke-width="1" aria-hidden="true"  :class="[category.id === currentCategory?.id ? 'rotate-180' : '']" />
                                                </span>
                                                <span class="inline-flex items-center rounded-md bg-accent-50 px-2 py-1 text-xs font-medium text-accent-600 ring-1 ring-accent-600 ring-inset">{{ category.articles?.length || 0 }}</span>
                                            </div>
                                        </Link>
                                        <div v-if="category.id === currentCategory?.id" :class="currentCategory?.subcategories?.length > 0 ? 'mt-4' : ''">
                                            <div v-for="subCategory in visibleSubCategories" :key="subCategory.id" class="first:pt-0 last:pb-0">
                                                <Link preserve-scroll :href="route('inventory.sub.category.show', {
                                                     inventoryCategory: category.id,
                                                           inventorySubCategory: subCategory.id,
                                                           ...linkQuery
                                                        })" class="flex items-center justify-between" :class="[route().current('inventory.sub.category.show', { inventoryCategory: category.id,
                                                            inventorySubCategory: subCategory.id
                                                        }) ? 'text-artwork-buttons-create font-semibold' : '']">
                                                    <div class="first-letter:capitalize text-xs pl-2 py-1 flex items-center justify-between w-full">
                                                        <div class="flex items-center gap-x-0.5">
                                                            <component :is="IconPointFilled" class="size-4" stroke-width="1" aria-hidden="true" />
                                                            <span class="first-letter:capitalize">
                                                                {{ subCategory.name }}
                                                            </span>
                                                        </div>
                                                        <span class="inline-flex items-center rounded-md bg-accent-50 px-2 py-1 text-xs font-medium text-accent-600 ring-1 ring-accent-600 ring-inset">{{ subCategory.articles?.length }}</span>
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
import {computed} from "vue";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import {IconChevronDown, IconPointFilled} from "@tabler/icons-vue";

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    articlesCount: {
        type: Number,
        required: true
    },
    hasActiveFilter: {
        type: Boolean,
        required: false,
        default: false
    },
    currentCategory: {
        type: Object,
        required: false,
        default: []
    },
    /**
     * Query-Parameter, die beim Kategoriewechsel erhalten bleiben müssen
     * (Status, Suche, Such-Scope) — sie leben nur in der URL, nicht im
     * serverseitigen Filter-State, und würden sonst beim Klick verloren gehen.
     */
    linkQuery: {
        type: Object,
        required: false,
        default: () => ({})
    }
})

// Bei aktivem Filter/aktiver Suche nur Kategorien mit passenden Artikeln zeigen,
// damit die Liste auf die für die Suche relevanten Kategorien reduziert wird.
// Die aktuell geöffnete Kategorie bleibt sichtbar, auch wenn sie leer gefiltert ist.
const visibleCategories = computed(() => {
    if (!props.hasActiveFilter) {
        return props.categories
    }

    return props.categories.filter((category) =>
        (category.articles?.length || 0) > 0 || category.id === props.currentCategory?.id
    )
})

// Subkategorien der geöffneten Kategorie analog einschränken.
const visibleSubCategories = computed(() => {
    const subCategories = props.currentCategory?.subcategories ?? []

    if (!props.hasActiveFilter) {
        return subCategories
    }

    return subCategories.filter((subCategory) => (subCategory.articles?.length || 0) > 0)
})

</script>

<style scoped>

</style>
