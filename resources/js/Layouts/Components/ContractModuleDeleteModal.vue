<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Delete contract module')}}
                </div>
                <div class="errorText">
                    {{ $t('Are you sure you want to delete this module from the system?')}}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton :text="$t('Delete')"
                               @click="destroy" />
                    <div class="flex my-auto">
                            <span @click="closeModal"
                                  class="xsLight cursor-pointer">{{ $t('No, not really') }}</span>
                    </div>
                </div>
            </div>

    </BaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: "ContractModuleDeleteModal",
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        XIcon
    },
    props: {
        contractModule: Object,
        show: Boolean,
        closeModal: Function
    },
    methods: {
        destroy() {
            this.$inertia.delete(`/contract_modules/${this.contractModule.id}`);
            this.closeModal()
        },
    }
}
</script>

<style scoped>

</style>
