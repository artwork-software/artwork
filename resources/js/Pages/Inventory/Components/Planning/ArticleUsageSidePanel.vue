<template>
    <Teleport to="body">
        <div
            class="fixed inset-y-0 right-0 z-40 flex pointer-events-none"
        >
            <Transition
                enter-active-class="transition-transform duration-200 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-150 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="visible"
                    ref="panelRef"
                    class="relative z-10 h-full w-[400px] max-w-[90vw] bg-white shadow-2xl border-l border-border-subtle flex flex-col pointer-events-auto"
                >
                    <header class="flex items-start gap-2 border-b border-border-subtle px-4 py-3 sticky top-0 bg-white z-10">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-text truncate">
                                {{ props.detailsForModal?.article?.name || $t('Article Usage Overview') }}
                            </h3>
                            <p class="text-[11px] text-text-subtle mt-0.5">
                                <template v-if="props.detailsForModal?.date">
                                    {{ formatDate(props.detailsForModal.date) }}
                                </template>
                                <template v-else-if="props.detailsForModal?.start_date">
                                    {{ formatDate(props.detailsForModal.start_date) }} – {{ formatDate(props.detailsForModal.end_date) }}
                                </template>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-md p-1 text-text-subtle hover:bg-surface-sunken hover:text-text-muted"
                            @click="$emit('close')"
                            :aria-label="$t('Close')"
                        >
                            <IconX class="size-5" />
                        </button>
                    </header>

                    <!-- Lightweight loading skeleton while the JSON endpoint is in flight -->
                    <div v-if="loading && !props.detailsForModal?.article" class="px-4 py-3 space-y-3 animate-pulse">
                        <div class="h-20 rounded-xl bg-surface-sunken"></div>
                        <div class="h-32 rounded-xl bg-surface-sunken"></div>
                        <div class="h-24 rounded-xl bg-surface-sunken"></div>
                    </div>

                    <div
                        v-else
                        class="flex-1 overflow-y-auto px-4 py-3 space-y-4 text-sm text-text"
                        :class="{ 'text-text-subtle pointer-events-none': isRefreshing || loading }"
                    >
                        <!-- Article quantity summary -->
                        <section v-if="props.detailsForModal?.article" class="rounded-xl border border-border-subtle bg-white">
                            <div class="border-b border-border-subtle bg-gradient-to-r from-info-surface to-transparent px-3 py-2 rounded-t-xl">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-medium uppercase tracking-wide text-info">{{ $t('Stock') }}</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 divide-x divide-border-subtle text-center text-[11px]">
                                <div class="px-2 py-2">
                                    <div class="text-text-subtle">{{ $t('Total quantity') }}</div>
                                    <div class="text-lg font-semibold tabular-nums text-text">{{ props.detailsForModal.article.quantity ?? 0 }}</div>
                                </div>
                                <div class="px-2 py-2">
                                    <div class="text-text-subtle">{{ $t('of which available') }}</div>
                                    <div class="text-lg font-semibold tabular-nums text-success">{{ getEinsatzbereitQuantity() }}</div>
                                </div>
                                <div class="px-2 py-2">
                                    <div class="text-text-subtle">{{ $t('available after usage') }}</div>
                                    <div
                                        class="text-lg font-semibold tabular-nums"
                                        :class="{ 'text-danger': getAvailableQuantity() < 0, 'text-success': getAvailableQuantity() >= 0 }"
                                    >{{ getAvailableQuantity() }}</div>
                                </div>
                            </div>

                            <!-- Breakdown of the total stock by article status -->
                            <div
                                v-if="statusBreakdown.length"
                                class="border-t border-border-subtle px-3 py-2.5"
                            >
                                <div class="text-[10px] font-medium uppercase tracking-wide text-text-subtle mb-1">
                                    {{ $t('Status composition') }}
                                </div>
                                <div class="divide-y divide-border-subtle">
                                    <div
                                        v-for="status in statusBreakdown"
                                        :key="status.id"
                                        class="flex items-center justify-between gap-2 py-1.5"
                                    >
                                        <span class="flex min-w-0 items-center gap-1.5">
                                            <span class="inline-block size-2 shrink-0 rounded-full" :style="{ backgroundColor: status.color }"></span>
                                            <span class="truncate text-[11px] text-text">{{ status.name }}</span>
                                        </span>
                                        <span class="shrink-0 text-[11px] font-semibold tabular-nums text-text">{{ status.value }}</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Tabs: internal / external -->
                        <section v-if="props.detailsForModal?.article" class="rounded-xl border border-border-subtle bg-white p-3">
                            <TabGroup :default-index="defaultTabIndex">
                                <TabList class="flex rounded-lg bg-surface-sunken p-1">
                                    <Tab as="template" v-slot="{ selected }">
                                        <button
                                            class="w-full rounded-md py-1.5 text-xs font-medium leading-5 focus:outline-none transition"
                                            :class="selected ? 'bg-white text-accent-700 shadow' : 'text-text-muted hover:bg-white/70'"
                                        >
                                            {{ $t('internal') }}
                                            <span class="ml-1 text-[10px] bg-border-subtle text-text-muted rounded-full px-1.5 py-0.5 tabular-nums">
                                                {{ getTotalQuantity(props.detailsForModal.internal || []) }}
                                            </span>
                                        </button>
                                    </Tab>
                                    <Tab as="template" v-slot="{ selected }">
                                        <button
                                            class="w-full rounded-md py-1.5 text-xs font-medium leading-5 focus:outline-none transition"
                                            :class="selected ? 'bg-white text-accent-700 shadow' : 'text-text-muted hover:bg-white/70'"
                                        >
                                            {{ $t('external') }}
                                            <span class="ml-1 text-[10px] bg-border-subtle text-text-muted rounded-full px-1.5 py-0.5 tabular-nums">
                                                {{ getTotalQuantity(props.detailsForModal.external || []) }}
                                            </span>
                                        </button>
                                    </Tab>
                                </TabList>

                                <TabPanels class="mt-3">
                                    <TabPanel>
                                        <UsageTable
                                            :issues="props.detailsForModal.internal || []"
                                            :editing-issue-id="editingIssueId"
                                            :editing-article-quantity="editingArticleQuantity"
                                            :planning-date="props.detailsForModal.date || null"
                                            :highlight-issue-id="props.focusIssueType === 'intern' ? props.focusIssueId : null"
                                            @dataChanged="handleDataChanged"
                                            @quantityUpdated="handleQuantityUpdated"
                                        />
                                    </TabPanel>
                                    <TabPanel>
                                        <UsageTable
                                            :issues="props.detailsForModal.external || []"
                                            extern
                                            :planning-date="props.detailsForModal.date || null"
                                            :highlight-issue-id="props.focusIssueType === 'extern' ? props.focusIssueId : null"
                                            @dataChanged="handleDataChanged"
                                            @quantityUpdated="handleQuantityUpdated"
                                        />
                                    </TabPanel>
                                </TabPanels>
                            </TabGroup>
                        </section>

                        <!-- Negative availability warning -->
                        <div
                            v-if="props.detailsForModal?.article && getAvailableQuantity() < 0"
                            class="rounded-lg border border-danger-border bg-danger-surface p-2 text-xs text-danger flex items-center gap-2"
                        >
                            <span class="inline-flex size-4 items-center justify-center rounded-full bg-danger text-white text-[9px] font-bold">!</span>
                            <span>{{ $t('Selected usage exceeds available stock for this date.') }}</span>
                        </div>
                    </div>
                </aside>
            </Transition>
        </div>
    </Teleport>
