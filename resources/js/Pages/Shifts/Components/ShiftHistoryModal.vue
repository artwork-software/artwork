<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_project_history.svg">
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    {{$t('Shift history')}}
                </div>
                <div class="text-secondary subpixel-antialiased">
                    {{$t('Here you can see what was changed by whom and when.')}}
                </div>
                <div class="flex w-full flex-wrap mt-4 max-h-96">
                    <div v-for="(historyItem , index) in history">
                        <div class="flex w-full my-1" v-if="historyItem?.changes !== null">
                            <div class="flex w-full ">
                                    <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                        {{ historyItem.created_at }}
                                    </span>
                                <UserPopoverTooltip :height="7" :width="7" v-if="historyItem.changes[0].changed_by"
                                                :user="historyItem.changes[0].changed_by" :id="index"/>
                                <div v-else class="xsLight ml-3">
                                    {{ $t('deleted User')}}
                                </div>
                                <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto w-96">
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
import dayjs from "dayjs";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'ShiftHistoryModal',
    mixins: [Permissions],
    components: {
        BaseModal,
        UserPopoverTooltip,
        NewUserToolTip,
        JetDialogModal,
        XIcon,
        CheckIcon
    },
    props: ['history'],
    emits: ['closed'],
    computed: {
        // order history by date
        history() {
            return this.history.sort((a, b) => {
                return dayjs(b.created_at).unix() - dayjs(a.created_at).unix()
            })
        }
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
