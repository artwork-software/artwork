<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Details" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            Details
                        </div>
                        {{row}}
                    </h1>
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-gray-200">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
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
                                        <NewUserToolTip :id="comment.id" :user="comment.user" :height="8"
                                                        :width="8"></NewUserToolTip>
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
                            <AddButton @click="addCommentToRow()" text="Speichern"
                                       :disabled="this.commentForm.description === null && this.commentForm.description === ''"
                                       :class="this.commentForm.description === null || this.commentForm.description === '' ? 'bg-secondary hover:bg-secondary' : ''"
                                       class="text-sm ml-0 px-24 py-5 xsWhiteBold"></AddButton>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions, RadioGroup, RadioGroupOption} from "@headlessui/vue";

import JetDialogModal from "@/Jetstream/DialogModal";
import {CheckIcon, ChevronDownIcon, PlusCircleIcon, XIcon} from '@heroicons/vue/outline';
import AddButton from "@/Layouts/Components/AddButton.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/inertia-vue3";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";



export default {
    name: 'CellDetailComponent',

    components: {
        NewUserToolTip,
        UserTooltip,
        AddButton,
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
            isCommentTab: false,
            isExcludeTab: false,
            hoveredBorder: false,
            cellComment: null,
            commentHovered: null,
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
                {name: 'Kommentar', href: '#', current: this.isCommentTab},
                {name: 'Ausklammern', href: '#', current: this.isExcludeTab},
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
            if (selectedTab.name === 'Kommentar') {
                this.isCommentTab = true;
            } else if (selectedTab.name === 'Ausklammern') {
                this.isExcludeTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        deleteCommentFromRow(comment) {
            //TODO HIER NOCH NEUE ROUTE FÜR ROW COMMENTS
            this.$inertia.delete(route('project.budget.row.comment.delete', {rowComment: comment.id}), {
                preserveState: true,
                preserveScroll: true
            });
        },
        addCommentToRow() {
            //TODO HIER NOCH NEUE ROUTE FÜR ROW COMMENTS#
            this.commentForm.post(route('project.budget.row.comment.store', { row: this.row.id }), {
                preserveState: true,
                preserveScroll: true
            });
            this.commentForm.reset('description');
        },
    },
}
</script>

<style scoped></style>
