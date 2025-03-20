<template>
    <div class="w-full h-full p-6 bg-white rounded-lg border border-gray-100 hover:shadow-lg duration-300 ease-in-out cursor-pointer overflow-hidden">
        <div class="flex items-center justify-center">
            <img src="https://tailwindcss.com/plus-assets/img/ecommerce-images/category-page-02-image-card-01.jpg" alt="" class="w-44 h-44 object-center object-cover">
        </div>
        <div>
            <nav class="flex py-2" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-1 text-xs text-artwork-buttons-create cursor-pointer">
                    <li>
                        <div class="flex items-center gap-x-1">
                            <Link preserve-scroll :href="route('inventory.category.show', item.category.id)" class="font-medium  hover:text-gray-700 first-letter:capitalize truncate">
                                {{ item.category.name }}
                            </Link>
                        </div>
                    </li>
                    <li v-if="item.sub_category">
                        <div class="flex items-center gap-x-1">
                            <component is="IconChevronRight" class="size-3 shrink-0 text-gray-400" aria-hidden="true" />
                            <Link preserve-scroll :href="route('inventory.sub.category.show', {
                                                                inventoryCategory: item.category.id,
                                                                subCategory: item.sub_category.id
                                                            })" class="font-medium  hover:text-gray-700 first-letter:capitalize truncate">
                                {{ item.sub_category.name }}
                            </Link>
                        </div>
                    </li>
                </ol>
            </nav>
            <h3 class="xsDark">{{ item.name }}</h3>
            <div class="my-2 xxsDark">
                <div v-for="property in item.properties">
                    <div class="flex items-center justify-between py-1" v-if="property.show_in_list">
                        <div>
                            {{ property.name }}
                        </div>
                        <div>
                            {{ property.pivot.value }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between py-1">
                    <div>
                        {{ $t('Quantity') }}
                    </div>
                    <div>
                        {{ item.quantity }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

import {Link} from "@inertiajs/vue3";

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

</script>

<style scoped>

</style>