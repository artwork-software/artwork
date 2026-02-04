<template>
    <AppLayout :title="$t('Inventory')">
        <div class="w-full px-10 min-h-screen">
            <div class="border-b border-gray-200 pt-8 pb-5 flex items-center justify-between">
                <div class="">
                    <div>
                        <BasePageTitle
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
                    <BaseUIButton is-add-button v-if="can('inventory.create_edit') || is('artwork admin')"@click="showAddEditArticleModal = true" label="Add Article" use-translation />
                </div>

            </div>

            <div class="pt-12 pb-24 grid grid-cols-1 sm:grid-cols-4 md:grid-cols-6 lg:gap-x-8 lg:grid-cols-8 2xl:flex 2xl:w-full">

                <div class="col-span-full md:col-span-full lg:col-span-2 xl:col-span-2 2xl:max-w-96 2xl:w-96">
                    <InventorySidebarComponent
                        :current-category="props.currentCategory"
                        :articles-count="props.articlesCount"
                        :categories="props.categories"
                    />
                </div>
                <section aria-labelledby="product-heading" class="col-span-full lg:col-span-6 2xl:w-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <InventoryBreadcrumbComponent :current-category="props.currentCategory" :current-sub-category="props.currentSubCategory"/>
                        </div>

                        <div class="flex items-center gap-x-6">
                            <div class="relative">
                                <span class="absolute -top-2 -right-2 size-5 rounded-full bg-blue-50 ring-1 ring-blue-200 text-blue-500 text-xs flex items-center justify-center">
                                    {{ productBaskets?.basket_articles?.length ?? 0 }}
                                </span>
                                <BaseUIButton :label="$t('Product Basket')" icon="IconBasket" @click="showProductBasketModal = true" />
                            </div>
                            <SwitchIconTooltip
                                v-model="enableAddArticleToBasket"
                                :tooltip-text="$t('Enable adding articles to a temporary product basket')"
                                size="md"
                                icon="IconBasket"
                            />
                            <InventoryLayoutSwitchComponent :grid-layout="gridLayout" @update:gridLayout="updateGridLayout" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <StatusOverview :counts-by-status="props.countsByStatus" />
                    </div>
                    <div class="mb-3" v-if="filterableProperties?.length > 0">
                        <InventoryFilterComponent :filterableProperties="filterableProperties" />
                    </div>
                    <div v-if="props.articles.data.length > 0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 3xl:grid-cols-8 gap-4 items-stretch" v-if="gridLayout">
                            <div v-for="item in props.articles.data">
                                <div class="relative">
                                    <div v-if="enableAddArticleToBasket" class="absolute inset-0 bg-zinc-500/30 rounded-lg opacity-0 hover:opacity-100 duration-200 cursor-pointer">
                                        <div class="flex items-center justify-center h-full w-full">
                                            <div class="relative">
                                                <span class="absolute -top-2 -right-2 size-5 rounded-full bg-blue-50 ring-2 ring-white text-blue-500 text-xs flex items-center justify-center">
                                                    {{ findBasketForArticle(item.id) ? findBasketForArticle(item.id).quantity : 0 }}
                                                </span>
                                                <BaseUIButton :label="$t('Add to Basket')" use-translation icon="IconBasketPlus" @click="addArticleToBasket(item.id)" />

                                            </div>
                                        </div>
                                    </div>
                                    <InventorySingleArticleInGrid :item="item" />
                                </div>
                            </div>
                        </div>
                        <div v-else class="space-y-8">
                            <!-- Loop through each category -->
                            <div v-for="(category, index) in groupedArticles" :key="category.id" class="space-y-6">
                                <!-- Category Header -->
                                <div class="border-b-2 border-gray-300 pb-2">
                                    <h2 class="text-xl font-bold text-gray-900">{{ category.name }}</h2>
                                </div>

                                <!-- Loop through each subcategory within the category -->
                                <div v-for="subcategory in category.subcategories" :key="subcategory.id" class="space-y-3">
                                    <!-- Subcategory Header -->
                                    <div class="bg-gray-50 px-4 py-2 rounded-t-lg border-l-4 border-artwork-buttons-create">
                                        <h3 class="text-lg font-semibold text-gray-800">{{ subcategory.name }}</h3>
                                    </div>

                                    <!-- Table for this subcategory -->
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-300 border border-gray-200 rounded-b-lg">
                                            <thead class="bg-gray-50">
                                                <tr class="divide-x divide-gray-200">
                                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left flex justify-center text-sm font-semibold text-gray-900 sm:pl-0">
                                                        {{ $t('Image') }}
                                                    </th>
                                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                        {{ $t('Name') }}
                                                    </th>
                                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                        {{ $t('Quantity') }}
                                                    </th>
                                                    <!-- Only show properties that exist in this subcategory -->
                                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900"
                                                        v-for="property in subcategory.properties"
                                                        :key="property.id">
                                                        {{ property?.name }}
                                                    </th>
                                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">
                                                        {{ $t('Actions') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                <tr v-for="item in subcategory.articles" :key="item?.id" class="divide-x divide-gray-200 relative hover:bg-gray-50">
                                                    <InventorySingleArticleInTable
                                                        :item="item"
                                                        :subcategory-properties="subcategory.properties"
                                                        :enable-add-article-to-basket="enableAddArticleToBasket"
                                                        :find-basket-for-article="findBasketForArticle"
                                                        @add-to-basket="addArticleToBasket"
                                                    />
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Divider between categories (not after the last one) -->
                                <div v-if="index < Object.keys(groupedArticles).length - 1" class="border-t-2 border-gray-400 my-8"></div>
                            </div>
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

            <ProductBasketModal
                v-if="showProductBasketModal"
                @close="closeModalProductBasket"
            />

            <issue-of-material-modal
                v-if="showIssueOfMaterialModal"
                :issue-of-material="!internOrExternIssue"
                :is-extern-or-intern="internOrExternIssue"
                @close="showIssueOfMaterialModal = false"
                :load-article-form-basket="true"
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
import {IconBarcode, IconIdBadge, IconLayoutGrid, IconLayoutList} from "@tabler/icons-vue";
import debounce from "lodash.debounce";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {can, is} from "laravel-permission-to-vuejs";
import StatusOverview from "@/Pages/Inventory/Components/StatusOverview.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import ProductBasketModal from "@/Pages/Inventory/ProductBasket/Components/ProductBasketModal.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation()

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
    },
    statuses: {
        type: Object,
        required: true
    },
    countsByStatus: {
        type: Object,
        required: true
    },
    productBaskets: {
        type: Object,
        required: false,
        default: () => ({})
    },
    tagGroups: {
        type: Object,
        required: false,
        default: () => ({})
    },
    tags: {
        type: Object,
        required: false,
        default: () => ({})
    },
    inventoryGridLayout: {
        type: Boolean,
        required: false,
        default: true
    }
})

