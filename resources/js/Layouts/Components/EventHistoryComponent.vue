<template>
    <jet-dialog-modal :show="true" @close="closeEventHistoryModal(false)">
        <template #content>
            <img alt="Terminverlauf" src="/Svgs/Overlays/illu_event_history.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    Terminverlauf
                </div>
                <XIcon @click="closeEventHistoryModal(false)"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary subpixel-antialiased">
                    Hier kannst du nachvollziehen, was von wem wann geändert wurde.
                </div>
                <div class="flex w-full flex-wrap mt-4 overflow-y-auto max-h-96">
                    <div class="flex w-full my-1" v-for="historyItem in this.event_history">
                            <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                {{ historyItem.created_at }}:
                            </span>
                        <div class="flex w-full">
                            <img v-if="historyItem.changes[0].changed_by"
                                 :data-tooltip-target="historyItem.changes[0].changed_by?.id"
                                 :src="historyItem.changes[0].changed_by?.profile_photo_url"
                                 :alt="historyItem.changes[0].changed_by?.first_name"
                                 class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                            <UserTooltip v-if="historyItem.changes[0].changed_by"
                                         :user="historyItem.changes[0].changed_by"/>
                            <div v-else class="xsLight ml-3">
                                gelöschte Nutzer:in
                            </div>
                            <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto">
                                {{ historyItem.changes[0].message }}
                            </div>
                        </div>
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
import UserTooltip from "@/Layouts/Components/UserTooltip";

export default {
    name: 'RoomHistoryComponent',
    components: {
        JetDialogModal,
        XIcon,
        AddButton,
        CheckIcon,
        UserTooltip
    },
    props: ['event_history'],
    emits: ['closed'],
    methods: {
        closeEventHistoryModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
