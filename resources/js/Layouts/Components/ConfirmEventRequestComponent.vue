<template>
    <BaseModal @closed="closeModal(false)" v-if="showModal" :modal-image="mode === 'warning' ? '/Svgs/Overlays/illu_warning.svg' : '/Svgs/Overlays/illu_success.svg'">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Room request')}} {{ mode === 'warning' ? $t('Confirm') : $t('Reject')}}
                </div>
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
    </BaseModal>
</template>

<script>

import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from '@heroicons/vue/outline';
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'ConfirmationComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
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
