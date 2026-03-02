<template>
    <div class="flex w-full justify-between">
        <div></div>
        <div class="flex justify-end items-center ml-8 -mt-14">
            <button v-if="hasAnyTrashedItems" @click="showConfirmDeleteAll = true"
                    class="cursor-pointer text-red-500 hover:text-red-700 mr-3">
                <TrashIcon class="h-5 w-5" aria-hidden="true"/>
            </button>
        </div>
    </div>
    <TrashItems :items="props.trashed_genres" :type="$t('Project category - Genre')" model="genres" />
    <TrashItems :items="props.trashed_categories" :type="$t('Project category - Category')" model="categories" />
    <TrashItems :items="props.trashed_sectors" :type="$t('Project category - Area')" model="sectors" />
    <TrashItems :items="props.trashed_project_states" :type="$t('Project category - Project status')" model="state" />
    <TrashItems :items="props.trashed_contract_types" :type="$t('Project category - Contract type')" model="contract_types" />
    <TrashItems :items="props.trashed_company_types" :type="$t('Project contracts - corporate form')" model="company_types" />
    <TrashItems :items="props.trashed_currencies" :type="$t('Project contracts - Currency')" model="currencies" />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";

export default {
    layout: [AppLayout, TrashLayout],
};
</script>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import TrashItems from "@/Layouts/Components/TrashItems.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {TrashIcon} from "@heroicons/vue/outline";

const props = defineProps({
    trashed_genres: Array,
    trashed_categories: Array,
    trashed_sectors: Array,
    trashed_project_states: Array,
    trashed_contract_types: Array,
    trashed_company_types: Array,
    trashed_currencies: Array,
    //trashed_project_headlines: Array
})

const showConfirmDeleteAll = ref(false);

const hasAnyTrashedItems = computed(() => {
    return (props.trashed_genres?.length > 0) ||
        (props.trashed_categories?.length > 0) ||
        (props.trashed_sectors?.length > 0) ||
        (props.trashed_project_states?.length > 0) ||
        (props.trashed_contract_types?.length > 0) ||
        (props.trashed_company_types?.length > 0) ||
        (props.trashed_currencies?.length > 0);
});

function forceDeleteAll() {
    router.delete(route('projects.settings.force.all'), {
        onSuccess: () => {
            showConfirmDeleteAll.value = false;
        }
    });
}

</script>

<style scoped>

</style>
