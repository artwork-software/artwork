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
                <div class="flex justify-start mt-6">
                    <FormButton @click="closeModal(true)" :text="confirm ?? $t('Yes')"/>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon} from '@heroicons/vue/outline';
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    mixins: [Permissions],
    name: 'ErrorComponent',
    components: {
        FormButton,
        JetDialogModal,
        XIcon,
        CheckIcon
    },
    props: [
        'titel',
        'description',
        'confirm',
        'cancel'
    ],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>
