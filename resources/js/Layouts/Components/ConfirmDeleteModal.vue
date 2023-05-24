<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-30" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-30 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ title }}
                                </div>
                                <p class="text-error subpixel-antialiased">{{ description }}</p>
                            </div>
                            <div class="flex justify-between mt-5">
                                <AddButton mode="modal" @click="deleteElement(true)"
                                           class="!border-2 !border-buttonBlue text-white bg-buttonHover !hover:border-transparent resize-none"
                                           text="Löschen"/>
                                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal">Nein, doch nicht</p>
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
import AddButton from "@/Layouts/Components/AddButton.vue";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "ConfirmDeleteModal",
    mixins: [Permissions],
    components: {
        AddButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    data(){
        return {
            open: true
        }
    },
    props: ['title', 'description'],
    emits: ['closed', 'delete'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool)
        },
        deleteElement(bool){
            this.$emit('delete', bool)
        }
    }
}
</script>

<style scoped>

</style>
