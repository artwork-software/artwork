<template>
    <TrashItems :items="props.trashed_genres" :type="$t('Project category - Genre')" model="genres" />
    <TrashItems :items="props.trashed_categories" :type="$t('Project category - Category')" model="categories" />
    <TrashItems :items="props.trashed_sectors" :type="$t('Project category - Area')" model="sectors" />
    <TrashItems :items="props.trashed_project_states" :type="$t('Project category - Project status')" model="state" />
    <TrashItems :items="props.trashed_contract_types" :type="$t('Project category - Contract type')" model="contract_types" />
    <TrashItems :items="props.trashed_company_types" :type="$t('Project contracts - corporate form')" model="company_types" />
    <TrashItems :items="props.trashed_collecting_societies" :type="$t('Project contracts - collecting society')" model="collecting_societies" />
    <TrashItems :items="props.trashed_currencies" :type="$t('Project contracts - Currency')" model="currencies" />
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";

export default {
    layout: [AppLayout, TrashLayout],
};
</script>

<script setup>

import TrashItems from "@/Layouts/Components/TrashItems.vue";

const props = defineProps({
    trashed_genres: Array,
    trashed_categories: Array,
    trashed_sectors: Array,
    trashed_project_states: Array,
    trashed_contract_types: Array,
    trashed_company_types: Array,
    trashed_collecting_societies: Array,
    trashed_currencies: Array,
    //trashed_project_headlines: Array
})

</script>

<style scoped>

</style>
