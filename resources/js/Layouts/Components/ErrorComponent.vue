<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="font-black font-lexend text-text text-3xl my-2">
                    {{ titel }}
                </div>
                <div class="text-danger subpixel-antialiased">
                    {{ description }}
                </div>
                <div class="flex justify-start mt-6">
                    <FormButton @click="closeModal(true)" :text="confirm ?? $t('Yes')"/>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {IconCheck, IconX} from "@tabler/icons-vue";
import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions, IconLib],
    name: 'ErrorComponent',
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        IconX,
        IconCheck
    },
    props: [
        'titel',
        'description',
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
