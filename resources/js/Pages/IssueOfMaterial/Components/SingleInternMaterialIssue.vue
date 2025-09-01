<template>
    <div class="p-4 font-lexend" :class="{ 'border-2 border-pink-500 rounded-lg': usePage().props.urlParameters.issue === issueOfMaterial.id.toString() }">
        <div class="grid grid-cols-1 md:grid-cols-9 gap-4">
            <div @click="showIssueOfMaterialDetailModal = true" class="flex items-center w-full cursor-pointer group-hover/issueOfMaterial:text-artwork-buttons-create text-sm">
                {{issueOfMaterial.name}}
            </div>
            <div class="text-xs flex items-center ">
                {{issueOfMaterial.start_date_time }} - {{issueOfMaterial.end_date_time}}
            </div>
            <div class="flex items-center text-xs">
                {{issueOfMaterial.room?.name }}
            </div>
            <div class="flex items-center text-xs">
                {{issueOfMaterial.project?.name }}
            </div>
            <div class="flex items-center text-xs">
                {{ issueOfMaterial.files.length > 0 ? issueOfMaterial.files.length : $t('No Files') }}
            </div>
            <div class="flex items-center text-xs">
                <div class="line-clamp-2">
                    {{issueOfMaterial.notes }}
                </div>

            </div>
            <div class="flex items-center text-xs">
                <div class="flex items-center print:hidden">
                    <div class="flex -space-x-2 overflow-hidden items-center">
                        <UserPopoverTooltip v-for="user in issueOfMaterial.responsible_users" :key="user.id" :user="user" width="8" height="8" classes="border-2 border-white rounded-full" />
                    </div>
                </div>
            </div>
            <div class="flex items-center text-xs">
                <div class="">
                    <div v-if="!issueOfMaterial?.special_items_done && issueOfMaterial?.special_items?.length > 0">
                        <span class="text-red-500 font-bold">
                            <component is="IconAlertTriangle" class="size-4 inline-block mr-1" />
                            {{ $t('Special items not completed') }}
                        </span>
                    </div>
                    <div v-else>
                        <span class="text-green-500 font-bold">
                            <component is="IconCheck" class="size-4 inline-block mr-1" />
                            {{ $t('Completed') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end w-full">
                <BaseMenu white-menu-background has-no-offset menu-width="w-fit">
                    <BaseMenuItem white-menu-background title="Edit" @click="showIssueOfMaterialModal = true" />
                    <BaseMenuItem white-menu-background title="Special items closed" icon="IconCheck" @click="setSpecialItemsDone" v-if="checkIfStatusOrHasAnySpecialItem" />
                    <BaseMenuItem white-menu-background title="Delete" icon="IconTrash" @click="showIssueOfMaterialConfirmDeleteModal = true" />
                </BaseMenu>
            </div>
        </div>

    </div>

    <issue-of-material-modal
        v-if="showIssueOfMaterialModal"
        @close="showIssueOfMaterialModal = false"
        :issue-of-material="issueOfMaterial"
    />

    <ConfirmDeleteModal
        :title="$t('Delete issue of material')"
        :description="$t('Are you sure you want to delete this issue of material?')"
        v-if="showIssueOfMaterialConfirmDeleteModal"
        @closed="showIssueOfMaterialConfirmDeleteModal = false"
        @delete="deleteIssue"
    />

    <DetailModalInternMaterialModal :issue="issueOfMaterial" v-if="showIssueOfMaterialDetailModal" @close="showIssueOfMaterialDetailModal = false" :detailed-article="detailedArticle" />
</template>

<script setup>

import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import {computed, ref} from "vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router, usePage} from "@inertiajs/vue3";
import DetailModalInternMaterialModal from "@/Pages/IssueOfMaterial/Components/DetailModalInternMaterialModal.vue";

const props = defineProps({
    issueOfMaterial: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            project_id: null,
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
    detailedArticle: {
        type: Object,
        required: false,
        default: () => ({})
    }
})

const showIssueOfMaterialModal = ref(false);
const showIssueOfMaterialConfirmDeleteModal = ref(false);
const showIssueOfMaterialDetailModal = ref(false);


const deleteIssue = () => {
    router.delete(route('issue-of-material.destroy', props.issueOfMaterial.id), {
        onSuccess: () => {
            showIssueOfMaterialConfirmDeleteModal.value = false;
        },
        onError: () => {
            showIssueOfMaterialConfirmDeleteModal.value = false;
        }
    });
}

const setSpecialItemsDone = () => {
    router.post(route('issue-of-material.set-special-items-done', props.issueOfMaterial.id), {
        preserveState: true,
        preserveScroll: true
    });
}

const checkIfStatusOrHasAnySpecialItem = computed(() => {
    const hasSpecialItems = props.issueOfMaterial?.special_items?.length > 0;
    const isIncomplete = !props.issueOfMaterial?.special_items_done;

    return hasSpecialItems && isIncomplete;
});

</script>

<style scoped>

</style>