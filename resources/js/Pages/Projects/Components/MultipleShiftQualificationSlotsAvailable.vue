<template>
    <BaseModal @closed="close(null, null, null, true)" v-if="show" modal-image="/Svgs/Overlays/illu_user_invite.svg">
            <div class="mx-4">
                <ModalHeader
                    :title="$t('Select qualification')"
                    :description="$t('In which qualification should the user be added?')" />
                <div class="grid grid-cols-2 w-full gap-4">
                    <AddButtonSmall no-icon v-for="availableShiftQualificationSlot in this.availableShiftQualificationSlots"
                                :text="$t('Use as {0}', [availableShiftQualificationSlot.name])"
                                @click="this.close(null, this.droppedUser, availableShiftQualificationSlot.id)" />
                </div>
            </div>
    </BaseModal>
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

export default defineComponent({
    components: {
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
