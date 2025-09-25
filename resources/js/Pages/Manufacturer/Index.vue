<template>
    <UserHeader
        title="Manufacturers"
        description="Edit and create manufacturers for your inventory."
    >
        <!-- Headbar -->
        <template #tabBar>
            <div class="flex items-center gap-3">
                <!-- Search -->
                <div class="flex items-center">
                    <button
                        v-if="!showSearchManufacturerInput && !searchManufacturerInput"
                        @click="openSearchbar"
                        type="button"
                        class="ui-button"
                    >
                        <SearchIcon class="size-6 text-zinc-600" aria-hidden="true" />
                    </button>

                    <div v-else class="flex items-center w-64 rounded-lg ring-1 ring-zinc-300 bg-white px-2 py-1.5">
                        <input
                            ref="searchBarInput"
                            v-model="searchManufacturerInput"
                            :placeholder="$t('Search Manufacturer')"
                            type="text"
                            autocomplete="off"
                            class="w-full bg-transparent text-sm text-zinc-900 placeholder:text-zinc-400 outline-none"
                        />
                        <button @click="closeSearchbar" type="button" class="ml-1 p-1 rounded hover:bg-zinc-100">
                            <XIcon class="size-5 text-zinc-500" />
                        </button>
                    </div>
                </div>

                <!-- Create -->
                <BaseCardButton text="Create Manufacturer" class="whitespace-nowrap" @click="showAddEditManufacturerModal = true" />
            </div>
        </template>

        <!-- Content -->
        <template #default>
            <!-- Skeleton loader while fetching -->
            <ul v-if="isLoading" role="list" class="divide-y divide-zinc-100">
                <li v-for="n in 6" :key="n" class="py-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded bg-zinc-200 animate-pulse" />
                            <div class="h-4 w-48 rounded bg-zinc-200 animate-pulse" />
                        </div>
                        <div class="h-8 w-24 rounded bg-zinc-200 animate-pulse" />
                    </div>
                </li>
            </ul>

            <!-- List -->
            <ul
                v-else-if="manufacturers?.data?.length > 0"
                role="list"
                class="divide-y divide-zinc-100 rounded-xl bg-white ring-1 ring-zinc-200"
            >
                <li
                    v-for="manufacturer in manufacturers.data"
                    :key="manufacturer.id"
                    class="flex items-center justify-between gap-x-6 px-4 py-4 sm:px-6"
                >
                    <SingleManufacturerInList :manufacturer="manufacturer" />
                </li>
            </ul>

            <!-- Empty state -->
            <div v-else class="rounded-xl border border-zinc-200 bg-white p-6 text-center">
                <BaseAlertComponent message="No manufacturers found" use-translation type="error" />
                <div class="mt-4">
                    <BaseCardButton text="Create Manufacturer" @click="showAddEditManufacturerModal = true" />
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-5">
                <BasePaginator property-name="manufacturers" :entities="manufacturers" />
            </div>
        </template>
    </UserHeader>

    <!-- Modal -->
    <CreateOrUpdateManufacturerModal
        v-if="showAddEditManufacturerModal"
        @close="showAddEditManufacturerModal = false"
    />
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import UserHeader from '@/Pages/Users/UserHeader.vue'
import BaseCardButton from '@/Artwork/Buttons/BaseCardButton.vue'
import BasePaginator from '@/Components/Paginate/BasePaginator.vue'
import SingleManufacturerInList from '@/Pages/Manufacturer/Components/General/SingleManufacturerInList.vue'
import CreateOrUpdateManufacturerModal from '@/Pages/Manufacturer/Components/Modals/CreateOrUpdateManufacturerModal.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import { SearchIcon, XIcon } from '@heroicons/vue/outline'
import debounce from 'lodash.debounce'

const props = defineProps({
    manufacturers: { type: Object, required: true },
})

const showAddEditManufacturerModal = ref(false)
const searchManufacturerInput = ref(usePage().props?.urlParameters?.search ?? '')
const showSearchManufacturerInput = ref(false)
const searchBarInput = ref(null)
const isLoading = ref(false)

const openSearchbar = () => {
    showSearchManufacturerInput.value = true
    nextTick(() => searchBarInput.value?.focus())
}

const closeSearchbar = () => {
    searchManufacturerInput.value = ''
    showSearchManufacturerInput.value = false
}

/** Debounced search */
const searchManufacturers = debounce(() => {
    router.reload({
        data: { search: searchManufacturerInput.value },
        preserveScroll: true,
        only: ['manufacturers'],
    })
}, 500)

watch(searchManufacturerInput, () => searchManufacturers())


</script>

<style scoped></style>
