<template>
    <jet-dialog-modal v-show="true" @close="closeModal(false)">
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
                    <AddButton class="px-20 py-4" @click="closeModal(true)" :text="confirm ?? 'Ja'" mode="modal"/>
                    <div class="my-auto xsLight cursor-pointer"
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
import AddButton from "@/Layouts/Components/AddButton";
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: 'ConfirmationComponent',
    mixins: [Permissions],
    components: {
        JetDialogModal,
        XIcon,
        AddButton,
        CheckIcon
    },
    props: ['titel', 'description', 'confirm', 'cancel'],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
