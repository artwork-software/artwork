<template>
    <ArtworkBaseModal
        title="Project status change"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <p class="text-sm text-text">
            {{ directBookingOnly
                ? $t('The new project status is no longer marked as planned. Do you want to confirm all planning events of the project as fixed events?')
                : $t('The new project status is no longer marked as planned. Do you want to request verification for all planning events of the project?') }}
        </p>
        <template #footer>
            <BaseUIButton
                label="No"
                is-cancel-button
                hide-icon
                :disabled="requestingVerification"
                @click="closeModal"
            />
            <BaseUIButton
                :label="directBookingOnly ? 'Confirm as fixed events' : 'Request appointments verification'"
                variant="primary"
                icon="IconCheck"
                :processing="requestingVerification"
                @click="requestVerification"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue';
import {router, usePage} from '@inertiajs/vue3';
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineOptions({name: 'ProjectStateChangeModal'})

// Instanz-Setting "Termine immer direkt buchbar": keine Verifizierung, Termine werden direkt übernommen
const directBookingOnly = computed(() => !!usePage().props.event_direct_booking_only);

const props = defineProps({
    projectId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['close']);

// Doppelklick schickte zwei Anfragen: beide bestanden die Prüfung, es gab doppelte Prüfanfragen
const requestingVerification = ref(false);

function closeModal() {
    if (requestingVerification.value) {
        return;
    }
    emit('close');
}

function requestVerification() {
    if (requestingVerification.value) {
        return;
    }
    requestingVerification.value = true;
    router.post(route('projects.request-verification', props.projectId), {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            requestingVerification.value = false;
            emit('close');
        },
        onFinish: () => {
            requestingVerification.value = false;
        },
    });
}
</script>
