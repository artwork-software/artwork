<template>
    <AppLayout :title="$t('Article Planning')">
        <div class="-ml-4">
            <!-- Topbar -->
            <div class="sticky top-0 z-40 border-b bg-white">
                <div class="flex items-center gap-3 px-4 py-3 overflow-x-auto whitespace-nowrap">
                    <!-- Datepicker first (top-left), as everywhere else in the app -->
                    <div class="shrink-0">
                        <DateRangeControl
                            v-if="dataArray"
                            :date-value-array="dataArray"
                            mode="article-planning"
                        />
                    </div>

                    <!-- Search directly next to the datepicker -->
                    <div class="shrink-0">
                        <input
                            type="text"
                            v-model="searchFilter"
                            :placeholder="$t('Search articles, categories...')"
                            class="px-3 py-1.5 text-sm border border-zinc-200 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-w-[200px]"
                        />
                    </div>

                    <!-- Expand / collapse all categories -->
                    <div class="shrink-0">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 bg-white/80 px-2.5 py-1.5 text-[11px] font-medium text-zinc-600 hover:bg-zinc-50 transition-colors"
                            :title="allExpanded ? $t('Collapse all') : $t('Expand all')"
                            @click="toggleAllCategories"
                        >
                            <IconChevronRight
                                class="size-4 transition-transform"
                                :class="allExpanded ? 'rotate-90' : ''"
                            />
                            {{ allExpanded ? $t('Collapse all') : $t('Expand all') }}
                        </button>
                    </div>

                    <div class="flex-1"></div>

                    <!-- Legend (info icon only; click toggles the same tooltip) -->
                    <div class="relative shrink-0" data-legend-wrapper ref="legendBtnRef">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white/80 p-1.5 text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700 transition-colors"
                            :class="showLegend ? 'bg-zinc-100 text-zinc-700' : ''"
                            :title="$t('Legend')"
                            :aria-label="$t('Legend')"
                            @click="toggleLegend"
                        >
                            <IconInfoCircle class="size-4" />
                        </button>
                    </div>

                    <teleport to="body">
                        <div
                            v-if="showLegend"
                            data-legend-wrapper
                            class="fixed z-[9999] rounded-lg border border-zinc-200 bg-white shadow-lg p-3 min-w-[200px]"
                            :style="{ right: legendPos.right + 'px', top: legendPos.top + 'px' }"
                        >
                            <div class="flex flex-col gap-2 text-[11px] text-zinc-600">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block size-2 rounded-full bg-red-600"></span>{{ $t('Overbooked (< 0)') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block size-2 rounded-full bg-indigo-600"></span>{{ $t('Today') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block size-2 rounded bg-zinc-300"></span>{{ $t('Weekend') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block h-1 w-3 rounded-sm bg-emerald-500"></span>{{ $t('Internal issue') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block h-1 w-3 rounded-sm bar-stripe-legend-dropdown"></span>{{ $t('External issue') }}
                                </span>
                            </div>
                        </div>
                    </teleport>

                    <!-- Display settings (extensible panel; holds the "only planned" toggle) -->
                    <div class="relative shrink-0" data-settings-wrapper ref="settingsBtnRef">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 bg-white/80 px-2.5 py-1.5 text-[11px] font-medium text-zinc-600 hover:bg-zinc-50 transition-colors"
                            :class="showSettings ? 'bg-zinc-100 text-zinc-700' : ''"
                            @click="toggleSettings"
                        >
                            <IconAdjustmentsHorizontal class="size-4" />
                            {{ $t('Display Settings') }}
                        </button>
                    </div>

                    <teleport to="body">
                        <div
                            v-if="showSettings"
                            data-settings-wrapper
                            class="fixed z-[9999] rounded-lg border border-zinc-200 bg-white shadow-lg p-3 min-w-[220px]"
                            :style="{ right: settingsPos.right + 'px', top: settingsPos.top + 'px' }"
                        >
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400 mb-2">
                                {{ $t('Display Settings') }}
                            </div>
                            <label class="flex items-center gap-2 text-[12px] font-medium text-zinc-700 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="onlyPlanned"
                                    class="size-3.5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                {{ $t('Only planned articles') }}
                            </label>
                        </div>
                    </teleport>

                    <InventoryFunctionBarFilter />
                </div>
            </div>

            <!-- Always-visible synchronized horizontal scrollbar (Ref 1.20) -->
            <div
                ref="topScrollbar"
                class="overflow-x-auto overflow-y-hidden sticky top-0 z-40"
                style="height: 14px;"
                @scroll="onTopScroll"
            >
                <div :style="{ width: contentWidth + 'px', height: '1px' }"></div>
            </div>

            <!-- Grid wrapper -->
            <div ref="gridWrapper" class="overflow-auto text-sm relative" style="height: calc(100vh - 114px);" @scroll="onGridScroll">
                <div ref="gridContent" class="min-w-max">
                    <!-- Timeline header row -->
                    <div class="flex sticky top-0 z-30 bg-white/90 backdrop-blur shadow-sm text-sm font-medium text-zinc-700">
                            <div class="sticky left-0 z-20 bg-white/90 px-4 py-2 font-medium w-[220px] min-w-[220px] flex items-center border-r border-zinc-200">
                                {{ $t('Article') }}
                            </div>
                            <div
                                v-for="date in dates"
                                :key="date.date"
                                class="px-4 py-2 text-center font-lexend text-[11px] min-w-24 max-w-24 w-24 flex flex-col items-center justify-center border-r border-zinc-200"
                                :class="[
                                    date.date === todayIso ? 'bg-indigo-50 text-indigo-700 font-semibold' : '',
                                    weekMeta.get(date.date)?.isWeekStart ? 'border-l-2 border-l-zinc-800' : ''
                                ]"
                            >
                                <span
                                    v-if="weekMeta.get(date.date)?.isWeekStart"
                                    class="text-[9px] font-bold uppercase tracking-wide text-zinc-800 leading-none mb-0.5"
                                >{{ $t('KW') }} {{ weekMeta.get(date.date)?.week }}</span>
                                {{ formattedDates[date.date] }}
                            </div>
                        </div>

                        <!-- Body -->
                        <div>
                            <template v-for="group in filteredGroupedArticles" :key="group.category">
                                <!-- Category row (toggle) -->
                                <div class="flex bg-zinc-100/80 border-t-2 border-zinc-800">
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
                                        <span class="truncate">{{ group.category }}</span>
                                        <span class="ml-auto text-[10px] text-zinc-500">
                      {{ countGroup(group) }}
                    </span>
                                    </button>
                                    <div class="flex-1 border-b border-zinc-200"></div>
                                </div>

                                <!-- Articles without subcategory -->
                                <template v-if="isCatOpen(group.category)">
                                    <ArticleRow
                                        v-for="article in group.articles"
                                        :key="article.id"
                                        :article="article"
                                        :dates="dates"
                                        :availability="availability"
                                        :issues-for-article="issuesByArticle[article.id] || []"
                                        :highlighted-project-ids="highlightedProjectIds"
                                        @cellClick="openCellPanel"
                                        @barClick="openBarPanel"
                                        @barHover="onBarHover"
                                        @barLeave="onBarLeave"
                                    />

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
                                                <span class="truncate">{{ sub.name }}</span>
                                                <span class="ml-auto text-[10px] text-zinc-500">{{ sub.articles?.length ?? 0 }}</span>
                                            </button>
                                            <div class="flex-1 border-b border-zinc-200"></div>
                                        </div>

                                        <!-- Subcategory articles (only if both: cat open + sub open) -->
                                        <template v-if="isSubOpen(group.category, sub.name)">
                                            <ArticleRow
                                                v-for="article in sub.articles"
                                                :key="article.id"
                                                :article="article"
                                                :dates="dates"
                                                :availability="availability"
                                                :issues-for-article="issuesByArticle[article.id] || []"
                                                :highlighted-project-ids="highlightedProjectIds"
                                                @cellClick="openCellPanel"
                                                @barClick="openBarPanel"
                                                @barHover="onBarHover"
                                                @barLeave="onBarLeave"
                                            />
                                        </template>
                                    </template>
                                </template>
                            </template>
                        </div>
                    </div>
            </div>
        </div>

        <!-- Floating Tooltip -->
        <div
            v-if="tooltip.visible"
            class="fixed z-50 pointer-events-none rounded-md bg-zinc-900 text-white text-[11px] px-2.5 py-1.5 shadow-lg"
            :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
        >
            <div class="font-semibold mb-0.5">{{ tooltip.issue?.name }}</div>
            <div class="text-zinc-300">{{ formatDateTooltip(tooltip.issue?.start) }} – {{ formatDateTooltip(tooltip.issue?.end) }}</div>
            <div v-if="tooltip.issue?.type === 'intern' && tooltip.issue?.project_name" class="text-zinc-300">
                {{ $t('Project') }}: {{ tooltip.issue.project_name }}
            </div>
            <div v-else-if="tooltip.issue?.type === 'extern' && tooltip.issue?.receiver_name" class="text-zinc-300">
                {{ $t('Recipient') }}: {{ tooltip.issue.receiver_name }}
            </div>
            <div class="text-zinc-400 mt-0.5">
                <span v-if="tooltip.issue?.type === 'intern'">{{ $t('Internal issue') }}</span>
                <span v-else>{{ $t('External issue') }}</span>
            </div>
        </div>

        <!-- Side Panel (replaces modal) -->
        <ArticleUsageSidePanel
            :visible="showSidePanel"
            :details-for-modal="panelDetails ?? detailsForModal"
            :focus-issue-id="focusIssueId"
            :focus-issue-type="focusIssueType"
            :loading="panelLoading"
            @close="closeSidePanel"
            @refreshData="refreshPanelData"
        />

        <!-- Edit modal (bar click with edit permission) -->
        <IssueOfMaterialModal
            v-if="showBarIssueModal"
            :issue-of-material="!barIssueIsExtern ? barIssueForModal : null"
            :is-extern-or-intern="barIssueIsExtern"
            :extern-material-issue="barIssueIsExtern ? barIssueForModal : null"
            :project="barIssueForModal?.project || null"
            :project-tab-id="props.projectMaterialIssueTabId"
            @close="closeBarIssueModal"
            @saved="closeBarIssueModal"
        />

        <!-- Read-only info modal (bar click without edit permission) -->
        <ArtworkBaseModal
            v-if="showBarIssueInfo && barIssueForModal"
            @close="closeBarIssueModal"
            modal-size="sm:max-w-lg"
            :title="barIssueForModal.name || $t('Material issue')"
            :description="barIssueIsExtern ? $t('External issue') : $t('Internal issue')"
        >
            <div class="space-y-3 text-sm text-zinc-700 mt-2">
                <div class="grid grid-cols-2 gap-2">
                    <div class="text-zinc-500">{{ $t('Period') }}</div>
                    <div>{{ formatDateTooltip(barIssueForModal.start) }} – {{ formatDateTooltip(barIssueForModal.end) }}</div>
                </div>
                <div v-if="barIssueForModal.project_name" class="grid grid-cols-2 gap-2">
                    <div class="text-zinc-500">{{ $t('Project') }}</div>
                    <div>
                        <a
                            v-if="barIssueForModal.project_id"
                            :href="route('projects.tab', {project: barIssueForModal.project_id, projectTab: props.projectMaterialIssueTabId})"
                            class="text-indigo-600 hover:underline"
                        >{{ barIssueForModal.project_name }}</a>
                        <span v-else>{{ barIssueForModal.project_name }}</span>
                    </div>
                </div>
                <div v-if="barIssueForModal.receiver_name" class="grid grid-cols-2 gap-2">
                    <div class="text-zinc-500">{{ $t('Recipient') }}</div>
                    <div>{{ barIssueForModal.receiver_name }}</div>
                </div>
            </div>
        </ArtworkBaseModal>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, reactive, onMounted, onUnmounted, watch, computed, defineAsyncComponent, provide, nextTick } from "vue";
import { IconAdjustmentsHorizontal, IconChevronRight, IconInfoCircle } from "@tabler/icons-vue";
import debounce from "lodash.debounce";
import axios from "axios";
import ArticleRow from "@/Pages/Inventory/Components/Planning/ArticleRow.vue";
import ArticleUsageSidePanel from "@/Pages/Inventory/Components/Planning/ArticleUsageSidePanel.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import { usePermission } from "@/Composeables/Permission.js";

const { can, hasAdminRole } = usePermission(usePage().props);
const canEditIssues = computed(() => can('inventory.disposition') || hasAdminRole());

const IssueOfMaterialModal = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue'),
    delay: 200,
});

