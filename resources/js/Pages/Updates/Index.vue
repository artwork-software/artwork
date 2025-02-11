<template>
    <AppLayout>
        <div class="inset-0 absolute z-50 bg-black opacity-50" v-if="isLoaded">
            <div class="w-full h-full flex items-center justify-center">
                <div class="bg-white p-8 rounded-lg shadow-lg flex items-center justify-center flex-col gap-y-3">
                    <svg class="animate-spin h-8 w-8 text-artwork-buttons-create" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <div class="headline3">Aktualisierungsdaten laden</div>
                </div>
            </div>
        </div>

        <div class="mx-14 my-14">
            <h1 class="headline2 mb-5">Updates</h1>
            <div class="space-y-4">
                <div v-for="item in items">
                    <div @click="openAndLoadUpdateDetailModal(item)" class="bg-white shadow-md rounded-lg border border-gray-200 hover:shadow-lg duration-200 ease-in-out cursor-pointer">
                        <div class="p-4 flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-800">{{ item.properties.find(property => property.id === 'title')?.plainText }}</h2>
                            <div v-if="item.properties.some(property => property.title === 'Status')">
                                <div
                                    v-for="property in item.properties.filter(p => p.title === 'Status')"
                                    :key="property.id"
                                    class="inline-block px-3 py-1 text-white text-xs rounded-full"
                                    :class="{
                                        'bg-gray-500': property.rawContent.color === 'gray',
                                        'bg-red-500': property.rawContent.color === 'red',
                                        'bg-green-500': property.rawContent.color === 'green',
                                        'bg-blue-500': property.rawContent.color === 'blue',
                                        'bg-yellow-500': property.rawContent.color === 'yellow',
                                        'bg-purple-500': property.rawContent.color === 'purple',
                                        'bg-pink-500': property.rawContent.color === 'pink',
                                        'bg-orange-500': property.rawContent.color === 'orange',
                                        'bg-brown-500': property.rawContent.color === 'brown',
                                    }">
                                    {{ property.rawContent?.name }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-gray-100">
                            <div v-if="item.properties.some(property => property.title === 'Author')">
                                <div class="flex items-center gap-x-2">
                                    <img
                                        v-if="item.properties.find(property => property.title === 'Author')?.rawContent[0]?.avatar_url"
                                        :src="item.properties.find(property => property.title === 'Author')?.rawContent[0]?.avatar_url"
                                        class="size-8 rounded-full object-cover"
                                    >
                                    <p class="xsDark">
                                        {{ item.properties.find(property => property.title === 'Author')?.rawContent[0]?.name }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>


        <ShowUpdateDetailModal
            v-if="openUpdateDetailModal"
            @close="openUpdateDetailModal = false"
            :item="updateDataForModal"
            :content="content"
            :content-as-collection="contentAsCollection"
        />




    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {router} from "@inertiajs/vue3";
import {ref} from "vue";
import ShowUpdateDetailModal from "@/Pages/Updates/Components/ShowUpdateDetailModal.vue";

const props = defineProps({
    items: {
        type: Object,
        required: true
    },
    content: {
        type: Object,
        required: true
    },
    contentAsCollection: {
        type: Object,
        required: true
    }
})

const updateDataForModal = ref(null)
const openUpdateDetailModal = ref(false)
const isLoaded = ref(false)
const openAndLoadUpdateDetailModal = (item) => {
    isLoaded.value = true;
    router.reload({
        //only: ['content'],
        data: {
            updateId: item.id
        },
        onSuccess: () => {
            updateDataForModal.value = item;
            openUpdateDetailModal.value = true;
            isLoaded.value = false;
        }
    })
}

</script>

<style scoped>

</style>