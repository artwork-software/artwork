<template>
    <BaseModal @closed="$emit('close')">
        <div class="space-y-6">
            <div class="font-black font-lexend text-text text-2xl mb-4">
                {{ $t('Project status change') }}
            </div>

            <div class="text-text-muted">
                {{ directBookingOnly
                    ? $t('The new project status is no longer marked as planned. Do you want to confirm all planning events of the project as fixed events?')
                    : $t('The new project status is no longer marked as planned. Do you want to request verification for all planning events of the project?') }}
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="$emit('close')" class="inline-flex justify-center rounded-md border border-border shadow-sm px-4 py-2 bg-white text-base font-medium text-text-muted hover:bg-surface-sunken focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-600 sm:text-sm">
                    {{ $t('No') }}
                </button>
                <button type="button" @click="requestVerification" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-accent-600 text-base font-medium text-white hover:bg-accent-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-600 sm:text-sm">
                    {{ directBookingOnly ? $t('Confirm as fixed events') : $t('Request appointments verification') }}
                </button>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { computed, ref } from 'vue';
import BaseModal from "@/Components/Modals/BaseModal.vue";
import { router, usePage } from '@inertiajs/vue3';

// Instanz-Setting "Termine immer direkt buchbar": keine Verifizierung, Termine werden direkt übernommen
const directBookingOnly = computed(() => !!usePage().props.event_direct_booking_only);

const props = defineProps({
    projectId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['close']);

const requestVerification = () => {
    router.post(route('projects.request-verification', props.projectId), {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            emit('close');
        }
    });
};
</script>
