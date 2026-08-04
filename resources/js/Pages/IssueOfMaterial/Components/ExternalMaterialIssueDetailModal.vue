<template>
    <ArtworkBaseModal
        title="Details External material issue"
        description="All details about this external loan - including contact, inventory, return status and additional information."
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >
        <div class="mt-4 space-y-6 text-sm text-text">
            <!-- Header Badges -->
            <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full bg-accent-50 px-2.5 py-1 text-[11px] font-medium text-accent-700 ring-1 ring-inset ring-accent-200">
          {{ issue?.articles?.length || 0 }} {{ $t('articles') }}
        </span>
                <span class="inline-flex items-center rounded-full bg-success-surface px-2.5 py-1 text-[11px] font-medium text-success ring-1 ring-inset ring-success-border">
          {{ issue?.files?.length || 0 }} {{ $t('files') }}
        </span>
                <span v-if="issue?.special_items?.length" class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-200">
          {{ issue?.special_items?.length }} {{ $t('Special article') }}
        </span>
            </div>

            <!-- Stammdaten -->
            <section class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle bg-gradient-to-r from-info-surface via-info-surface to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-info"></span>
                        {{ $t('Base data') }}
                    </h3>
                    <p class="text-[11px] text-text-subtle">{{ $t('Value, period and persons involved.') }}</p>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-text-subtle">{{ $t('Material value') }}</p>
                        <p class="font-semibold text-text">{{ Number(issue.material_value).toFixed(2) }} €</p>
                    </div>
                    <div>
                        <p class="text-text-subtle">{{ $t('Period') }}</p>
                        <p class="font-semibold text-text">{{ issue.issue_date_formatted }} – {{ issue.return_date_formatted }}</p>
                    </div>
                    <div>
                        <p class="text-text-subtle">{{ $t('Issued By') }}</p>
                        <div class="flex items-center mt-1">
                            <UserPopoverTooltip :user="issue.issued_by" width="8" height="8" classes="border-2 border-white rounded-full" />
                        </div>
                    </div>
                    <div v-if="issue.received_by">
                        <p class="text-text-subtle">{{ $t('Received by') }}</p>
                        <div class="flex items-center mt-1">
                            <UserPopoverTooltip :user="issue.received_by" width="8" height="8" classes="border-2 border-white rounded-full" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Externer Kontakt -->
            <section class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle bg-gradient-to-r from-accent-50 via-accent-50 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-accent-600"></span>
                        {{ $t('External contact person / company') }}
                    </h3>
                </div>
                <div class="p-5">
                    <p class="font-medium text-text">
                        {{ issue.external_name }}<br />
                        <span v-if="issue.external_address" class="text-text-muted">{{ issue.external_address }}</span><br />
                        <span v-if="issue.external_email" class="text-text-muted">{{ issue.external_email }}</span><br />
                        <span v-if="issue.external_phone" class="text-text-muted">{{ issue.external_phone }}</span>
                    </p>
                </div>
            </section>

            <!-- Artikel -->
            <section class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle bg-gradient-to-r from-accent-50 via-accent-50 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-accent-600"></span>
                        {{ $t('Articles') }}
                    </h3>
                    <p class="text-[11px] text-text-subtle" v-if="issue.articles?.length">{{ $t('Loaned inventory and quantities.') }}</p>
                </div>
                <div class="p-5">
                    <div v-if="issue.articles?.length" class="divide-y divide-border-subtle rounded-xl border border-border-subtle bg-surface-sunken overflow-hidden">
                        <div v-for="a in issue.articles" :key="a.id" class="flex items-center justify-between gap-3 p-3">
                            <div class="min-w-0 flex items-center gap-2">
                                <span class="font-medium text-text truncate">{{ a.name }}</span>
                                <component :is="IconSearch" @click="openArticleDetailModal(a.id)" class="size-4 cursor-pointer text-text-subtle hover:text-accent-600 duration-200" />
                            </div>
                            <div class="shrink-0 inline-flex items-center gap-1 rounded-md border border-accent-200 bg-accent-50 px-2 py-0.5 text-[11px] font-medium text-accent-700">
                                <span>{{ $t('Quantity') }}:</span>
                                <span class="tabular-nums">{{ a.pivot.quantity }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-text-subtle italic">{{ $t('No items assigned.') }}</p>
                </div>
            </section>

            <!-- Sonderartikel -->
            <section v-if="issue.special_items?.length" class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle bg-gradient-to-r from-violet-50 via-violet-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-violet-500"></span>
                        {{ $t('Special article') }}
                    </h3>
                </div>
                <div class="p-5">
                    <div class="divide-y divide-border-subtle rounded-xl border border-border-subtle bg-surface-sunken overflow-hidden">
                        <div v-for="s in issue.special_items" :key="s.id" class="flex items-center justify-between gap-3 p-3">
                            <span class="font-medium text-text truncate">{{ s.name }}</span>
                            <div class="shrink-0 inline-flex items-center gap-1 rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-800">
                                <span>{{ $t('Quantity') }}:</span>
                                <span class="tabular-nums">{{ s.quantity }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Rückgabe-Mängel -->
            <section v-if="issue.return_remarks" class="rounded-2xl border border-warning-border bg-warning-surface">
                <div class="border-b border-warning-border px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-warning flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-warning"></span>
                        {{ $t('Defects after return') }}
                    </h3>
                </div>
                <div class="p-5">
                    <p class="whitespace-pre-line font-medium text-warning">{{ issue.return_remarks }}</p>
                </div>
            </section>

            <!-- Dateien -->
            <section v-if="issue.files?.length" class="rounded-2xl border border-success-border bg-white shadow-sm">
                <div class="border-b border-success-border bg-gradient-to-r from-success-surface via-success-surface to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-success flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-success"></span>
                        {{ $t('Files') }}
                    </h3>
                </div>
                <div class="p-5">
                    <ul class="space-y-2">
                        <li v-for="file in issue.files" :key="file.id" class="flex items-center justify-between gap-3 rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                            <a :href="'/storage/' + file.file_path" target="_blank" download class="truncate text-sm font-medium text-accent-700 hover:underline">
                                {{ file.original_name }}
                            </a>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Footer Buttons -->
            <div class="flex justify-end">
                <BaseUIButton is-cancel-button :label="$t('Close')" @click="$emit('close')"/>
            </div>

            <ArticleDetailModal
                v-if="showDetailArticleModal"
                :article="detailedArticle"
                @close="showDetailArticleModal = false"
                :show-button-for-edit-and-delete="false"
            />
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import { defineAsyncComponent, ref } from "vue";
import { router } from '@inertiajs/vue3';
import {IconSearch} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    issue: { type: Object, required: true },
    detailedArticle: { type: Object, required: false }
});

const showDetailArticleModal = ref(false);

const openArticleDetailModal = (articleId) => {
    router.reload({
        data: { articleId },
        only: ['detailedArticle'],
        onSuccess: () => { showDetailArticleModal.value = true; }
    })
}

const ArticleDetailModal = defineAsyncComponent(() => import('@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue'))
</script>
