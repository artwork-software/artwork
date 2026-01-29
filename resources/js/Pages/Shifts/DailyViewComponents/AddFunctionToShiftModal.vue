<template>
    <ArtworkBaseModal
        :title="$t('Add Function')"
        :description="$t('Select a function to add to the shift')"
        @close="$emit('close')"
    >
        <div class="grid grid-cols-2 w-full gap-4">
            <BaseUIButton
                v-for="qualification in availableQualifications"
                :key="qualification.id"
                :label="qualification.name"
                @click="addQualification(qualification.id)"
                :icon="qualification.icon"
                is-add-button
            />
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import { router } from "@inertiajs/vue3";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    shift: {
        type: Object,
        required: true
    },
    shiftQualifications: {
        type: Array,
        required: true
    },
    crafts: {
        type: [Array, Object],
        required: true
    }
});

const emit = defineEmits(['close']);

const availableQualifications = computed(() => {
    const shiftCraftId = props.shift?.craft?.id;
    if (!shiftCraftId || !props.crafts) return [];

    // Look up the craft in props.crafts to find its associated qualifications
    const craftsArray = Array.isArray(props.crafts)
        ? props.crafts
        : Object.values(props.crafts || {});

    const foundCraft = craftsArray.find(c => c.id === shiftCraftId);
    if (!foundCraft || !foundCraft.qualifications) return [];

    return foundCraft.qualifications;
});

const addQualification = (qualificationId) => {
    router.patch(route('shifts.qualifications.add', { shift: props.shift.id }), {
        qualification_id: qualificationId
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        }
    });
};
</script>
