<template>
    <div class="w-full flex justify-start gap-3 mt-3">
        <div v-if="buttons?.includes('accept')">
            <button type="button" class="rounded-full bg-indigo-600 p-2 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <PencilAltIcon class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
        <div v-if="buttons?.includes('decline')">
            <button @click="$emit('openDeclineModal', true)" type="button" class="rounded-full bg-red-600 p-2 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                <XIcon class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
        <div v-if="buttons?.includes('change_room')">
            <button type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Raum ändern
            </button>
        </div>
        <div v-if="buttons?.includes('change_request')">
            <button type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Anfrage ändern
            </button>
        </div>
        <div v-if="buttons?.includes('event_delete')">
            <button type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Termin löschen
            </button>
        </div>
        <div v-if="buttons?.includes('answer')">
            <button type="button" class="rounded-full bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Antworten
            </button>
        </div>
    </div>
    <div class="hidden">
        <pre>
            {{ buttons }}
        </pre>
    </div>

</template>

<script>
import {PencilAltIcon} from "@heroicons/vue/outline";
import {XIcon} from "@heroicons/vue/solid";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";

export default {
    name: "NotificationButtons",
    props: ['buttons'],
    emits: ['openDeclineModal'],
    components: {
        DeclineEventModal,
        PencilAltIcon, XIcon
    }
}
</script>

<style scoped>

</style>
