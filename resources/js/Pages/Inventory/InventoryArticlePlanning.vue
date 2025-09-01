<template>
    <AppLayout :title="$t('Inventory Article Planning')">
        <div class="-ml-4">
            <!-- Topbar: One-line header (title • legend • datepicker) -->
            <div class="sticky top-0 z-40 border-b bg-gradient-to-r from-sky-50 via-indigo-50/50 to-transparent backdrop-blur">
                <div class="flex items-center gap-3 px-4 py-3 overflow-x-auto whitespace-nowrap">
                    <!-- Label + Hint -->
                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-800 ring-1 ring-inset ring-sky-200">
      {{ $t('Planning timeline') }}
    </span>
                    <span class="text-xs text-zinc-600">
      {{ $t('Click a cell to open usage details') }}
    </span>

                    <!-- Divider dot -->
                    <span class="mx-1 inline-block size-1 rounded-full bg-zinc-300"></span>

                    <!-- Legend (inline, compact) -->
                    <div class="flex items-center gap-2 text-[11px] text-zinc-600">
      <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
        <span class="inline-block size-2 rounded-full bg-red-600"></span>{{ $t('Overbooked (< 0)') }}
      </span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
        <span class="inline-block size-2 rounded-full bg-indigo-600"></span>{{ $t('Today') }}
      </span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
        <component is="IconRouteSquare" class="size-3" />{{ $t('Used in period') }}
      </span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
        <span class="inline-block size-2 rounded bg-zinc-300"></span>{{ $t('Weekend') }}
      </span>
                    </div>

                    <!-- Flexible spacer so DatePicker hugs the right while staying in one row -->
                    <div class="flex-1"></div>

                    <div class="shrink-0">
                        <date-picker-component
                            v-if="dataArray"
                            :dateValueArray="dataArray"
                            :is_inventory_article_planning="true"
                        />
                    </div>
                    <InventoryFunctionBarFilter />
                    <!-- Datepicker -->

                </div>
            </div>

            <!-- Grid wrapper -->
            <div class="flex flex-col w-full h-full overflow-hidden relative">
                <div class="flex-1 overflow-auto text-sm relative">
                    <div class="min-w-max">
                        <!-- Timeline header row -->
                        <div class="flex sticky top-0 z-30 bg-white/90 backdrop-blur shadow-sm text-sm font-medium text-zinc-700">
                            <div class="sticky left-0 z-20 bg-white/90 px-4 py-2 font-medium w-[220px] min-w-[220px] flex items-center border-r border-zinc-200">
                                {{ $t('Article') }}
                            </div>
                            <div v-for="date in dates" :key="date.date" class="px-4 py-2 text-center font-lexend text-[11px] min-w-24 max-w-24 w-24 flex items-center justify-center border-r border-zinc-200"
                                 :class="isToday(date.date) ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''">
                                {{ formatDate(date.date) }}
                            </div>
                        </div>

                        <!-- Body -->
                        <div>
                            <template v-for="group in groupedArticles" :key="group.category">
                                <!-- Category row -->
                                <div class="flex bg-zinc-100/80">
                                    <div class="sticky left-0 z-20 px-4 py-2 min-w-[220px] w-[220px] font-semibold text-[11px] text-zinc-700 flex items-center gap-2 border-r border-zinc-200">
                                        <component is="IconCategoryFilled" class="size-4 text-zinc-500" />
                                        {{ group.category }}
                                    </div>
                                    <div class="flex-1 border-b border-zinc-200"></div>
                                </div>

                                <!-- Articles without subcategory -->
                                <div v-for="article in group.articles" :key="article.id" class="flex border-b border-zinc-200">
                                    <div class="sticky left-0 z-20 bg-white px-4 py-2 text-xs text-zinc-900 font-medium border-r border-zinc-200 w-[220px] min-w-[220px]">
                                        {{ article.name }}
                                    </div>
                                    <div v-for="date in dates" :key="date.date"
                                         @click="openDetailModal(article.id, date.date)"
                                         class="text-xs px-2 py-2 text-center border-r border-zinc-200 min-w-24 max-w-24 w-24 flex items-center justify-center cursor-pointer transition"
                                         :class="[
                         date.isWeekend ? 'bg-zinc-50' : 'bg-white',
                         isToday(date.date) ? 'ring-1 ring-indigo-300 ring-inset' : 'hover:bg-zinc-50'
                       ]">
                                        <div class="inline-flex items-center gap-1">
                      <span class="tabular-nums"
                            :class="{ 'text-red-600 font-semibold': (availability.availability?.[date.date]?.[article.id] ?? 0) < 0 }">
                        {{ availability.availability?.[date.date]?.[article.id] ?? 0 }}
                      </span>
                                            <component is="IconRouteSquare" v-if="availability.usedFlag?.[date.date]?.[article.id]" class="size-3 text-zinc-500"/>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subcategories -->
                                <template v-for="sub in group.subcategories" :key="sub.name">
                                    <div class="flex bg-zinc-100/60">
                                        <div class="sticky left-0 z-20 px-4 py-2 min-w-[220px] w-[220px] text-[11px] font-semibold text-zinc-700 flex items-center gap-2 border-y border-r border-zinc-200">
                                            <component is="IconCategory2" class="size-4 text-zinc-500" />
                                            {{ sub.name }}
                                        </div>
                                        <div class="flex-1 border-b border-zinc-200"></div>
                                    </div>

                                    <div v-for="article in sub.articles" :key="article.id" class="flex border-b border-zinc-200">
                                        <div class="sticky left-0 z-20 bg-white px-4 py-2 text-xs text-zinc-900 font-medium border-r border-zinc-200 w-[220px] min-w-[220px]">
                                            {{ article.name }}
                                        </div>
                                        <div v-for="date in dates" :key="date.date"
                                             @click="openDetailModal(article.id, date.date)"
                                             class="text-xs px-2 py-2 text-center border-r border-zinc-200 min-w-24 max-w-24 w-24 flex items-center justify-center cursor-pointer transition"
                                             :class="[
                           date.isWeekend ? 'bg-zinc-50' : 'bg-white',
                           isToday(date.date) ? 'ring-1 ring-indigo-300 ring-inset' : 'hover:bg-zinc-50'
                         ]">
                                            <div class="inline-flex items-center gap-1">
                        <span class="tabular-nums"
                              :class="{ 'text-red-600 font-semibold': (availability.availability?.[date.date]?.[article.id] ?? 0) < 0 }">
                          {{ availability.availability?.[date.date]?.[article.id] ?? 0 }}
                        </span>
                                                <component is="IconRouteSquare" v-if="availability.usedFlag?.[date.date]?.[article.id]" class="size-3 text-zinc-500"/>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Modal -->
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
import { router } from "@inertiajs/vue3";
import ArticleUsageModal from "@/Pages/Inventory/Components/Planning/ArticleUsageModal.vue";
import { ref } from "vue";
import InventoryFunctionBarFilter from "@/Artwork/Filter/InventoryFunctionBarFilter.vue";

const props = defineProps({
    groupedArticles: { type: Object, required: true, default: () => [] },
    availability: { type: Object, required: true, default: () => [] },
    dates: { type: Object, required: true, default: () => ({ start_date: '', end_date: '' }) },
    dataArray: { type: Object, required: true, default: () => [] },
    detailsForModal: { type: Object, required: false, default: () => ({}) }
})

const showArticleUsageModal = ref(false);

const formatDate = (date) => new Date(date).toLocaleDateString('de-DE', {
    weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric'
})

const isToday = (date) => {
    const d = new Date(date)
    const today = new Date()
    return d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth() && d.getDate() === today.getDate()
}

const openDetailModal = (articleId, date) => {
    router.reload({
        data: { article_id: articleId, date },
        preserveState: true,
        preserveScroll: true,
        only: ['detailsForModal'],
        onSuccess: () => { showArticleUsageModal.value = true }
    })
}
</script>

<style scoped>
/* optional subtle shadow on sticky first column when scrolling horizontally */
:deep(.sticky[left]) { box-shadow: 1px 0 0 0 rgba(24,24,27,0.06); }
</style>
