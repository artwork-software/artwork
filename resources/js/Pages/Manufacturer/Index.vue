<template>
    <UserHeader title="Manufacturers" description="Edit and create manufacturers for your inventory.">

        <template #tabBar>
            <div class="flex items-center gap-x-4">
                <div>
                    <div v-if="!showSearchManufacturerInput && !searchManufacturerInput" @click="showSearchManufacturerInput = !showSearchManufacturerInput">
                        <SearchIcon class="size-7 !text-artwork-buttons-context" aria-hidden="true" stroke-width="1.5"/>
                    </div>
                    <div v-else class="flex items-center w-64">
                        <input ref="searchBarInput" v-model="searchManufacturerInput" :placeholder="$t('Search Manufacturer')" type="text" class="h-10 sDark inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300" />
                        <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                    </div>
                </div>
                <div>
                    <BaseCardButton text="Create Manufacturer" @click="showAddEditManufacturerModal = true"/>
                </div>
            </div>
        </template>

        <template #default>
            <ul role="list" class="divide-y divide-gray-100" v-if="manufacturers.data.length > 0">
                <li v-for="manufacturer in manufacturers.data" :key="manufacturer.id" class="flex items-center justify-between gap-x-6 py-5">
                    <SingleManufacturerInList :manufacturer="manufacturer" />
                </li>
            </ul>

            <div v-else >
                <BaseAlertComponent message="No manufacturers found" use-translation type="error" />
            </div>

            <div class="mt-5">
                <BasePaginator
                    property-name="manufacturers"
                    :entities="manufacturers"
                />
            </div>
        </template>


    </UserHeader>

    <CreateOrUpdateManufacturerModal
        v-if="showAddEditManufacturerModal"
        @close="showAddEditManufacturerModal = false"
    />
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
import BaseCardButton from "@/Artwork/Buttons/BaseCardButton.vue";
import {SearchIcon, XIcon} from "@heroicons/vue/outline";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

const props = defineProps({
    manufacturers: {
        type: Object,
        required: true
    }
})

const showAddEditManufacturerModal = ref(false)
const searchManufacturerInput = ref(usePage().props?.urlParameters?.search ?? '')
const showSearchManufacturerInput = ref(false)

const closeSearchbar = () => {
    searchManufacturerInput.value = ''
    showSearchManufacturerInput.value = false
}


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