provide('properties', props.properties)
provide('rooms', props.rooms)
provide('manufacturers', props.manufacturers)
provide('categories', props.categories)
provide('statuses', props.statuses)

// provide tag groups and tags for articles
provide('tagGroups', props.tagGroups)
provide('tags', props.tags)



const gridLayout = ref(props.inventoryGridLayout)
const enableAddArticleToBasket = ref(false)
const showProductBasketModal = ref(false)
const showIssueOfMaterialModal = ref(false)
const internOrExternIssue = ref(false)
const searchArticleInput = ref(usePage().props?.urlParameters?.search ?? '')
const showAddEditArticleModal = ref(false);

const AddEditArticleModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Article/Modals/AddEditArticleModal.vue'),
})

const updateGridLayout = (value) => {
    gridLayout.value = value

    // Persist the preference to the backend
    router.post(route('inventory.update-grid-layout'), {
        inventory_grid_layout: value
    }, {
        preserveScroll: true,
        preserveState: true
    })
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

// Group articles by category and subcategory for the new list view
const groupedArticles = computed(() => {
    const grouped = {}

    props.articles.data.forEach((article) => {
        const categoryId = article.category?.id || 'no-category'
        const categoryName = article.category?.name || $t('Without Subcategory')
        const subCategoryId = article.sub_category?.id || 'no-subcategory'
        const subCategoryName = article.sub_category?.name || $t('Without Subcategory')

        if (!grouped[categoryId]) {
            grouped[categoryId] = {
                id: categoryId,
                name: categoryName,
                subcategories: {}
            }
        }

        if (!grouped[categoryId].subcategories[subCategoryId]) {
            // Load properties directly from subcategory or category definition
            const definedProperties = article.sub_category?.properties || article.category?.properties || []
            grouped[categoryId].subcategories[subCategoryId] = {
                id: subCategoryId,
                name: subCategoryName,
                articles: [],
                properties: [...definedProperties]
            }
        }

        grouped[categoryId].subcategories[subCategoryId].articles.push(article)
    })

    return grouped
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


const addArticleToBasket = (articleId) => {
    // add article to basket
    router.post(route('inventory.basket.add'), {
        article_id: articleId,
        quantity: 1
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // optionally show a success message or update UI
        }
    })
}

const findBasketForArticle = (articleId) => {
    if (!props.productBaskets || !props.productBaskets.basket_articles) {
        return null;
    }

    return props.productBaskets.basket_articles.find(basketArticle => basketArticle.article_id === articleId) || null;
}

// watch for search input
watch(searchArticleInput, (value) => {
    // search for articles with debounce
    searchArticles()
})

const closeModalProductBasket = (payload) => {
    showProductBasketModal.value = false;

    if(payload) {
        if (payload.createIssue) {
            internOrExternIssue.value = payload.internOrExternIssue;
            showIssueOfMaterialModal.value = true;
        }
    }
}
</script>

<style scoped>

</style>
