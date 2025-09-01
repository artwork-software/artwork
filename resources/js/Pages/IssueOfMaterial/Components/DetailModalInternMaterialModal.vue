<template>
    <ArtworkBaseModal
        title="Details Internal material issue"
        description="Detailed information on the planned internal material issue - including time period, room, inventory and special items."
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
                    <p class="text-[11px] text-zinc-500">{{ $t('Name, period, room and responsibilities.') }}</p>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-zinc-500">{{ $t('Name') }}</p>
                        <p class="font-medium text-zinc-900">{{ issue.name }}</p>
                    </div>
                    <div v-if="issue.room">
                        <p class="text-zinc-500">{{ $t('Room') }}</p>
                        <p class="font-medium text-zinc-900">{{ issue.room.name }}</p>
                    </div>
                    <div v-if="issue.project">
                        <p class="text-zinc-500">{{ $t('Project') }}</p>
                        <p class="font-medium text-zinc-900">{{ issue.project.name }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-500">{{ $t('Period') }}</p>
                        <p class="font-medium text-zinc-900">{{ issue.start_date_time }} – {{ issue.end_date_time }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-zinc-500">{{ $t('Responsible persons') }}</p>
                        <div class="flex items-center print:hidden">
                            <div class="flex -space-x-2 overflow-hidden items-center">
                                <UserPopoverTooltip v-for="user in issue.responsible_users" :key="user.id || user.email" :user="user" width="8" height="8" classes="border-2 border-white rounded-full" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Notizen -->
            <section v-if="issue.notes" class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-indigo-50 via-indigo-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                        {{ $t('Notes') }}
                    </h3>
                </div>
                <div class="p-5">
                    <p class="whitespace-pre-line font-medium text-zinc-900">{{ issue.notes }}</p>
                </div>
            </section>

            <!-- Dateien -->
            <section v-if="issue.files?.length" class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-emerald-50 via-emerald-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
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

            <!-- Artikel -->
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-indigo-50 via-indigo-50/60 to-transparent px-5 py-3 rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                        {{ $t('Articles') }}
                    </h3>
                    <p class="text-[11px] text-zinc-500" v-if="issue.articles?.length">{{ $t('Inventory and quantities for this material issue.') }}</p>
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

            <!-- Status Hinweis -->
            <div v-if="issue.special_items?.length && !issue.special_items_done"
                 class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 flex items-center gap-2">
                <component is="IconAlertTriangle" class="h-4 w-4" />
                <span> {{ $t('There are unmarked special items in this material issue.') }} </span>
            </div>

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
