<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            {{ $t('Details')}}
                        </div>
                    </h1>
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-gray-200">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                       :aria-current="tab.current ? 'page' : undefined">
                                        {{ tab.name }}
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <!-- Commentary Tab -->
                    <div v-if="isCommentTab">
                         <textarea
                             placeholder="Was gibt es zu diesem Posten zu beachten?"
                             v-model="commentForm.description" rows="4"
                             class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 inputMain pt-3 mb-8 placeholder-secondary  w-full"/>
                        <div>

                            <div class="my-6" v-for="comment in this.row.comments"
                                 @mouseover="commentHovered = comment.id"
                                 @mouseout="commentHovered = null">
                                <div class="flex justify-between">
                                    <div class="flex items-center">
                                        <UserPopoverTooltip :id="comment.id" :user="comment.user" :height="8"
                                                        :width="8"></UserPopoverTooltip>
                                        <div class="ml-2 text-secondary"
                                             :class="commentHovered === comment.id ? 'text-primary':'text-secondary'">
                                            {{ formatDate(comment.created_at) }}
                                        </div>
                                    </div>
                                    <button v-show="commentHovered === comment.id" type="button"
                                            @click="deleteCommentFromRow(comment)">
                                        <span class="sr-only">Kommentar von Zeile entfernen</span>
                                        <XCircleIcon class="ml-2 h-7 w-7 hover:text-error"/>
                                    </button>
                                </div>
                                <div class="mt-2 mr-14 subpixel-antialiased text-primary font-semibold">
                                    {{ comment.description }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <FormButton @click="addCommentToRow()" text="Speichern"
                                       :disabled="this.commentForm.description === null && this.commentForm.description === ''"
                            ></FormButton>
                        </div>
                    </div>
                    <div v-if="isExcludeTab">
                        <h2 class="xsLight mb-2 mt-4">
                            {{$t('Excluded items are not included in the project budget. For example, you can list internal personnel, virtual costs such as internal services, etc. without these having an impact on the project budget.')}}
                        </h2>
                        <div class="flex items-center justify-start my-6">
                            <input v-model="isExcluded" type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[isExcluded ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto text-sm"> {{$t('Exclude')}}</p>
                        </div>
                        <div class="flex justify-center">
                            <FormButton @click="updateCommentedStatus()" :text="$t('Save')"
                            ></FormButton>
                        </div>
                    </div>
                </div>
            </div>
    </BaseModal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions, RadioGroup, RadioGroupOption} from "@headlessui/vue";

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {CheckIcon, ChevronDownIcon, PlusCircleIcon, XIcon} from '@heroicons/vue/outline';
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";


export default {
    name: 'CellDetailComponent',
    mixins: [Permissions],
    components: {
        BaseModal,
        FormButton,
        UserPopoverTooltip,
        NewUserToolTip,
        UserTooltip,
        ListboxOptions,
        ListboxOption,
        ListboxButton,
        Listbox,
        RadioGroupOption,
        RadioGroup,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        PlusCircleIcon,
        XCircleIcon
    },

    data() {
        return {
            isCommentTab: true,
            isExcludeTab: false,
            hoveredBorder: false,
            cellComment: null,
            commentHovered: null,
            isExcluded: this.row?.commented,
            commentForm: useForm({
                description: '',
                rowId: this.row.id
            })
        }
    },

    props: ['row', 'moneySources'],

    emits: ['closed'],

    watch: {},
    computed: {
        tabs() {
            return [
                {name: this.$t('Comment'), href: '#', current: this.isCommentTab},
                {name: this.$t('Exclude'), href: '#', current: this.isExcludeTab},
            ]
        },
    },

    methods: {
        formatDate(date) {
            const dateFormate = new Date(date);
            return dateFormate.toLocaleString('de-de', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        openModal() {
        },
        changeTab(selectedTab) {
            this.isCommentTab = false;
            this.isExcludeTab = false;
            if (selectedTab.name === this.$t('Comment')) {
                this.isCommentTab = true;
            } else if (selectedTab.name === this.$t('Exclude')) {
                this.isExcludeTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        deleteCommentFromRow(comment) {
            this.$inertia.delete(route('project.budget.row.comment.delete', {rowComment: comment.id}), {
                preserveState: true,
                preserveScroll: true
            });
        },
        addCommentToRow() {
            this.commentForm.post(route('project.budget.row.comment.store', {row: this.row.id}), {
                preserveState: true,
                preserveScroll: true
            });
            this.commentForm.reset('description');
        },
        updateCommentedStatus(){
            this.$inertia.patch(route('project.budget.row.commented',{row:this.row.id}), {
                commented: this.isExcluded
            },{preserveState: true,
                preserveScroll: true});
            this.closeModal(true);
        }
    },
}
</script>

<style scoped></style>
