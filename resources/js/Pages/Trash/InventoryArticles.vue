<template>
    <AppLayout>
        <TrashLayout>

            <div class="flex w-full justify-between">
                <div>

                </div>
                <div class="flex justify-end items-center ml-8 -mt-14">
                    <div v-if="!showSearchbar" @click="openSearchbar"
                         class="cursor-pointer inset-y-0 mr-3">
                        <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                    </div>
                    <div v-else class="flex items-center w-64 mr-2">
                        <div>
                            <input type="text"
                                   :placeholder="$t('Search')"
                                   v-model="searchText"
                                   ref="searchBarInput"
                                   class="h-10 sDark inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                    </div>
                </div>
            </div>

            <ul role="list" class="divide-y divide-gray-100 min-w-7xl w-full" v-if="filteredArticles.length > 0">
                <li v-for="article in filteredArticles" :key="article.id" class="flex items-center justify-between gap-x-6 py-5 ">
                    <SingleArticleInTrash :article="article" />
                </li>
            </ul>

            <div v-else class="mt-2 min-w-7xl w-full">
                <BaseAlertComponent message="No articles found" use-translation type="error" />
            </div>
        </TrashLayout>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import SingleArticleInTrash from "@/Pages/Trash/Components/SingleArticleInTrash.vue";
import {SearchIcon, XIcon} from "@heroicons/vue/solid";
import {computed, nextTick, ref} from "vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

const props = defineProps({
    trashedArticles: {
        type: Object,
        required: true
    }
})


const showSearchbar = ref(false)
const searchBarInput = ref(null)
const searchText = ref('')
const openSearchbar = () => {
    showSearchbar.value = true
    nextTick(() => {
        searchBarInput.value.focus()
    })
}

const closeSearchbar = () => {
    showSearchbar.value = false
    searchText.value = ''
    nextTick(() => {
        searchBarInput.value.blur()
    })
}

const filteredArticles = computed(() => {
    if (searchText.value === '') {
        return props.trashedArticles
    } else {
        return props.trashedArticles.filter(article => {
            return article.name.toLowerCase().includes(searchText.value.toLowerCase())
        })
    }
})
</script>

<style scoped>

</style>