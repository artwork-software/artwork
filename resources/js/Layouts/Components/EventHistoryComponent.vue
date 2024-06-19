<template>
    <BaseModal @closed="closeEventHistoryModal" v-if="true" modal-image="/Svgs/Overlays/illu_event_history.svg">
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    {{$t('Event process')}}
                </div>
                <div class="text-secondary subpixel-antialiased">
                    {{  $t('Here you can see what was changed by whom and when.') }}
                </div>
                <div class="flex w-full flex-wrap mt-4 ">
                    <div class="flex w-full my-1" v-for="(historyItem,index) in this.event_history">
                            <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                {{ historyItem.created_at }}:
                            </span>
                        <div class="flex w-full">
                            <UserPopoverTooltip :height="7" :width="7" v-if="historyItem.changes[0].changed_by"
                                            :user="historyItem.changes[0].changed_by" :id="index"/>
                            <div v-else class="xsLight ml-3">
                                {{$t('deleted User')}}
                            </div>
                            <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto">
                                {{
                                    $t(
                                        historyItem.changes[0].translationKey,
                                        historyItem.changes[0].translationKeyPlaceholderValues
                                    )
                                }}
                            </div>
                        </div>
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
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'RoomHistoryComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        UserPopoverTooltip,
        NewUserToolTip,
        JetDialogModal,
        XIcon,
        CheckIcon
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
