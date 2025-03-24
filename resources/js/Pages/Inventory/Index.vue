<template>
    <AppLayout>
        <div class="w-full ml-5 px-10 bg-gray-50 min-h-screen">
            <div class="border-b border-gray-200 pt-8 pb-5">
                <div class="max-w-3xl">
                    <TinyPageHeadline
                        :title="$t('Inventory')"
                        :description="$t('Welcome to the {0} inventory! Here you will find a complete overview of all available products. You can browse through the various items, view details and manage which products are currently in stock.', [usePage().props.name])"
                    />
                </div>

                <div class="">
                    <!-- name filter and search -->
                </div>

                <div class="mt-5">
                    <div @click="showAddEditArticleModal = true" class="flex items-center gap-x-2 text-gray-400 font-lexend font-semibold cursor-pointer hover:text-gray-600 duration-200 ease-in-out">
                        <component is="IconBarcode" class="size-5" aria-hidden="true" />
                        <span>
                            {{ $t('Add Article') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="pt-12 pb-24 lg:grid lg:grid-cols-3 lg:gap-x-8 xl:grid-cols-8">

                <InventorySidebarComponent
                    :current-category="props.currentCategory"
                    :articles-count="props.articlesCount"
                    :categories="props.categories"
                />

                <section aria-labelledby="product-heading" class="col-span-3 md:col-span-7">
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-7 3xl:grid-cols-8 gap-4" v-if="gridLayout">
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
            />
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {Link, usePage} from "@inertiajs/vue3"
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import InventorySidebarComponent from "@/Pages/Inventory/LayoutComponents/InventorySidebarComponent.vue";
import InventoryBreadcrumbComponent from "@/Pages/Inventory/LayoutComponents/InventoryBreadcrumbComponent.vue";
import InventoryEmptyProductsAlertComponent
    from "@/Pages/Inventory/LayoutComponents/InventoryEmptyProductsAlertComponent.vue";
import InventorySingleArticleInGrid from "@/Pages/Inventory/GridComponents/InventorySingleArticleInGrid.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {Switch} from "@headlessui/vue";
import {computed, onMounted, ref} from "vue";
import AddEditArticleModal from "@/Pages/Inventory/Components/Article/Modals/AddEditArticleModal.vue";
import InventoryFilterComponent from "@/Pages/Inventory/LayoutComponents/InventoryFilterComponent.vue";
import InventoryLayoutSwitchComponent from "@/Pages/Inventory/LayoutComponents/InventoryLayoutSwitchComponent.vue";
import InventorySingleArticleInTable from "@/Pages/Inventory/TableComponents/InventorySingleArticleInTable.vue";
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
    }
})

const gridLayout = ref(true)

const showAddEditArticleModal = ref(false);

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
</script>

<style scoped>

</style>