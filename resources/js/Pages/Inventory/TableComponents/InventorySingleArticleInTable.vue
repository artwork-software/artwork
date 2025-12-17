<template>
    <!-- Galleria Component -->
    <Galleria
        v-model:activeIndex="activeIndex"
        v-model:visible="displayCustom"
        :value="item.images"
        :responsiveOptions="responsiveOptions"
        :numVisible="7"
        :pt="{ mask: { onClick: onMaskClick } }"
        containerStyle="max-width: 850px"
        :circular="true"
        :fullScreen="true"
        :showItemNavigators="true"
        :showThumbnails="false"
    >
        <template #item="slotProps">
            <img
                :src="'/storage/' + slotProps.item.image"
                :alt="slotProps.item.alt"
                style="width: 100%; display: block"
                @error="(e) => (e.target.src = usePage().props.big_logo)"
            />
        </template>
        <template #thumbnail="slotProps">
            <img
                :src="'/storage/' + slotProps.item.image"
                :alt="slotProps.item.alt"
                style="display: block"
                @error="(e) => (e.target.src = usePage().props.big_logo)"
                class="w-20 max-w-20"
            />
        </template>
    </Galleria>

    <td v-if="enableAddArticleToBasket" class="absolute inset-0 bg-zinc-500/30 opacity-0 hover:opacity-100 duration-200 cursor-pointer z-10 pointer-events-none hover:pointer-events-auto">
        <div class="flex items-center justify-center h-full w-full">
            <div class="relative pointer-events-auto">
                <span class="absolute -top-2 -right-2 size-5 rounded-full bg-blue-50 ring-2 ring-white text-blue-500 text-xs flex items-center justify-center">
                    {{ findBasketForArticle(item.id) ? findBasketForArticle(item.id).quantity : 0 }}
                </span>
                <BaseUIButton :label="$t('Add to Basket')" use-translation icon="IconBasketPlus" @click="$emit('add-to-basket', item.id)" />
            </div>
        </div>
    </td>
    <td class="py-3 flex justify-center text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
        <img
            :src="getMainImageInImage.image"
            @error="(e) => e.target.src = usePage().props.big_logo"
            alt=""
            class="w-12 h-12 object-fill rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
            @click="imageClick(0)"
        >
    </td>
    <td class="p-3 text-sm whitespace-nowrap text-secondary font-semibold"><div class="flex items-center">{{ item?.name }}<IconIdBadge v-if="item?.is_detailed_quantity" class="size-4 text-secondary font-semibold ml-2" /> </div></td>
    <td class="p-3 text-sm whitespace-nowrap" :class="item.quantity === 0 ? 'text-red-500' : 'text-artwork-buttons-create'">{{ formatQuantity(item?.quantity) }}</td>
    <td class="p-3 text-sm whitespace-nowrap text-secondary font-semibold" v-for="property in subcategoryProperties" :key="property.id">
        {{ formatPropertyValue(property) }}
    </td>
    <td class="py-3 pr-3 pl-3 text-sm whitespace-nowrap text-secondary font-semibold sm:pr-0">
        <div class="flex items-center gap-x-4">
            <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover" @click="showArticleDetail = true">
                <component :is="IconEye" class="h-5 w-5" aria-hidden="true" />
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
import {IconEye, IconIdBadge, IconPhoto} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import Galleria from 'primevue/galleria';
const $t = useTranslation()

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    subcategoryProperties: {
        type: Array,
        required: true
    },
    enableAddArticleToBasket: {
        type: Boolean,
        required: false,
        default: false
    },
    findBasketForArticle: {
        type: Function,
        required: false,
        default: () => null
    }
})

const emit = defineEmits(['add-to-basket'])

const showEditArticleModal = ref(false);
const showArticleDetail = ref(false);

// Galleria reactive variables
const activeIndex = ref(0);
const displayCustom = ref(false);

const responsiveOptions = ref([
    {
        breakpoint: '1024px',
        numVisible: 5,
    },
    {
        breakpoint: '768px',
        numVisible: 3,
    },
    {
        breakpoint: '560px',
        numVisible: 1,
    },
]);

const imageClick = (index) => {
    // Don't open gallery if article has no images (showing default image)
    if (!hasImage.value) {
        return;
    }
    activeIndex.value = index;
    displayCustom.value = true;
};

const onMaskClick = (e) => {
    if (e.target === e.currentTarget) {
        displayCustom.value = false;
    }
};

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

const hasImage = computed(() => {
    const images = props.item.images || [];
    return images.length > 0;
});

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

    // 3. Wenn keine Bilder vorhanden sind, gib ein leeres Objekt zurück
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
        return property.pivot.value;
    }

    if (property.type === 'datetime') {
        return new Date(property.pivot.value).toLocaleString();
    }

    if (property.type === 'checkbox') {
        return property.pivot.value ? $t('Yes') : $t('No');
    }

    return property.pivot.value;
}

// New function to format property values correctly by looking up from article's own properties
const formatPropertyValue = (subcategoryProperty) => {
    // Find the matching property in the article's own properties
    const articleProperty = props.item.properties.find(p => p.id === subcategoryProperty.id);

    // If the article doesn't have this property, return a dash
    if (!articleProperty) {
        return '-';
    }

    // Handle special property types
    if (subcategoryProperty.type === 'room') {
        return props.item.room?.name === 'Room not found' ? $t(props.item?.room?.name) : props.item?.room?.name;
    }

    if (subcategoryProperty.type === 'manufacturer') {
        return props.item.manufacturer?.name === 'Manufacturer not found' ? $t(props.item.manufacturer?.name) : props.item.manufacturer?.name;
    }

    if (subcategoryProperty.type === 'date') {
        return articleProperty.pivot.value ? new Date(articleProperty.pivot.value).toLocaleDateString() : '-';
    }

    if (subcategoryProperty.type === 'time') {
        return articleProperty.pivot.value || '-';
    }

    if (subcategoryProperty.type === 'datetime') {
        return articleProperty.pivot.value ? new Date(articleProperty.pivot.value).toLocaleString() : '-';
    }

    if (subcategoryProperty.type === 'checkbox') {
        return articleProperty.pivot.value ? $t('Yes') : $t('No');
    }

    return articleProperty.pivot.value || '-';
}

</script>

<style scoped>

</style>
