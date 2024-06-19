<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_project_history.svg">
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    {{ $t('Project process') }}
                </div>
                <div class="text-secondary subpixel-antialiased">
                    {{ $t('Here you can see what was changed by whom and when.') }}
                </div>
                <div class="mb-4">
                    <div class="hidden sm:block">
                        <div class="border-gray-200">
                            <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                 aria-label="Tabs">
                                <a @click="changeHistoryTabs(tab)" v-for="tab in historyTabs" href="#"
                                   :key="tab.name"
                                   :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold']"
                                   :aria-current="tab.current ? 'page' : undefined">
                                    {{ tab.name }}
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="flex  w-full flex-wrap mt-4 max-h-96 overflow-x-scroll" v-if="showProjectHistoryTab">
                    <div v-for="(historyItem,index) in project_history">
                        <div class="flex w-full my-1" v-if="historyItem?.changes !== null && historyItem.changes[0]?.type === 'project' || historyItem.changes[0]?.type === 'public_changes'">
                            <div class="flex w-full">
                                    <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                        {{ historyItem.created_at }}:
                                    </span>
                                <UserPopoverTooltip v-if="historyItem.changes[0].changed_by" :user="historyItem.changes[0].changed_by" :id="index" height="7" width="7"/>
                                <div v-else class="xsLight ml-3">
                                    {{ $t('deleted User') }}
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
                <div class="flex w-full flex-wrap mt-4 max-h-96 overflow-x-scroll" v-if="showBudgetHistoryTab">
                    <div class="flex w-full my-1" v-for="historyItem in project_history">
                        <div v-if="historyItem.changes[0].type === 'budget'" class="flex w-full ">
                            <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                {{ historyItem.created_at }}:
                            </span>
                            <UserPopoverTooltip v-if="historyItem.changes[0].changed_by" :user="historyItem.changes[0].changed_by" :id="index" height="7" width="7"/>
                            <div v-else class="xsLight ml-3">
                                {{ $t('deleted User') }}
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
        </BaseModal>
</template>

<script>
import 'vue-cal/dist/vuecal.css'
import {XIcon} from '@heroicons/vue/outline';
import {CheckIcon} from "@heroicons/vue/solid";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'ProjectHistoryComponent',
    mixins: [Permissions],
    components: {
        BaseModal,
        UserPopoverTooltip,
        NewUserToolTip,
        XIcon,
        CheckIcon
    },
    props: ['project_history','access_budget'],
    emits: ['closed'],
    computed: {
        historyTabs() {
            if (this.hasAdminRole() || this.access_budget?.includes(this.$page.props.user.id)) {
                return [
                    {name: this.$t('Project'), href: '#', current: this.showProjectHistoryTab},
                    {name: this.$t('Budget'), href: '#', current: this.showBudgetHistoryTab},
                ]
            } else {
                return [
                    {name: this.$t('Project'), href: '#', current: this.showProjectHistoryTab},
                ]
            }
        },
    },
    data() {
        return {
            showProjectHistoryTab: true,
            showBudgetHistoryTab: false,
        }
    },
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        changeHistoryTabs(selectedTab) {
            this.showProjectHistoryTab = false;
            this.showBudgetHistoryTab = false;
            if (selectedTab.name === this.$t('Project')) {
                this.showProjectHistoryTab = true;
            } else {
                this.showBudgetHistoryTab = true;
            }
        },
    },
}
</script>
