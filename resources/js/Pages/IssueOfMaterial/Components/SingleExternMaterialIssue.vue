<template>
    <div class="p-4 font-lexend" :class="{ 'border-2 border-pink-500 rounded-lg': usePage().props.urlParameters.issue === externMaterialIssue.id.toString() }">
        <div class="grid grid-cols-1 md:grid-cols-10 gap-4">
            <div @click="showIssueOfMaterialModal = true" class="flex items-center w-full cursor-pointer group-hover/issueOfMaterial:text-artwork-buttons-create text-sm">
                {{externMaterialIssue.name}}
            </div>
            <div @click="showIssueOfMaterialDetailModal = true" class="flex items-center w-full cursor-pointer group-hover/issueOfMaterial:text-artwork-buttons-create text-sm">
                {{externMaterialIssue.material_value}}
            </div>
            <div class="text-xs flex items-center ">
                {{externMaterialIssue.issue_date_formatted }} - {{externMaterialIssue.return_date_formatted}}
            </div>
            <div class="flex items-center text-xs">
                <UserPopoverTooltip :user="externMaterialIssue.issued_by" width="8" height="8" classes="border-2 border-white rounded-full" />
            </div>
            <div class="flex items-center text-xs">
                {{externMaterialIssue.external_name }}
            </div>
            <div class="flex items-center text-xs">
                {{ externMaterialIssue.files.length > 0 ? externMaterialIssue.files.length : $t('No Files') }}
            </div>
            <div class="flex items-center text-xs">
                <div class="line-clamp-2">
                    {{ externMaterialIssue.return_remarks }}
                </div>
            </div>
            <div class="flex items-center text-xs">
                <UserPopoverTooltip v-if="externMaterialIssue.received_by" :user="externMaterialIssue.received_by" width="8" height="8" classes="border-2 border-white rounded-full" />
            </div>
            <div class="flex items-center text-xs">
                <div class="">
                    <div v-if="!externMaterialIssue?.special_items_done && externMaterialIssue?.special_items?.length > 0">
                        <span class="text-red-500 font-bold">
                            <component :is="IconAlertTriangle" class="size-4 inline-block mr-1" />
                            {{ $t('Special items not completed') }}
                        </span>
                    </div>
                    <div v-else>
                        <span class="text-green-500 font-bold">
                            <component :is="IconCheck" class="size-4 inline-block mr-1" />
                            {{ $t('Completed') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end !w-full gap-x-3 text-end">
                <div class="cursor-pointer">
                    <component :is="IconPrinter" class="size-5 mr-2" stroke-width="1.5" @click="printExternal()" />
                </div>
                <BaseMenu white-menu-background has-no-offset menu-width="w-fit">
                    <BaseMenuItem white-menu-background title="Edit" :icon="IconEdit" @click="showIssueOfMaterialModal = true" />
                    <BaseMenuItem white-menu-background title="Enter return" :icon="IconReceiptRefund" @click="showEnterExternalIssueReturnModal = true" v-if="!externMaterialIssue.received_by" />
                    <BaseMenuItem white-menu-background title="Special items closed" :icon="IconCheck" @click="setSpecialItemsDone" v-if="checkIfStatusOrHasAnySpecialItem" />
                    <BaseMenuItem white-menu-background title="Delete" :icon="IconTrash" @click="showIssueOfMaterialConfirmDeleteModal = true" />
                </BaseMenu>
            </div>
        </div>


    </div>

    <issue-of-material-modal
        v-if="showIssueOfMaterialModal"
        @close="showIssueOfMaterialModal = false"
        :extern-material-issue="externMaterialIssue"
        :is-extern-or-intern="true"
    />

    <ConfirmDeleteModal
        :title="$t('Delete issue of material')"
        :description="$t('Are you sure you want to delete this issue of material?')"
        v-if="showIssueOfMaterialConfirmDeleteModal"
        @closed="showIssueOfMaterialConfirmDeleteModal = false"
        @delete="deleteIssue"
    />

    <EnterExternalIssueReturnModal
        v-if="showEnterExternalIssueReturnModal"
        @close="showEnterExternalIssueReturnModal = false"
        :external-issue="externMaterialIssue"
        />

    <ExternalMaterialIssueDetailModal :issue="externMaterialIssue" @close="showIssueOfMaterialDetailModal = false" v-if="showIssueOfMaterialDetailModal" :detailed-article="detailedArticle" />
</template>

<script setup>

import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import {computed, ref} from "vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router, usePage} from "@inertiajs/vue3";
import EnterExternalIssueReturnModal from "@/Pages/IssueOfMaterial/Components/EnterExternalIssueReturnModal.vue";
import ExternalMaterialIssueDetailModal from "@/Pages/IssueOfMaterial/Components/ExternalMaterialIssueDetailModal.vue";
import {IconAlertTriangle, IconCheck, IconEdit, IconPrinter, IconReceiptRefund, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    externMaterialIssue: {
        type: Object,
        required: false,
        default: () => ({
            material_value: 0.00,
            name: '',
            issue_date: '',
            return_date: '',
            return_remarks: '',
            external_name: '',
            external_address: '',
            external_email: '',
            external_phone: '',
            files: [],
            articles: [],
            special_items: [],
        })
    },
    detailedArticle: {
        type: Object,
        required: false
    }
})

const showIssueOfMaterialModal = ref(false);
const showIssueOfMaterialConfirmDeleteModal = ref(false);
const showEnterExternalIssueReturnModal = ref(false);
const showIssueOfMaterialDetailModal = ref(false);


const deleteIssue = () => {
    router.delete(route('extern-issue-of-material.destroy', props.externMaterialIssue.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showIssueOfMaterialModal.value = false;
        }
    });
}

const setSpecialItemsDone = () => {
    router.post(route('extern-issue-of-material.set-special-items-done', props.externMaterialIssue.id), {
        preserveState: true,
        preserveScroll: true
    });
}

const checkIfStatusOrHasAnySpecialItem = computed(() => {
    const hasSpecialItems = props.externMaterialIssue?.special_items?.length > 0;
    const isIncomplete = !props.externMaterialIssue?.special_items_done;

    return hasSpecialItems && isIncomplete;
});

const printExternal = () => {
    window.open(route('extern-issue-of-material.print', props.externMaterialIssue.id), '_blank');
}
</script>

<style scoped>

</style>
