<script>
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    name: "AddEditSidebarTab",
    mixins: [IconLib],
    components: {
        BaseInput,
        BaseUIButton,
        ArtworkBaseModal,
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    emits: ['close'],
    props: ['tabToEdit', 'tab'],
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
                this.tabForm.patch(route('tab.sidebar.update', {projectTabSidebarTab: this.tabToEdit.id}), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false);
                    },
                })
            } else {
                this.tabForm.post(route('tab.sidebar.store', {projectTab: this.tab.id}),
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

    <ArtworkBaseModal :title="tabToEdit ? $t('Rename sidebar tab') : $t('Create sidebar tab')" description="" @close="closeModal">
        <div>
            <BaseInput v-model="tabForm.name" name="email" id="email" label="Name" />

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
