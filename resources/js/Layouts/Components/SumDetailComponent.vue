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
                        <div class="flex-grow flex items-center headline1">
                            Details
                        </div>
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
                             v-model="commentForm.comment" rows="4"
                             class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 inputMain pt-3 mb-8 placeholder-secondary  w-full"/>
                        <div>

                            <div class="my-6" v-for="comment in selectedSumDetail.comments"
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
                                    <button v-show="commentHovered === comment.id && comment.user_id === $page.props.user.id" type="button"
                                            @click="deleteCommentFromCell(comment)">
                                        <span class="sr-only">Kommentar von Projekt entfernen</span>
                                        <XCircleIcon class="ml-2 h-7 w-7 hover:text-error"/>
                                    </button>
                                </div>
                                <div class="mt-2 mr-14 subpixel-antialiased text-primary font-semibold">
                                    {{ comment.comment }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <AddButton @click="addCommentToCell()" text="Speichern"
                                       :disabled="commentForm.comment === null && commentForm.comment === ''"
                                       :class="commentForm.comment === null || commentForm.comment === '' ? 'bg-secondary hover:bg-secondary' : ''"
                                       class="text-sm ml-0 px-24 py-5 xsWhiteBold"></AddButton>
                        </div>
                    </div>
                    <!-- Link Tab -->
                    <div v-if="isLinkTab">
                        <h2 class="xsLight mb-2 mt-4">
                            Behalte den Überblick über deine Finanzierungsquellen. Du kannst den Wert zur
                            Quelle entweder addieren oder subtrahieren.
                        </h2>
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
    name: 'SumDetailComponent',

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
            isCommentTab: true,
            isLinkTab: false,
            cellComment: null,
            commentHovered: null,
            calculationHovered: null,
            commentForm: useForm({
                comment: '',
                commentable_id: this.selectedSumDetail.id,
                commentable_type: this.selectedSumDetail.class
            }),
        }
    },

    props: ['selectedSumDetail'],

    emits: ['closed'],

    computed: {
        tabs() {
            return [
                {name: 'Kommentar', href: '#', current: this.isCommentTab},
                {name: 'Verlinkung', href: '#', current: this.isLinkTab},
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
        changeTab(selectedTab) {
            this.isCommentTab = false;
            this.isLinkTab = false;
            if (selectedTab.name === 'Kommentar') {
                this.isCommentTab = true;
            } else {
                this.isLinkTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        deleteCommentFromCell(comment) {

            this.$inertia.delete(route('sum.comments.delete', {comment: comment.id}), {
                preserveState: true,
                preserveScroll: true
            });
        },
        addCommentToCell() {
            if(!this.commentForm.comment){
                return;
            }
            this.commentForm.post(route('sum.comments.store'), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.commentForm.reset();
                }
            });
        },
    },
}
</script>
