<template>
    <ArtworkBaseModal
        :title="isOverbooking ? $t('Add overbooking') : $t('Add Function')"
        :description="isOverbooking ? $t('Select a function to overbook. The demand remains unchanged.') : $t('Select a function to add to the shift')"
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
    },
    // Überbuchungs-Modus: schafft einen Überbuchungsplatz statt regulären Bedarf zu erhöhen
    isOverbooking: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const availableQualifications = computed(() => {
    const shiftCraftId = props.shift?.craftId ?? props.shift?.craft_id ?? props.shift?.craft?.id;
    if (!shiftCraftId || !props.crafts) return [];

    const craftsArray = Array.isArray(props.crafts)
        ? props.crafts
        : Object.values(props.crafts || {});

    const foundCraft = craftsArray.find(c => c.id === shiftCraftId);
    if (!foundCraft || !foundCraft.qualifications) return [];

    return foundCraft.qualifications;
});

const addQualification = (qualificationId) => {
    const routeName = props.isOverbooking
        ? 'shifts.qualifications.overbook.increase'
        : 'shifts.qualifications.add';

    router.patch(route(routeName, { shift: props.shift.id }), {
        qualification_id: qualificationId
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        }
    });
};
</script>
