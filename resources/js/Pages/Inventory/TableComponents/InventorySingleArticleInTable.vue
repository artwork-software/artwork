<template>
    <td class="py-3 pr-3 pl-3 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
        <img :src="getMainImageInImage.image" alt="" class="w-12 h-12 object-fill rounded-lg">
    </td>
    <td class="p-3 text-sm whitespace-nowrap text-gray-500">{{ item?.name }}</td>
    <td class="p-3 text-sm whitespace-nowrap" :class="item.quantity === 0 ? 'text-red-500' : 'text-artwork-buttons-create'">{{ formatQuantity(item?.quantity) }}</td>
    <td class="p-3 text-sm whitespace-nowrap text-gray-500" v-for="property in allPropertiesFromArticles">
        {{ formatProperty(property) }}
    </td>
    <td class="py-3 pr-3 pl-3 text-sm whitespace-nowrap text-gray-500 sm:pr-0">
        <div class="flex items-center gap-x-4">
            <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover" @click="showArticleDetail = true">
                <component is="IconEye" class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
    </td>


    <ArticleDetailModal :article="item" v-if="showArticleDetail" @close="showArticleDetail = false" @openArticleEditModal="openEditArticleModal"  />

    <AddEditArticleModal
        v-if="showEditArticleModal"
        @close="showEditArticleModal = false"
        :article="item"
    />
</template>

<script setup>

import {computed, defineAsyncComponent, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";
const $t = useTranslation()

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

const showEditArticleModal = ref(false);
const showArticleDetail = ref(false);

const ArticleDetailModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue'),
})

const AddEditArticleModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/AddEditArticleModal.vue'),
})

const formatQuantity = (quantity) => {

    if (quantity === 0) return $t('Out of stock');
    // if not return 10000 to 10.000

    return quantity.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

const openEditArticleModal = () => {
    showArticleDetail.value = false;
    showEditArticleModal.value = true;
}

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

const formatProperty = (property) => {
    if (property.type === 'room') {
        return props.item.room?.name === 'Room not found' ? $t(props.item?.room?.name) : props.item?.room?.name;
    }

    if (property.type === 'manufacturer') {
        return props.item.manufacturer?.name === 'Manufacturer not found' ? $t(props.item.manufacturer?.name) : props.item.manufacturer?.name;
    }

    if (property.type === 'date') {
        return new Date(property.pivot.value).toLocaleDateString();
    }

    if (property.type === 'time') {
        return new Date(property.pivot.value).toLocaleTimeString();
    }

    if (property.type === 'datetime') {
        return new Date(property.pivot.value).toLocaleString();
    }

    if (property.type === 'checkbox') {
        return property.pivot.value ? $t('Yes') : $t('No');
    }

    return property.pivot.value;
}

</script>

<style scoped>

</style>