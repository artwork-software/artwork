<template>
    <AppLayout :title="$t('Inventory Article Planning')">
        <div class="-ml-4">
            <!-- Topbar -->
            <div class="sticky top-0 z-40 border-b ">
                <div class="flex items-center gap-3 px-4 py-3 overflow-x-auto whitespace-nowrap">
          <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold text-sky-800 ring-1 ring-inset ring-sky-200">
            {{ $t('Planning timeline') }}
          </span>
                    <span class="mx-1 inline-block size-1 rounded-full bg-zinc-300"></span>

                    <div class="flex items-center gap-2 text-[11px] text-zinc-600">
            <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
              <span class="inline-block size-2 rounded-full bg-red-600"></span>{{ $t('Overbooked (< 0)') }}
            </span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
              <span class="inline-block size-2 rounded-full bg-indigo-600"></span>{{ $t('Today') }}
            </span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
              <component :is="IconRouteSquare" class="size-3" />{{ $t('Used in period') }}
            </span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white/80 px-2 py-0.5">
              <span class="inline-block size-2 rounded bg-zinc-300"></span>{{ $t('Weekend') }}
            </span>
                    </div>

                    <div class="flex-1"></div>

                    <div class="shrink-0">
                        <DatePickerComponent
                            v-if="dataArray"
                            :dateValueArray="dataArray"
                            :is_inventory_article_planning="true"
                        />
                    </div>

                    <InventoryFunctionBarFilter />
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
                            <div
                                v-for="date in dates"
                                :key="date.date"
                                class="px-4 py-2 text-center font-lexend text-[11px] min-w-24 max-w-24 w-24 flex items-center justify-center border-r border-zinc-200"
                                :class="isToday(date.date) ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''"
                            >
                                {{ formatDate(date.date) }}
                            </div>
                        </div>

                        <!-- Body -->
                        <div>
                            <template v-for="group in groupedArticles" :key="group.category">
                                <!-- Category row (toggle) -->
                                <div class="flex bg-zinc-100/80">
                                    <button
                                        type="button"
                                        class="sticky left-0 z-20 px-4 py-2 min-w-[220px] w-[220px] font-semibold text-[11px] text-zinc-700 inline-flex items-center gap-2 border-r border-zinc-200 select-none"
                                        :aria-expanded="isCatOpen(group.category)"
                                        @click="toggleCategory(group.category)"
                                    >
                                        <component
                                            :is="IconChevronRight"
                                            class="size-4 transition-transform"
                                            :class="isCatOpen(group.category) ? 'rotate-90' : ''"
                                        />
                                        <component :is="IconCategoryFilled" class="size-4 text-zinc-500" />
                                        <span class="truncate">{{ group.category }}</span>
                                        <span class="ml-auto text-[10px] text-zinc-500">
                      {{ countGroup(group) }}
                    </span>
                                    </button>
                                    <div class="flex-1 border-b border-zinc-200"></div>
                                </div>

                                <!-- Articles without subcategory -->
                                <template v-if="isCatOpen(group.category)">
                                    <div
                                        v-for="article in group.articles"
                                        :key="article.id"
                                        class="flex border-b border-zinc-200"
                                    >
                                        <div
                                            class="sticky left-0 z-20 bg-white px-4 py-2 text-xs text-zinc-900 font-medium border-r border-zinc-200 w-[220px] min-w-[220px]"
                                        >
                                            {{ article.name }}
                                        </div>
                                        <div
                                            v-for="date in dates"
                                            :key="date.date"
                                            @click="openDetailModal(article.id, date.date)"
                                            class="text-xs px-2 py-2 text-center border-r border-zinc-200 min-w-24 max-w-24 w-24 flex items-center justify-center cursor-pointer transition"
                                            :class="[
                        date.isWeekend ? 'bg-zinc-50' : 'bg-white',
                        isToday(date.date) ? 'ring-1 ring-indigo-300 ring-inset' : 'hover:bg-zinc-50'
                      ]"
                                        >
                                            <div class="inline-flex items-center gap-1">
                        <span
                            class="tabular-nums"
                            :class="{ 'text-red-600 font-semibold': (availability.availability?.[date.date]?.[article.id] ?? 0) < 0 }"
                        >
                          {{ availability.availability?.[date.date]?.[article.id] ?? 0 }}
                        </span>
                                                <component
                                                    :is="IconRouteSquare"
                                                    v-if="availability.usedFlag?.[date.date]?.[article.id]"
                                                    class="size-3 text-zinc-500"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Subcategories -->
                                    <template v-for="sub in group.subcategories" :key="sub.name">
                                        <!-- Subcategory row (toggle) -->
                                        <div class="flex bg-zinc-100/60">
                                            <button
                                                type="button"
                                                class="sticky left-0 z-20 px-4 py-2 min-w-[220px] w-[220px] text-[11px] font-semibold text-zinc-700 inline-flex items-center gap-2 border-y border-r border-zinc-200 select-none"
                                                :aria-expanded="isSubOpen(group.category, sub.name)"
                                                @click="toggleSub(group.category, sub.name)"
                                            >
                                                <component
                                                    :is="IconChevronRight"
                                                    class="size-4 transition-transform"
                                                    :class="isSubOpen(group.category, sub.name) ? 'rotate-90' : ''"
                                                />
                                                <component :is="IconCategory2" class="size-4 text-zinc-500" />
                                                <span class="truncate">{{ sub.name }}</span>
                                                <span class="ml-auto text-[10px] text-zinc-500">{{ sub.articles?.length ?? 0 }}</span>
                                            </button>
                                            <div class="flex-1 border-b border-zinc-200"></div>
                                        </div>

                                        <!-- Subcategory articles (only if both: cat open + sub open) -->
                                        <template v-if="isSubOpen(group.category, sub.name)">
                                            <div
                                                v-for="article in sub.articles"
                                                :key="article.id"
                                                class="flex border-b border-zinc-200"
                                            >
                                                <div
                                                    class="sticky left-0 z-20 bg-white px-4 py-2 text-xs text-zinc-900 font-medium border-r border-zinc-200 w-[220px] min-w-[220px]"
                                                >
                                                    {{ article.name }}
                                                </div>
                                                <div
                                                    v-for="date in dates"
                                                    :key="date.date"
                                                    @click="openDetailModal(article.id, date.date)"
                                                    class="text-xs px-2 py-2 text-center border-r border-zinc-200 min-w-24 max-w-24 w-24 flex items-center justify-center cursor-pointer transition"
                                                    :class="[
                            date.isWeekend ? 'bg-zinc-50' : 'bg-white',
                            isToday(date.date) ? 'ring-1 ring-indigo-300 ring-inset' : 'hover:bg-zinc-50'
                          ]"
                                                >
                                                    <div class="inline-flex items-center gap-1">
                            <span
                                class="tabular-nums"
                                :class="{ 'text-red-600 font-semibold': (availability.availability?.[date.date]?.[article.id] ?? 0) < 0 }"
                            >
                              {{ availability.availability?.[date.date]?.[article.id] ?? 0 }}
                            </span>
                                                        <component
                                                            :is="IconRouteSquare"
                                                            v-if="availability.usedFlag?.[date.date]?.[article.id]"
                                                            class="size-3 text-zinc-500"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </template>
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
            @refreshData="refreshModalData"
            v-if="showArticleUsageModal"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import {ref, reactive, onMounted, watch, defineAsyncComponent} from "vue";
