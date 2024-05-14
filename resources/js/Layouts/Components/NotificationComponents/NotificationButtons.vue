<template>
    <div class="w-full flex items-center justify-start gap-3 mt-3">
        <div v-if="buttons?.includes('accept')">
            <button @click="$emit('openEventEditAccept', true)" type="button" class="rounded-full bg-indigo-600 p-2 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <IconEdit stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
        <div v-if="buttons?.includes('decline')">
            <button @click="$emit('openDeclineModal', true)" type="button" class="rounded-full bg-red-600 p-2 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                <IconX stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
        <div v-if="buttons?.includes('change_shift')">
            <button @click="$emit('openProject')" type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{  $t('Change shift') }}
            </button>
        </div>
        <div v-if="buttons?.includes('change_shift')">
            <button @click="$emit('openProject')" type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('View')}}
            </button>
        </div>
        <div v-if="buttons?.includes('change_shift_conflict')">
            <button @click="$emit('openProject')" type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Re-staff shift')}}
            </button>
        </div>
        <div v-if="buttons?.includes('change_room')">
            <button type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Change room')}}
            </button>
        </div>
        <div v-if="buttons?.includes('change_request')">
            <button @click="$emit('openEventWithoutRoomModal', true)" type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Change request')}}
            </button>
        </div>
        <div v-if="buttons?.includes('event_delete')">
            <button @click="$emit('deleteEvent', true)" type="button" class="rounded-full bg-transparent px-3.5 py-1.5 text-sm font-semibold text-indigo-600 hover:text-white ring-2 ring-indigo-600 hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Delete event')}}
            </button>
        </div>
        <div v-if="buttons?.includes('answer')">
            <button @click="$emit('openEventEditAccept', true)" type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Answers')}}
            </button>
        </div>
        <div v-if="buttons?.includes('answerDialog')">
            <button @click="$emit('openDialogModal', true)" type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Answers')}}
            </button>
        </div>
        <div v-if="buttons?.includes('calculation_check')">
            <button @click="$emit('openProjectCalculation', true)" type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Check calculation')}}
            </button>
        </div>
        <div v-if="buttons?.includes('delete_request')">
            <button @click="$emit('deleteVerificationRequest', true)" type="button" class="rounded-full bg-transparent px-3.5 py-1.5 text-sm font-semibold text-indigo-600 hover:text-white ring-2 ring-indigo-600 hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Delete request')}}
            </button>
        </div>
        <div v-if="buttons?.includes('delete_shift_notification')">
            <button @click="$emit('deleteNotification', true)" type="button" class="rounded-full bg-transparent px-3.5 py-1.5 text-sm font-semibold text-indigo-600 hover:text-white ring-2 ring-indigo-600 hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Ignore')}}
            </button>
        </div>
        <div v-if="buttons?.includes('showInTasks')">
            <button @click="$emit('showInTask', true)" type="button" class="rounded-full bg-transparent px-3.5 py-1.5 text-sm font-semibold text-indigo-600 hover:text-white ring-2 ring-indigo-600 hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('View in tasks')}}
            </button>
        </div>
        <div v-if="buttons?.includes('show_project')">
            <button @click="$emit('showProject', true)" type="button" class="rounded-full bg-transparent px-3.5 py-1.5 text-sm font-semibold text-indigo-600 hover:text-white ring-2 ring-indigo-600 hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ $t('Open Project')}}
            </button>
        </div>
    </div>

</template>

<script>
import {PencilAltIcon} from "@heroicons/vue/outline";
import {XIcon} from "@heroicons/vue/solid";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "NotificationButtons",
    mixins: [IconLib],
    props: ['buttons'],
    emits: [
        'openDeclineModal',
        'openEventEditAccept',
        'deleteEvent',
        'openProjectCalculation',
        'deleteVerificationRequest',
        'openEventWithoutRoomModal',
        'openDialogModal',
        'deleteNotification',
        'openProject',
        'showInTask',
        'showProject'
    ],
    components: {
        DeclineEventModal,
        PencilAltIcon,
        XIcon
    }
}
</script>
