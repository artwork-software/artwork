<template>
    <AppLayout :title="$t('Material Issues')">
        <div class="artwork-container-fluid">
            <!-- Header -->

            <ToolbarHeader title="Material issue book"
                           description="Track and filter all internal material issues across projects and rooms."
                           :icon="IconMenu4"
                           band
            >
                <template #actions>
                    <ToolTipComponent
                        v-if="can('can view material issue log') || is('artwork admin')"
                        direction="bottom"
                        :tooltip-text="$t('Material issue log')"
                        icon="IconHistory"
                        icon-size="h-5 w-5"
                        white-icon
                        classes-button="select-none size-[30px] min-h-0 p-0 inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16 cursor-pointer transition-[background-color] duration-150 ease-out"
                        @click="showLogModal = true"
                    />
                    <BaseUIButton variant="primary" on-band hide-icon @click="openIssueOfMaterialModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5"/>
                        {{ $t('New issue of material') }}
                    </BaseUIButton>
                </template>

            </ToolbarHeader>


            <!--<div class="flex flex-wrap items-center justify-between gap-4 pt-6 pb-2 hidden">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                    <span class="inline-flex size-6 items-center justify-center rounded-md bg-accent-50 text-accent-700">
                      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.5" d="M4 7h16M4 12h10M4 17h16"/></svg>
                    </span>
                        <h1 class="text-2xl font-semibold tracking-tight">{{ $t('Material issue book') }}</h1>
                    </div>
                    <div class="mt-2 h-1 w-24 rounded-full bg-gradient-to-r from-accent-600 via-info to-success"></div>
                    <p class="text-sm text-text-subtle mt-2">
                        {{ $t('Track and filter all internal material issues across projects and rooms.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 w-96">
                        <ArticleSearch
                            id="article-search"
                            class="w-72"
                            @article-selected="addArticleNameToFilter"
                        />
                        <button
                            type="button"
                            @click="filterIssueByArticleIds"
                            class="p-4 inline-flex items-center justify-center rounded-md border border-border-subtle bg-white hover:bg-surface-sunken focus:outline-none focus:ring-2 focus:ring-accent-600"
                        >
                            <component :is="IconSearch" class="size-5" stroke-width="1.5" />
                        </button>
                    </div>

                    <BaseButton
                        v-if="can('inventory.disposition') || is('artwork admin')"
                        :text="$t('New issue of material')"
                        @click="openIssueOfMaterialModal"
                        class="!bg-accent-600 hover:!bg-accent-700 !text-white !border-transparent"
                    >
                        <component :is="IconCopyPlus" class="size-5 mr-2" />
                    </BaseButton>
                </div>
            </div>-->

            <div>
                <IssueTabs/>
            </div>

            <!-- Artikel-Chips -->
            <div v-if="articleNamesForFilter.length" class="mb-3">
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(article, index) in articleNamesForFilter"
                        :key="index"
                        class="inline-flex items-center rounded-full border border-info-border bg-info-surface/70 px-2.5 py-0.5 text-sm text-info ring-1 ring-inset ring-info-border"
                    >
                        <span class="truncate max-w-[220px]">{{ article.name }}</span>
                        <button
                            type="button"
                            class="ml-2 text-info hover:text-info focus:outline-none"
                            @click="articleNamesForFilter.splice(index, 1)"
                        >
                            <component :is="IconX" class="size-4"/>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filterleiste: eine kompakte Zeile, Werte stehen direkt in den Feldern -->
            <div class="ui-card flex flex-wrap items-center gap-2">
                <BaseInput
                    id="issue-filter-search"
                    v-model="filters.q"
                    label="Search by issue name"
                    :show-label="false"
                    :placeholder="$t('Search by issue name')"
                    class="w-full sm:w-60"
                />
                <button
                    type="button"
                    class="ui-button sm:hidden!"
                    :aria-expanded="showFilterControls"
                    @click="showFilterControls = !showFilterControls"
                >
                    <component :is="IconFilter" class="size-4" stroke-width="1.5"/>
                    {{ $t('Filters') }}
                    <span v-if="activeFilterCount" class="rounded-full bg-accent-50 px-1.5 text-[11px] text-accent-700">{{ activeFilterCount }}</span>
                </button>
                <div :class="showFilterControls ? 'flex' : 'hidden'" class="w-full flex-wrap items-center gap-2 sm:contents">
                    <IssueDateRangeFilter
                        v-model:from="filters.date_from"
                        v-model:to="filters.date_to"
                        id-prefix="issue-filter"
                    />
                    <div class="w-full sm:w-44">
                        <SearchableSelect
                            v-model="filters.room_id"
                            :options="rooms"
                            value-key="id"
                            label-key="name"
                            :empty-option="{ label: 'All rooms', value: '' }"
                            :placeholder="$t('All rooms')"
                        />
                    </div>
                    <div class="w-full sm:w-52">
                        <SearchableSelect
                            v-model="filters.project_id"
                            :options="projects"
                            value-key="id"
                            label-key="name"
                            :empty-option="{ label: 'All projects', value: '' }"
                            :placeholder="$t('All projects')"
                        />
                    </div>
                    <div class="w-full sm:w-52">
                        <ArtworkBaseListbox
                            :model-value="selectedResponsibleUsers"
                            @update:model-value="onResponsibleUsersChange"
                            :items="users"
                            multiple
                            by="id"
                            :option-label="displayUserName"
                            option-key="id"
                            :use-translations="false"
                            :placeholder="$t('Responsible')"
                        />
                    </div>
                </div>
                <button
                    v-if="hasAnyFilter"
                    type="button"
                    class="ui-button sm:ml-auto!"
                    @click="resetFilters"
                >
                    <component :is="IconFilterOff" class="size-4" stroke-width="1.5"/>
                    {{ $t('Reset filters') }}
                </button>
            </div>

            <div class="ui-card mt-3">
                <!-- Tabellenkopf -->
                <div>
                    <div
                        class="grid grid-cols-12 gap-4 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-text-subtle">
                        <div class="col-span-3">{{ $t('Name') }}</div>
                        <div class="col-span-2">{{ $t('Time range') }}</div>
                        <div class="col-span-1">{{ $t('Room') }}</div>
                        <div class="col-span-2">{{ $t('Project') }}</div>
                        <div class="col-span-1">{{ $t('Files') }}</div>
                        <div class="col-span-2">{{ $t('Responsible') }}</div>
                        <div class="col-span-1">{{ $t('Status') }}</div>
                    </div>
                    <div class="border-y border-border-subtle"></div>
                </div>

                <!-- Rows -->
                <div>
                    <template v-if="issues?.data?.length">
                        <SingleInternMaterialIssue
                            v-for="issueOfMaterial in issues.data"
                            :key="issueOfMaterial.id"
                            :issue-of-material="issueOfMaterial"
                            :detailed-article="detailedArticle"
                        />
                    </template>
                    <div v-else class="mt-6">
                        <BaseAlertComponent message="No issues of material found" type="error" use-translation/>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                <BasePaginator property-name="issues" :entities="issues"/>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <issue-of-material-modal
            v-if="showIssueOfMaterialModal"
            :issue-of-material="issueToEdit"
            @close="closeIssueModal"
        />

        <!-- Log Modal -->
        <MaterialIssueLogModal
            v-if="showLogModal"
            :projects="projects"
            @close="showLogModal = false"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArticleSearch from "@/Components/SearchBars/ArticleSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import IssueTabs from "@/Pages/IssueOfMaterial/Components/IssueTabs.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import MaterialIssueLogModal from "@/Pages/IssueOfMaterial/Components/MaterialIssueLogModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SingleInternMaterialIssue from "@/Pages/IssueOfMaterial/Components/SingleInternMaterialIssue.vue";
import {router, usePage} from "@inertiajs/vue3";
import {computed, provide, ref, watch} from "vue";
import {can, is} from "laravel-permission-to-vuejs";
import {IconCirclePlus, IconCopyPlus, IconFilter, IconFilterOff, IconMenu4, IconSearch, IconX} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import IssueDateRangeFilter from "@/Pages/IssueOfMaterial/Components/IssueDateRangeFilter.vue";

const props = defineProps({
    issues: Object,
    articlesInFilter: {type: Array, default: () => []},
    materialSets: Object,
    detailedArticle: Object,
    categories: Object,
    filterable_properties: {type: Array, default: () => []},
    articles: {type: Object, default: () => []}
});

provide("materialSets", props.materialSets);

const page = usePage();
const rooms = computed(() => page.props.rooms ?? []);
const projects = computed(() => page.props.projects ?? []);
const users = computed(() => page.props.users ?? []);
const initial = page.props.urlParameters ?? {};

const showIssueOfMaterialModal = ref(false);
const showLogModal = ref(false);
const issueToEdit = ref(null);
const openIssueOfMaterialModal = () => {
    issueToEdit.value = null;
    showIssueOfMaterialModal.value = true;
};
const closeIssueModal = () => {
    showIssueOfMaterialModal.value = false;
    issueToEdit.value = null;
};

// Artikel-Filter
const articleNamesForFilter = ref(props.articlesInFilter ?? []);
const addArticleNameToFilter = (article) => {
    if (!articleNamesForFilter.value.find(a => a.id === article.id)) {
        articleNamesForFilter.value.push(article);
    }
};
const currentArticleIdsCsv = computed(() =>
    articleNamesForFilter.value?.length
        ? articleNamesForFilter.value.map(a => a.id).join(',')
        : (page.props.urlParameters?.article_ids ?? '')
);
const filterIssueByArticleIds = () => {
    router.reload({
        preserveState: true, preserveScroll: true,
        data: {article_ids: currentArticleIdsCsv.value || undefined},
        only: ['issues', 'articlesInFilter'], replace: true
    });
};
watch(articleNamesForFilter, () => filterIssueByArticleIds(), {deep: true});

// Kernfilter
const filters = ref({
    date_from: initial.date_from ?? "",
    date_to: initial.date_to ?? "",
    project_id: initial.project_id ?? "",
    room_id: initial.room_id ?? "",
    responsible_user_ids: Array.isArray(initial.responsible_user_ids)
        ? initial.responsible_user_ids.map(Number)
        : (typeof initial.responsible_user_ids === 'string' && initial.responsible_user_ids.length
            ? initial.responsible_user_ids.split(',').map(x => Number(x))
            : []),
    q: initial.q ?? "", // <- NEU
});

const applyFilters = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
        data: {
            article_ids: currentArticleIdsCsv.value || undefined,
            date_from: filters.value.date_from || undefined,
            date_to: filters.value.date_to || undefined,
            project_id: filters.value.project_id || undefined,
            room_id: filters.value.room_id || undefined,
            responsible_user_ids: filters.value.responsible_user_ids?.length
                ? filters.value.responsible_user_ids.join(',')
                : undefined,
            q: filters.value.q || undefined, // <- NEU
        },
        replace: true,
        only: ['issues', 'articlesInFilter']
    });
};

