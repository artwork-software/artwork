<template>
    <jet-dialog-modal :show="showModal" @close="closeModal(false)">
        <template #content>
            <img v-if="mode === 'warning'" src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <img v-else src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Raumanfrage {{ mode === 'warning' ? 'ablehnen' : 'bestätigen'}}
                </div>
                <XIcon @click="closeModal(false)"
                    class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                    aria-hidden="true"/>
                <div :class="[mode === 'warning' ? 'text-error' : 'text-success']">
                    Möchtest du die Raumanfrage für {{requestToApprove.room.name}} | {{requestToApprove.eventType.name}}, {{requestToApprove.eventName}} | {{requestToApprove.project.name}} | {{requestToApprove.start}} - {{requestToApprove.end}} {{ mode === 'warning' ? 'ablehnen' : 'bestätigen'}}?
                </div>
                <div class="flex justify-between mt-6">
                    <AddButton v-if="!showCheckButton" class="px-20 py-4" @click="closeModal(true)" mode="modal" :text="mode === 'warning' ? 'Ablehnen' : 'Bestätigen'" />
                    <AddButton v-else class="px-20 py-4" @click="closeModal(true)" mode="modal"><CheckIcon class="h-6 w-6 text-secondaryHover"/></AddButton>
                    <div v-if="!showCheckButton" class="my-auto xsLight cursor-pointer"
                        @click="closeModal(false)">
                        Nein, doch nicht
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

export default {
    name: 'ConfirmationComponent',
    components: {
        JetDialogModal,
        XIcon,
        AddButton,
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