const props = defineProps({
    groupedArticles: { type: Array, required: true, default: () => [] },
    availability: { type: Object, required: true, default: () => ({}) },
    dates: { type: Array, required: true, default: () => [] },
    dataArray: { type: Array, required: true, default: () => [] },
    detailsForModal: { type: Object, required: false, default: () => ({}) },
    issues: { type: Array, required: false, default: () => [] },
    projects: { type: Array, required: false, default: () => [] },
    projectMaterialIssueTabId: { type: Number, required: false, default: 1 },
    planningSettings: {
        type: Object,
        required: false,
        default: () => ({ only_planned: false, open_categories: [], open_subcategories: [] }),
    },
});

// Toolbar popovers (legend + display settings). Both teleport their panel to
// <body> to escape the toolbar's overflow-x-auto clipping, and are right-aligned
// under their trigger button.
const showLegend = ref(false);
const legendBtnRef = ref(null);
const legendPos = reactive({ right: 0, top: 0 });

const showSettings = ref(false);
const settingsBtnRef = ref(null);
const settingsPos = reactive({ right: 0, top: 0 });

const positionPanel = (btnRef, pos) => {
    const rect = btnRef.value.getBoundingClientRect();
    pos.right = Math.max(8, window.innerWidth - rect.right);
    pos.top = rect.bottom + 4;
};

