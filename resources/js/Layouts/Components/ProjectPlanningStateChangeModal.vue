<template>
    <ArtworkBaseModal
        title="Project status change"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <p class="text-sm text-text">
            {{ $t('The new project status is marked as planned. Do you want to convert all events of the project to planning events?') }}
        </p>
        <template #footer>
            <BaseUIButton
                label="No"
                is-cancel-button
                hide-icon
                :disabled="converting"
                @click="closeModal"
            />
            <BaseUIButton
                label="Convert to planning events"
                variant="primary"
                icon="IconCheck"
                :processing="converting"
                @click="convertToPlanning"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from 'vue';
import {router} from '@inertiajs/vue3';
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineOptions({name: 'ProjectPlanningStateChangeModal'})

const props = defineProps({
    projectId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['close']);

// Doppelklick schickte sonst zwei Umwandlungen los
const converting = ref(false);

function closeModal() {
    if (converting.value) {
        return;
    }
    emit('close');
}

function convertToPlanning() {
    if (converting.value) {
        return;
    }
    converting.value = true;
    router.post(route('projects.convert-to-planning', props.projectId), {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            converting.value = false;
            emit('close');
        },
        onFinish: () => {
            converting.value = false;
        },
    });
}
</script>
