<template>
    <!-- Zeile -->
    <div
        class="group grid grid-cols-12 gap-4 px-2 py-3 text-sm border-b border-gray-100 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white relative"
        :class="{ 'bg-yellow-50': usePage().props.urlParameters?.issue === String(issueOfMaterial.id) }"
    >
        <!-- Linke Akzent-Leiste bei Hover -->
        <span class="pointer-events-none absolute left-0 top-0 h-full w-0.5 bg-indigo-400/0 transition-all duration-200 group-hover:bg-indigo-400/70"></span>

        <!-- Name + Notes -->
        <div class="col-span-3">
            <button class="text-left text-indigo-600 hover:text-indigo-700 hover:underline underline-offset-2" @click="showIssueOfMaterialModal = true">
                {{ issueOfMaterial.name }}
            </button>
            <p v-if="issueOfMaterial.notes" class="text-xs text-gray-500 line-clamp-2 mt-0.5">
                {{ issueOfMaterial.notes }}
            </p>
        </div>

        <!-- Zeitraum -->
        <div class="col-span-2 text-xs cursor-pointer text-gray-700" @click="showIssueOfMaterialDetailModal = true">
            {{ issueOfMaterial.start_date_time }} – {{ issueOfMaterial.end_date_time }}
        </div>

        <!-- Raum -->
        <div class="col-span-1 text-xs text-gray-700">
            {{ issueOfMaterial.room?.name ?? '—' }}
        </div>

        <!-- Projekt -->
        <div class="col-span-2 text-xs text-gray-700">
            {{ issueOfMaterial.project?.name ?? '—' }}
        </div>

        <!-- Files -->
        <div class="col-span-1 text-xs">
      <span v-if="issueOfMaterial.files?.length"
            class="inline-flex items-center rounded-md bg-sky-50 px-1.5 py-0.5 text-sky-700 border border-sky-200 text-[11px]">
        {{ issueOfMaterial.files.length }}
      </span>
            <span v-else class="text-gray-400">{{ $t('No Files') }}</span>
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
                    classes="border-2 border-white rounded-full ring-1 ring-indigo-100"
                />
            </div>
        </div>

        <!-- Status + Menü -->
        <div class="col-span-1 flex items-center justify-end gap-2">
      <span v-if="!issueOfMaterial?.special_items_done && issueOfMaterial?.special_items?.length"
            class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-1.5 py-0.5 text-[11px] text-rose-700">
        <component :is="IconAlertTriangle" class="size-3.5" />
        {{ $t('Open') }}
      </span>
            <span v-else class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[11px] text-emerald-700">
        <component :is="IconCheck" class="size-3.5" />
        {{ $t('Completed') }}
      </span>

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
import { IconAlertTriangle, IconCheck, IconEdit, IconTrash } from "@tabler/icons-vue";
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

const checkIfStatusOrHasAnySpecialItem = computed(() => {
    const hasSpecialItems = props.issueOfMaterial?.special_items?.length > 0;
    const isIncomplete = !props.issueOfMaterial?.special_items_done;
    return hasSpecialItems && isIncomplete;
});
</script>

<style scoped>
/* only utilities used */
</style>
