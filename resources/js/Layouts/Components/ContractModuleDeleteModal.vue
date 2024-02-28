<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Delete contract module')}}
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="errorText">
                    {{ $t('Are you sure you want to delete this module from the system?')}}
                </div>
                <div class="flex justify-between mt-6">
                    <AddButton :text="$t('Delete')" mode="modal" class="px-20 py-3"
                               @click="destroy" />
                    <div class="flex my-auto">
                            <span @click="closeModal"
                                  class="xsLight cursor-pointer">{{ $t('No, not really') }}</span>
                    </div>
                </div>
            </div>

        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "ContractModuleDeleteModal",
    mixins: [Permissions],
    components: {
        JetDialogModal,
        AddButton,
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
