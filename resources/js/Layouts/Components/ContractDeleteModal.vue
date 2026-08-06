<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text my-2">
                    {{  $t('Delete contract') }}
                </div>
                <div class="text-sm/5 text-danger">
                    {{ $t('Are you sure you want to delete this contract from the system?')}}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton :text="$t('Delete')"
                               @click="destroy" />
                    <div class="flex my-auto">
                            <span @click="closeModal" class="text-sm/5 font-bold text-text-subtle cursor-pointer">{{ $t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: "ContractDeleteModal",
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        IconX
    },
    props: {
        contract: Object,
        show: Boolean,
        closeModal: Function
    },
    methods: {
        destroy() {
            this.$inertia.delete(route('contract.delete', this.contract.id));
            this.closeModal()
        },
    }
}
</script>

<style scoped>

</style>
