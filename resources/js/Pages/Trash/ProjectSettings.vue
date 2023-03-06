<template>
    <div v-if="trashed_genres.length > 0" v-for="(genre,index) in trashed_genres" :key="genre.id"
         class="mt-5 border-b-2 border-gray-200 w-full">
        <div
            class="py-5 flex justify-between">
            <div>{{genre.name}}</div>
        </div>
    </div>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import TrashLayout from "@/Layouts/TrashLayout";

export default {
    layout: [AppLayout, TrashLayout],
};
</script>

<script setup>

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