const toggleLegend = () => {
    if (!showLegend.value && legendBtnRef.value) {
        positionPanel(legendBtnRef, legendPos);
        showSettings.value = false;
    }
    showLegend.value = !showLegend.value;
};

const toggleSettings = () => {
    if (!showSettings.value && settingsBtnRef.value) {
        positionPanel(settingsBtnRef, settingsPos);
        showLegend.value = false;
    }
    showSettings.value = !showSettings.value;
};

const onClickOutsidePopovers = (e) => {
    if (showLegend.value && !e.target.closest('[data-legend-wrapper]')) {
        showLegend.value = false;
    }
    if (showSettings.value && !e.target.closest('[data-settings-wrapper]')) {
        showSettings.value = false;
    }
};
onMounted(() => document.addEventListener('click', onClickOutsidePopovers));
onUnmounted(() => document.removeEventListener('click', onClickOutsidePopovers));

// Ref 1.20: synchronized, always-visible horizontal scrollbar pinned above the
// grid. The top scrollbar and the grid wrapper mirror each other's scrollLeft;
// `scrollSyncing` guards against the feedback loop the two @scroll handlers
// would otherwise create.
const topScrollbar = ref(null);
const gridWrapper = ref(null);
const gridContent = ref(null);
const contentWidth = ref(0);
let scrollSyncing = false;

