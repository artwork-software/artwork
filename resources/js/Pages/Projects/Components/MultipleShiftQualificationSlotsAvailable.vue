<template>
    <ArtworkBaseModal
        v-if="show"
        title="Select qualification"
        description="In which qualification should the user be added?"
        modal-size="sm:max-w-xl"
        is-in-shift-plan
        @close="close(null, null, null, true)"
    >
        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2">
            <BaseUIButton
                v-for="(availableShiftQualificationSlot, index) in availableShiftQualificationSlots"
                :key="`${availableShiftQualificationSlot.id}-${availableShiftQualificationSlot.pivot?.craft_id ?? index}`"
                :label="slotLabel(availableShiftQualificationSlot)"
                :use-translation="false"
                :icon="availableShiftQualificationSlot.icon"
                variant="secondary"
                class="w-full"
                :class="{ 'border-warning-border! border-dashed!': availableShiftQualificationSlot.isOverbooked }"
                @click="close(null, droppedUser, availableShiftQualificationSlot.id, false, !!availableShiftQualificationSlot.isOverbooked)"
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                @click="close(null, null, null, true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {defineComponent} from 'vue';
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default defineComponent({
    name: 'MultipleShiftQualificationSlotsAvailable',
    components: {
        BaseUIButton,
        ArtworkBaseModal,
    },
    props: [
        'show',
        'availableShiftQualificationSlots',
        'droppedUser',
        'crafts'
    ],
    emits: [
        'close'
    ],
    computed: {
        hasDuplicateQualifications() {
            if (!this.availableShiftQualificationSlots) return false;
            const counts = {};
            for (const slot of this.availableShiftQualificationSlots) {
                counts[slot.id] = (counts[slot.id] || 0) + 1;
            }
            return Object.values(counts).some(count => count > 1);
        }
    },
    methods: {
        /**
         * Fertig übersetzte Beschriftung; der Button übersetzt sie nicht noch einmal,
         * weil Namen sonst als i18n-Syntax ausgewertet würden.
         */
        slotLabel(slot) {
            let label = this.$t('Use as {0}', [slot.name]);
            if (this.shouldShowAbbreviation(slot) && slot.pivot?.craft_id) {
                label += ' [' + this.getCraftAbbreviation(slot.pivot.craft_id) + ']';
            }
            if (slot.isOverbooked) {
                label += ' (' + this.$t('Overbook') + ')';
            }
            return label;
        },
        shouldShowAbbreviation(slot) {
            if (!this.hasDuplicateQualifications) return false;

            const qualificationId = slot.id;
            const occurrences = this.availableShiftQualificationSlots.filter(s => s.id === qualificationId).length;

            return occurrences > 1;
        },
        getCraftAbbreviation(craftId) {
            const crafts = this.crafts || this.$page.props.crafts;
            if (!crafts || !craftId) return '';
            const craft = crafts.find(c => c.id === craftId);
            return craft ? craft.abbreviation : '';
        },
        close(event, droppedUser = null, shiftQualificationId = null, closeOnButton = false, isOverbooked = false) {
            this.$emit('close', droppedUser, shiftQualificationId, closeOnButton, isOverbooked);
        }
    }
});
</script>
