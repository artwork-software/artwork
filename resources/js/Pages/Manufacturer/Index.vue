<template>
    <AppLayout>

        <div class="ml-14 mt-5 mb-20">
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
                                {{ $t('Create Category') }}
                            </button>
                        </div>
                    </div>

                    <!--<BasePaginator
                        property-name="categories"
                        :entities="categories"
                    />-->

                </div>


                <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="manufacturer in manufacturers" :key="manufacturer.id" class="flex items-center justify-between gap-x-6 py-5">
                        <div class="min-w-0">
                            <div class="flex items-start gap-x-3">
                                <p class="text-sm/6 font-semibold text-gray-900">{{ manufacturer.name }}</p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                                <p class="whitespace-nowrap">
                                   {{ manufacturer.address }}
                                </p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                                <p class="whitespace-nowrap">
                                   {{ manufacturer.contact_person }}
                                </p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                                <p class="whitespace-nowrap">
                                   {{ manufacturer.phone }}
                                </p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                                <p class="whitespace-nowrap">
                                   {{ manufacturer.email }}
                                </p>
                            </div>
                            <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                                <p class="whitespace-nowrap">
                                   {{ $t('Customer Number') }}: {{ manufacturer.customer_number }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-none items-center gap-x-4">
                            <BaseMenu has-no-offset>

                            </BaseMenu>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {ref} from "vue";

const props = defineProps({
    manufacturers: {
        type: Object,
        required: true
    }
})

const showAddEditManufacturerModal = ref(false)

</script>

<style scoped>

</style>