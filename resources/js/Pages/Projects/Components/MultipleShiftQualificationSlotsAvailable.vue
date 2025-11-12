<template>
    <ArtworkBaseModal @close="close(null, null, null, true)" v-if="show"  :title="$t('Select qualification')"
                      :description="$t('In which qualification should the user be added?')"  is-in-shift-plan>
            <div class="mx-4">
                <div class="grid grid-cols-2 w-full gap-4">

                    <BaseUIButton
                        v-for="availableShiftQualificationSlot in this.availableShiftQualificationSlots"
                        :label="$t('Use as {0}', [availableShiftQualificationSlot.name])"
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
        'droppedUser'
    ],
    emits: [
        'close'
    ],
    methods: {
        close(event, droppedUser = null, shiftQualificationId = null, closeOnButton = false) {
            this.$emit('close', droppedUser, shiftQualificationId, closeOnButton);
        }
    }
});
</script>
