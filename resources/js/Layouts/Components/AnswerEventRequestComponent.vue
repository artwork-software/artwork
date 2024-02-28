<template>
    <jet-dialog-modal :show="true" @close="closeModal(false)">
        <template #content>
            <img v-if="type === 'accept'" src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
            <img v-else-if="type === 'decline'" src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Room request')}} {{ type === 'accept' ? $t('Confirm') : $t('Reject')}}
                </div>
                <XIcon @click="closeModal(false)"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="w-10/12" :class="type === 'accept' ? 'successText' : 'errorText'">
                    {{ $t('Would you like to submit a room request for')}} {{ this.rooms.find(room => room.id === request.room_id).name}} | {{this.eventTypes.find(eventType => eventType.id === request.event_type_id).name}},
                    {{request.eventName}}  {{ request.project_id ? '|' : ''}} {{this.projects.find(project => project.id === request.project_id)?.name}} | {{formatDate(request.start_time)}} - {{ formatDate(request.end_time)}}
                    {{ type === 'accept' ? $t('Confirm') : $t('Reject')}}?
                </div>
                <div class="flex justify-between mt-6">
                    <AddButton class="px-20 py-4" @click="closeModal(true)" :text="$t('Confirm')" mode="modal"/>
                    <div class="flex my-auto">
                            <span @click="closeModal(false)"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
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
    name: 'AnswerEventRequestComponent',
    mixins: [Permissions],
    components: {
        JetDialogModal,
        XIcon,
        AddButton,
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