const onTopScroll = () => {
    if (scrollSyncing || !gridWrapper.value || !topScrollbar.value) return;
    scrollSyncing = true;
    gridWrapper.value.scrollLeft = topScrollbar.value.scrollLeft;
    scrollSyncing = false;
};
const onGridScroll = () => {
    if (scrollSyncing || !gridWrapper.value || !topScrollbar.value) return;
    scrollSyncing = true;
    topScrollbar.value.scrollLeft = gridWrapper.value.scrollLeft;
    scrollSyncing = false;
};

const measureContentWidth = () => {
    if (gridContent.value) {
        contentWidth.value = gridContent.value.scrollWidth;
    }
};
const remeasureContentWidth = () => nextTick(measureContentWidth);

onMounted(() => {
    measureContentWidth();
    window.addEventListener('resize', measureContentWidth);
});
onUnmounted(() => window.removeEventListener('resize', measureContentWidth));

// Column count is driven by `dates`; remeasure whenever the visible range changes.
watch(() => props.dates, remeasureContentWidth, { deep: false });

// Side panel state
const showSidePanel = ref(false);
const currentArticleId = ref(null);
const currentDate = ref(null);
const focusIssueId = ref(null);
const focusIssueType = ref(null);

// Search filter — `searchFilter` is bound to the input; `debouncedSearch` is
// what the (potentially expensive) `filteredGroupedArticles` reads, so typing
// fast does not trigger the full filter pipeline per keystroke. (F2)
const searchFilter = ref('');
const debouncedSearch = ref('');
const updateDebouncedSearch = debounce((value) => {
    debouncedSearch.value = value;
}, 200);
watch(searchFilter, (value) => {
    updateDebouncedSearch(value);
});

// Project highlight filter (frontend-only)
const highlightedProjectIds = ref([]);

