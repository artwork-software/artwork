<template>
    <BaseModal @closed="$emit('close')">
        <div class="space-y-6">
            <div class="font-black font-lexend text-primary text-2xl mb-4">
                {{ $t('Project status change') }}
            </div>

            <div class="text-gray-700">
                {{ $t('The new project status is marked as planned. Do you want to convert all events of the project to planning events?') }}
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="$emit('close')" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                    {{ $t('No') }}
                </button>
                <button type="button" @click="convertToPlanning" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-artwork-buttons-create text-base font-medium text-white hover:bg-artwork-buttons-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                    {{ $t('Convert to planning events') }}
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

const convertToPlanning = () => {
    router.post(route('projects.convert-to-planning', props.projectId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        }
    });
};
</script>
