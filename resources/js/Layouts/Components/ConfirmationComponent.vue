<template>
    <ArtworkBaseModal @close="closeModal(false)" v-if="true" :title="titel" description="">
        <div class="mt-5">
            <BaseAlertComponent :message="description" type="error"/>
        </div>
        <div class="">
            <div class="flex justify-between mt-6">
                <BaseUIButton label="Delete" use-translation is-delete-button @click="closeModal(true)"/>

                <BaseUIButton label="No, not really" use-translation @click="closeModal(false)" icon="IconCancel"/>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from '@heroicons/vue/outline';
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

export default {
    name: 'ConfirmationComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseAlertComponent,
        BaseUIButton,
        ArtworkBaseModal,
        BaseModal,
        FormButton,
        JetDialogModal,
        XIcon,
        CheckIcon
    },
    props: ['titel', 'description', 'confirm', 'cancel'],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
