<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_project_history.svg">
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    {{ $t('Course of funding sources')}}
                </div>
                <div class="text-secondary subpixel-antialiased">
                    {{ $t('Here you can see what was changed by whom and when.')}}
                </div>

                <div class="flex w-full flex-wrap mt-4 max-h-96">
                    <div v-for="(historyItem,index) in history">
                        <div class="flex w-full my-1">
                            <div class="flex w-full ">
                                    <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                        {{ historyItem.created_at }}:
                                    </span>
                                <NewUserToolTip :height="7"
                                                :width="7"
                                                :user="historyItem.changes[0].changed_by"
                                                :id="index"/>
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
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: 'ProjectHistoryComponent',
    components: {
        BaseModal,
        NewUserToolTip,
        JetDialogModal,
        XIcon,
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
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
