<template>
    <AppLayout>
        <div class="w-full px-10 bg-gray-50 min-h-screen">
            <div class="border-b border-gray-200 pt-8 pb-5 flex items-center justify-between">
                <div class="">
                    <div>
                        <TinyPageHeadline
                            :title="$t('Inventory')"
                            :description="$t('Welcome to the {0} inventory! Here you will find a complete overview of all available products. You can browse through the various items, view details and manage which products are currently in stock.', [usePage().props.name])"
                        />
                    </div>

                    <div class="max-w-xs pt-5">
                        <!-- name filter and search -->
                        <BaseInput
                            id="productSearch"
                            v-model="searchArticleInput"
                            :label="$t('Search Articles')"
                            />
                    </div>
                </div>


                <div class="mt-5">
                    <SmallFormButton class="flex items-center gap-x-2 font-lexend" @click="showAddEditArticleModal = true">
                        <component is="IconBarcode" class="size-5" aria-hidden="true" />
                        <span>
                            {{ $t('Add Article') }}
                        </span>
                    </SmallFormButton>
                </div>

            </div>

            <div class="pt-12 pb-24 lg:grid lg:grid-cols-3 lg:gap-x-6 xl:grid-cols-7">

                <div class="mb-8 md:col-span-3 lg:col-span-3 xl:col-span-2 col-span-6">
                <InventorySidebarComponent
                    :current-category="props.currentCategory"
                    :articles-count="props.articlesCount"
                    :categories="props.categories"
                />
                </div>
                <section aria-labelledby="product-heading" class="col-span-3 md:col-span-6 lg:col-span-6 xl:col-span-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <InventoryBreadcrumbComponent :current-category="props.currentCategory" :current-sub-category="props.currentSubCategory"/>
                        </div>

                        <div>
                            <InventoryLayoutSwitchComponent :grid-layout="gridLayout" @update:gridLayout="updateGridLayout" />
                        </div>
                    </div>
                    <div class="mb-3" v-if="filterableProperties?.length > 0">
                        <InventoryFilterComponent :filterableProperties="filterableProperties" />
                    </div>
                    <div v-if="props.articles.data.length > 0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 3xl:grid-cols-8 gap-4" v-if="gridLayout">
                            <div v-for="item in props.articles.data">
                                <InventorySingleArticleInGrid :item="item" />
                            </div>
                        </div>
                        <div v-else>
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                <tr class="divide-x divide-gray-200">
                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                                        {{ $t('Image') }}</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        {{ $t('Name') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        {{ $t('Quantity') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900" v-for="property in allPropertiesFromArticles">
                                        {{ property?.name }}
                                    </th>
                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('Actions') }}</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                <tr v-for="item in props.articles.data" :key="item?.id" class="divide-x divide-gray-200">
                                    <InventorySingleArticleInTable :item="item" :all-properties-from-articles="allPropertiesFromArticles" />
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-10">
                            <BasePaginator property-name="articles" :entities="articles" />
                        </div>
                    </div>
                    <div v-else>
                        <InventoryEmptyProductsAlertComponent />
                    </div>
                </section>
            </div>

            <AddEditArticleModal
                v-if="showAddEditArticleModal"
                @close="showAddEditArticleModal = false"
                :article="null"
                :category="props.currentCategory"
                :categories="props.categories"
                :properties="props.properties"
                :rooms="props.rooms"
                :manufacturers="props.manufacturers"
            />
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {Link, router, usePage} from "@inertiajs/vue3"
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import InventorySidebarComponent from "@/Pages/Inventory/LayoutComponents/InventorySidebarComponent.vue";
import InventoryBreadcrumbComponent from "@/Pages/Inventory/LayoutComponents/InventoryBreadcrumbComponent.vue";
import InventoryEmptyProductsAlertComponent
    from "@/Pages/Inventory/LayoutComponents/InventoryEmptyProductsAlertComponent.vue";
import InventorySingleArticleInGrid from "@/Pages/Inventory/GridComponents/InventorySingleArticleInGrid.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {Switch} from "@headlessui/vue";
import {computed, onMounted, ref, watch, provide, defineAsyncComponent} from "vue";
import InventoryFilterComponent from "@/Pages/Inventory/LayoutComponents/InventoryFilterComponent.vue";
import InventoryLayoutSwitchComponent from "@/Pages/Inventory/LayoutComponents/InventoryLayoutSwitchComponent.vue";
import InventorySingleArticleInTable from "@/Pages/Inventory/TableComponents/InventorySingleArticleInTable.vue";
import SmallFormButton from "@/Components/Buttons/SmallFormButton.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {IconIdBadge} from "@tabler/icons-vue";
import debounce from "lodash.debounce";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    currentCategory: {
        type: Object,
        required: false,
        default: []
    },
    currentSubCategory: {
        type: Object,
        required: false,
        default: []
    },
    articles: {
        type: Object,
        required: true
    },
    articlesCount: {
        type: Number,
        required: true
    },
    filterableProperties: {
        type: Object,
        required: true
    },
    properties: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    manufacturers: {
        type: Object,
        required: true
    }
})

provide('properties', props.properties)
provide('rooms', props.rooms)
provide('manufacturers', props.manufacturers)
provide('categories', props.categories)



const gridLayout = ref(true)
const searchArticleInput = ref(usePage().props?.urlParameters?.search ?? '')
const showAddEditArticleModal = ref(false);

const AddEditArticleModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/AddEditArticleModal.vue'),
})

const updateGridLayout = (value) => {
    gridLayout.value = value
}


const allPropertiesFromArticles = computed(() => {
    const properties = [];

    // get all properties from articles and remove duplicates
    props.articles.data.forEach((article) => {
        article.properties.forEach((property) => {
            if (!properties.find((p) => p.id === property.id)) {
                properties.push(property)
            }
        })
    })

    return properties;
})

const searchArticles = debounce(() => {
    // search for articles
    router.reload({
        data: {
            search: searchArticleInput.value
        },
        preserveScroll: true,
        only: ['articles']
    })
}, 500)


// watch for search input
watch(searchArticleInput, (value) => {
    // search for articles with debounce
    searchArticles()
})
</script>

<style scoped>

</style>
