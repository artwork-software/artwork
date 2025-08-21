<template>
    <ArtworkBaseModal
        :title="$t('Article Usage Overview')"
        :description="$t('Overview of internal and external usage for the selected article.')"
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >

        <ul class="mt-2 divide-y text-gray-700 mb-5">
            <li class="pb-2 space-y-1">
                <div class="flex items-center sDark justify-between pb-2">
                    <div>{{ props.detailsForModal.article.name }}</div>
                    <div class="flex space-x-4">
                        <div class="flex flex-col items-center">
                            <span class="text-xs text-gray-600">{{ $t('total') }}</span>
                            <div class="w-full border-t border-gray-300 my-1"></div>
                            <span class="text-xl">{{ props.detailsForModal?.article?.quantity }}</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="text-xs text-gray-600">{{ $t('available') }}</span>
                            <div class="w-full border-t border-gray-300 my-1"></div>
                            <span class="text-xl" :class="{ 'text-red-600': getAvailableQuantity() < 0 }">
                                {{ getAvailableQuantity() }}
                            </span>
                        </div>
                    </div>
                </div>



                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-sm mt-3">
                    <div
                        v-for="status in props.detailsForModal.article.status"
                        :key="status.id"
                        class="flex flex-col items-center"
                    >
                        <div class="w-full h-12 flex items-center justify-center rounded-lg font-medium text-xl mb-2 border shadow-sm" :style="{backgroundColor: status.color + '33', borderColor: status.color + '66', color: status.color}">
                            {{ status.value }}
                        </div>
                        <span class="text-xs font-medium">{{ status.name }}</span>
                    </div>
                </div>
            </li>
        </ul>
        <div class="pb-4">
            <dashed-divider></dashed-divider>
        </div>
        <h4 class="text-md font-semibold text-gray-800 mb-3">{{ $t('usage_on_by') }} {{ formatDate(props.detailsForModal.date) }}</h4>
        <TabGroup>
        <TabList class="flex space-x-1 rounded-lg bg-gray-100 p-1">
            <Tab
                v-for="tab in ['internal', 'external']"
                :key="tab"
                v-slot="{ selected }"
                class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 focus:outline-none relative"
                :class="[
                    selected
                        ? 'bg-white text-blue-700 shadow-md border-b-2 border-blue-500'
                        : 'text-gray-600 hover:bg-white/70 hover:text-blue-600'
                ]"
            >
                <div class="flex items-center justify-center">
                    {{ $t(tab) }}
                    <span class="ml-1 text-xs bg-gray-200 text-gray-700 rounded-full px-2 py-0.5">
                        {{ tab === 'internal' ? getTotalQuantity(props.detailsForModal.internal || []) : getTotalQuantity(props.detailsForModal.external || []) }}
                    </span>
                    <div v-if="selected" class="absolute -bottom-1 left-0 w-full h-0.5 bg-blue-500"></div>
                </div>
            </Tab>
        </TabList>

        <TabPanels class="mt-6">
            <TabPanel>
                <UsageTable :issues="props.detailsForModal.internal || []" />
            </TabPanel>
            <TabPanel>
                <UsageTable :issues="props.detailsForModal.external || []" />
            </TabPanel>
        </TabPanels>
    </TabGroup>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import UsageTable from './UsageTable.vue'
import DashedDivider from "@/Artwork/Divider/DashedDivider.vue";
const props = defineProps({
    detailsForModal: {
        type: Object,
        required: true
    }
})

const statusMap = {
    'Einsatzbereit': { color: 'text-green-600', icon: '🟢', bgColor: 'bg-green-100', borderColor: 'border-green-300' },
    'Defekt': { color: 'text-red-500', icon: '❌', bgColor: 'bg-red-100', borderColor: 'border-red-300' },
    'Ausgesondert': { color: 'text-yellow-500', icon: '⚠️', bgColor: 'bg-yellow-100', borderColor: 'border-yellow-300' },
    'Nicht auffindbar': { color: 'text-gray-500', icon: '❓', bgColor: 'bg-gray-100', borderColor: 'border-gray-300' },
    'fest verbaut': { color: 'text-blue-500', icon: '📦', bgColor: 'bg-blue-100', borderColor: 'border-blue-300' }
}

const emit = defineEmits(['close']);

/**
 * Calculate the total quantity of articles in the given issues
 * @param {Array} issues - Array of issues
 * @return {number} - Total quantity
 */
const getTotalQuantity = (issues) => {
    return issues.reduce((total, issue) => {
        return total + issue.articles.reduce((articleTotal, article) => {
            return articleTotal + (article.pivot?.quantity || 0);
        }, 0);
    }, 0);
};

/**
 * Format date to DD.MM.YYYY
 * @param {string} dateString - Date string in YYYY-MM-DD format
 * @return {string} - Formatted date string
 */
const formatDate = (dateString) => {
    if (!dateString) return '';
    const [year, month, day] = dateString.split('-');
    return `${day}.${month}.${year}`;
};

/**
 * Get the quantity of articles with status "Einsatzbereit" (available)
 * @return {number} - Available quantity
 */
const getAvailableQuantity = () => {
    if (!props.detailsForModal?.article?.status) return 0;

    // Find the status with name "Einsatzbereit" (available)
    const availableStatus = props.detailsForModal.article.status.find(
        status => status.name === 'Einsatzbereit'
    );
    const internalUsage = getTotalQuantity(props.detailsForModal.internal || []);
    const externalUsage = getTotalQuantity(props.detailsForModal.external || []);

    // Return the value of the available status or 0 if not found
    return availableStatus ? availableStatus.value - (internalUsage + externalUsage) : 0;
};

</script>

<style scoped>

</style>
