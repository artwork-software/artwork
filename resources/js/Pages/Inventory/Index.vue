<template>
    <AppLayout>
        <div class="w-full ml-5 px-10 bg-gray-50">
            <div class="border-b border-gray-200 pt-8 pb-10">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900">Inventar</h1>
                <p class="mt-4 text-base text-gray-500">
                    Hier findest du alle Produkte, die im {{ $page.props.name }} Inventar verfügbar sind.
                </p>
            </div>

            <div class="pt-12 pb-24 lg:grid lg:grid-cols-3 lg:gap-x-8 xl:grid-cols-8">
                <aside class="">
                    <h2 class="sr-only">Filters</h2>
                    <div class="">
                        <div class="divide-y divide-gray-200">
                            <Link preserve-scroll :href="route('inventory.index')" class="group flex items-center justify-between py-4">
                                <span class=" text-sm font-bold tracking-tight" :class="route().current('inventory.index') ? 'text-artwork-buttons-create' : ''">All Products</span>
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 ring-1 ring-blue-500/10 ring-inset">{{ props.articlesCount }}</span>
                            </Link>
                            <div v-for="category in props.categories" :key="category.id" class="py-5 first:pt-0 last:pb-0">
                                <Link preserve-scroll class="flex items-center justify-between " :href="route('inventory.category.show', category.id)" :class="[route().current('inventory.category.show', category.id) ? 'text-artwork-buttons-create font-semibold' : '']">
                                    <div class="first-letter:capitalize text-sm font-bold tracking-tight">
                                        <div>
                                            {{ category.name }}
                                        </div>
                                    </div>
                                    <span v-if="category?.subcategories?.length > 0">
                                        <component is="IconChevronDown" class="size-5" stroke-width="1" aria-hidden="true"  :class="[category.id === currentCategory.id ? 'rotate-180' : '']" />
                                    </span>
                                </Link>

                                <div v-if="category.id === currentCategory?.id" :class="currentCategory?.subcategories?.length > 0 ? 'mt-4' : ''">
                                    <div v-for="subCategory in currentCategory.subcategories" :key="category.id" class="first:pt-0 last:pb-0">
                                        <Link preserve-scroll :href="route('inventory.sub.category.show', {
                                            inventoryCategory: category.id,
                                            subCategory: subCategory.id
                                        })" class="flex items-center justify-between" :class="[route().current('inventory.sub.category.show', {
                                            inventoryCategory: category.id,
                                            subCategory: subCategory.id
                                        }) ? 'text-artwork-buttons-create font-semibold' : '']">
                                            <div class="first-letter:capitalize text-xs px-3 py-1 flex items-center gap-x-0.5">
                                                <component is="IconPointFilled" class="size-4" stroke-width="1" aria-hidden="true" />
                                                <span class="first-letter:capitalize">
                                                    {{ subCategory.name }}
                                                </span>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <section aria-labelledby="product-heading" class="col-span-3 md:col-span-7">
                    <nav class="flex mb-5" aria-label="Breadcrumb">
                        <ol role="list" class="flex items-center space-x-4">
                            <li>
                                <div>
                                    <a href="#" class="text-gray-400 hover:text-gray-500">
                                        <component is="IconHome" class="size-5 shrink-0" aria-hidden="true" />
                                        <span class="sr-only">Inventory</span>
                                    </a>
                                </div>
                            </li>
                            <li v-if="currentCategory?.id">
                                <div class="flex items-center">
                                    <svg class="size-5 shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                                    </svg>
                                    <Link preserve-scroll :href="route('inventory.category.show', currentCategory?.id)"  class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700 first-letter:capitalize">{{ currentCategory.name }}</Link>
                                </div>
                            </li>
                            <li v-if="currentSubCategory?.id">
                                <div class="flex items-center">
                                    <svg class="size-5 shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                                    </svg>
                                    <Link  preserve-scroll :href="route('inventory.sub.category.show', {
                                                                inventoryCategory: currentCategory.id,
                                                                subCategory: currentSubCategory.id
                                                            })" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700 first-letter:capitalize">{{ currentSubCategory.name }}</Link>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h2 id="product-heading" class="sr-only">Products</h2>
                    <div v-if="props.articles.data.length > 0">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div v-for="item in props.articles.data">
                                <div class="w-full h-full p-6 bg-white rounded-lg border border-gray-100 hover:shadow-lg duration-300 ease-in-out cursor-pointer overflow-hidden">
                                    <div class="flex items-center justify-center">
                                        <img src="https://tailwindcss.com/plus-assets/img/ecommerce-images/category-page-02-image-card-01.jpg" alt="" class="w-32 h-32 object-center object-cover">
                                    </div>
                                    <div>
                                        <nav class="flex py-2" aria-label="Breadcrumb">
                                            <ol role="list" class="flex items-center space-x-1 text-xs text-artwork-buttons-create cursor-pointer">
                                                <li>
                                                    <div class="flex items-center gap-x-1">
                                                        <Link preserve-scroll :href="route('inventory.category.show', item.category.id)" class="font-medium  hover:text-gray-700 first-letter:capitalize truncate">
                                                            {{ item.category.name }}
                                                        </Link>
                                                    </div>
                                                </li>
                                                <li v-if="item.sub_category">
                                                    <div class="flex items-center gap-x-1">
                                                        <component is="IconChevronRight" class="size-3 shrink-0 text-gray-400" aria-hidden="true" />
                                                        <Link preserve-scroll :href="route('inventory.sub.category.show', {
                                                                inventoryCategory: item.category.id,
                                                                subCategory: item.sub_category.id
                                                            })" class="font-medium  hover:text-gray-700 first-letter:capitalize truncate">
                                                            {{ item.sub_category.name }}
                                                        </Link>
                                                    </div>
                                                </li>
                                            </ol>
                                        </nav>
                                        <h3 class="xsDark">{{ item.name }}</h3>
                                        <div class="my-2 xxsDark">
                                            <div v-for="property in item.properties">
                                                <div class="flex items-center justify-between py-1" v-if="property.show_in_list">
                                                    <div>
                                                        {{ property.name }}
                                                    </div>
                                                    <div>
                                                        {{ property.pivot.value }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between py-1">
                                                <div>
                                                    {{ $t('Quantity') }}
                                                </div>
                                                <div>
                                                    {{ item.quantity }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10">
                            <BasePaginator property-name="articles" :entities="articles" />
                        </div>

                    </div>
                    <div v-else>
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="shrink-0">
                                    <component is="IconExclamationCircleFilled" class="size-5 text-red-400" aria-hidden="true" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">
                                        {{ $t('No products found') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <pre>
                {{ currentCategory }}
            </pre>
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {Link, usePage} from "@inertiajs/vue3"
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
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
    }
})


const filters = [
    {
        id: 'color',
        name: 'Color',
        options: [
            { value: 'white', label: 'White' },
            { value: 'beige', label: 'Beige' },
            { value: 'blue', label: 'Blue' },
            { value: 'brown', label: 'Brown' },
            { value: 'green', label: 'Green' },
            { value: 'purple', label: 'Purple' },
        ],
    },
    {
        id: 'category',
        name: 'Category',
        options: [
            { value: 'new-arrivals', label: 'All New Arrivals' },
            { value: 'tees', label: 'Tees' },
            { value: 'crewnecks', label: 'Crewnecks' },
            { value: 'sweatshirts', label: 'Sweatshirts' },
            { value: 'pants-shorts', label: 'Pants & Shorts' },
        ],
    },
    {
        id: 'sizes',
        name: 'Sizes',
        options: [
            { value: 'xs', label: 'XS' },
            { value: 's', label: 'S' },
            { value: 'm', label: 'M' },
            { value: 'l', label: 'L' },
            { value: 'xl', label: 'XL' },
            { value: '2xl', label: '2XL' },
        ],
    },
]
const products = [
    {
        id: 1,
        name: 'Basic Tee 8-Pack',
        href: '#',
        price: '$256',
        description: 'Get the full lineup of our Basic Tees. Have a fresh shirt all week, and an extra for laundry day.',
        options: '8 colors',
        imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/category-page-02-image-card-01.jpg',
        imageAlt: 'Eight shirts arranged on table in black, olive, grey, blue, white, red, mustard, and green.',
    },
    {
        id: 2,
        name: 'Basic Tee',
        href: '#',
        price: '$32',
        description: 'Look like a visionary CEO and wear the same black t-shirt every day.',
        options: 'Black',
        imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/category-page-02-image-card-02.jpg',
        imageAlt: 'Front of plain black t-shirt.',
    },
    // More products...
]

</script>

<style scoped>

</style>