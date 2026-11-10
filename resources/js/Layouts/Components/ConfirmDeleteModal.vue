<template>
    <ArtworkBaseModal
        :title="title"
        modal-size="sm:max-w-lg"
        :z-index="String(zIndex)"
        @close="closeModal(false)"
    >
        <p v-if="description" class="text-sm text-text">
            {{ description }}
        </p>
        <template #footer>
            <!-- Budget: Abbrechen heißt hier „Weiter ohne zu speichern“ -->
            <BaseUIButton
                v-if="is_budget && !isSeriesDelete"
                label="Continue without saving"
                is-cancel-button
                hide-icon
                :disabled="loading"
                @click="closeModal(false)"
            />
            <BaseUIButton
                v-else
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="loading"
                @click="closeModal(false)"
            />
            <BaseUIButton
                v-if="isSeriesDelete"
                label="Delete series entry completely"
                variant="danger"
                icon="IconTrash"
                :disabled="loading"
                @click="completeDelete(true)"
            />
            <BaseUIButton
                :label="buttonText"
                :use-translation="!button"
                :variant="is_budget || isSeriesDelete ? 'primary' : 'danger'"
                :icon="is_budget || isSeriesDelete ? 'IconCheck' : 'IconTrash'"
                :processing="loading"
                @click="deleteElement(true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: "ConfirmDeleteModal",
    components: {
        ArtworkBaseModal,
        BaseUIButton,
    },
    props: {
        title: String,
        description: String,
        /** Beschriftung der Bestätigung (bereits übersetzt); ohne Angabe „Löschen“ */
        button: String,
        is_budget: Boolean,
        isSeriesDelete: Boolean,
        loading: Boolean,
        zIndex: {
            type: [String, Number],
            default: '100',
        },
    },
    // close zusätzlich zu closed: ein Teil der Aufrufer hört nur auf @close
    emits: ['closed', 'close', 'delete', 'complete_delete'],
    computed: {
        buttonText() {
            return this.button || 'Delete'
        },
    },
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool)
            this.$emit('close')
        },
        deleteElement(bool) {
            this.$emit('delete', bool)
        },
        completeDelete(bool) {
            this.$emit('complete_delete', bool)
        },
    },
}
</script>
