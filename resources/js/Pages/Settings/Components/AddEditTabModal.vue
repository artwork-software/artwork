<script>
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: "AddEditTabModal",
    mixins: [IconLib],
    components: {
        BaseUIButton,
        ArtworkBaseModal,
        BaseInput,
        TextInputComponent,
        ModalHeader,
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    emits: ['close'],
    props: ['tabToEdit'],
    data(){
        return {
            open: true,
            tabForm: useForm({
                name: this.tabToEdit ? this.tabToEdit.name : '',
            })
        }
    },
    methods: {
        closeModal(bool){
            this.$emit('close', bool)
        },
        saveTab() {
            if (this.tabToEdit) {
                this.tabForm.patch(route('tab.update', {projectTab: this.tabToEdit.id}), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false);
                    },
                })
            } else {
                this.tabForm.post(route('tab.store'),
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            this.closeModal(false);
                        },
                    });
            }
        }
    }
}
</script>

<template>

    <ArtworkBaseModal :title="tabToEdit ? $t('Edit tab') : $t('Create tab')" :description="tabToEdit ? $t('Edit tab name') : $t('Create a new tab')" @close="closeModal">
        <div>
            <BaseInput type="text" v-model="tabForm.name" label="Name" id="email" />
        </div>
        <div class="flex justify-between mt-5 items-center">
            <BaseUIButton
                @click="saveTab(true)"
                is-add-button
                :label="tabToEdit ? $t('Edit') : $t('Create')" />

            <BaseUIButton
                @click="closeModal"
                is-cancel-button
                :label="$t('No, not really')" />
        </div>
    </ArtworkBaseModal>
</template>

<style scoped>

</style>