// Provide highlight state for the InventoryFilterModal section
provide('inventoryPlanningProjectHighlight', {
    projects: computed(() => props.projects || []),
    selected: highlightedProjectIds,
});

// F4: Compute the date-index map ONCE at the page level and provide it to all
// ArticleRow instances. Previously each row built its own identical Map.
const planningDateIndex = computed(() => {
    const map = new Map();
    (props.dates ?? []).forEach((d, idx) => map.set(d.date, idx));
    return map;
});
const planningRangeBounds = computed(() => ({
    first: props.dates?.[0]?.date ?? null,
    last:  props.dates?.[props.dates.length - 1]?.date ?? null,
}));
provide('planningDateIndex', planningDateIndex);
provide('planningRangeBounds', planningRangeBounds);

// ISO calendar-week metadata per date. Drives the thicker black week divider
// (drawn on the first day of each week) and the "CW xx" label in the header.
// The first visible column is always treated as a week start so it gets a label.
const isoWeekNumber = (dateStr) => {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt = new Date(Date.UTC(y, m - 1, d));
    const day = dt.getUTCDay() || 7;                 // Mon=1 … Sun=7
    dt.setUTCDate(dt.getUTCDate() + 4 - day);        // shift to the week's Thursday
    const yearStart = new Date(Date.UTC(dt.getUTCFullYear(), 0, 1));
    return Math.ceil(((dt - yearStart) / 86400000 + 1) / 7);
};
const isMonday = (dateStr) => {
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(Date.UTC(y, m - 1, d)).getUTCDay() === 1;
};
const weekMeta = computed(() => {
    const map = new Map();
    (props.dates ?? []).forEach((entry, idx) => {
        map.set(entry.date, {
            week: isoWeekNumber(entry.date),
            isWeekStart: idx === 0 || isMonday(entry.date),
        });
    });
    return map;
});
provide('planningWeekMeta', weekMeta);

// Group issues by article id once for fast lookup
const issuesByArticle = computed(() => {
    const map = {};
    for (const issue of props.issues || []) {
        for (const articleId of issue.article_ids || []) {
            (map[articleId] ||= []).push(issue);
        }
    }
    return map;
});

// Ref 1.18: "nur verplante" toggle (persisted per user).
const onlyPlanned = ref(props.planningSettings?.only_planned ?? false);
const isArticlePlanned = (article) => (issuesByArticle.value[article.id]?.length ?? 0) > 0;

// Filtered grouped articles based on debounced search input
const searchFilteredGroups = computed(() => {
    if (!debouncedSearch.value.trim()) {
        return props.groupedArticles;
    }

    const searchTerm = debouncedSearch.value.trim().toLowerCase();
    const filtered = [];

    for (const group of props.groupedArticles) {
        const categoryName = group.category.toLowerCase();

        if (categoryName.includes(searchTerm)) {
            filtered.push(group);
            continue;
        }

        const matchingSubcategories = (group.subcategories || []).filter(sub =>
            sub.name.toLowerCase().includes(searchTerm)
        );

        if (matchingSubcategories.length > 0) {
            filtered.push(group);
            continue;
        }

        const filteredArticles = (group.articles || []).filter(article =>
            article.name.toLowerCase().includes(searchTerm)
        );

        const filteredSubcategories = (group.subcategories || []).map(sub => ({
            ...sub,
            articles: (sub.articles || []).filter(article =>
                article.name.toLowerCase().includes(searchTerm)
            )
        })).filter(sub => sub.articles.length > 0);

        if (filteredArticles.length > 0 || filteredSubcategories.length > 0) {
            filtered.push({
                ...group,
                articles: filteredArticles,
                subcategories: filteredSubcategories
            });
        }
    }

    return filtered;
});

// Ref 1.18: apply the "only planned" filter on top of the search result.
const filteredGroupedArticles = computed(() => {
    const groups = searchFilteredGroups.value;
    if (!onlyPlanned.value) {
        return groups;
    }

    const result = [];
    for (const group of groups) {
        const articles = (group.articles || []).filter(isArticlePlanned);
        const subcategories = (group.subcategories || []).map(sub => ({
            ...sub,
            articles: (sub.articles || []).filter(isArticlePlanned),
        })).filter(sub => sub.articles.length > 0);

        if (articles.length > 0 || subcategories.length > 0) {
            result.push({ ...group, articles, subcategories });
        }
    }
    return result;
});

