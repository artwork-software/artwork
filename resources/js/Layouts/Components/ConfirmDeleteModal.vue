<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 pl-4">
                                <div class="font-black font-lexend text-primary text-3xl my-2 mb-6">
                                    {{ title }}
                                </div>
                                <p class="text-error subpixel-antialiased">{{ description }}</p>
                            </div>
                            <div class="flex justify-between mt-5 items-center pr-4" v-if="!is_budget && !isSeriesDelete">
                                <FormButton
                                    @click="deleteElement(true)"
                                    :text="buttonText" />
                                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal">
                                    {{ $t('No, not really') }}
                                </p>
                            </div>
                            <div v-if="isSeriesDelete" class="flex justify-between mt-5 items-center pr-4">
                                <FormButton
                                    @click="deleteElement(true)"
                                    :text="buttonText" />
                                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="complete_delete">
                                    {{ $t('Delete series entry completely') }}
                                </p>
                            </div>
                            <div v-if="is_budget && !isSeriesDelete" class="flex justify-between mt-5 pl-4 items-center pr-4">
                                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal">{{ $t('Continue without saving') }}</p>
                                <FormButton
                                    @click="deleteElement(true)"
                                    :text="buttonText" />

                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import {XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ConfirmDeleteModal",
    mixins: [Permissions, IconLib],
    components: {
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    data(){
        return {
            open: true,
            buttonText: this.button ? this.button : this.$t('Delete')
        }
    },
    props: ['title', 'description', 'button', 'is_budget', 'isSeriesDelete'],
    emits: ['closed', 'delete', 'complete_delete'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool)
        },
        deleteElement(bool){
            this.$emit('delete', bool)
        },
        complete_delete(){
            this.$emit('complete_delete', true)
        }
    }
}
</script>
