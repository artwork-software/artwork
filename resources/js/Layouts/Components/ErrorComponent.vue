<template>
    <ArtworkBaseModal
        :title="titel || 'An error has occurred'"
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <BaseAlertComponent
            v-if="description"
            class="mb-0!"
            type="error"
            :message="String(description)"
        />
        <template #footer>
            <BaseUIButton
                :label="confirm || 'OK'"
                :use-translation="!confirm"
                variant="primary"
                icon="IconCheck"
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
    name: 'ErrorComponent',
    components: {
        ArtworkBaseModal,
        BaseUIButton,
        BaseAlertComponent,
    },
    props: [
        'titel',
        'description',
        // Beschriftung der Bestätigung (bereits übersetzt)
        'confirm',
        'cancel'
    ],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>
