<template>
    <div v-if="issues && issues.length" class="space-y-4">
        <div
            v-for="issue in issues"
            :key="issue.id"
            class="rounded-lg border border-gray-300 p-4 shadow-sm bg-white flex justify-between"
        >
            <div>
                <div class="font-medium text-gray-800">{{ issue.name }}</div>
                <div class="text-xs text-gray-500">{{ issue.start_date_time }} – {{ issue.end_date_time }}</div>

                <ul class="mt-2 divide-y text-sm text-gray-700">
                    <li
                        v-for="article in issue.articles"
                        :key="article.id"
                        class="flex justify-between py-1"
                    >
                        <span>{{ article.name }}</span>
                        <span class="flex items-center gap-2">
                            <span>{{ article.pivot.quantity }} {{ $t('Stk') }}</span>
                            <span
                                v-if="editingIssueId && issue.id === editingIssueId && editingArticleQuantity != null"
                                class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-300"
                            >
                                {{ $t('Current adjustment') }}: {{ editingArticleQuantity }} {{ $t('Stk') }}
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
            <div>
                <button class="new-button-small" @click="openMaterialIssueModal(issue.id)" type="button" :disabled="loadingIssueId === issue.id">
                    <IconLoader2 v-if="loadingIssueId === issue.id" class="size-4 animate-spin" />
                    <IconEdit v-else class="size-4" />
                    {{ $t('Edit')}}
                </button>
            </div>
        </div>
    </div>
    <div v-else class="text-sm text-gray-400 italic">
        {{ $t('No data available') }}
    </div>


    <IssueOfMaterialModal
        v-if="showIssueOfMaterialModal"
        @close="closeIssueOfMaterialModal"
        @saved="handleDataSaved"
        :issue-of-material="extern ? null : issueForModal"
        :is-extern-or-intern="extern"
        :extern-material-issue="extern ? issueForModal : null"
        :project="issueForModal?.project || null"
        :planning-date="props.planningDate"
    />
</template>

<script setup>

import { IconEdit, IconLoader2 } from '@tabler/icons-vue';
import {defineAsyncComponent, ref} from 'vue';
import axios from 'axios';

const props = defineProps({
    issues: {
        type: Array,
        default: () => []
    },
    extern: {
        type: Boolean,
        default: false
    },
    editingIssueId: {
        type: Number,
        default: null
    },
    editingArticleQuantity: {
        type: Number,
        default: null
    },
    planningDate: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['dataChanged', 'quantityUpdated']);


const showIssueOfMaterialModal = ref(false);
const issueForModal = ref(null);
const loadingIssueId = ref(null);

const IssueOfMaterialModal = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue'),
    delay: 200,
})
const openMaterialIssueModal = async (issueId) => {
    const localIssue = props.issues.find(issue => issue.id === issueId) || null;

    if (!props.extern) {
        loadingIssueId.value = issueId;
        try {
            const response = await axios.get(route('issue-of-material.show', issueId));
            issueForModal.value = response.data;
        } catch (error) {
            console.error('Failed to fetch full issue data, using local fallback:', error);
            issueForModal.value = localIssue;
        } finally {
            loadingIssueId.value = null;
        }
    } else {
        issueForModal.value = localIssue;
    }

    showIssueOfMaterialModal.value = true;
};

const closeIssueOfMaterialModal = () => {
    showIssueOfMaterialModal.value = false;
    issueForModal.value = null;
    // Emit event to trigger data refresh in parent components
    emit('dataChanged');
};

const handleDataSaved = (quantityData) => {
    // Emit event with quantity data to directly update quantities in parent modal
    if (quantityData && quantityData.updatedArticles) {
        emit('quantityUpdated', quantityData);
    }
    // Also emit general data changed event for fallback
    emit('dataChanged');
};
</script>

<style scoped>

</style>
