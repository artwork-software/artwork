<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text">
                            {{ $t('Details')}}
                        </div>
                    </h1>
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-border-subtle">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-accent-600 text-accent-600' : 'border-transparent text-text-subtle hover:text-text-muted hover:border-border', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
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
                             class="resize-none focus:outline-none focus:ring-0 focus:border-text-subtle focus:border-1 border border-border pt-3 mb-8 placeholder-text-subtle  w-full"/>
                        <div>

                            <div class="my-6" v-for="comment in this.row.comments"
                                 @mouseover="commentHovered = comment.id"
                                 @mouseout="commentHovered = null">
                                <div class="flex justify-between">
                                    <div class="flex items-center">
                                        <UserPopoverTooltip :id="comment.id" :user="comment.user" :height="8"
                                                        :width="8"></UserPopoverTooltip>
                                        <div class="ml-2 text-text-subtle"
                                             :class="commentHovered === comment.id ? 'text-text':'text-text-subtle'">
                                            {{ formatDate(comment.created_at) }}
                                        </div>
                                    </div>
                                    <button v-show="commentHovered === comment.id" type="button"
                                            @click="deleteCommentFromRow(comment)">
                                        <span class="sr-only">Kommentar von Zeile entfernen</span>
                                        <IconCircleX class="ml-2 h-7 w-7 hover:text-danger"/>
                                    </button>
                                </div>
                                <div class="mt-2 mr-14 subpixel-antialiased text-text font-semibold">
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
                        <h2 class="text-sm/5 font-bold text-text-subtle mb-2 mt-4">
                            {{$t('Excluded items are not included in the project budget. For example, you can list internal personnel, virtual costs such as internal services, etc. without these having an impact on the project budget.')}}
                        </h2>
                        <div class="flex items-center justify-start my-6">
                            <input v-model="isExcluded" type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-border"/>
                            <p :class="[isExcluded ? 'text-sm/5 font-semibold text-text' : 'text-sm/5 font-bold text-text-subtle']"
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
import {IconCheck, IconChevronDown, IconCirclePlus, IconCircleX, IconX} from "@tabler/icons-vue";

import {Listbox, ListboxButton, ListboxOption, ListboxOptions, RadioGroup, RadioGroupOption} from "@headlessui/vue";

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
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
        IconX,
        IconCheck,
        IconChevronDown,
        IconCirclePlus,
        IconCircleX
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
