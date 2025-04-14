<template>
    <UserHeader>
        <div class="mt-5 mb-20">
            <div class="container max-w-7xl">
                <div class="mb-10">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <TinyPageHeadline
                                :title="$t('Manufacturers')"
                                :description="$t('Edit and create manufacturers for your inventory.')"
                            />
                        </div>
                        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                            <button @click="showAddEditManufacturerModal = true" type="button" class="block rounded-md bg-artwork-buttons-create px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-artwork-buttons-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ $t('Create Manufacturer') }}
                            </button>
                        </div>
                    </div>

                    <div class="max-w-xs pt-5">
                        <!-- name filter and search -->
                        <TextInputComponent
                            id="productSearch"
                            v-model="searchManufacturerInput"
                            :label="$t('Search Manufacturer')"
                        />
                    </div>
                </div>


                <ul role="list" class="divide-y divide-gray-100" v-if="manufacturers.data.length > 0">
                    <li v-for="manufacturer in manufacturers.data" :key="manufacturer.id" class="flex items-center justify-between gap-x-6 py-5">
                        <SingleManufacturerInList :manufacturer="manufacturer" />
                    </li>
                </ul>

                <div v-else >
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <component is="IconExclamationCircleFilled" class="size-5 text-red-400" aria-hidden="true" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ $t('No manufacturers found') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <BasePaginator
                        property-name="manufacturers"
                        :entities="manufacturers"
                    />
                </div>
            </div>
        </div>

        <CreateOrUpdateManufacturerModal
            v-if="showAddEditManufacturerModal"
            @close="showAddEditManufacturerModal = false"
        />

    </UserHeader>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {ref, watch} from "vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import debounce from "lodash.debounce";
import {router, usePage} from "@inertiajs/vue3";
import SingleManufacturerInList from "@/Pages/Manufacturer/Components/General/SingleManufacturerInList.vue";
import CreateOrUpdateManufacturerModal
    from "@/Pages/Manufacturer/Components/Modals/CreateOrUpdateManufacturerModal.vue";
import UserHeader from "@/Pages/Users/UserHeader.vue";

const props = defineProps({
    manufacturers: {
        type: Object,
        required: true
    }
})

const showAddEditManufacturerModal = ref(false)
const searchManufacturerInput = ref(usePage().props?.urlParameters?.search ?? '')

const searchManufacturers = debounce(() => {
    // search for articles
    router.reload({
        data: {
            search: searchManufacturerInput.value,
        },
        preserveScroll: true,
        only: ['manufacturers']
    })
}, 500)


// watch for search input
watch(searchManufacturerInput, (value) => {
    // search for articles with debounce
    searchManufacturers()
})
</script>

<style scoped>

</style>