<template>
    <ArtworkBaseModal
        :title="$t('Article Usage Overview')"
        :description="$t('Overview of internal and external usage for the selected article.')"
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >
        <div class="mt-4 space-y-6 text-sm text-text" :class="{ 'text-text-subtle pointer-events-none': isRefreshing }">
            <!-- Artikel Kopfkarte -->
            <section class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div
                    class="border-b border-border-subtle bg-gradient-to-r from-info-surface via-info-surface/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-text flex items-center gap-2">
                        {{ props.detailsForModal.article.name }}
                    </h3>
                </div>
                <!-- Bestandsübersicht -->
                <div class="grid grid-cols-3 divide-x divide-border-subtle text-center">
                    <div class="px-4 py-4">
                        <div class="text-xs text-text-subtle">{{ $t('Total quantity') }}</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums text-text">
                            {{ props.detailsForModal?.article?.quantity ?? 0 }}
                        </div>
                    </div>
                    <div class="px-4 py-4">
                        <div class="text-xs text-text-subtle">{{ $t('of which available') }}</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums text-success">
                            {{ getEinsatzbereitQuantity() }}
                        </div>
                    </div>
                    <div class="px-4 py-4">
                        <div class="text-xs text-text-subtle">{{ $t('available after usage') }}</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums"
                             :class="getAvailableQuantity() < 0 ? 'text-danger' : 'text-success'">
                            {{ getAvailableQuantity() }}
                        </div>
                    </div>
                </div>

                <!-- Statusverteilung -->
                <div v-if="statusBreakdown.length" class="border-t border-border-subtle px-5 py-3">
                    <div class="flex h-2 w-full overflow-hidden rounded-full bg-surface-sunken">
                        <div
                            v-for="status in statusBreakdown"
                            :key="status.id"
                            class="h-full"
                            :style="{ width: getStatusPercent(status), backgroundColor: status.color }"
                            :title="`${status.name}: ${status.value}`"
                        ></div>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                        <span
                            v-for="status in statusBreakdown"
                            :key="status.id"
                            class="inline-flex items-center gap-1.5 text-xs text-text-muted"
                        >
                            <span class="inline-block size-2 rounded-full" :style="{ backgroundColor: status.color }"></span>
                            {{ status.name }}
                            <span class="font-semibold tabular-nums text-text">{{ status.value }}</span>
                        </span>
                    </div>
                </div>
            </section>

            <!-- Availability Timeline (only in range mode) -->
            <section v-if="props.detailsForModal.availability_timeline && props.detailsForModal.availability_timeline.length > 0"
                     class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle bg-gradient-to-r from-warning-surface via-warning-surface to-transparent px-5 py-3 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-text flex items-center gap-2">
                            <span class="inline-block size-2 rounded-full bg-warning"></span>
                            {{ $t('Availability timeline') }}
                        </h3>
                        <div class="text-sm font-medium"
                             :class="props.detailsForModal.min_available < 0 ? 'text-danger' : 'text-success'">
                            {{ $t('Minimum availability in period') }}:
                            <span class="font-bold tabular-nums text-base">{{ props.detailsForModal.min_available }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    <div class="flex rounded-lg overflow-hidden border border-border-subtle">
                        <div v-for="(segment, idx) in props.detailsForModal.availability_timeline"
                             :key="idx"
                             class="relative flex flex-col items-center justify-center py-3 px-1 min-w-[40px] text-center border-r last:border-r-0 border-border-subtle transition-all"
                             :style="{ flexGrow: segment.days }"
                             :class="getSegmentColorClass(segment.available)"
                        >
                            <span class="text-lg font-bold tabular-nums">{{ segment.available }}</span>
                            <span class="text-[10px] text-text-muted leading-tight">{{ $t('available') }}</span>
                            <span class="text-[10px] text-text-subtle mt-1 leading-tight">
                                {{ formatDate(segment.start) }}<template v-if="segment.start !== segment.end"> – {{ formatDate(segment.end) }}</template>
                            </span>
                            <span class="text-[10px] text-text-subtle">{{ segment.usage }} {{ $t('in use') }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stichtag / Datum -->
            <div class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div
                    class="border-b border-border-subtle bg-gradient-to-r from-accent-50 via-accent-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-accent-600"></span>
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
                        <TabList class="flex rounded-xl bg-surface-sunken p-1">
                            <Tab
                                v-for="tab in ['internal', 'external']"
                                :key="tab"
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 focus:outline-none relative transition"
                                    :class="selected ? 'bg-white text-accent-700 shadow border border-accent-200'
                    : 'text-text-muted hover:bg-white/70 hover:text-accent-700'"
                                >
                                    <div class="flex items-center justify-center">
                                        {{ $t(tab) }}
                                        <span
                                            class="ml-2 text-[11px] bg-border-subtle text-text-muted rounded-full px-2 py-0.5 tabular-nums">
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
                                            :editing-issue-id="editingIssueId"
                                            :editing-article-quantity="editingArticleQuantity"
                                            :planning-date="props.detailsForModal.date || null"
                                            @dataChanged="handleDataChanged" @quantityUpdated="handleQuantityUpdated"/>
                            </TabPanel>
                            <TabPanel>
                                <UsageTable :issues="props.detailsForModal.external || []" extern
                                            :planning-date="props.detailsForModal.date || null"
                                            @dataChanged="handleDataChanged" @quantityUpdated="handleQuantityUpdated"/>
                            </TabPanel>
                        </TabPanels>
                    </TabGroup>
                </div>
            </div>

            <!-- Warnung bei negativer Verfügbarkeit -->
            <div v-if="getAvailableQuantity() < 0"
                 class="rounded-xl border border-danger-border bg-danger-surface p-3 text-sm text-danger flex items-center gap-2">
                <span
                    class="inline-flex size-5 items-center justify-center rounded-full bg-danger text-white text-[10px] font-bold">!</span>
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
import {TabGroup, TabList, Tab, TabPanels, TabPanel} from '@headlessui/vue'
import UsageTable from './UsageTable.vue'
import {computed, ref} from 'vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import axios from 'axios';

const props = defineProps({
    detailsForModal: {
        type: Object,
        required: true
    },
    editingIssueId: {
        type: Number,
        default: null
    },
    editingArticleQuantity: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['close', 'refreshData'])

const statusBreakdown = computed(() =>
    (props.detailsForModal?.article?.status || []).filter((status) => (status.value ?? 0) > 0)
)

const getStatusPercent = (status) => {
    const total = statusBreakdown.value.reduce((sum, s) => sum + (s.value ?? 0), 0)
    if (!total) return '0%'
    return `${((status.value ?? 0) / total) * 100}%`
}

const isRefreshing = ref(false)

const handleDataChanged = async () => {
    const articleId = props.detailsForModal?.article?.id
    const startDate = props.detailsForModal?.start_date
    const endDate = props.detailsForModal?.end_date
    const singleDate = props.detailsForModal?.date

    // Self-refresh via axios
    if (articleId && (startDate || singleDate)) {
        isRefreshing.value = true
        try {
            const response = await axios.get(route('inventory.articles.usage'), {
                params: {
                    article_id: articleId,
                    start_date: startDate || singleDate,
                    end_date: endDate || singleDate,
                }
            })
            const newData = response.data.data
            // Update the detailsForModal prop data in-place
            Object.assign(props.detailsForModal, newData)
        } catch (error) {
            console.error('Failed to refresh usage data:', error)
        } finally {
            isRefreshing.value = false
        }
    }

    // Also emit to parent (for planning grid context which uses Inertia reload)
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
    // Use min_available from backend (sweep-line) when available (range mode)
    if (props.detailsForModal.min_available !== undefined && props.detailsForModal.min_available !== null) {
        return props.detailsForModal.min_available
    }
    // Fallback for single-day view (naive sum is correct for a single day)
    const avail = getEinsatzbereitQuantity()
    const internalUsage = getTotalQuantity(props.detailsForModal.internal || [])
    const externalUsage = getTotalQuantity(props.detailsForModal.external || [])
    return avail - (internalUsage + externalUsage)
}

const getSegmentColorClass = (available) => {
    if (available < 0) return 'bg-danger-surface text-danger'
    if (available === 0) return 'bg-special-orange-surface text-special-orange'
    if (available <= 2) return 'bg-warning-surface text-warning'
    return 'bg-success-surface text-success'
}
</script>
