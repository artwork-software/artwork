<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Delete document')}}
                </div>
                <div class="errorText">
                    {{ $t('Are you sure you want to delete this document from the system?')}}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton :text="$t('Delete')"
                               @click="destroy" />
                    <div class="flex my-auto">
                            <span @click="closeModal"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
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
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "MoneySourceFileDeleteModal",
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        XIcon
    },
    props: {
        file: Object,
        show: Boolean,
        closeModal: Function,
        moneySourceId: Number,
    },
    methods: {
        destroy() {
            this.$inertia.delete(route('money_sources_delete_file', [this.moneySourceId, this.file.id] ));
            //this.$inertia.delete(`/money_sources/${this.file.id}/files`);
            this.closeModal()
        },
    }
}
</script>

<style scoped>

</style>
