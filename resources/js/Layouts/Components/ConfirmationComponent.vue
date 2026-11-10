<template>
    <ArtworkBaseModal
        :title="titel"
        modal-size="sm:max-w-lg"
        :z-index="String(zIndex)"
        @close="closeModal(false)"
    >
        <BaseAlertComponent
            v-if="description"
            class="mb-0!"
            :message="description"
            :type="destructive ? 'error' : 'warning'"
        />
        <template #footer>
            <BaseUIButton
                :label="cancel || 'No, not really'"
                :use-translation="!cancel"
                is-cancel-button
                hide-icon
                @click="closeModal(false)"
            />
            <BaseUIButton
                :label="confirm || 'Delete'"
                :use-translation="!confirm"
                :variant="destructive ? 'danger' : 'primary'"
                :icon="destructive ? 'IconTrash' : 'IconCheck'"
                @click="closeModal(true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

export default {
    name: 'ConfirmationComponent',
    components: {
        BaseAlertComponent,
        BaseUIButton,
        ArtworkBaseModal,
    },
    props: {
        titel: String,
        description: String,
        /** Beschriftung der Bestätigung (bereits übersetzt); ohne Angabe „Löschen“ */
        confirm: String,
        /** Beschriftung des Abbrechens (bereits übersetzt); ohne Angabe „Nein, doch nicht“ */
        cancel: String,
        /** Rot/Mülleimer für zerstörende Aktionen; false für neutrale Bestätigungen (z. B. Neu buchen) */
        destructive: {
            type: Boolean,
            default: true
        },
        zIndex: {
            type: [String, Number],
            default: '100'
        }
    },
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>
