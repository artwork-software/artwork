<template>
    <AppLayout :title="$t('Inventory')">
        <div class="w-full px-10 min-h-screen">
            <!-- Sticky Funktionsleiste über die gesamte Breite -->
            <div class="sticky top-0 z-40 -mx-10 px-10 bg-white border-b border-border-subtle pt-3 pb-2 shadow-sm">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                    <div class="w-72">
                        <BaseInput
                            id="productSearch"
                            v-model="searchArticleInput"
                            :label="$t('Search Articles')"
                            is-small
                        />
                    </div>

                    <div
                        class="w-56"
                        v-tooltip.bottom="{ value: $t('Here you can specify whether to search all properties or only a specific one.'), class: 'aw-tooltip', appendTo: 'body' }"
                    >
                        <ArtworkBaseListbox
                            :model-value="selectedSearchProperty"
                            @update:model-value="onSearchPropertyChange"
                            :items="propertySearchOptions"
                            by="id"
                            option-label="name"
                            :button-class="COMPACT_LISTBOX_BUTTON_CLASS"
                        />
                    </div>

                    <div class="grow"></div>

                    <div class="flex items-center gap-x-4">
                        <div class="relative">
                            <ToolTipComponent
                                direction="bottom"
                                :tooltip-text="$t('Opens your product basket. Enable the toggle next to it to add articles by clicking them in the overview; when you are done, open the basket here, make final adjustments and create a material issue from it.')"
                                icon="IconBasket"
                                icon-size="h-5 w-5"
                                classesButton="ui-button"
                                @click="showProductBasketModal = true"
                            />
                            <span class="absolute -top-2 -right-2 size-5 rounded-full bg-accent-50 ring-1 ring-accent-200 text-accent-600 text-xs flex items-center justify-center pointer-events-none">
                                {{ productBaskets?.basket_articles?.length ?? 0 }}
                            </span>
                        </div>

                        <SwitchIconTooltip
                            v-model="enableAddArticleToBasket"
                            :tooltip-text="$t('Enable this toggle to add articles to your product basket by clicking them in the overview. Then use the basket button next to it to review the basket and create a material issue.')"
                            size="md"
                            icon="IconBasket"
                        />

                        <InventoryLayoutSwitchComponent :grid-layout="gridLayout" @update:gridLayout="updateGridLayout" />

                        <div class="relative">
                            <ToolTipComponent
                                direction="bottom"
                                :tooltip-text="$t('Display settings')"
                                icon="IconSettings"
                                icon-size="h-5 w-5"
                                classesButton="ui-button"
                                @click="showDisplaySettingsModal = true"
                            />
                            <span class="absolute flex size-2.5 top-0 right-0 pointer-events-none" v-if="hideArticleImages">
                                <span class="relative inline-flex size-2.5 rounded-full bg-accent-600"></span>
                            </span>
                        </div>

                        <InventoryFilterComponent :filterable-properties="filterableProperties ?? []" />

                        <ToolTipComponent
                            v-if="can('inventory.create_edit') || hasAdminRole()"
                            direction="bottom"
                            :tooltip-text="$t('Add Article')"
                            icon="IconCirclePlus"
                            icon-size="h-5 w-5"
                            classesButton="ui-button"
                            @click="showAddEditArticleModal = true"
                        />
                    </div>
                </div>

                <!-- Status-Schnellfilter: eine Zeile, direkt klickbar -->
                <div class="mt-2 overflow-x-auto" v-if="Object.keys(props.countsByStatus ?? {}).length">
                    <StatusOverview :counts-by-status="props.countsByStatus" :active-status-id="props.activeStatusId" />
                </div>
            </div>

            <!-- Aktive Filter-Chips -->
            <div class="mt-3">
                <InventoryActiveFilterChips
                    :search="searchArticleInput"
                    :search-property-id="searchPropertyId"
                    :counts-by-status="props.countsByStatus"
                    :active-status-id="props.activeStatusId"
                    @clear-search="onClearSearchChip"
                    @reset="clearSearchLocal"
                />
            </div>

            <div class="pt-4 pb-24 grid grid-cols-1 sm:grid-cols-4 md:grid-cols-6 lg:gap-x-8 lg:grid-cols-8 2xl:flex 2xl:w-full">

                <div class="col-span-full md:col-span-full lg:col-span-2 xl:col-span-2 2xl:max-w-96 2xl:w-96">
                    <InventorySidebarComponent
                        :current-category="props.currentCategory"
                        :articles-count="props.articlesCount"
                        :has-active-filter="props.hasActiveFilter"
                        :categories="props.categories"
                        :link-query="persistentLinkQuery"
                    />
                </div>
                <section aria-labelledby="product-heading" class="col-span-full lg:col-span-6 2xl:w-full">
                    <div class="mb-3" v-if="props.currentCategory?.id">
                        <InventoryBreadcrumbComponent :current-category="props.currentCategory" :current-sub-category="props.currentSubCategory" :link-query="persistentLinkQuery"/>
                    </div>
                    <div v-if="props.articles.data.length > 0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 3xl:grid-cols-8 gap-4 items-stretch" v-if="gridLayout">
                            <div v-for="item in props.articles.data">
                                <div class="relative">
                                    <div v-if="enableAddArticleToBasket" class="absolute inset-0 bg-text-subtle rounded-lg opacity-0 hover:opacity-100 duration-200 cursor-pointer">
                                        <div class="flex items-center justify-center h-full w-full">
                                            <div class="relative">
                                                <span class="absolute -top-2 -right-2 size-5 rounded-full bg-accent-50 ring-2 ring-white text-accent-600 text-xs flex items-center justify-center">
                                                    {{ findBasketForArticle(item.id) ? findBasketForArticle(item.id).quantity : 0 }}
                                                </span>
                                                <BaseUIButton :label="$t('Add to Basket')" use-translation icon="IconBasketPlus" @click="addArticleToBasket(item.id)" />

                                            </div>
                                        </div>
                                    </div>
                                    <InventorySingleArticleInGrid :item="item" :hide-image="hideArticleImages" />
                                </div>
                            </div>
                        </div>
                        <div v-else class="space-y-10">
                            <!-- Loop through each category -->
                            <div v-for="(category, index) in groupedArticles" :key="category.id" class="space-y-3">
                                <!-- Category Header -->
                                <div class="border-b-2 border-border pb-2">
                                    <h2 class="text-xl font-bold text-text">{{ category.name }}</h2>
                                </div>

                                <!--
                                    One table for the WHOLE category so that identical properties line up
                                    vertically across all subcategories. The union of every subcategory's
                                    properties defines a fixed column set (fixed order + fixed widths).
                                    Subcategories become full-width group rows inside this single table;
                                    the whole category scrolls horizontally as one unit when there are many
                                    properties. Articles without a given property show a dash.
                                -->
                                <!--
                                    The category scrolls inside itself (both axes) via max-height + overflow-auto
                                    so that `sticky top-0` on the header has a scroll container to dock to — with
                                    page-level scrolling a sticky header would have no reference point and scroll
                                    away. Short categories simply don't scroll internally.
                                -->
                                <div class="max-h-[75vh] overflow-auto border border-border-subtle rounded-lg">
                                    <!--
                                        NOTE: no `min-w-full` here. With `table-fixed`, a table narrower than
                                        its container would distribute the slack proportionally across ALL
                                        columns, which would break the hardcoded sticky `left` offsets below
                                        (Bild/Name/Menge would render wider than nominal → gaps appear and
                                        scrolling columns bleed through). Letting the table size to the sum of
                                        the fixed column widths keeps the sticky offsets exact.
                                    -->
                                    <table class="w-max table-fixed divide-y divide-border-subtle">
                                        <colgroup>
                                            <col v-if="!hideArticleImages" class="w-20" />
                                            <col class="w-64" />
                                            <col class="w-28" />
                                            <col v-for="property in category.unionProperties" :key="property.id" class="w-44" />
                                            <col class="w-24" />
                                        </colgroup>
                                        <thead class="bg-surface-sunken">
                                            <tr class="divide-x divide-border-subtle">
                                                <!-- Corner cells: pinned both top (header) and left (identity columns) -> higher z -->
                                                <th v-if="!hideArticleImages" scope="col" class="sticky top-0 left-0 z-30 bg-surface-sunken py-3.5 pr-4 pl-4 text-center text-sm font-semibold text-text">
                                                    {{ $t('Image') }}
                                                </th>
                                                <th scope="col" class="sticky top-0 z-30 bg-surface-sunken px-4 py-3.5 text-left text-sm font-semibold text-text" :class="hideArticleImages ? 'left-0' : 'left-20'">
                                                    {{ $t('Name') }}
                                                </th>
                                                <th scope="col" class="sticky top-0 z-30 bg-surface-sunken px-4 py-3.5 text-left text-sm font-semibold text-text" :class="hideArticleImages ? 'left-[256px]' : 'left-[336px]'">
                                                    {{ $t('Quantity') }}
                                                </th>
                                                <!-- Union of all properties across this category's subcategories (pinned top only) -->
                                                <th scope="col" class="sticky top-0 z-20 bg-surface-sunken px-4 py-3.5 text-left text-sm font-semibold text-text truncate"
                                                    :class="isNumericProperty(property) ? 'text-right' : ''"
                                                    v-for="property in category.unionProperties"
                                                    :key="property.id"
                                                    :title="property?.name">
                                                    {{ property?.name }}
                                                </th>
                                                <th scope="col" class="sticky top-0 z-20 bg-surface-sunken py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text">
                                                    {{ $t('Actions') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border-subtle bg-white">
                                            <template v-for="subcategory in category.subcategories" :key="subcategory.id">
                                                <!-- Subcategory group row (spans the full width of the shared table) -->
                                                <tr class="bg-surface-sunken">
                                                    <td :colspan="category.unionProperties.length + (hideArticleImages ? 3 : 4)" class="p-0">
                                                        <div class="sticky left-0 w-fit px-4 py-2 border-l-4 border-accent-600 flex items-center gap-x-2">
                                                            <h3 class="text-sm font-semibold text-text">{{ subcategory.name }}</h3>
                                                            <span class="inline-flex items-center rounded-full bg-border-subtle px-2 py-0.5 text-xs font-medium text-text-muted">
                                                                {{ subcategory.articles.length }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <!-- Article rows share the category-wide column layout -->
                                                <tr v-for="item in subcategory.articles" :key="item?.id" class="divide-x divide-border-subtle relative bg-white hover:bg-surface-sunken">
                                                    <InventorySingleArticleInTable
                                                        :item="item"
                                                        :column-properties="category.unionProperties"
                                                        :enable-add-article-to-basket="enableAddArticleToBasket"
                                                        :find-basket-for-article="findBasketForArticle"
                                                        :hide-image="hideArticleImages"
                                                        @add-to-basket="addArticleToBasket"
                                                    />
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="mt-10">
                            <BasePaginator property-name="articles" :entities="articles" />
                        </div>
                    </div>
                    <div v-else>
                        <!-- Bei aktiven Filtern direkt anbieten, sie zurückzusetzen -->
                        <div
                            v-if="props.hasActiveFilter || props.activeStatusId"
                            class="rounded-lg border border-accent-200 bg-accent-50 px-4 py-3 flex flex-wrap items-center justify-between gap-3"
                        >
                            <span class="text-sm text-accent-700">
                                {{ $t('No articles match your search or filters.') }}
                            </span>
                            <BaseUIButton
                                type="button"
                                icon="IconRefresh"
                                label="Reset all filters"
                                use-translation
                                @click="onResetFromEmptyState"
                            />
                        </div>
                        <InventoryEmptyProductsAlertComponent v-else />
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

            <ArtworkBaseModal
                v-if="showDisplaySettingsModal"
                modalSize="sm:max-w-lg"
                :title="$t('Display settings')"
                :description="$t('These settings only affect your own view.')"
                @close="showDisplaySettingsModal = false"
            >
                <div class="mt-2">
                    <BaseCheckbox
                        :model-value="hideArticleImages"
                        :label="$t('Hide article images in overview')"
                        :description="$t('Shows more article tiles at once by hiding the images.')"
                        @update:model-value="updateHideArticleImages"
                    />
                </div>
            </ArtworkBaseModal>
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
import {computed, nextTick, ref, watch, provide, defineAsyncComponent} from "vue";
import debounce from "lodash.debounce";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import InventoryFilterComponent from "@/Pages/Inventory/LayoutComponents/InventoryFilterComponent.vue";
import InventoryActiveFilterChips from "@/Pages/Inventory/LayoutComponents/InventoryActiveFilterChips.vue";
import {COMPACT_LISTBOX_BUTTON_CLASS, INVENTORY_FILTER_RELOAD_PROPS, useInventoryFilters} from "@/Pages/Inventory/Composables/useInventoryFilters.js";
import InventoryLayoutSwitchComponent from "@/Pages/Inventory/LayoutComponents/InventoryLayoutSwitchComponent.vue";
import InventorySingleArticleInTable from "@/Pages/Inventory/TableComponents/InventorySingleArticleInTable.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {IconBarcode, IconIdBadge, IconLayoutGrid, IconLayoutList} from "@tabler/icons-vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {can} from "laravel-permission-to-vuejs";
import {usePermission} from "@/Composeables/Permission.js";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import ProductBasketModal from "@/Pages/Inventory/ProductBasket/Components/ProductBasketModal.vue";
import StatusOverview from "@/Pages/Inventory/Components/StatusOverview.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation()
const { hasAdminRole } = usePermission(usePage().props)

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
    hasActiveFilter: {
        type: Boolean,
        required: false,
        default: false
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
    activeStatusId: {
        type: Number,
        required: false,
        default: null
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
    },
    inventoryHideImages: {
        type: Boolean,
        required: false,
        default: false
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
const hideArticleImages = ref(props.inventoryHideImages)
const enableAddArticleToBasket = ref(false)
const showProductBasketModal = ref(false)
const showIssueOfMaterialModal = ref(false)
const internOrExternIssue = ref(false)
const showAddEditArticleModal = ref(false);
const showDisplaySettingsModal = ref(false);

const { resetAllFilters } = useInventoryFilters()

/**
 * Suche (+ optionaler Eigenschafts-Scope) in der Funktionsleiste.
 */
const searchArticleInput = ref(usePage().props?.urlParameters?.search ?? '')
const searchPropertyId = ref(
    usePage().props?.urlParameters?.search_property_id
        ? Number(usePage().props.urlParameters.search_property_id)
        : null
)

const propertySearchOptions = computed(() => [
    { id: null, name: $t('All properties') },
    ...(props.properties ?? []),
])
const selectedSearchProperty = computed(() =>
    propertySearchOptions.value.find((p) => p.id === searchPropertyId.value) ?? propertySearchOptions.value[0]
)
const onSearchPropertyChange = (value) => {
    searchPropertyId.value = (value && typeof value === 'object') ? (value.id ?? null) : (value ?? null)
}

// Status und Suche leben nur in der URL (nicht im serverseitigen Filter-State).
// Damit sie beim Kategoriewechsel aktiv bleiben, bekommen Sidebar- und
// Breadcrumb-Links sie als Query-Parameter mit.
const persistentLinkQuery = computed(() => {
    const query = {}
    if (props.activeStatusId) query.status_id = props.activeStatusId
    if ((searchArticleInput.value ?? '').trim() !== '') query.search = searchArticleInput.value
    if (searchPropertyId.value) query.search_property_id = searchPropertyId.value
    return query
})

// Programmatischen Wechsel (Reset/Chip-Entfernung) vom User-Tippen
// unterscheiden, damit kein doppelter Reload ausgelöst wird.
let suppressSearchWatch = false

const debouncedSearch = debounce(() => {
    router.reload({
        data: {
            search: searchArticleInput.value,
            search_property_id: searchPropertyId.value,
        },
        preserveScroll: true,
        only: INVENTORY_FILTER_RELOAD_PROPS,
    })
}, 500)

watch([searchArticleInput, searchPropertyId], () => {
    if (suppressSearchWatch) return
    debouncedSearch()
})

const clearSearchLocal = () => {
    suppressSearchWatch = true
    searchArticleInput.value = ''
    searchPropertyId.value = null
    nextTick(() => {
        suppressSearchWatch = false
    })
    debouncedSearch.cancel()
}

// Such-Chip in der Chips-Zeile entfernt -> lokal leeren + Suche zurücksetzen
const onClearSearchChip = () => {
    clearSearchLocal()
    router.reload({
        data: { search: '', search_property_id: null },
        preserveScroll: true,
        only: INVENTORY_FILTER_RELOAD_PROPS,
    })
}

// Reset-Button im Leer-Zustand: alles zurücksetzen (Filter, Tags, Preset,
// Suche, Status) und die lokalen Suchfelder leeren.
const onResetFromEmptyState = () => {
    clearSearchLocal()
    resetAllFilters()
}

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

const updateHideArticleImages = (value) => {
    hideArticleImages.value = value

    // Persist the per-user preference to the backend
    router.post(route('inventory.update-hide-images'), {
        inventory_hide_images: value
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

// Stable, global property order (by id) so union columns keep a consistent order
// across every subcategory of a category. Unknown properties are appended.
const propertyOrderIndex = computed(() => {
    const map = new Map()
    ;(props.properties ?? []).forEach((property, index) => map.set(property.id, index))
    return map
})

const isNumericProperty = (property) => property?.type === 'number' || property?.type === 'year'

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

    // Build one shared, ordered column set (union) per category so that identical
    // properties end up in the same column across all its subcategories.
    const orderIndex = propertyOrderIndex.value
    const rank = (property) => (orderIndex.has(property.id) ? orderIndex.get(property.id) : Number.MAX_SAFE_INTEGER)

    Object.values(grouped).forEach((category) => {
        const union = new Map()
        Object.values(category.subcategories).forEach((subcategory) => {
            subcategory.properties.forEach((property) => {
                if (!union.has(property.id)) {
                    union.set(property.id, property)
                }
            })
        })

        category.unionProperties = [...union.values()].sort((a, b) => {
            const rankDiff = rank(a) - rank(b)
            return rankDiff !== 0 ? rankDiff : (a.name || '').localeCompare(b.name || '')
        })
    })

    return grouped
})

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
