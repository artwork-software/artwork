<template>
    <ArtworkBaseModal
        :title="$t('Article Usage Overview')"
        :description="$t('Overview of internal and external usage for the selected article.')"
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >
        <div class="mt-4 space-y-6 text-sm text-zinc-800">
            <!-- Header badges -->
            <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-inset ring-blue-200">
          {{ $t('total') }}: <span class="ml-1 tabular-nums">{{ props.detailsForModal?.article?.quantity ?? 0 }}</span>
        </span>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
          {{ $t('available') }}: <span class="ml-1 tabular-nums" :class="{ 'text-red-700': getAvailableQuantity() < 0 }">{{ getAvailableQuantity() }}</span>
        </span>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200">
          {{ $t('internal') }}: <span class="ml-1 tabular-nums">{{ getTotalQuantity(props.detailsForModal.internal || []) }}</span>
        </span>
                <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-200">
          {{ $t('external') }}: <span class="ml-1 tabular-nums">{{ getTotalQuantity(props.detailsForModal.external || []) }}</span>
        </span>
            </div>

            <!-- Artikel Kopfkarte -->
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-sky-50 via-sky-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-sky-500"></span>
                        {{ props.detailsForModal.article.name }}
                    </h3>
                    <p class="text-[11px] text-zinc-500">{{ $t('Status distribution and stock levels') }}</p>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-sm">
                        <div
                            v-for="status in props.detailsForModal.article.status"
                            :key="status.id"
                            class="flex flex-col items-center"
                        >
                            <div
                                class="w-full h-12 flex items-center justify-center rounded-lg font-semibold text-base mb-2 border shadow-sm"
                                :style="{ backgroundColor: status.color + '26', borderColor: status.color + '66', color: status.color }"
                            >
                                <span class="tabular-nums">{{ status.value }}</span>
                            </div>
                            <span class="text-[11px] font-medium text-zinc-700 text-center">{{ status.name }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stichtag / Datum -->
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-indigo-50 via-indigo-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                        {{ $t('usage_on_by') }} {{ formatDate(props.detailsForModal.date) }}
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
                                        <span class="ml-2 text-[11px] bg-zinc-200 text-zinc-700 rounded-full px-2 py-0.5 tabular-nums">
                      {{ tab === 'internal'
                                            ? getTotalQuantity(props.detailsForModal.internal || [])
                                            : getTotalQuantity(props.detailsForModal.external || []) }}
                    </span>
                                    </div>
                                </button>
                            </Tab>
                        </TabList>

                        <TabPanels class="mt-5">
                            <TabPanel>
                                <UsageTable :issues="props.detailsForModal.internal || []" />
                            </TabPanel>
                            <TabPanel>
                                <UsageTable :issues="props.detailsForModal.external || []" />
                            </TabPanel>
                        </TabPanels>
                    </TabGroup>
                </div>
            </div>

            <!-- Warnung bei negativer Verfügbarkeit -->
            <div v-if="getAvailableQuantity() < 0" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 flex items-center gap-2">
                <span class="inline-flex size-5 items-center justify-center rounded-full bg-red-600 text-white text-[10px] font-bold">!</span>
                <span>{{ $t('Selected usage exceeds available stock for this date.') }}</span>
            </div>

            <!-- Footer -->
            <div class="flex justify-end">
                <ArtworkBaseModalButton type="button" variant="danger" @click="$emit('close')">
                    {{ $t('Close') }}
                </ArtworkBaseModalButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import UsageTable from './UsageTable.vue'

const props = defineProps({
    detailsForModal: {
        type: Object,
        required: true
    }
})

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

const getAvailableQuantity = () => {
    const statuses = props.detailsForModal?.article?.status || []
    const avail = statuses.find(s => s.name === 'Einsatzbereit')?.value ?? 0
    const internalUsage = getTotalQuantity(props.detailsForModal.internal || [])
    const externalUsage = getTotalQuantity(props.detailsForModal.external || [])
    return avail - (internalUsage + externalUsage)
}
</script>
