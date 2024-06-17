<template>
    <BaseModal @closed="closeModal" v-if="true" :modal-image="type === 'accept' ? '/Svgs/Overlays/illu_success.svg' : '/Svgs/Overlays/illu_warning.svg'">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Room request')}} {{ type === 'accept' ? $t('Confirm') : $t('Reject')}}
                </div>
                <div class="w-10/12" :class="type === 'accept' ? 'successText' : 'errorText'">
                    {{ $t('Would you like to submit a room request for')}} {{ this.rooms.find(room => room.id === request.room_id).name}} | {{this.eventTypes.find(eventType => eventType.id === request.event_type_id).name}},
                    {{request.eventName}}  {{ request.project_id ? '|' : ''}} {{this.projects.find(project => project.id === request.project_id)?.name}} | {{formatDate(request.start_time)}} - {{ formatDate(request.end_time)}}
                    {{ type === 'accept' ? $t('Confirm') : $t('Reject')}}?
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton @click="closeModal(true)" :text="$t('Confirm')"/>
                    <div class="flex my-auto">
                            <span @click="closeModal(false)"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
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
    name: 'AnswerEventRequestComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        XIcon,
        CheckIcon
    },
    props: ['type','request','rooms','projects','eventTypes'],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        formatDate(isoDate) {
            if(isoDate?.split('T').length > 1){
                return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
            }else if(isoDate?.split(' ').length > 1){
                return isoDate.split(' ')[0].substring(8, 10) + '.' + isoDate.split(' ')[0].substring(5, 7) + '.' + isoDate.split(' ')[0].substring(0, 4) + ', ' + isoDate.split(' ')[1].substring(0, 5)
            }
        },
    },
}
</script>

<style scoped></style>
