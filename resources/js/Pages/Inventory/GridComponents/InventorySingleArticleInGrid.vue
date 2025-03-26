<template>
    <div class="w-full h-full p-6 bg-white rounded-lg border border-gray-100 hover:shadow-lg duration-300 ease-in-out cursor-pointer overflow-hidden font-lexend" @click="showArticleDetail = true">
        <div class="flex items-center justify-center">
            <img :src="getMainImageInImage.image" @error="(e) => e.target.src = usePage().props.big_logo" alt="" class="w-44 h-44 object-fill rounded-lg">
        </div>
        <div class="mt-4">
            <h3 class="xsDark">{{ item.name }}</h3>
            <p class="text-xs text-gray-500 line-clamp-2">
                {{ item.description }}
            </p>
            <div class="my-2 text-xs divide-y divide-gray-100 divide-dashed">
                <div class="flex items-center justify-between py-2 font-bold font-lexend">
                    <div class="text-gray-500">
                        {{ $t('Quantity') }}
                    </div>
                    <div :class="item.quantity === 0 ? 'text-red-500' : 'text-artwork-buttons-create'">
                        {{ formatQuantity(item.quantity) }}
                    </div>
                </div>
                <div v-for="property in item.properties">
                    <div class="flex items-center justify-between py-2 font-lexend" v-if="property.show_in_list">
                        <div>
                            {{ property.name }}
                        </div>
                        <div>
                            {{ formatProperty(property) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ArticleDetailModal :article="item" v-if="showArticleDetail" @close="showArticleDetail = false" @openArticleEditModal="openEditArticleModal"  />

    <AddEditArticleModal
        v-if="showEditArticleModal"
        @close="showEditArticleModal = false"
        :article="item"
    />
</template>

<script setup>

import {Link, usePage} from "@inertiajs/vue3";
import {computed, defineAsyncComponent, ref} from "vue";
import {useTranslation} from "@/Composeables/Translation.js";
const $t = useTranslation()

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const showEditArticleModal = ref(false);

const openEditArticleModal = () => {
    showArticleDetail.value = false;
    showEditArticleModal.value = true;
}

const ArticleDetailModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue'),
})

const AddEditArticleModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/AddEditArticleModal.vue'),
})

const showArticleDetail = ref(false)

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
        return props.item.manufacturer?.name === 'Room not found' ? $t(props.item.manufacturer?.name) : props.item.manufacturer?.name;
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

const formatQuantity = (quantity) => {

    if (quantity === 0) return $t('Out of stock');
    // if not return 10000 to 10.000

    return quantity.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

</script>

<style scoped>

</style>