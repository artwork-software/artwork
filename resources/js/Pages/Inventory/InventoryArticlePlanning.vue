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
                        <div class="flex sticky top-0 z-30 bg-white border-b shadow-sm text-sm font-medium text-gray-700">
                            <div class="sticky left-0 z-20 bg-white px-4 py-2 text-gray-900 font-medium border-r w-[200px] min-w-[200px] flex items-center">Artikel</div>
                            <div v-for="date in dates" :key="date.date" class="flex-1 px-4 py-2 text-center font-lexend text-xs border-r min-w-20 max-w-20 w-20 flex items-center justify-center" :class="[date.isWeekend ? 'bg-gray-100' : 'bg-white']">
                                {{ formatDate(date.date) }}
                            </div>
                        </div>

                        <!-- Hauptbereich -->
                        <div>
                            <template v-for="group in groupedArticles" :key="group.category">
                                <!-- Kategorie (sticky in erster Spalte) -->
                                <div class="flex border-t bg-artwork-navigation-background font-bold text-white">
                                    <div class="sticky left-0 z-20 bg-artwork-navigation-background font-bold text-white px-4 py-5 min-w-[200px] w-[200px] flex items-center gap-x-1">
                                        <component is="IconCategory" class="inline-block size-4" />
                                        {{ group.category }}
                                    </div>
                                    <div class="flex-1"></div>
                                </div>

                                <!-- Artikel ohne Subkategorie -->
                                <div v-for="article in group.articles" :key="article.id" class="flex border-b">
                                    <div class="sticky left-0 z-20 bg-white px-4 py-2 text-gray-900 font-medium border-r w-[200px] min-w-[200px]">
                                        {{ article.name }}
                                    </div>
                                    <div v-for="date in dates" :key="date.date" class="flex-1 px-4 py-2 text-center border-r min-w-20 max-w-20 w-20 flex items-center justify-center" :class="[date.isWeekend ? 'bg-gray-100' : 'bg-white']">
                                    <span :class="{ 'text-red-600 font-bold': availability[date.date]?.[article.id] < 0}">
                                      {{ availability[date.date]?.[article.id] ?? 0 }}
                                    </span>
                                    </div>
                                </div>

                                <!-- Subkategorien -->
                                <template v-for="sub in group.subcategories" :key="sub.name">
                                    <div class="flex border-t bg-gray-100 font-bold text-gray-800">
                                        <div class="sticky left-0 z-20 bg-gray-100 px-4 py-3 min-w-[200px] w-[200px] flex items-center gap-x-1 border-r">
                                            <component is="IconCategory2" class="inline-block size-4" />
                                            {{ sub.name }}
                                        </div>
                                        <div class="flex-1"></div>
                                    </div>

                                    <div v-for="article in sub.articles" :key="article.id" class="flex border-b">
                                        <div class="sticky left-0 z-20 bg-white px-4 py-2 text-gray-900 border-r w-[200px] min-w-[200px]">
                                            {{ article.name }}
                                        </div>
                                        <div v-for="date in dates" :key="date.date" class="flex-1 px-4 py-2 border-r min-w-20 max-w-20 w-20 flex items-center justify-center" :class="[date.isWeekend ? 'bg-gray-100' : 'bg-white']">
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
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";

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
    }
})

const formatDate = date =>
    new Date(date).toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' })

</script>

<style scoped>

</style>