/** --- Collapsible state --- */
const catOpen = reactive({});
const subOpen = reactive({});
const keyFor = (cat, sub) => `${cat}:::${sub}`;

// Ref 1.18: grouping is collapsed by default; the user's previously expanded
// categories/subcategories are restored from the persisted settings.
const persistedOpenCategories = new Set(props.planningSettings?.open_categories ?? []);
const persistedOpenSubcategories = new Set(props.planningSettings?.open_subcategories ?? []);

const ensureInitialState = () => {
    for (const g of filteredGroupedArticles.value ?? []) {
        if (catOpen[g.category] === undefined) {
            catOpen[g.category] = persistedOpenCategories.has(g.category);
        }
        for (const s of g.subcategories ?? []) {
            const k = keyFor(g.category, s.name);
            if (subOpen[k] === undefined) {
                subOpen[k] = persistedOpenSubcategories.has(k);
            }
        }
    }
};
onMounted(ensureInitialState);

// Ref 1.18: persist the view settings (only-planned + expanded groups) per user.
const persistViewSettings = debounce(() => {
    const openCategories = Object.keys(catOpen).filter((cat) => catOpen[cat]);
    const openSubcategories = Object.keys(subOpen).filter((k) => subOpen[k]);
    router.patch(route('update.user.inventory.article-plan.view-settings.update', usePage().props.auth.user.id), {
        only_planned: onlyPlanned.value,
        open_categories: openCategories,
        open_subcategories: openSubcategories,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: [],
    });
}, 600);

// F3: Watch only the flat structure (category/sub names), not the entire
// filtered tree. `ensureInitialState` is idempotent — it only needs to run
// when categories/sub-categories actually appear or disappear.
const structureKey = computed(() => {
    return (filteredGroupedArticles.value ?? [])
        .map(g => `${g.category}|${(g.subcategories ?? []).map(s => s.name).join(',')}`)
        .join(';');
});
watch(structureKey, ensureInitialState);

const isCatOpen = (cat) => catOpen[cat] ?? false;
const toggleCategory = (cat) => {
    catOpen[cat] = !isCatOpen(cat);
    persistViewSettings();
};

const isSubOpen = (cat, sub) => subOpen[keyFor(cat, sub)] ?? false;
const toggleSub = (cat, sub) => {
    subOpen[keyFor(cat, sub)] = !isSubOpen(cat, sub);
    persistViewSettings();
};

// Are all currently visible categories (and their subcategories) expanded?
const allExpanded = computed(() => {
    const groups = filteredGroupedArticles.value ?? [];
    if (groups.length === 0) return false;
    for (const g of groups) {
        if (!isCatOpen(g.category)) return false;
        for (const s of g.subcategories ?? []) {
            if (!isSubOpen(g.category, s.name)) return false;
        }
    }
    return true;
});

// Expand or collapse every visible category and subcategory at once.
const toggleAllCategories = () => {
    const open = !allExpanded.value;
    for (const g of filteredGroupedArticles.value ?? []) {
        catOpen[g.category] = open;
        for (const s of g.subcategories ?? []) {
            subOpen[keyFor(g.category, s.name)] = open;
        }
    }
    persistViewSettings();
};

// Persist the toggle whenever it changes.
watch(onlyPlanned, persistViewSettings);

/** Helpers */
const countGroup = (group) => {
    const base = (group.articles?.length ?? 0);
    const sub = (group.subcategories ?? []).reduce((n, s) => n + (s.articles?.length ?? 0), 0);
    return base + sub;
};

const headerDateFormatter = new Intl.DateTimeFormat("de-DE", {
    weekday: "short",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
});

// F11: Pre-format every date string in the visible range ONCE. The grid
// header iterates over `formattedDates` instead of calling `formatDate()`
// per cell render.
const formattedDates = computed(() => {
    const map = {};
    for (const d of props.dates ?? []) {
        map[d.date] = headerDateFormatter.format(new Date(d.date));
    }
    return map;
});

const formatDate = (date) => formattedDates.value[date] ?? headerDateFormatter.format(new Date(date));

const formatDateTooltip = (date) => {
    if (!date) return '';
    const [year, month, day] = date.split('-');
    return `${day}.${month}.${year}`;
};

// F5/F11: Compute today's ISO string ONCE per mount, then do string-equals
// in the template instead of constructing a Date and comparing fields per cell.
const todayIso = (() => {
    const t = new Date();
    return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;
})();

