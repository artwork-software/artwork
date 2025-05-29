<template>
    <ArtworkBaseModal
        title="Article Usage Overview"
        description="Overview of internal and external usage for the selected article."
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >

        <ul class="mt-2 divide-y text-gray-700 mb-5">
            <li class="py-2 space-y-1">
                <div class="flex sDark justify-between">
                    <span>{{ detailsForModal.article.name }}</span>
                    <span>{{ detailsForModal.article.quantity }} Stk</span>
                </div>
                <div class="py-2">
                    <dashed-divider></dashed-divider>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-sm mt-3">
                    <div
                        v-for="status in detailsForModal.article.status"
                        :key="status.id"
                        class="flex flex-col items-center"
                    >
                        <div
                            class="w-full h-12 flex items-center justify-center rounded-lg font-medium text-xl mb-2 border-2 shadow-sm"
                            :class="[
                                statusMap[status.name]?.bgColor || 'bg-gray-100',
                                statusMap[status.name]?.borderColor || 'border-gray-200'
                            ]"
                        >
                            {{ status.value }}
                        </div>
                        <span class="text-xs font-medium text-gray-700">{{ status.name }}</span>
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
                class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 focus:outline-none relative"
                :class="[
                    selected
                        ? 'bg-white text-blue-700 shadow-md border-b-2 border-blue-500'
                        : 'text-gray-600 hover:bg-white/70 hover:text-blue-600'
                ]"
            >
                <div class="flex items-center justify-center">
                    {{ $t(tab) }}
                    <div v-if="selected" class="absolute -bottom-1 left-0 w-full h-0.5 bg-blue-500"></div>
                </div>
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

</script>

<style scoped>

</style>
