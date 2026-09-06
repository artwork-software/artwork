<template>
    <AppLayout :title="$t('Work time change requests')">

        <div class="container mx-auto max-w-6xl px-4 py-6 space-y-6">
            <WorkTimeTabComponent />

            <div
                v-if="!requestList.length"
                class="rounded-2xl border border-dashed border-border-subtle bg-surface p-8 text-center"
            >
                <p class="text-sm text-text-subtle">{{ $t('You have not submitted any work time change requests yet.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="request in requestList"
                    :key="request.id"
                    class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-6 relative"
                >
                    <div v-if="request.status === 'pending'" class="absolute top-4 right-4">
                        <ToolTipComponent
                            direction="left"
                            :tooltip-text="$t('Delete request')"
                            icon="IconTrash"
                            icon-size="h-5 w-5 text-danger"
                            classes-button="p-2 hover:bg-surface-sunken rounded-lg transition-colors"
                            @click="askDelete(request)"
                        />
                    </div>
                    <SingleWorkTimeChangeRequest :request="request" :need-approval="false" />
                </div>
            </div>
        </div>

        <!-- Bestätigung statt window.confirm -->
        <ArtworkBaseDeleteModal
            v-if="requestToDelete"
            :title="$t('Delete request')"
            :description="$t('Do you really want to delete this request?')"
            @close="requestToDelete = null"
            @delete="deleteRequest"
        />

        <NotificationToast
            v-if="toast"
            v-model:show="toastVisible"
            :title="toast.title"
            :description="toast.description"
            :type="toast.type"
        />
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import WorkTimeTabComponent from "@/Pages/WorkTime/Components/WorkTimeTabComponent.vue";
import SingleWorkTimeChangeRequest from "@/Pages/WorkTime/Components/SingleWorkTimeChangeRequest.vue";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import NotificationToast from "@/Artwork/Feedback/NotificationToast.vue";
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    requests: {
        type: [Object, Array],
        required: true
    }
})

// Payload kann Array oder Objekt (id => request) sein
const requestList = computed(() => Array.isArray(props.requests) ? props.requests : Object.values(props.requests ?? {}));

const requestToDelete = ref(null);
const toast = ref(null);
const toastVisible = ref(false);

const askDelete = (request) => {
    requestToDelete.value = request;
};

const deleteRequest = () => {
    const request = requestToDelete.value;
    if (!request) return;
    router.delete(route('worktime.change-request.destroy', { workTimeChangeRequest: request.id }), {
        preserveScroll: true,
        onSuccess: () => {
            requestToDelete.value = null;
            toast.value = { title: 'Request deleted', description: '', type: 'success' };
            toastVisible.value = true;
        },
        onError: (error) => {
            console.error('Error deleting request:', error);
            requestToDelete.value = null;
            toast.value = { title: 'The request could not be deleted.', description: '', type: 'error' };
            toastVisible.value = true;
        }
    });
}
</script>

<style scoped>

</style>
