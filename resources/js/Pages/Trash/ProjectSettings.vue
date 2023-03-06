<template>
    <TrashItems :items="props.trashed_genres" type="Projektkategorie - Genre" model="genres" />
    <TrashItems :items="props.trashed_categories" type="Projektkategorie - Kategorie" model="categories" />
    <TrashItems :items="props.trashed_sectors" type="Projektkategorie - Bereich" model="sectors" />
    <TrashItems :items="props.trashed_project_states" type="Projektkategorie - Projektstatus" model="state" />
    <TrashItems :items="props.trashed_contract_types" type="Projektkategorie - Vertragsart" model="contract_types" />
    <TrashItems :items="props.trashed_company_types" type="Projektverträge - Unternehmensform" model="company_types" />
    <TrashItems :items="props.trashed_collecting_societies" type="Projektverträge - Verwertungsgesellschaft" model="collecting_societies" />
    <TrashItems :items="props.trashed_currencies" type="Projektverträge - Währung" model="currencies" />
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import TrashLayout from "@/Layouts/TrashLayout";

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
