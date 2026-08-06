<template>
    <!-- Galleria Component -->
    <Galleria
        v-if="displayCustom"
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
                :src="'/storage/' + (slotProps.item.thumbnail || slotProps.item.image)"
                :alt="slotProps.item.alt"
                style="display: block"
                @error="(e) => (e.target.src = usePage().props.big_logo)"
                class="w-20 max-w-20"
            />
        </template>
    </Galleria>

    <td v-if="enableAddArticleToBasket" class="absolute inset-0 bg-text-subtle/30 opacity-0 hover:opacity-100 duration-200 cursor-pointer z-10 pointer-events-none hover:pointer-events-auto">
        <div class="flex items-center justify-center h-full w-full">
            <div class="relative pointer-events-auto">
                <span class="absolute -top-2 -right-2 size-5 rounded-full bg-accent-50 ring-2 ring-white text-accent-600 text-xs flex items-center justify-center">
                    {{ findBasketForArticle(item.id) ? findBasketForArticle(item.id).quantity : 0 }}
                </span>
                <BaseUIButton :label="$t('Add to Basket')" use-translation icon="IconBasketPlus" @click="$emit('add-to-basket', item.id)" />
            </div>
        </div>
    </td>
    <td v-if="!hideImage" class="sticky left-0 z-10 bg-inherit p-3 text-sm font-medium whitespace-nowrap text-text first-letter:capitalize">
        <div class="flex justify-center">
            <img
                :src="getMainImageInImage.image"
                @error="(e) => e.target.src = usePage().props.big_logo"
                alt=""
                class="w-12 h-12 object-fill rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                loading="lazy"
                decoding="async"
                @click="imageClick(0)"
            >
        </div>
    </td>
    <td class="sticky z-10 bg-inherit p-3 text-sm whitespace-nowrap text-text-subtle font-semibold" :class="hideImage ? 'left-0' : 'left-20'">
        <div class="flex items-center">
            <span class="truncate">{{ item?.name }}</span>
            <IconIdBadge v-if="item?.is_detailed_quantity" class="size-4 text-text-subtle font-semibold ml-2 shrink-0" />
        </div>
        <div v-if="item?.inventory_number" class="text-xs font-mono font-normal text-text-subtle">
            {{ (usePage().props.inventoryNumberPrefix || '') + item.inventory_number }}
        </div>
    </td>
    <td class="sticky z-10 bg-inherit p-3 text-sm whitespace-nowrap" :class="[hideImage ? 'left-[256px]' : 'left-[336px]', item.quantity === 0 ? 'text-danger' : 'text-accent-600']">{{ formatQuantity(item?.quantity) }}</td>
    <td class="p-3 text-sm whitespace-nowrap font-semibold truncate"
        :class="[ isNumericProperty(property) ? 'text-right tabular-nums' : '',
            isEmptyProperty(property) ? 'text-text-subtle font-normal' : 'text-text-subtle'
        ]"
        v-for="property in columnProperties" :key="property.id">
        <template v-if="cellDisplays[property.id].type === 'file'">
            <a v-if="cellDisplays[property.id].file"
               :href="route('inventory-management.articles.property-file.download', { path: cellDisplays[property.id].file.path })"
               class="text-accent-600 hover:text-accent-700 underline cursor-pointer">
                {{ cellDisplays[property.id].file.name }}
            </a>
            <span v-else>-</span>
        </template>
        <PropertyDiffTooltip
            v-else-if="cellDisplays[property.id].varied"
            :values="cellDisplays[property.id].distinctValues"
            :heading="$t('Values')"
        >
            {{ cellDisplays[property.id].text }}
        </PropertyDiffTooltip>
        <template v-else>{{ cellDisplays[property.id].empty ? '-' : cellDisplays[property.id].text }}</template>
    </td>
    <td class="py-3 pr-3 pl-3 text-sm whitespace-nowrap text-text-subtle font-semibold sm:pr-0">
        <div class="flex items-center gap-x-4">
            <button type="button" class="text-accent-600 hover:text-accent-700" @click="showArticleDetail = true">
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
import {useInventoryPropertyDisplay} from "@/Composeables/InventoryPropertyDisplay.js";
import PropertyDiffTooltip from "@/Pages/Inventory/Components/PropertyDiffTooltip.vue";
import {IconEye, IconIdBadge, IconPhoto} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import Galleria from 'primevue/galleria';
const $t = useTranslation()
const {getPropertyDisplay} = useInventoryPropertyDisplay()

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    columnProperties: {
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
    },
    hideImage: {
        type: Boolean,
        required: false,
        default: false
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

    // Die Übersicht rendert nur eine kleine Vorschau — Thumbnail serven,
    // Fallback aufs Original für Bilder ohne Thumbnail (z.B. SVG).
    const mainImage = images.find(image => image.is_main_image) ?? images[0];
    if (mainImage) return {
        image: '/storage/' + (mainImage.thumbnail || mainImage.image),
    };

    return {
        image: usePage().props.big_logo,
    };
});

// Precompute a display descriptor per (category-wide) property column. Handles both
// regular articles and aggregated detailed-quantity articles (see composable).
const cellDisplays = computed(() => {
    const map = {};
    for (const property of props.columnProperties) {
        map[property.id] = getPropertyDisplay(props.item, property);
    }
    return map;
})

// Numeric columns are right-aligned + tabular for easier comparison down the column.
const isNumericProperty = (property) => property?.type === 'number' || property?.type === 'year'

// A cell is "empty" when the article does not define this column's property, so it
// is rendered as a dimmed dash. File columns count as empty when no file is stored.
const isEmptyProperty = (property) => {
    const display = cellDisplays.value[property.id]
    if (!display) {
        return true
    }
    return display.type === 'file' ? !display.file : !!display.empty
}

</script>

<style scoped>

</style>
