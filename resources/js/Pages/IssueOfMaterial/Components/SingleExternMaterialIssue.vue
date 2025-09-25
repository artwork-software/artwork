<template>
    <div
        class="group grid grid-cols-12 gap-4 px-2 py-3 text-sm border-b border-gray-100 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white relative"
        :class="{ 'bg-yellow-50': usePage().props.urlParameters?.issue === String(externMaterialIssue.id) }"
    >
        <span class="pointer-events-none absolute left-0 top-0 h-full w-0.5 bg-indigo-400/0 transition-all duration-200 group-hover:bg-indigo-400/70"></span>

        <!-- Name -->
        <div class="col-span-3">
            <button class="text-left text-indigo-600 hover:text-indigo-700 hover:underline underline-offset-2"
                    @click="showIssueOfMaterialModal = true">
                {{ externMaterialIssue.name }}
            </button>
            <p v-if="externMaterialIssue.return_remarks" class="text-xs text-gray-500 line-clamp-2 mt-0.5">
                {{ externMaterialIssue.return_remarks }}
            </p>
        </div>

        <!-- Materialwert -->
        <div class="col-span-1 text-xs text-gray-700">
            {{ numberFmt(externMaterialIssue.material_value) }}
        </div>

        <!-- Zeitraum -->
        <div class="col-span-2 text-xs text-gray-700 cursor-pointer" @click="showIssueOfMaterialDetailModal = true">
            {{ externMaterialIssue.issue_date_formatted }} – {{ externMaterialIssue.return_date_formatted }}
        </div>

        <!-- Issued by -->
        <div class="col-span-1">
            <UserPopoverTooltip :user="externMaterialIssue.issued_by" width="8" height="8" classes="border-2 border-white rounded-full ring-1 ring-indigo-100" />
        </div>

        <!-- External name -->
        <div class="col-span-2 text-xs text-gray-700">
            {{ externMaterialIssue.external_name || '—' }}
        </div>

        <!-- Files -->
        <div class="col-span-1 text-xs">
      <span v-if="externMaterialIssue.files?.length"
            class="inline-flex items-center rounded-md bg-sky-50 px-1.5 py-0.5 text-sky-700 border border-sky-200 text-[11px]">
        {{ externMaterialIssue.files.length }}
      </span>
            <span v-else class="text-gray-400">{{ $t('No Files') }}</span>
        </div>

        <!-- Received by -->
        <div class="col-span-1">
            <UserPopoverTooltip v-if="externMaterialIssue.received_by" :user="externMaterialIssue.received_by" width="8" height="8" classes="border-2 border-white rounded-full ring-1 ring-emerald-100" />
            <span v-else class="text-[11px] text-gray-400">—</span>
        </div>

        <!-- Status + Aktionen -->
        <div class="col-span-1 flex items-center justify-end gap-2">
      <span v-if="!externMaterialIssue?.special_items_done && externMaterialIssue?.special_items?.length"
            class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-1.5 py-0.5 text-[11px] text-rose-700">
        <component :is="IconAlertTriangle" class="size-3.5" />
        {{ $t('Open') }}
      </span>
            <span v-else class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[11px] text-emerald-700">
        <component :is="IconCheck" class="size-3.5" />
        {{ $t('Completed') }}
      </span>

            <div class="flex items-center gap-2">
                <button class="cursor-pointer" :title="$t('Print')" @click="printExternal()">
                    <component :is="IconPrinter" class="size-5" stroke-width="1.5" />
                </button>

                <BaseMenu white-menu-background has-no-offset menu-width="w-fit">
                    <BaseMenuItem white-menu-background :title="$t('Edit')" :icon="IconEdit" @click="showIssueOfMaterialModal = true" />
                    <BaseMenuItem white-menu-background :title="$t('Enter return')" :icon="IconReceiptRefund" @click="showEnterExternalIssueReturnModal = true" v-if="!externMaterialIssue.received_by" />
                    <BaseMenuItem white-menu-background :title="$t('Special items closed')" :icon="IconCheck" @click="setSpecialItemsDone" v-if="checkIfStatusOrHasAnySpecialItem" />
                    <BaseMenuItem white-menu-background :title="$t('Delete')" :icon="IconTrash" @click="showIssueOfMaterialConfirmDeleteModal = true" />
                </BaseMenu>
            </div>
        </div>
    </div>

    <!-- Edit -->
    <issue-of-material-modal
        v-if="showIssueOfMaterialModal"
        @close="showIssueOfMaterialModal = false"
        :extern-material-issue="externMaterialIssue"
        :is-extern-or-intern="true"
    />

    <!-- Return -->
    <EnterExternalIssueReturnModal
        v-if="showEnterExternalIssueReturnModal"
        @close="showEnterExternalIssueReturnModal = false"
        :external-issue="externMaterialIssue"
    />

    <!-- Detail -->
    <ExternalMaterialIssueDetailModal
        :issue="externMaterialIssue"
        v-if="showIssueOfMaterialDetailModal"
        @close="showIssueOfMaterialDetailModal = false"
        :detailed-article="detailedArticle"
    />

    <!-- Delete -->
    <ConfirmDeleteModal
        :title="$t('Delete issue of material')"
        :description="$t('Are you sure you want to delete this issue of material?')"
        v-if="showIssueOfMaterialConfirmDeleteModal"
        @closed="showIssueOfMaterialConfirmDeleteModal = false"
        @delete="deleteIssue"
    />
