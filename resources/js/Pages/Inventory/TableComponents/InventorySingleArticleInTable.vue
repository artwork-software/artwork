<template>
    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
        <img :src="getMainImageInImage.image" alt="" class="w-12 h-12 object-fill rounded-lg">
    </td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500">{{ item?.name }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500" v-for="property in allPropertiesFromArticles">
        {{ item?.properties.find(p => p.id === property.id)?.pivot.value }}
    </td>
    <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0">
        <div class="flex items-center gap-x-4">
            <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover">
                <component is="IconEye" class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
    </td>
</template>

<script setup>

import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    allPropertiesFromArticles: {
        type: Array,
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