const isToday = (date) => date === todayIso;

// --- Side panel interactions ---
// B8: We fetch cell details via a thin JSON endpoint instead of an Inertia
// partial reload — the page-level prop `detailsForModal` is now optional
// (only used as a hydration fallback on initial render with query params).
const panelDetails = ref(null);
const panelLoading = ref(false);

const fetchPanelDetails = async (articleId, date) => {
    panelLoading.value = true;
    try {
        const response = await axios.get(route('inventory.articles.planning-cell'), {
            params: { article_id: articleId, date },
        });
        panelDetails.value = response.data?.data ?? null;
    } catch (error) {
        console.error('Failed to load cell details:', error);
        panelDetails.value = null;
    } finally {
        panelLoading.value = false;
    }
};

const openCellPanel = ({ articleId, date }) => {
    currentArticleId.value = articleId;
    currentDate.value = date;
    focusIssueId.value = null;
    focusIssueType.value = null;
    showSidePanel.value = true;          // open immediately for snappy UX
    fetchPanelDetails(articleId, date);
};

// --- Bar click: open issue modal directly ---
const showBarIssueModal = ref(false);
const barIssueForModal = ref(null);
const barIssueIsExtern = ref(false);
const barIssueLoading = ref(false);
const showBarIssueInfo = ref(false);

const openBarPanel = async ({ articleId, issue, date }) => {
    const isExtern = issue.type === 'extern';

    if (canEditIssues.value) {
        barIssueLoading.value = true;
        barIssueIsExtern.value = isExtern;
        try {
            if (!isExtern) {
                const response = await axios.get(route('issue-of-material.show', issue.id));
                barIssueForModal.value = response.data;
            } else {
                barIssueForModal.value = issue;
            }
            showBarIssueModal.value = true;
        } catch (error) {
            console.error('Failed to fetch issue data:', error);
        } finally {
            barIssueLoading.value = false;
        }
    } else {
        barIssueIsExtern.value = isExtern;
        barIssueForModal.value = issue;
        showBarIssueInfo.value = true;
    }
};

const closeBarIssueModal = () => {
    showBarIssueModal.value = false;
    showBarIssueInfo.value = false;
    barIssueForModal.value = null;
    // Refresh grid data
    router.reload({
        preserveState: true,
        preserveScroll: true,
        only: ["availability", "issues", "projects"],
    });
};

const closeSidePanel = () => {
    showSidePanel.value = false;
    focusIssueId.value = null;
    focusIssueType.value = null;
    panelDetails.value = null;
};

const refreshPanelData = () => {
    if (currentArticleId.value && currentDate.value) {
        // Re-fetch panel detail (fast JSON endpoint)…
        fetchPanelDetails(currentArticleId.value, currentDate.value);
        // …and pull a fresh availability/issues snapshot for the grid.
        router.reload({
            preserveState: true,
            preserveScroll: true,
            only: ["availability", "issues", "projects"],
        });
    }
};

// --- Tooltip ---
const tooltip = reactive({ visible: false, x: 0, y: 0, issue: null });

const onBarHover = ({ event, issue }) => {
    tooltip.issue = issue;
    tooltip.x = event.clientX + 12;
    tooltip.y = event.clientY + 12;
    tooltip.visible = true;
};

const onBarLeave = () => {
    tooltip.visible = false;
    tooltip.issue = null;
};

const DateRangeControl = defineAsyncComponent({
    loader: () => import('@/Artwork/DateRange/DateRangeControl.vue'),
    delay: 200,
});

const InventoryFunctionBarFilter = defineAsyncComponent({
    loader: () => import('@/Artwork/Filter/InventoryFunctionBarFilter.vue'),
    delay: 200,
});
</script>

<style scoped>
/* Sticky positioning for first column (Article) */
.sticky.left-0 {
    position: sticky;
    position: -webkit-sticky;
    box-shadow: 2px 0 4px -1px rgba(0, 0, 0, 0.1);
}

.bar-stripe-legend,
.bar-stripe-legend-dropdown {
    background-image: repeating-linear-gradient(
        45deg,
        rgb(16 185 129) 0,
        rgb(16 185 129) 2px,
        rgba(16, 185, 129, 0.35) 2px,
        rgba(16, 185, 129, 0.35) 4px
    );
}
</style>
