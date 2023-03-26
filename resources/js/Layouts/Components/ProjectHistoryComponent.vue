<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_history.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    Projektverlauf
                </div>
                <XIcon @click="closeModal()"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary subpixel-antialiased">
                    Hier kannst du nachvollziehen, was von wem wann geändert wurde.
                </div>

                <div class="mb-4">
                    <div class="hidden sm:block">
                        <div class="border-gray-200">
                            <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                 aria-label="Tabs">
                                <a @click="changeHistoryTabs(tab)" v-for="tab in historyTabs" href="#"
                                   :key="tab.name"
                                   :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                   :aria-current="tab.current ? 'page' : undefined">
                                    {{ tab.name }}
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="flex w-full flex-wrap mt-4 max-h-96 overflow-y-auto" v-if="showProjectHistoryTab">
                    <div v-for="(historyItem,index) in project_history">
                        <div class="flex w-full my-1" v-if="historyItem?.changes !== null && historyItem.changes[0]?.type === 'project' || historyItem.changes[0]?.type === 'public_changes'">
                            <div class="flex w-full ">
                                    <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                        {{ historyItem.created_at }}:
                                    </span>
                                <NewUserToolTip :height="7" :width="7" v-if="historyItem.changes[0].changed_by"
                                                :user="historyItem.changes[0].changed_by" :id="index"/>
                                <div v-else class="xsLight ml-3">
                                    gelöschte Nutzer:in
                                </div>
                                <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto w-96">
                                    {{ historyItem.changes[0].message }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex w-full flex-wrap mt-4 overflow-y-auto max-h-96" v-if="showBudgetHistoryTab">
                    <div class="flex w-full my-1" v-for="historyItem in project_history">
                        <div v-if="historyItem.changes[0].type === 'budget'" class="flex w-full ">
                            <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                {{ historyItem.created_at }}:
                            </span>
                            <NewUserToolTip :height="7" :width="7" v-if="historyItem.changes[0].changed_by"
                                            :user="historyItem.changes[0].changed_by" :id="index"/>
                            <div v-else class="xsLight ml-3">
                                gelöschte Nutzer:in
                            </div>
                            <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto w-96">
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
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";

export default {
    name: 'ProjectHistoryComponent',
    components: {
        NewUserToolTip,
        JetDialogModal,
        XIcon,
        AddButton,
        CheckIcon
    },
    props: ['project_history','access_budget'],
    emits: ['closed'],
    computed: {
        historyTabs() {
            if (this.$page.props.is_admin || this.access_budget?.includes(this.$page.props.user.id)) {
                return [
                    {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
                    {name: 'Budget', href: '#', current: this.showBudgetHistoryTab},
                ]
            } else {
                return [
                    {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
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
            if (selectedTab.name === 'Projekt') {
                this.showProjectHistoryTab = true;
            } else {
                this.showBudgetHistoryTab = true;
            }
        },
    },
}
</script>

<style scoped></style>