// Debounce auto-apply
let debounceTimer = null;
watch(filters, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 280);
}, {deep: true});

const resetFilters = () => {
    filters.value = {
        date_from: "",
        date_to: "",
        project_id: "",
        room_id: "",
        responsible_user_ids: [],
        q: "", // <- NEU
    };
    applyFilters();
};

const hasAnyFilter = computed(() =>
    !!(filters.value.date_from || filters.value.date_to || filters.value.project_id || filters.value.room_id || filters.value.responsible_user_ids?.length || filters.value.q)
);

/** Anzahl gesetzter Filter für das Badge am mobilen Filter-Button */
const activeFilterCount = computed(() =>
    [
        filters.value.date_from || filters.value.date_to,
        filters.value.room_id,
        filters.value.project_id,
        filters.value.responsible_user_ids?.length,
    ].filter(Boolean).length
);

/** Mobil sind die Filterfelder hinter einem Button eingeklappt, ab sm immer sichtbar */
const showFilterControls = ref(false);

// Zuständig (Mehrfachauswahl)
const displayUserName = (u) => {
    const first = (u.first_name || '').trim();
    const last = (u.last_name || '').trim();
    const full = [first, last].filter(Boolean).join(' ').trim();
    return full || u.name || u.email || `#${u.id}`;
};
const selectedResponsibleUsers = computed(() =>
    (users.value || []).filter(u => (filters.value.responsible_user_ids || []).includes(u.id))
);
const onResponsibleUsersChange = (selectedUsers) => {
    filters.value.responsible_user_ids = (selectedUsers ?? []).map(u => u.id);
};
</script>
