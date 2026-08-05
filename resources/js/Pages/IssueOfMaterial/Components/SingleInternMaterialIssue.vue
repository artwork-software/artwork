<template>
    <!-- Zeile -->
    <div
        class="group grid grid-cols-12 gap-4 px-2 py-3 text-sm border-b border-border-subtle hover:bg-gradient-to-r hover:from-surface-sunken hover:to-white relative"
        :class="{ 'bg-warning-surface': usePage().props.urlParameters?.issue === String(issueOfMaterial.id) }"
    >
        <!-- Linke Akzent-Leiste bei Hover -->
        <span class="pointer-events-none absolute left-0 top-0 h-full w-0.5 bg-accent-500/0 transition-all duration-200 group-hover:bg-accent-500/70"></span>

        <!-- Name + Notes -->
        <div class="col-span-3">
            <button class="text-left text-accent-600 hover:text-accent-700 hover:underline underline-offset-2" @click="showIssueOfMaterialModal = true">
                {{ issueOfMaterial.name }}
            </button>
            <p v-if="issueOfMaterial.notes" class="text-xs text-text-subtle line-clamp-2 mt-0.5">
                {{ issueOfMaterial.notes }}
            </p>
            <div v-if="issueOfMaterial.articles?.length" class="mt-2 flex items-center gap-2">
                <div class="flex -space-x-2" :aria-label="$t('Issued articles')">
                    <div
                        v-for="article in issueOfMaterial.articles.slice(0, 3)"
                        :key="article.id"
                        class="grid size-8 place-items-center overflow-hidden rounded-lg border-2 border-white bg-surface-sunken text-[10px] font-semibold text-text-subtle shadow-sm"
                        :title="article.name"
                    >
                        <img
                            v-if="article.images?.[0]?.image"
                            :src="'/storage/' + (article.images[0].thumbnail || article.images[0].image)"
                            :alt="article.images[0].alt || article.name"
                            loading="lazy"
                            class="size-full object-cover"
                        >
                        <span v-else aria-hidden="true">{{ article.name?.slice(0, 2).toUpperCase() }}</span>
                    </div>
                </div>
                <span class="text-[11px] text-text-subtle">
                    {{ issueOfMaterial.articles.length }} {{ issueOfMaterial.articles.length === 1 ? $t('article') : $t('articles') }}
                </span>
            </div>
        </div>

        <!-- Zeitraum -->
        <div class="col-span-2 text-xs cursor-pointer text-text-muted" @click="showIssueOfMaterialDetailModal = true">
            {{ issueOfMaterial.start_date_time }} – {{ issueOfMaterial.end_date_time }}
        </div>

        <!-- Raum -->
        <div class="col-span-1 text-xs text-text-muted">
            {{ issueOfMaterial.room?.name ?? '—' }}
        </div>

        <!-- Projekt -->
        <div class="col-span-2 text-xs text-text-muted">
            {{ issueOfMaterial.project?.name ?? '—' }}
        </div>

        <!-- Files -->
        <div class="col-span-1 text-xs">
      <span v-if="issueOfMaterial.files?.length"
            class="inline-flex items-center rounded-md bg-info-surface px-1.5 py-0.5 text-info border border-info-border text-[11px]">
        {{ issueOfMaterial.files.length }}
      </span>
            <span v-else class="text-text-subtle">{{ $t('No Files') }}</span>
        </div>

        <!-- Responsible -->
        <div class="col-span-2">
            <div class="flex -space-x-2 items-center">
                <UserPopoverTooltip
                    v-for="user in issueOfMaterial.responsible_users"
                    :key="user.id"
                    :user="user"
                    width="8"
                    height="8"
                    classes="border-2 border-white rounded-full ring-1 ring-accent-200"
                />
            </div>
        </div>

        <!-- Status + Menü -->
        <div class="col-span-1 flex items-center justify-end gap-2">
      <span v-if="!issueOfMaterial?.special_items_done && issueOfMaterial?.special_items?.length"
            class="inline-flex items-center gap-1 rounded-md border border-danger-border bg-danger-surface px-1.5 py-0.5 text-[11px] text-danger">
        <component :is="IconAlertTriangle" class="size-3.5" />
        {{ $t('Status Open') }}
      </span>
            <span v-else class="inline-flex items-center gap-1 rounded-md border border-success-border bg-success-surface px-1.5 py-0.5 text-[11px] text-success">
        <component :is="IconCheck" class="size-3.5" />
        {{ $t('Completed') }}
      </span>

            <div class="flex items-center gap-2">
                <button class="cursor-pointer" :title="$t('Print')" @click="printInternal()">
                    <component :is="IconPrinter" class="size-5" stroke-width="1.5" />
                </button>

                <BaseMenu white-menu-background has-no-offset menu-width="w-fit">
                    <BaseMenuItem white-menu-background :title="$t('Edit')" :icon="IconEdit" @click="showIssueOfMaterialModal = true" />
                    <BaseMenuItem
                        white-menu-background
                        :title="$t('Special items closed')"
                        :icon="IconCheck"
                        @click="setSpecialItemsDone"
                        v-if="checkIfStatusOrHasAnySpecialItem"
                    />
                    <BaseMenuItem white-menu-background :title="$t('Delete')" :icon="IconTrash" @click="showIssueOfMaterialConfirmDeleteModal = true" />
                </BaseMenu>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <issue-of-material-modal
        v-if="showIssueOfMaterialModal"
        @close="showIssueOfMaterialModal = false"
        :issue-of-material="issueOfMaterial"
    />

    <!-- Confirm Delete -->
    <ConfirmDeleteModal
        :title="$t('Delete issue of material')"
        :description="$t('Are you sure you want to delete this issue of material?')"
        v-if="showIssueOfMaterialConfirmDeleteModal"
        @closed="showIssueOfMaterialConfirmDeleteModal = false"
        @delete="deleteIssue"
    />

    <!-- Detail Modal -->
    <DetailModalInternMaterialModal
        :issue="issueOfMaterial"
        v-if="showIssueOfMaterialDetailModal"
        @close="showIssueOfMaterialDetailModal = false"
        :detailed-article="detailedArticle"
    />
