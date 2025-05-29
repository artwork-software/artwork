<template>
    <ArtworkBaseModal
        title="Article Usage Overview"
        description="Overview of internal and external usage for the selected article."
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >

        <ul class="mt-2 divide-y text-gray-700 mb-5">
            <li class="py-2 space-y-1">
                <div class="flex justify-between">
                    <span class="font-medium">{{ detailsForModal.article.name }}</span>
                    <span>{{ detailsForModal.article.quantity }} Stk</span>
                </div>

                <div class="flex flex-wrap gap-2 text-sm mt-1">
                    <div
                        v-for="status in detailsForModal.article.status"
                        :key="status.id"
                        :class="['flex items-center gap-1 font-medium', statusMap[status.name]?.color || 'text-gray-500']"
                    >
                        <span>{{ statusMap[status.name]?.icon || '🔘' }}</span>
                        <span>{{ status.name }} ({{ status.value }})</span>
                    </div>
                </div>
            </li>
        </ul>
        <TabGroup>
        <TabList class="flex space-x-1 rounded-lg bg-gray-100 p-1">
            <Tab
                v-for="tab in ['Intern', 'Extern']"
                :key="tab"
                v-slot="{ selected }"
                class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 text-blue-700 focus:outline-none"
                :class="selected ? 'bg-white shadow' : 'hover:bg-white/70'"
            >
                {{ $t(tab) }}
            </Tab>
        </TabList>

        <TabPanels class="mt-6">
            <TabPanel>
                <UsageTable :issues="detailsForModal.internal" />
            </TabPanel>
            <TabPanel>
                <UsageTable :issues="detailsForModal.external" />
            </TabPanel>
        </TabPanels>
    </TabGroup>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import UsageTable from './UsageTable.vue'
const props = defineProps({
    detailsForModal: {
        type: Object,
        required: true
    }
})

const statusMap = {
    'Einsatzbereit': { color: 'text-green-600', icon: '🟢' },
    'Defekt': { color: 'text-red-500', icon: '❌' },
    'Ausgesondert': { color: 'text-yellow-500', icon: '⚠️' },
    'Nicht auffindbar': { color: 'text-gray-500', icon: '❓' },
    'fest verbaut': { color: 'text-blue-500', icon: '📦' }
}

const emit = defineEmits(['close']);

</script>

<style scoped>

</style>