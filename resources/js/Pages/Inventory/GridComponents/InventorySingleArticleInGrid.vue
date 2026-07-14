<template>
    <div
        class="w-full h-full p-6 bg-white rounded-lg border border-gray-100 hover:shadow-lg duration-300 ease-in-out cursor-pointer overflow-hidden font-lexend"
        @click="showArticleDetail = true"
    >
        <div v-if="!hideImage" class="flex items-center justify-center">
            <img :src="getMainImageInImage.image" @error="handleImageError" alt="" :class="imageClasses" loading="lazy" decoding="async" />
        </div>

        <div :class="hideImage ? '' : 'mt-4'">
            <div class="flex items-center">
                <h3 class="xsDark break-words min-w-0">
                    {{ item.name }}
                </h3>
                <IconIdBadge v-if="item?.is_detailed_quantity" class="size-4 xsDark ml-2 shrink-0" />
            </div>

            <p v-if="item?.inventory_number" class="text-xs text-gray-400 font-mono break-words">
                {{ (usePage().props.inventoryNumberPrefix || '') + item.inventory_number }}
            </p>

            <p class="text-xs text-gray-500 line-clamp-2 break-words">
                {{ item.description }}
            </p>

            <!-- 🔹 Tags als kleine Pills -->
            <div
                v-if="hasTags"
                class="mt-2 flex flex-wrap gap-1.5"
            >
                <span
                    v-for="tag in item.tags"
                    :key="tag.id"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium border border-gray-100 max-w-[120px] bg-gray-50"
                    :style="tagStyle(tag)"
                >
                    <span
                        class="inline-block h-2 w-2 rounded-full border border-white/70 shrink-0"
                        :style="{ backgroundColor: tag.color || '#4f46e5' }"
                    />
                    <span class="truncate">
                        {{ tag.name }}
                    </span>
                </span>
            </div>
            <!-- Ende Tags -->

            <div class="my-2 text-xs divide-y divide-gray-100 divide-dashed">
                <div class="flex items-center justify-between py-2 font-bold font-lexend">
                    <div class="text-gray-500">
                        {{ $t('Quantity') }}
                    </div>
                    <div :class="item.quantity === 0 ? 'text-red-500' : 'text-artwork-buttons-create'">
                        {{ formatQuantity(item.quantity) }}
                    </div>
                </div>

                <div v-for="property in displayProperties" :key="property.id">
                    <div class="flex items-center justify-between py-2 font-lexend">
                        <div>
                            {{ property.name }}
                        </div>
                        <div>
                            <template v-if="property.type === 'file'">
                                <a v-if="property.file"
                                   :href="route('inventory-management.articles.property-file.download', { path: property.file.path })"
                                   class="text-artwork-buttons-create hover:text-artwork-buttons-hover underline cursor-pointer">
                                    {{ property.file.name }}
                                </a>
                                <span v-else>-</span>
                            </template>
                            <PropertyDiffTooltip
                                v-else-if="property.varied"
                                :values="property.distinctValues"
                                :heading="$t('Values')"
                                class="text-gray-500"
                            >
                                {{ property.text }}
                            </PropertyDiffTooltip>
                            <template v-else>{{ property.empty ? '-' : property.text }}</template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ArticleDetailModal
        :article="item"
        v-if="showArticleDetail"
        @close="showArticleDetail = false"
        @openArticleEditModal="openEditArticleModal"
    />

    <AddEditArticleModal
        v-if="showEditArticleModal"
        @close="showEditArticleModal = false"
        :article="item"
    />
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, defineAsyncComponent, ref } from 'vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { useInventoryPropertyDisplay } from '@/Composeables/InventoryPropertyDisplay.js'
import PropertyDiffTooltip from '@/Pages/Inventory/Components/PropertyDiffTooltip.vue'
import { IconIdBadge, IconPhoto } from '@tabler/icons-vue'

const $t = useTranslation()
const { getDisplayProperties } = useInventoryPropertyDisplay()

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    hideImage: {
        type: Boolean,
        required: false,
        default: false,
    },
})

const displayProperties = computed(() => getDisplayProperties(props.item))

const showEditArticleModal = ref(false)
const showArticleDetail = ref(false)
const isUsingFallbackImage = ref(false)

const openEditArticleModal = () => {
    showArticleDetail.value = false
    showEditArticleModal.value = true
}

const handleImageError = (e) => {
    e.target.src = usePage().props.big_logo
    isUsingFallbackImage.value = true
}

const imageClasses = computed(() => {
    const baseClasses = 'w-44 h-44 rounded-lg'
    const isFallbackImage =
        isUsingFallbackImage.value || getMainImageInImage.value.image === usePage().props.big_logo
    const objectFitClass = isFallbackImage ? 'object-contain' : 'object-cover'
    return `${baseClasses} ${objectFitClass}`
})

const ArticleDetailModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue'),
})

const AddEditArticleModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/AddEditArticleModal.vue'),
})

/**
 * 🔹 Tags
 */
const hasTags = computed(() => Array.isArray(props.item.tags) && props.item.tags.length > 0)

const tagStyle = (tag) => {
    const baseColor = tag?.color || '#4f46e5'

    // einfache leichte Hinterlegung aus der Tagfarbe
    return {
        backgroundColor: baseColor + '10', // sehr transparent
        borderColor: baseColor + '30',
        color: baseColor,
    }
}

const hasImage = computed(() => {
    const images = props.item.images || []
    return images.length > 0
})

const getMainImageInImage = computed(() => {
    const images = props.item.images || []

    // The overview only renders a small preview — serve the WebP thumbnail
    // and fall back to the original for images without one (e.g. SVG).
    const mainImage = images.find((image) => image.is_main_image) ?? images[0]
    if (mainImage) {
        return {
            image: '/storage/' + (mainImage.thumbnail || mainImage.image),
        }
    }

    return {
        image: usePage().props.big_logo,
    }
})

const formatQuantity = (quantity) => {
    if (quantity === 0) return $t('Out of stock')
    return quantity.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}
</script>

<style scoped>
</style>
