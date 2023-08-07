<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_history.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    Schichtverlauf
                </div>
                <XIcon @click="closeModal()"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary subpixel-antialiased">
                    Hier kannst du nachvollziehen, was von wem wann geändert wurde.
                </div>


                <div class="flex w-full flex-wrap mt-4 max-h-96">
                    <div v-for="(historyItem , index) in history">
                        <div class="flex w-full my-1" v-if="historyItem?.changes !== null">
                            <div class="flex w-full ">
                                    <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                        {{ dayjs(historyItem.created_at).format('DD.MM.YYYY HH:mm') }}
                                    </span>
                                <NewUserToolTip :height="7" :width="7" v-if="JSON.parse(historyItem.changes, null, 2)[0].changed_by"
                                                :user="JSON.parse(historyItem.changes, null, 2)[0].changed_by" :id="index"/>
                                <div v-else class="xsLight ml-3">
                                    gelöschte Nutzer:in
                                </div>
                                <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto w-96">
                                    <p>
                                        {{ JSON.parse(historyItem.changes, null, 2)[0].event_title }}
                                    </p>
                                    <p>
                                        {{ JSON.parse(historyItem.changes, null, 2)[0].message }}
                                    </p>
                                </div>
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
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import Permissions from "@/mixins/Permissions.vue";
import dayjs from "dayjs";

export default {
    name: 'ShiftHistoryModal',
    mixins: [Permissions],
    components: {
        NewUserToolTip,
        JetDialogModal,
        XIcon,
        AddButton,
        CheckIcon
    },
    props: ['history'],
    emits: ['closed'],
    computed: {

    },
    data() {
        return {
        }
    },
    methods: {
        dayjs,
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
