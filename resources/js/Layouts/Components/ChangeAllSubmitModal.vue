<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closed">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_budget_access.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closed">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Apply to all')}}
                                </div>
                                <p class="xsLight">
                                    {{ $t('Would you like to apply the changes to all dates?')}}
                                </p>
                            </div>
                            <div class="flex justify-center mt-5 gap-4">
                                <button @click="allElement()" type="button" class=" hover:bg-artwork-buttons-hover py-2 px-8 rounded-full text-white" :class="clickedAll ? 'bg-success-500' : 'bg-artwork-buttons-create'">{{ $t('Apply to all')}}</button>
                                <button @click="singleElement()" type="button" class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover py-2 px-8 rounded-full text-white">{{ $t('Only apply to this event')}}</button>
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
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ChangeAllSubmitModal",
    mixins: [Permissions, IconLib],
    components: {
        DialogPanel,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon
    },
    data(){
        return {
            open: true,
            clickedAll: false,
            clickedSingle: false,
        }
    },
    emits: ['close-modal', 'single', 'allEvents'],
    methods: {
        closed(){
            this.$emit('close-modal')
        },
        singleElement(){
            this.$emit('single')
        },
        allElement(){
            this.$emit('allEvents')
            setTimeout(() => {
                this.clickedAll = true
            }, 5000)
        },
    }
}
</script>

<style scoped>

</style>
