<template>
    <ArtworkBaseModal
        title="Details External material issue"
        description="All details about this external loan - including contact, inventory, return status and additional information."
        @close="$emit('close')"
        modal-size="max-w-4xl"
    >
        <div class="mt-4 space-y-6 text-sm text-zinc-800">
            <!-- Header Badges -->
            <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-inset ring-blue-200">
          {{ issue?.articles?.length || 0 }} {{ $t('articles') }}
        </span>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
          {{ issue?.files?.length || 0 }} {{ $t('files') }}
        </span>
                <span v-if="issue?.special_items?.length" class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-200">
          {{ issue?.special_items?.length }} {{ $t('Special article') }}
        </span>
            </div>

            <!-- Stammdaten -->
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-sky-50 via-sky-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-sky-500"></span>
                        {{ $t('Base data') }}
                    </h3>
                    <p class="text-[11px] text-zinc-500">{{ $t('Value, period and persons involved.') }}</p>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-zinc-500">{{ $t('Material value') }}</p>
                        <p class="font-semibold text-zinc-900">{{ Number(issue.material_value).toFixed(2) }} €</p>
                    </div>
                    <div>
                        <p class="text-zinc-500">{{ $t('Period') }}</p>
                        <p class="font-semibold text-zinc-900">{{ issue.issue_date_formatted }} – {{ issue.return_date_formatted }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-500">{{ $t('Issued By') }}</p>
                        <div class="flex items-center mt-1">
                            <UserPopoverTooltip :user="issue.issued_by" width="8" height="8" classes="border-2 border-white rounded-full" />
                        </div>
                    </div>
                    <div v-if="issue.received_by">
                        <p class="text-zinc-500">{{ $t('Received by') }}</p>
                        <div class="flex items-center mt-1">
                            <UserPopoverTooltip :user="issue.received_by" width="8" height="8" classes="border-2 border-white rounded-full" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Externer Kontakt -->
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-indigo-50 via-indigo-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                        {{ $t('External contact person / company') }}
                    </h3>
                </div>
                <div class="p-5">
                    <p class="font-medium text-zinc-900">
                        {{ issue.external_name }}<br />
                        <span v-if="issue.external_address" class="text-zinc-700">{{ issue.external_address }}</span><br />
                        <span v-if="issue.external_email" class="text-zinc-700">{{ issue.external_email }}</span><br />
                        <span v-if="issue.external_phone" class="text-zinc-700">{{ issue.external_phone }}</span>
                    </p>
                </div>
            </section>

            <!-- Artikel -->
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-indigo-50 via-indigo-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                        {{ $t('Articles') }}
                    </h3>
                    <p class="text-[11px] text-zinc-500" v-if="issue.articles?.length">{{ $t('Loaned inventory and quantities.') }}</p>
                </div>
                <div class="p-5">
                    <div v-if="issue.articles?.length" class="divide-y divide-zinc-200/80 rounded-xl border border-zinc-200 bg-zinc-50/40 overflow-hidden">
                        <div v-for="a in issue.articles" :key="a.id" class="flex items-center justify-between gap-3 p-3">
                            <div class="min-w-0 flex items-center gap-2">
                                <span class="font-medium text-zinc-900 truncate">{{ a.name }}</span>
                                <component is="IconSearch" @click="openArticleDetailModal(a.id)" class="size-4 cursor-pointer text-zinc-400 hover:text-indigo-600 duration-200" />
                            </div>
                            <div class="shrink-0 inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-800">
                                <span>{{ $t('Quantity') }}:</span>
                                <span class="tabular-nums">{{ a.pivot.quantity }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-zinc-500 italic">{{ $t('No items assigned.') }}</p>
                </div>
            </section>

            <!-- Sonderartikel -->
            <section v-if="issue.special_items?.length" class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-violet-50 via-violet-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-violet-500"></span>
                        {{ $t('Special article') }}
                    </h3>
                </div>
                <div class="p-5">
                    <div class="divide-y divide-zinc-200/80 rounded-xl border border-zinc-200 bg-zinc-50/40 overflow-hidden">
                        <div v-for="s in issue.special_items" :key="s.id" class="flex items-center justify-between gap-3 p-3">
                            <span class="font-medium text-zinc-900 truncate">{{ s.name }}</span>
                            <div class="shrink-0 inline-flex items-center gap-1 rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-800">
                                <span>{{ $t('Quantity') }}:</span>
                                <span class="tabular-nums">{{ s.quantity }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Rückgabe-Mängel -->
            <section v-if="issue.return_remarks" class="rounded-2xl border border-amber-200 bg-amber-50/60">
                <div class="border-b border-amber-100 px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-amber-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-amber-500"></span>
                        {{ $t('Defects after return') }}
                    </h3>
                </div>
                <div class="p-5">
                    <p class="whitespace-pre-line font-medium text-amber-900">{{ issue.return_remarks }}</p>
                </div>
            </section>

            <!-- Dateien -->
            <section v-if="issue.files?.length" class="rounded-2xl border border-emerald-200 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 via-emerald-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-emerald-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-emerald-500"></span>
                        {{ $t('Files') }}
                    </h3>
                </div>
                <div class="p-5">
                    <ul class="space-y-2">
                        <li v-for="file in issue.files" :key="file.id" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2">
                            <a :href="'/storage/' + file.file_path" target="_blank" download class="truncate text-sm font-medium text-blue-700 hover:underline">
                                {{ file.original_name }}
                            </a>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Footer Buttons -->
            <div class="flex justify-end">
                <ArtworkBaseModalButton type="button" variant="danger" @click="$emit('close')">
                    {{ $t('Close') }}
                </ArtworkBaseModalButton>
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
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import { defineAsyncComponent, ref } from "vue";
import { router } from '@inertiajs/vue3';

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
