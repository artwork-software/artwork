<template>
    <ArtworkBaseModal @close="close(null, null, null, true)" v-if="show"  :title="$t('Select qualification')"
                      :description="$t('In which qualification should the user be added?')"  is-in-shift-plan>
            <div class="mx-4">
                <div class="grid grid-cols-2 w-full gap-4">
                    <BaseUIButton
                        v-for="availableShiftQualificationSlot in this.availableShiftQualificationSlots"
                        :label="$t('Use as {0}', [availableShiftQualificationSlot.name]) + (shouldShowAbbreviation(availableShiftQualificationSlot) && availableShiftQualificationSlot.pivot?.craft_id ? ' [' + getCraftAbbreviation(availableShiftQualificationSlot.pivot.craft_id) + ']' : '')"
                        @click="this.close(null, this.droppedUser, availableShiftQualificationSlot.id)"
                        :icon="availableShiftQualificationSlot.icon"
                        is-add-button
                    />
                </div>
            </div>
    </ArtworkBaseModal>
</template>

<script>
import {defineComponent} from 'vue';
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from "@heroicons/vue/outline";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default defineComponent({
    components: {
        BaseUIButton,
        ArtworkBaseModal,
        ModalHeader,
        AddButtonSmall,
        FormButton,
        BaseModal,
        XIcon,
        JetDialogModal,
        ShiftQualificationIconCollection
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
        close(event, droppedUser = null, shiftQualificationId = null, closeOnButton = false) {
            this.$emit('close', droppedUser, shiftQualificationId, closeOnButton);
        }
    }
});
</script>
