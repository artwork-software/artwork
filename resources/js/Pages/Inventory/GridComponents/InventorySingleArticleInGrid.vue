<template>
    <div class="w-full h-full p-6 bg-white rounded-lg border border-gray-100 hover:shadow-lg duration-300 ease-in-out cursor-pointer overflow-hidden">
        <div class="flex items-center justify-center">
            <img :src="getMainImageInImage.image" alt="" class="w-44 h-44 object-center rounded-lg">
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
            <p class="text-xs text-gray-500 line-clamp-2">
                {{ item.description }}
            </p>
            <div class="my-2 xxsDark">
                <div class="flex items-center justify-between py-1 text-artwork-buttons-create">
                    <div>
                        {{ $t('Quantity') }}
                    </div>
                    <div>
                        {{ item.quantity }}
                    </div>
                </div>
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


            </div>
        </div>
    </div>
</template>

<script setup>

import {Link, usePage} from "@inertiajs/vue3";
import {computed} from "vue";

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const getMainImageInImage = computed(() => {
    const images = props.item.images || [];

    // 1. Suche nach dem Hauptbild
    const mainImage = images.find(image => image.is_main_image);
    if (mainImage) return {
        image: '/storage/' + mainImage.image,
    };

    // 2. Wenn kein Hauptbild, nimm das erste Bild
    if (images.length > 0) return {
        image: '/storage/' + images[0].image,
    };

    // 3. Wenn keine Bilder vorhanden sind, gib ein Standardbild zurück
    return {
        image: usePage().props.big_logo, // Passe den Pfad zu deinem Standardbild an
    };
});


</script>

<style scoped>

</style>