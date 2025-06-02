<template>
    <AppLayout>
        <div class="-ml-4">
            <!-- topbar with date range selector -->
            <div class="flex items-center p-4 border-b shadow-sm">
                <date-picker-component v-if="dataArray" :dateValueArray="dataArray" :is_inventory_article_planning="true"/>
            </div>


            <div class="flex flex-col w-full h-full overflow-hidden relative">
                <div class="flex-1 overflow-auto text-sm relative">
                    <!-- Innerer flex Container für horizontales Scrollen -->
                    <div class="min-w-max">
                        <!-- Kopfzeile: Zeitleiste -->
                        <div class="flex sticky top-0 z-30 bg-artwork-navigation-background shadow-sm text-sm font-medium text-white">
                            <div class="sticky left-0 z-20 bg-artwork-navigation-background px-4 py-2  font-medium w-[200px] min-w-[200px] flex items-center">Artikel</div>
                            <div v-for="date in dates" :key="date.date" class="flex-1 px-4 py-2 text-center font-lexend text-xs min-w-24 max-w-24 w-24 flex items-center justify-center">
                                {{ formatDate(date.date) }}
                            </div>
                        </div>

                        <!-- Hauptbereich -->
                        <div>
                            <template v-for="group in groupedArticles" :key="group.category">
                                <!-- Kategorie (sticky in erster Spalte) -->
                                <div class="flex font-bold bg-gray-200">
                                    <div class="sticky left-0 z-20 font-bold font-lexend text-xs px-4 py-3 min-w-[200px] w-[200px] flex items-center gap-x-1">
                                        <component is="IconCategoryFilled" class="inline-block size-4" />
                                        {{ group.category }}
                                    </div>
                                    <div class="flex-1"></div>
                                </div>

                                <!-- Artikel ohne Subkategorie -->
                                <div v-for="article in group.articles" :key="article.id" class="flex border-b">
                                    <div class="sticky left-0 z-20 bg-white px-4 py-3 text-xs text-gray-900 font-medium border-r w-[200px] min-w-[200px]">
                                        {{ article.name }}
                                    </div>
                                    <div v-for="date in dates" :key="date.date" @click="openDetailModal(article.id, date.date)" class="text-xs flex-1 px-4 py-3 text-center border-r min-w-24 max-w-24 w-24 flex items-center justify-center hover:bg-gray-300 cursor-pointer transition-all duration-300 ease-in-out" :class="[date.isWeekend ? 'bg-gray-200' : 'bg-white']">
                                        <span :class="{ 'text-red-600 font-bold': availability[date.date]?.[article.id] < 0}">
                                          {{ availability[date.date]?.[article.id] ?? 0 }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Subkategorien -->
                                <template v-for="sub in group.subcategories" :key="sub.name">
                                    <div class="flex border-t bg-gray-200 font-bold text-gray-800">
                                        <div class="sticky left-0 z-20  font-lexend text-xs px-4 py-3 min-w-[200px] w-[200px] flex items-center gap-x-1 border-r">
                                            <div class="relative">
                                                <component is="IconRadiusBottomLeft" class="inline-block size-3 absolute" />
                                                <component is="IconCategory2" class="inline-block size-4 ml-4" />
                                                {{ sub.name }}
                                            </div>
                                        </div>
                                        <div class="flex-1"></div>
                                    </div>

                                    <div v-for="article in sub.articles" :key="article.id" class="flex border-b">
                                        <div class="sticky left-0 z-20 bg-white px-4 py-3 text-xs text-gray-900 border-r w-[200px] min-w-[200px]">
                                            {{ article.name }}
                                        </div>
                                        <div v-for="date in dates" :key="date.date" @click="openDetailModal(article.id, date.date)" class="flex-1 text-xs px-4 py-3 border-r min-w-24 max-w-24 w-24 flex items-center justify-center hover:bg-gray-300 cursor-pointer transition-all duration-300 ease-in-out" :class="[date.isWeekend ? 'bg-gray-200' : 'bg-white']">
                                            <span :class="{ 'text-red-600 font-bold': availability[date.date]?.[article.id] < 0}">
                                              {{ availability[date.date]?.[article.id] ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <ArticleUsageModal
            :details-for-modal="detailsForModal"
            @close="showArticleUsageModal = false"
            v-if="showArticleUsageModal"
        />
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import {router} from "@inertiajs/vue3";
import ArticleUsageModal from "@/Pages/Inventory/Components/Planning/ArticleUsageModal.vue";
import {ref} from "vue";

const props = defineProps({
    groupedArticles: {
        type: Object,
        required: true,
        default: () => []
    },
    availability: {
        type: Object,
        required: true,
        default: () => []
    },
    dates: {
        type: Object,
        required: true,
        default: () => ({
            start_date: '',
            end_date: ''
        })
    },
    dataArray: {
        type: Object,
        required: true,
        default: () => []
    },
    detailsForModal: {
        type: Object,
        required: false,
        default: () => ({})
    }
})

const showArticleUsageModal = ref(false);

const formatDate = date =>
    new Date(date).toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' })


const openDetailModal = (articleId, date) => {
    router.reload({
        data: {
            article_id: articleId,
            date: date
        },
        preserveState: true,
        preserveScroll: true,
        only: ['detailsForModal'],
        onSuccess: () => {
            showArticleUsageModal.value = true;
        }
    })
};
</script>

<style scoped>

</style>