</template>

<script setup>
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import EnterExternalIssueReturnModal from "@/Pages/IssueOfMaterial/Components/EnterExternalIssueReturnModal.vue";
import ExternalMaterialIssueDetailModal from "@/Pages/IssueOfMaterial/Components/ExternalMaterialIssueDetailModal.vue";
import { IconAlertTriangle, IconCheck, IconEdit, IconPrinter, IconReceiptRefund, IconTrash } from "@tabler/icons-vue";
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const props = defineProps({
    externMaterialIssue: {
        type: Object,
        default: () => ({
            id: null,
            material_value: 0.0,
            name: '',
            issue_date: '',
            return_date: '',
            issue_date_formatted: '',
            return_date_formatted: '',
            return_remarks: '',
            external_name: '',
            files: [],
            articles: [],
            special_items: [],
            special_items_done: false,
            issued_by: null,
            received_by: null
        })
    },
    detailedArticle: Object
});

const showIssueOfMaterialModal = ref(false);
const showIssueOfMaterialConfirmDeleteModal = ref(false);
const showEnterExternalIssueReturnModal = ref(false);
const showIssueOfMaterialDetailModal = ref(false);

const numberFmt = (v) => {
    try { return new Intl.NumberFormat(usePage().props.locale, { style: 'currency', currency: usePage().props.currency || 'EUR' }).format(Number(v||0)); }
    catch { return Number(v||0).toFixed(2); }
}

const deleteIssue = () => {
    router.delete(route('extern-issue-of-material.destroy', props.externMaterialIssue.id), {
        preserveState: true, preserveScroll: true,
        onSuccess: () => { showIssueOfMaterialModal.value = false; }
    });
};

const setSpecialItemsDone = () => {
    router.post(route('extern-issue-of-material.set-special-items-done', props.externMaterialIssue.id), {
        preserveState: true, preserveScroll: true
    });
};

const checkIfStatusOrHasAnySpecialItem = computed(() => {
    const hasSpecialItems = props.externMaterialIssue?.special_items?.length > 0;
    const isIncomplete = !props.externMaterialIssue?.special_items_done;
    return hasSpecialItems && isIncomplete;
});

const printExternal = () => {
    window.open(route('extern-issue-of-material.print', props.externMaterialIssue.id), '_blank');
};
</script>

<style scoped>
/* utilities only */
</style>
