<template>
    <AppLayout :title="$t('Inventory')">
        <div class="px-10 w-full mx-auto">
            <div class="flex justify-between items-center pt-5">
                <h2 class="text-2xl font-semibold">{{ $t('Material issue book')}}</h2>
                <div class="flex items-center gap-x-1 w-96">
                    <ArticleSearch
                        @article-selected="addArticleNameToFilter"
                        class="w-72"
                    />
                    <button type="button" @click="filterIssueByArticleIds" class="p-4 flex items-center justify-center bg-gray-100 shadow-sm border border-gray-200 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer">
                        <component is="IconSearch" class="size-5" stroke-width="1.5"/>
                    </button>
                </div>

                <div>
                    <BaseButton v-if="can('inventory.disposition') || is('artwork admin')" :text="$t('New issue of material')" @click="openIssueOfMaterialModal">
                        <component is="IconCopyPlus" class="size-5 mr-2" />
                    </BaseButton>
                </div>
            </div>

            <div>
                <IssueTabs />
            </div>

            <div class="mb-4">
                <div class="flex flex-wrap gap-2">
                    <div v-for="(article, index) in articleNamesForFilter" :key="index" class="bg-blue-50 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full flex items-center border border-blue-200 font-lexend">
                        {{ article.name }}
                        <button type="button" @click="articleNamesForFilter.splice(index, 1)" class="ml-2 text-gray-400 hover:text-gray-600 focus:outline-none">
                            <component is="IconX" class="size-4" />
                        </button>
                    </div>
                </div>
            </div>
            <div class="relative">
                <BaseCard class="p-4">
                    <div class="sticky top-0 z-10 mb-4 rounded-lg bg-white w-full">
                        <div class="grid px-3 py-3 grid-cols-9 gap-4 w-full">
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Name') }}</h3>
                            </div>
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Time range') }}</h3>
                            </div>
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Room') }}</h3>
                            </div>
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Project') }}</h3>
                            </div>
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Files') }}</h3>
                            </div>
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Notes') }}</h3>
                            </div>
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Responsible') }}</h3>
                            </div>
                            <div class="flex items-center">
                                <h3 class="xsDark">{{ $t('Unresolved Special Items') }}</h3>
                            </div>
                            <div class="flex items-center"></div>
                        </div>
                    </div>
                    <div class="">
                        <div v-if="issues.data.length > 0">
                            <WhiteInnerCard class="my-3 group/issueOfMaterial" :key="issueOfMaterial.id" v-for="issueOfMaterial in issues.data">
                                <SingleInternMaterialIssue :issue-of-material="issueOfMaterial" />
                            </WhiteInnerCard>
                        </div>
                        <div v-else>
                            <BaseAlertComponent message="No issues of material found" type="error" use-translation />
                        </div>
                    </div>
                    <div class="mt-10 px-2">
                        <BasePaginator property-name="issues" :entities="issues" />
                    </div>
                </BaseCard>
            </div>
        </div>
        <issue-of-material-modal
            v-if="showIssueOfMaterialModal"
            @close="showIssueOfMaterialModal = false"
            :issue-of-material="null"
        />

    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import {computed, ref, provide} from "vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import SingleInternMaterialIssue from "@/Pages/IssueOfMaterial/Components/SingleInternMaterialIssue.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import IssueTabs from "@/Pages/IssueOfMaterial/Components/IssueTabs.vue";
import ArticleSearch from "@/Components/SearchBars/ArticleSearch.vue";
import {router, usePage} from "@inertiajs/vue3";
import {watch} from "vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {can, is} from "laravel-permission-to-vuejs";

const props = defineProps({
    issues: {
        type: Object,
        required: false,
    },
    articlesInFilter: {
        type: Array,
        required: false,
        default: () => []
    },
    materialSets: {
        type: Object,
        required: false,
    }
})
const showIssueOfMaterialModal = ref(false);
provide("materialSets", props.materialSets);

const openIssueOfMaterialModal = () => {
    showIssueOfMaterialModal.value = true;
};

const articleNamesForFilter = ref( props.articlesInFilter ?? [] );
const articleName = ref('');
const addArticleNameToFilter = (article) => {
    if (!articleNamesForFilter.value.includes(article)) {
        articleNamesForFilter.value.push(article);
    }
    articleName.value = '';
};

const filterIssueByArticleIds = () => {

    router.reload({
        data: {
            article_ids: articleNamesForFilter.value.map(a => a.id).join(',')
        }
    })
};

watch(articleNamesForFilter, (newValue) => {
    filterIssueByArticleIds()
}, {deep: true});
</script>

<style scoped>

</style>
