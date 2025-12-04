<template>
    <AppLayout>

        <div class="container mx-auto max-w-6xl px-4 py-6 space-y-6">
            <WorkTimeTabComponent />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="request in requests"
                    :key="request.id"
                    class="card white p-6 relative"
                >
                    <button
                        v-if="request.status === 'pending'"
                        @click="deleteRequest(request.id)"
                        class="absolute top-4 right-4 p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        :title="$t('Delete request')"
                    >
                        <IconTrash class="h-5 w-5 text-red-600" />
                    </button>
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
import { IconTrash } from "@tabler/icons-vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    requests: {
        type: Object,
        required: true
    }
})

const deleteRequest = (requestId) => {
    if (confirm('Möchten Sie diese Anfrage wirklich löschen?')) {
        router.delete(route('worktime.change-request.destroy', { workTimeChangeRequest: requestId }), {
            preserveScroll: true,
            onSuccess: () => {
                // Request successfully deleted
            },
            onError: (error) => {
                console.error('Error deleting request:', error);
            }
        });
    }
}
</script>

<style scoped>

</style>
