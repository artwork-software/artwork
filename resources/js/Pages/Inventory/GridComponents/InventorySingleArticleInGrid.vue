<template>
    <div
        class="w-full h-full p-6 bg-white rounded-lg border border-gray-100 hover:shadow-lg duration-300 ease-in-out cursor-pointer overflow-hidden font-lexend"
        @click="showArticleDetail = true"
    >
        <div class="flex items-center justify-center">
            <img :src="getMainImageInImage.image" @error="handleImageError" alt="" :class="imageClasses" />
        </div>

        <div class="mt-4">
            <div class="flex items-center">
                <h3 class="xsDark">
                    {{ item.name }}
                </h3>
                <IconIdBadge v-if="item?.is_detailed_quantity" class="size-4 xsDark ml-2" />
            </div>

            <p class="text-xs text-gray-500 line-clamp-2">
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

                <div v-for="property in item.properties" :key="property.id">
                    <div
                        class="flex items-center justify-between py-2 font-lexend"
                        v-if="property.show_in_list"
                    >
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
import { IconIdBadge, IconPhoto } from '@tabler/icons-vue'

const $t = useTranslation()

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
})

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

    const mainImage = images.find((image) => image.is_main_image)
    if (mainImage) {
        return {
            image: '/storage/' + mainImage.image,
        }
    }

    if (images.length > 0) {
        return {
            image: '/storage/' + images[0].image,
        }
    }

    return {
        image: usePage().props.big_logo,
    }
})

const formatProperty = (property) => {
    if (property.type === 'room') {
        return props.item.room?.name === 'Room not found' ? '-' : props.item?.room?.name
    }

    if (property.type === 'manufacturer') {
        return props.item.manufacturer?.name === 'Manufacturer not found'
            ? '-'
            : props.item?.manufacturer?.name
    }

    if (property.type === 'date') {
        return new Date(property.pivot.value).toLocaleDateString()
    }

    if (property.type === 'time') {
        return new Date(property.pivot.value).toLocaleTimeString()
    }

    if (property.type === 'datetime') {
        return new Date(property.pivot.value).toLocaleString()
    }

    if (property.type === 'checkbox') {
        return property.pivot.value ? $t('Yes') : $t('No')
    }

    return property.pivot.value
}

const formatQuantity = (quantity) => {
    if (quantity === 0) return $t('Out of stock')
    return quantity.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}
</script>

<style scoped>
</style>
