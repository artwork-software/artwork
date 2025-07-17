<template>
    <AppLayout title="Antragsübersicht">
        <div class="max-w-4xl mx-auto py-8 font-lexend">
            <h1 class="text-3xl font-semibold text-gray-900 mb-8">Anträge</h1>

            <div v-if="requests.length === 0" class="text-gray-500 text-center">
                Keine Anträge vorhanden.
            </div>

            <div
                v-for="request in requests"
                :key="request.id"
                class="card white p-5 mb-5"
            >
               <SingleCommitRequest :request="request" />
            </div>
        </div>
    </AppLayout>
</template>


<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {router} from "@inertiajs/vue3";
import SingleCommitRequest from "@/Pages/Shifts/ShiftCommitWorkflowRequests/Components/SingleCommitRequest.vue";

const props = defineProps({
    requests: {
        type: Object,
        required: true
    }
})



</script>

<style scoped>

</style>
