<template>
    <BaseModal @closed="closeModal(false)" v-if="true" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    {{ titel }}
                </div>
                <div class="text-error subpixel-antialiased">
                    {{ description }}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton
                        @click="closeModal(true)"
                        :text="confirm ?? $t('Yes')"
                    />
                    <div class="my-auto xsLight cursor-pointer"
                        @click="closeModal(false)">
                        {{ cancel ?? $t('No, not really') }}
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from '@heroicons/vue/outline';
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'ConfirmationComponent',
    mixins: [Permissions, IconLib],
    components: {
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