</template>

<script setup>
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import DetailModalInternMaterialModal from "@/Pages/IssueOfMaterial/Components/DetailModalInternMaterialModal.vue";
import { IconAlertTriangle, IconCheck, IconEdit, IconPrinter, IconTrash } from "@tabler/icons-vue";
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const props = defineProps({
    issueOfMaterial: {
        type: Object,
        default: () => ({
            id: null,
            name: '',
            project_id: null,
            project: null,
            start_date: '',
            start_time: '00:00',
            end_date: '',
            end_time: '23:59',
            room_id: null,
            notes: '',
            responsible_user_ids: [],
            special_items_done: false,
            files: [],
            articles: [],
            special_items: []
        })
    },
    detailedArticle: { type: Object, default: () => ({}) }
});

const showIssueOfMaterialModal = ref(false);
const showIssueOfMaterialConfirmDeleteModal = ref(false);
const showIssueOfMaterialDetailModal = ref(false);

const deleteIssue = () => {
    router.delete(route('issue-of-material.destroy', props.issueOfMaterial.id), {
        onSuccess: () => { showIssueOfMaterialConfirmDeleteModal.value = false; },
        onError: () => { showIssueOfMaterialConfirmDeleteModal.value = false; }
    });
};

const setSpecialItemsDone = () => {
    router.post(route('issue-of-material.set-special-items-done', props.issueOfMaterial.id), {
        preserveState: true, preserveScroll: true
    });
};

const printInternal = () => {
    window.open(route('issue-of-material.print', props.issueOfMaterial.id), '_blank');
};

const checkIfStatusOrHasAnySpecialItem = computed(() => {
    const hasSpecialItems = props.issueOfMaterial?.special_items?.length > 0;
    const isIncomplete = !props.issueOfMaterial?.special_items_done;
    return hasSpecialItems && isIncomplete;
});
</script>

<style scoped>
/* only utilities used */
</style>
