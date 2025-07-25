<template>
    <BaseModal @closed="$emit('close')">
        <div class="space-y-6">
            <div class="font-black font-lexend text-primary text-2xl mb-4">
                {{ $t('Project status change') }}
            </div>

            <div class="text-gray-700">
                {{ $t('The new project status is no longer marked as planned. Do you want to request verification for all planning events of the project?') }}
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="$emit('close')" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                    {{ $t('No') }}
                </button>
                <button type="button" @click="requestVerification" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-artwork-buttons-create text-base font-medium text-white hover:bg-artwork-buttons-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                    {{ $t('Request appointments verification') }}
                </button>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref } from 'vue';
import BaseModal from "@/Components/Modals/BaseModal.vue";
import { router } from '@inertiajs/vue3';

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
