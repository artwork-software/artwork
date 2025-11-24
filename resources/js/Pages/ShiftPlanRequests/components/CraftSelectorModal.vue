<template>
    <ArtworkBaseModal :title="$t('Select craft')" :description="$t('Select any Craft to show changes in shifts')">
        <ArtworkBaseListbox
            v-model="selectedCraft"
            :items="allCrafts"
            by="id"
            option-label="name"
            option-key="id"
            label="Craft"
            :use-translations="false"
            :show-color-indicator="true"
            color-property="color"
            @change="reloadPageWithCraftId"
        />

    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {router, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";

const allCrafts = ref(usePage().props.allCrafts);
const selectedCraft = ref(null);

const reloadPageWithCraftId = () => {
    if (selectedCraft.value) {
        router.visit(route('shifts.approvals.changes-craft', {craft: selectedCraft.value.id}), {
            preserveState: false,
            preserveScroll: true,
        });
    }
}

</script>
<style scoped>

</style>
