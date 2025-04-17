<script>
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    name: "AddEditTabModal",
    mixins: [IconLib],
    components: {
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
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 px-4">
                                <ModalHeader
                                    :title="tabToEdit ? $t('Edit tab') : $t('Create tab')"
                                    :description="tabToEdit ? $t('Edit tab name') : $t('Create a new tab')"
                                />
                                <div>
                                    <BaseInput type="text" v-model="tabForm.name" label="Name" id="email" />
                                </div>
                                <div class="flex justify-between mt-5 items-center pr-4">
                                    <FormButton
                                        @click="saveTab(true)"
                                        :text="tabToEdit ? $t('Edit') : $t('Create')" />
                                    <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal">
                                        {{ $t('No, not really') }}
                                    </p>
                                </div>
                            </div>

                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<style scoped>

</style>