import {IconCategory2, IconCategoryFilled, IconChevronRight, IconRouteSquare} from "@tabler/icons-vue";

const props = defineProps({
    groupedArticles: { type: Array, required: true, default: () => [] },
    availability: { type: Object, required: true, default: () => ({}) },
    dates: { type: Array, required: true, default: () => [] },
    dataArray: { type: Array, required: true, default: () => [] },
    detailsForModal: { type: Object, required: false, default: () => ({}) }
});

const showArticleUsageModal = ref(false);
const currentModalArticleId = ref(null);
const currentModalDate = ref(null);

/** --- Collapsible state --- */
const catOpen = reactive({}); // key: category -> boolean
const subOpen = reactive({}); // key: `${category}:::${sub}` -> boolean
const keyFor = (cat, sub) => `${cat}:::${sub}`;

const ensureInitialState = () => {
    for (const g of props.groupedArticles ?? []) {
        if (catOpen[g.category] === undefined) catOpen[g.category] = true;
        for (const s of g.subcategories ?? []) {
            const k = keyFor(g.category, s.name);
            if (subOpen[k] === undefined) subOpen[k] = true;
        }
    }
};
onMounted(ensureInitialState);
watch(() => props.groupedArticles, ensureInitialState, { deep: true });

const isCatOpen = (cat) => catOpen[cat] ?? true;
const toggleCategory = (cat) => (catOpen[cat] = !isCatOpen(cat));

const isSubOpen = (cat, sub) => subOpen[keyFor(cat, sub)] ?? true;
const toggleSub = (cat, sub) => (subOpen[keyFor(cat, sub)] = !isSubOpen(cat, sub));

/** Small helpers */
const countGroup = (group) => {
    const base = (group.articles?.length ?? 0);
    const sub = (group.subcategories ?? []).reduce((n, s) => n + (s.articles?.length ?? 0), 0);
    return base + sub;
};

const formatDate = (date) =>
    new Date(date).toLocaleDateString("de-DE", {
        weekday: "short",
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
    });

const isToday = (date) => {
    const d = new Date(date);
    const t = new Date();
    return d.getFullYear() === t.getFullYear() && d.getMonth() === t.getMonth() && d.getDate() === t.getDate();
};

const openDetailModal = (articleId, date) => {
    currentModalArticleId.value = articleId;
    currentModalDate.value = date;
    router.reload({
        data: { article_id: articleId, date },
        preserveState: true,
        preserveScroll: true,
        only: ["detailsForModal"],
        onSuccess: () => {
            showArticleUsageModal.value = true;
        }
    });
};

const refreshModalData = () => {
    if (currentModalArticleId.value && currentModalDate.value) {
        router.reload({
            data: {
                article_id: currentModalArticleId.value,
                date: currentModalDate.value
            },
            preserveState: true,
            preserveScroll: true,
            only: ["detailsForModal", "availability"]
        });
    }
};

const DatePickerComponent = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/DatePickerComponent.vue'),
    delay: 200,
});

const ArticleUsageModal = defineAsyncComponent({
    loader: () => import('@/Pages/Inventory/Components/Planning/ArticleUsageModal.vue'),
    delay: 200,
});

const InventoryFunctionBarFilter = defineAsyncComponent({
    loader: () => import('@/Artwork/Filter/InventoryFunctionBarFilter.vue'),
    delay: 200,
});
</script>

<style scoped>
/* optional subtle shadow on sticky first column when scrolling horizontally */
:deep(.sticky[left]) { box-shadow: 1px 0 0 0 rgba(24,24,27,0.06); }
</style>
