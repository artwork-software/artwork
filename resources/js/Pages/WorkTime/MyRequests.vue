<template>
    <AppLayout>

        <div class="container mx-auto max-w-6xl px-4 py-6 space-y-6">
            <WorkTimeTabComponent />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="request in requests"
                    :key="request.id"
                    class="card white p-6"
                >
                    <SingleWorkTimeChangeRequest :request="request" :need-approval="false" />
                </div>
            </div>
        </div>


    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import WorkTimeTabComponent from "@/Pages/WorkTime/Components/WorkTimeTabComponent.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import SingleWorkTimeChangeRequest from "@/Pages/WorkTime/Components/SingleWorkTimeChangeRequest.vue";

const props = defineProps({
    requests: {
        type: Object,
        required: true
    }
})
</script>

<style scoped>

</style>