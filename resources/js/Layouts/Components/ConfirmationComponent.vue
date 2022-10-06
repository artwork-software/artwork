<template>
    <jet-dialog-modal :show="true" @close="closeModal(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    {{ titel }}
                </div>
                <XIcon @click="closeModal(false)"
                    class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                    aria-hidden="true"/>
                <div class="text-error subpixel-antialiased">
                    {{ description }}
                </div>
                <div class="flex justify-between mt-6">
                    <button class="bg-primary focus:outline-none my-auto px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                        @click="closeModal(true)">
                        {{ confirm ?? 'Ja' }}
                    </button>
                    <div class="my-auto text-secondary subpixel-antialiased cursor-pointer"
                        @click="closeModal(false)">
                        {{ cancel ?? 'Nein, doch nicht' }}
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>

import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon} from '@heroicons/vue/outline';

export default {
    name: 'ConfirmationComponent',
    components: {
        JetDialogModal,
        XIcon,
    },
    props: ['titel', 'description', 'confirm', 'cancel', 'showModal'],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