</template>

<script setup>
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import UsageTable from './UsageTable.vue';
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { IconX } from '@tabler/icons-vue';
import axios from 'axios';

const props = defineProps({
    visible: { type: Boolean, default: false },
    detailsForModal: { type: Object, default: () => ({}) },
    editingIssueId: { type: Number, default: null },
    editingArticleQuantity: { type: Number, default: null },
    focusIssueId: { type: Number, default: null },
    focusIssueType: { type: String, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'refreshData']);

const panelRef = ref(null);

const onClickOutside = (e) => {
    // Ignore clicks inside a modal/dialog opened on top of the panel (e.g. the
    // material-issue edit modal). It is portaled outside the panel's DOM, so a
    // click in one of its fields would otherwise be treated as "outside" and
    // close both the modal and the panel.
    if (e.target.closest?.('[role="dialog"], .draggableModal')) {
        return;
    }
    if (panelRef.value && !panelRef.value.contains(e.target)) {
        emit('close');
    }
};

const onEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

watch(() => props.visible, (val) => {
    if (val) {
        document.addEventListener('mousedown', onClickOutside);
        document.addEventListener('keydown', onEscape);
    } else {
        document.removeEventListener('mousedown', onClickOutside);
        document.removeEventListener('keydown', onEscape);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside);
    document.removeEventListener('keydown', onEscape);
});

const isRefreshing = ref(false);

const defaultTabIndex = computed(() => (props.focusIssueType === 'extern' ? 1 : 0));

// All status buckets of the article (Einsatzbereit, fest verbaut, ausgesondert, …)
// with their configured colour, rendered as badges in the stock summary.
const statusBreakdown = computed(() => {
    const statuses = props.detailsForModal?.article?.status ?? [];
    return statuses.filter((s) => s && s.color);
});

const handleDataChanged = async () => {
    const articleId = props.detailsForModal?.article?.id;
    const singleDate = props.detailsForModal?.date;
    const startDate = props.detailsForModal?.start_date;
    const endDate = props.detailsForModal?.end_date;

    if (articleId && (startDate || singleDate)) {
        isRefreshing.value = true;
        try {
            const response = await axios.get(route('inventory.articles.usage'), {
                params: {
                    article_id: articleId,
                    start_date: startDate || singleDate,
                    end_date: endDate || singleDate,
                },
            });
            Object.assign(props.detailsForModal, response.data.data);
        } catch (error) {
            console.error('Failed to refresh usage data:', error);
        } finally {
            isRefreshing.value = false;
        }
    }

    emit('refreshData');
};

const handleQuantityUpdated = (quantityData) => {
    if (!quantityData?.updatedArticles || !quantityData.issueId) return;
    const apply = (list) => {
        list?.forEach((issue) => {
            if (issue.id === quantityData.issueId && issue.articles) {
                issue.articles.forEach((article) => {
                    const updated = quantityData.updatedArticles.find((ua) => ua.id === article.id);
                    if (updated && article.pivot) {
                        article.pivot.quantity = updated.quantity;
                    }
                });
            }
        });
    };
    apply(props.detailsForModal.internal);
    apply(props.detailsForModal.external);
};

const getTotalQuantity = (issues) => (issues || []).reduce((total, issue) => {
    return total + (issue.articles || []).reduce((acc, a) => acc + (a.pivot?.quantity || 0), 0);
}, 0);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const [year, month, day] = dateString.split('-');
    return `${day}.${month}.${year}`;
};

const getEinsatzbereitQuantity = () => {
    const statuses = props.detailsForModal?.article?.status || [];
    return statuses.find((s) => s.name === 'Einsatzbereit')?.value ?? 0;
};

const getAvailableQuantity = () => {
    if (props.detailsForModal.min_available !== undefined && props.detailsForModal.min_available !== null) {
        return props.detailsForModal.min_available;
    }
    const avail = getEinsatzbereitQuantity();
    const internalUsage = getTotalQuantity(props.detailsForModal.internal || []);
    const externalUsage = getTotalQuantity(props.detailsForModal.external || []);
    return avail - (internalUsage + externalUsage);
};
</script>
