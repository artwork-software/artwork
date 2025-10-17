<template>
    <ArtworkBaseModal
        :title="$t('Article Usage Overview')"
        :description="$t('Overview of internal and external usage for the selected article.')"
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >
        <div class="mt-4 space-y-6 text-sm text-zinc-800">
            <!-- Artikel Kopfkarte -->
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div
                    class="border-b border-zinc-100 bg-gradient-to-r from-sky-50 via-sky-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        {{ props.detailsForModal.article.name }}
                    </h3>
                </div>
                <!-- Enhanced Stock Information -->
                <div class="flex flex-wrap items-start w-full">
                    <div class="flex flex-col items-center">
                        <button
                            @click="toggleStatusDetails"
                            class="inline-flex items-center flex flex-wrap text-lg bg-blue-50 py-2.5 w-72 text-md font-medium text-blue-700 ring-1 ring-inset ring-blue-200 hover:bg-blue-100 hover:ring-blue-300 transition-all duration-200 cursor-pointer group">
                            <div class="flex w-full items-center justify-between">
                                <div class="pl-2">
                                    {{ $t('Total quantity') }}:
                                </div>
                                <div class="flex flex-wrap">
                                    <div class="ml-1 tabular-nums text-2xl w-16">
                                        {{ props.detailsForModal?.article?.quantity ?? 0 }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-center w-full">
                                <div class="ml-2 text-secondary text-sm">{{ $t('Details on Click') }}</div>
                                <svg
                                    class=" w-3 h-3 transition-transform duration-200"
                                    :class="{ 'rotate-90': isStatusDetailsExpanded }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </button>
                        <div
                            class="inline-flex items-center justify-between flex text-lg bg-green-50 pr-6 py-2.5 text-md w-72 font-medium text-green-700 ring-1 ring-inset ring-green-200">
                            <div class="ml-2">
                                {{ $t('of which available') }}:
                            </div>
                            <div class="ml-1 tabular-nums text-2xl">{{ getEinsatzbereitQuantity() }}</div>
                        </div>
                        <div
                            class="inline-flex justify-between flex rounded-bl-lg items-center text-lg bg-emerald-50 pr-2 py-2.5 text-md w-72 font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                            <div class="ml-2">
                                {{ $t('available after usage') }}:
                            </div>
                            <div class="ml-1 tabular-nums text-2xl"
                                 :class="{ 'text-red-700': getAvailableQuantity() < 0 }">
                                {{ getAvailableQuantity() }}
                            </div>
                        </div>
                    </div>

                    <!-- Expandable Status Details -->
                    <div class="w-1/2">
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 max-h-0"
                            enter-to-class="opacity-100 max-h-screen"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 max-h-screen"
                            leave-to-class="opacity-0 max-h-0"
                        >
                            <div v-show="isStatusDetailsExpanded" class="overflow-hidden">
                                <div
                                    class="ml-4 mt-3 bg-gradient-to-r from-blue-50/30 to-transparent border-2 border-blue-200 p-4 rounded-lg shadow-sm">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-xs font-medium text-blue-700">{{
                                                $t('Status composition')
                                            }}:</span>
                                        <div class="h-px bg-blue-200 flex-1"></div>
                                    </div>
                                    <div class="flex flex-wrap gap-3 text-sm justify-start">
                                        <div
                                            v-for="status in props.detailsForModal.article.status"
                                            :key="status.id"
                                            class="flex flex-col items-center group"
                                        >
                                            <div
                                                class="w-20 h-10 flex items-center justify-center rounded-xl font-semibold text-base mb-2 border-2 shadow-sm transition-all duration-200 group-hover:scale-105"
                                                :style="{
                                                    backgroundColor: status.color + '20',
                                                    borderColor: status.color + '80',
                                                    color: status.color,
                                                    boxShadow: `0 2px 8px ${status.color}20`
                                                }"
                                            >
                                                <span class="tabular-nums font-bold">{{ status.value }}</span>
                                            </div>
                                            <span
                                                class="text-[10px] font-medium text-zinc-600 text-center leading-tight">{{
                                                    status.name
                                                }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </section>

            <!-- Stichtag / Datum -->
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div
                    class="border-b border-zinc-100 bg-gradient-to-r from-indigo-50 via-indigo-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                        <template v-if="props.detailsForModal.date">
                            {{ $t('usage_on_by') }} {{ formatDate(props.detailsForModal.date) }}
                        </template>
                        <template v-else>
                            {{ $t('usage_in_period') }} {{ formatDate(props.detailsForModal.start_date) }} - {{ formatDate(props.detailsForModal.end_date) }}
                        </template>
                    </h3>
                </div>
                <div class="p-5">
                    <!-- Tabs -->
                    <TabGroup>
                        <TabList class="flex rounded-xl bg-zinc-100 p-1">
                            <Tab
                                v-for="tab in ['internal', 'external']"
                                :key="tab"
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 focus:outline-none relative transition"
                                    :class="selected
                    ? 'bg-white text-indigo-700 shadow border border-indigo-200'
                    : 'text-zinc-600 hover:bg-white/70 hover:text-indigo-700'"
                                >
                                    <div class="flex items-center justify-center">
                                        {{ $t(tab) }}
                                        <span
                                            class="ml-2 text-[11px] bg-zinc-200 text-zinc-700 rounded-full px-2 py-0.5 tabular-nums">
                      {{
                                                tab === 'internal'
                                                    ? getTotalQuantity(props.detailsForModal.internal || [])
                                                    : getTotalQuantity(props.detailsForModal.external || [])
                                            }}
                    </span>
                                    </div>
                                </button>
                            </Tab>
                        </TabList>

                        <TabPanels class="mt-5">
                            <TabPanel>
                                <UsageTable :issues="props.detailsForModal.internal || []"
                                            @dataChanged="handleDataChanged" @quantityUpdated="handleQuantityUpdated"/>
                            </TabPanel>
                            <TabPanel>
                                <UsageTable :issues="props.detailsForModal.external || []" extern
                                            @dataChanged="handleDataChanged" @quantityUpdated="handleQuantityUpdated"/>
                            </TabPanel>
                        </TabPanels>
                    </TabGroup>
                </div>
            </div>

            <!-- Warnung bei negativer Verfügbarkeit -->
            <div v-if="getAvailableQuantity() < 0"
                 class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 flex items-center gap-2">
                <span
                    class="inline-flex size-5 items-center justify-center rounded-full bg-red-600 text-white text-[10px] font-bold">!</span>
                <span>{{ $t('Selected usage exceeds available stock for this date.') }}</span>
            </div>

            <!-- Footer -->
            <div class="flex justify-end">
                <BaseUIButton is-cancel-button @click="$emit('close')" :label="$t('Close')"/>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {TabGroup, TabList, Tab, TabPanels, TabPanel} from '@headlessui/vue'
import UsageTable from './UsageTable.vue'
import {ref} from 'vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    detailsForModal: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'refreshData'])

// State for status details expansion
const isStatusDetailsExpanded = ref(false)

const toggleStatusDetails = () => {
    isStatusDetailsExpanded.value = !isStatusDetailsExpanded.value
}

const handleDataChanged = () => {
    // Emit refresh event to parent to reload modal data
    emit('refreshData')
}

const handleQuantityUpdated = (quantityData) => {
    if (!quantityData || !quantityData.updatedArticles || !quantityData.issueId) {
        return
    }

    // Update quantities in internal issues
    if (props.detailsForModal.internal) {
        props.detailsForModal.internal.forEach(issue => {
            if (issue.id === quantityData.issueId && issue.articles) {
                issue.articles.forEach(article => {
                    const updatedArticle = quantityData.updatedArticles.find(ua => ua.id === article.id)
                    if (updatedArticle && article.pivot) {
                        article.pivot.quantity = updatedArticle.quantity
                    }
                })
            }
        })
    }

    // Update quantities in external issues (if needed in the future)
    if (props.detailsForModal.external) {
        props.detailsForModal.external.forEach(issue => {
            if (issue.id === quantityData.issueId && issue.articles) {
                issue.articles.forEach(article => {
                    const updatedArticle = quantityData.updatedArticles.find(ua => ua.id === article.id)
                    if (updatedArticle && article.pivot) {
                        article.pivot.quantity = updatedArticle.quantity
                    }
                })
            }
        })
    }
}

// helpers
const getTotalQuantity = (issues) => {
    return (issues || []).reduce((total, issue) => {
        return total + (issue.articles || []).reduce((acc, a) => acc + (a.pivot?.quantity || 0), 0)
    }, 0)
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const [year, month, day] = dateString.split('-')
    return `${day}.${month}.${year}`
}

const getEinsatzbereitQuantity = () => {
    const statuses = props.detailsForModal?.article?.status || []
    return statuses.find(s => s.name === 'Einsatzbereit')?.value ?? 0
}

const getAvailableQuantity = () => {
    const avail = getEinsatzbereitQuantity()
    const internalUsage = getTotalQuantity(props.detailsForModal.internal || [])
    const externalUsage = getTotalQuantity(props.detailsForModal.external || [])
    return avail - (internalUsage + externalUsage)
}
</script>
