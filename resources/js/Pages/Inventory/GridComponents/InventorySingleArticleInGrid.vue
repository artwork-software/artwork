<template>
    <div class="w-full h-full p-6 bg-white rounded-lg border border-gray-100 hover:shadow-lg duration-300 ease-in-out cursor-pointer overflow-hidden font-lexend" @click="showArticleDetail = true">
        <div class="flex items-center justify-center">
            <img :src="getMainImageInImage.image" alt="" class="w-44 h-44 object-fill rounded-lg">
        </div>
        <div class="mt-4">
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
                            {{ formatProperty(property) }}
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <ArticleDetailModal :article="item" v-if="showArticleDetail" @close="showArticleDetail = false" />
</template>

<script setup>

import {Link, usePage} from "@inertiajs/vue3";
import {computed, defineAsyncComponent, ref} from "vue";

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const ArticleDetailModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue'),
    loadingComponent: {
        template: '<div>Loading...</div>'
    }
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

</script>

<style scoped>

</style>