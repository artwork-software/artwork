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
                        <span>{{ article.pivot.quantity }} Stk</span>
                    </li>
                </ul>
            </div>
            <div>
                <button class="new-button-small" @click="openMaterialIssueModal(issue.id)" type="button">
                    <IconEdit class="size-4" />
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
    />
</template>

<script setup>

import { IconEdit } from '@tabler/icons-vue';
import {defineAsyncComponent, ref} from 'vue';

const props = defineProps({
    issues: {
        type: Array,
        default: () => []
    },
    extern: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['dataChanged', 'quantityUpdated']);


const showIssueOfMaterialModal = ref(false);
const issueForModal = ref(null);

const IssueOfMaterialModal = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue'),
    delay: 200,
})
const openMaterialIssueModal = (issueId) => {
    issueForModal.value = props.issues.find(issue => issue.id === issueId) || null;
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
