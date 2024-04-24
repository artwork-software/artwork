<template>
    <jet-dialog-modal :show="showModal" @close="closeModal(false)">
        <template #content>
            <img v-if="mode === 'warning'" src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <img v-else src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Room request')}} {{ mode === 'warning' ? $t('Confirm') : $t('Reject')}}
                </div>
                <IconX stroke-width="1.5" @click="closeModal(false)"
                    class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                    aria-hidden="true"/>
                <div :class="[mode === 'warning' ? 'text-error' : 'text-success']">
                    {{$t('Would you like to submit a room request for')}} {{requestToApprove.room.name}} | {{requestToApprove.eventType.name}}, {{requestToApprove.eventName}} | {{requestToApprove.project.name}} | {{requestToApprove.start}} - {{requestToApprove.end}} {{ mode === 'warning' ? $t('Confirm') : $t('Reject')}}?
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton v-if="!showCheckButton" @click="closeModal(true)" :text="mode === 'warning' ? $t('Confirm') : $t('Reject')" />
                    <FormButton v-else @click="closeModal(true)"><CheckIcon class="h-6 w-6 text-secondaryHover"/></FormButton>
                    <div v-if="!showCheckButton" class="my-auto xsLight cursor-pointer"
                        @click="closeModal(false)">
                        {{ $t('No, not really')}}
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
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: 'ConfirmationComponent',
    mixins: [Permissions, IconLib],
    components: {
        FormButton,
        JetDialogModal,
        XIcon,
        CheckIcon
    },
    props: ['showModal','requestToApprove','mode'],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.showCheckButton = true;
            this.showModal = false;
            this.$emit('closed', bool);
        },
    },
    data(){
        return {
            showCheckButton: false,
        }
    }
}
</script>

<style scoped></style>
