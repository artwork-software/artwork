<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                             leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"/>
            </TransitionChild>
            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300"
                                     enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                                     leave-from="opacity-100 translate-y-0 sm:scale-100"
                                     leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="modal" :class="[modalSize, fullModal ? '' : 'sm:p-6 px-4 pt-5 pb-4']">
                            <!--<img v-if="showImage" :src="modalImage" class=" mb-4 rounded-tl-lg"
                                 :class="fullModal ? '' : '-ml-6 -mt-6'"/>-->
                            <div class="absolute top-0 right-0 pt-4 pr-4 hidden sm:block z-50">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                                        @click="closeModal(false)">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true"/>
                                </button>
                            </div>
                            <div>
                                <slot/>
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
    name: "BaseModal",
    mixins: [Permissions, IconLib],
    components: {
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    data() {
        return {
            open: true,
        }
    },
    props: {
        modalImage: {
            type: String,
            default: '/Svgs/Overlays/illu_appointment_edit.svg'
        },
        showImage: {
            type: Boolean,
            default: true
        },
        modalSize: {
            type: String,
            default: 'sm:max-w-2xl'
        },
        fullModal: {
            type: Boolean,
            default: false
        }
    },
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool)
        },
    }
}
</script>
