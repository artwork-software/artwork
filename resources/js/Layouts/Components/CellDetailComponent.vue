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
                    <!-- Calculate Tab -->
                    <div v-if="isCalculateTab">
                        <div v-if="this.cell.calculations?.length > 0"
                             v-for="(calculation,index) in this.cell.calculations">
                            <div @mouseover="calculationHovered = calculation.id"
                                 @mouseout="calculationHovered = null">
                                <div class="h-1.5 my-2 bg-silverGray"
                                />
                                <div class="flex space-x-4 mb-3">
                                    <div class="w-1/2">
                                        <input type="text"
                                               v-model="calculation.name"
                                               placeholder="Name"
                                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <div class="w-1/2">
                                        <input type="text" @focusout="this.refreshSumKey++"
                                               v-model="calculation.value"
                                               placeholder="Wert"
                                               class="h-12 sDark text-right inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <button v-show="calculationHovered === calculation.id" type="button"
                                            @click="deleteCalculationFromCell(calculation)">
                                        <span class="sr-only">Rechnung von Zelle entfernen</span>
                                        <XCircleIcon class="ml-2 h-7 w-7 hover:text-error"/>
                                    </button>
                                </div>
                                <textarea placeholder="Kommentar"
                                          v-model="calculation.description"
                                          rows="4"
                                          class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                        </div>
                        <div @click="addCalculation(cell.id)"
                             class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                            <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                                Position
                                <PlusCircleIcon
                                    class="h-6 w-6 ml-5 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                            </div>
                        </div>
                        <div class="py-2 bg-silverGray flex justify-between mt-2">
                            <div class="ml-2 sDark">
                                SUM
                            </div>
                            <div class="mr-2 sDark">
                                {{ this.calculationSum }}
                            </div>
                        </div>
                        <div class="flex justify-center mt-6">
                            <AddButton @click="saveCalculation()" text="Speichern"
                                       class="text-sm ml-0 px-24 py-5 xsWhiteBold"></AddButton>
                        </div>
                    </div>
                    <!-- Commentary Tab -->
                    <div v-if="isCommentTab">
                         <textarea
                             placeholder="Was gibt es zu diesem Posten zu beachten?"
                             v-model="commentForm.description" rows="4"
                             class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 inputMain pt-3 mb-8 placeholder-secondary  w-full"/>
                        <div>

                            <div class="my-6" v-for="comment in this.cell.comments"
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
                                            @click="deleteCommentFromCell(comment)">
                                        <span class="sr-only">Kommentar von Projekt entfernen</span>
                                        <XCircleIcon class="ml-2 h-7 w-7 hover:text-error"/>
                                    </button>
                                </div>
                                <div class="mt-2 mr-14 subpixel-antialiased text-primary font-semibold">
                                    {{ comment.description }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <AddButton @click="addCommentToCell()" text="Speichern"
                                       :disabled="this.commentForm.description === null && this.commentForm.description === ''"
                                       :class="this.commentForm.description === null || this.commentForm.description === '' ? 'bg-secondary hover:bg-secondary' : ''"
                                       class="text-sm ml-0 px-24 py-5 xsWhiteBold"></AddButton>
                        </div>
                    </div>
                    <!-- Link Tab -->
                    <div v-if="isLinkTab">
                        <h2 class="xsLight mb-2 mt-8">
                            Behalte den Überblick über deine Finanzierungsquellen. Du kannst den Wert entweder zur
                            Quelle/-gruppe addieren oder subtrahieren.
                        </h2>
                        <div class="flex items-center justify-start my-6">
                            <input v-model="isLinked" type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[isLinked ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto text-sm"> Mit Finanzierungsquelle/-gruppe verlinken</p>
                        </div>
                        <div v-if="isLinked" class="flex w-full">
                            <Listbox as="div" v-model="linkedType" id="linked_type">
                                <ListboxButton class="inputMain w-12 h-10 cursor-pointer truncate flex p-2">
                                    <div class="flex-grow xsLight text-left subpixel-antialiased">
                                        {{ linkedType.name }}
                                    </div>
                                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <ListboxOptions class="w-12 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                    <ListboxOption v-for="type in linkTypes"
                                                   class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                   :key="type.name"
                                                   :value="type"
                                                   v-slot="{ active, selected }">
                                        <div :class="[selected ? 'text-white' : '']">
                                            {{ type.name }}
                                        </div>
                                        <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                            <Listbox as="div" v-model="selectedMoneySource" id="room" class="w-full">
                                <ListboxButton class="inputMain h-10 cursor-pointer w-11/12 truncate flex p-2">
                                    <div class="w-full xsLight text-left subpixel-antialiased"
                                         v-if="selectedMoneySource === null">
                                        Quelle wählen*
                                    </div>
                                    <div class="w-full xsDark text-left subpixel-antialiased" v-else>
                                        {{ selectedMoneySource.name }}
                                    </div>
                                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <ListboxOptions class="w-[74%] bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                    <ListboxOption v-for="moneySource in moneySources"
                                                   class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                   :key="moneySource.name"
                                                   :value="moneySource"
                                                   v-slot="{ active, selected }">
                                        <div :class="[selected ? 'text-white' : '']">
                                            {{ moneySource.name }}
                                        </div>
                                        <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                        </div>
                        <div class="flex justify-center">
                            <AddButton @click="updateMoneySourceLink()"
                                       class="mt-8 py-3 px-24 flex" text="Speichern"
                                       mode="modal"></AddButton>
                        </div>
                    </div>
                    <div v-if="isExcludeTab">
                        <h2 class="xsLight mb-2 mt-8">
                            Ausgeklammerte Posten werden nicht in das Projektbudget gerechnet. So kannst du zB. internes
                            Personal, virtuelle Kosten wie Eigenleistungen oä. aufführen, ohne dass diese Einfluss auf
                            das Projektbudget haben.
                        </h2>
                        <div class="flex items-center justify-start my-6">
                            <input v-model="isExcluded" type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[isExcluded ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto text-sm"> Ausklammern</p>
                        </div>
                        <div class="flex justify-center">
                            <AddButton @click="updateCommentedStatus()" text="Speichern"
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

const linkTypes = [
    {name: '+', type: 'EARNING'},
    {name: '-', type: 'COST'}
]

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
            isLinked: this.cell.linked_money_source_id !== null,
            linkedType: this.cell.linked_type === 'COST' ? linkTypes[1] : linkTypes[0],
            selectedMoneySource: this.cell.linked_money_source_id !== null ? this.moneySources.find(moneySource => moneySource.id === this.cell.linked_money_source_id) : null,
            linkTypes,
            isCalculateTab: this.cell.column.type === 'empty',
            isCommentTab: this.cell.column.type !== 'empty',
            isExcludeTab: false,
            isLinkTab: false,
            hoveredBorder: false,
            refreshSumKey: 0,
            isExcluded: this.cell.commented,
            cellComment: null,
            commentHovered: null,
            calculationHovered: null,
            commentForm: useForm({
                description: '',
                cellId: this.cell.id
            })
        }
    },

    props: ['cell', 'moneySources'],

    emits: ['closed'],

    watch: {},
    computed: {
        tabs() {
            if (this.cell.column.type === 'empty') {
                return [
                    {name: 'Kalkulation', href: '#', current: this.isCalculateTab},
                    {name: 'Kommentar', href: '#', current: this.isCommentTab},
                    {name: 'Ausklammern', href: '#', current: this.isExcludeTab},
                    {name: 'Verlinkung', href: '#', current: this.isLinkTab},
                ]
            } else {
                return [
                    {name: 'Kommentar', href: '#', current: this.isCommentTab},
                    {name: 'Verlinkung', href: '#', current: this.isLinkTab},
                ]
            }

        },
        calculationValues() {
            let calculations = this.cell.calculations;
            let values = []
            calculations?.forEach((calculation) => {
                values.push(calculation.value);
            })
            return values;
        },
        calculationSum() {
            this.refreshSumKey++;
            let sum = 0;
            this.calculationValues?.forEach((value) => {
                if (!isNaN(value) && value !== '') {
                    sum += parseInt(value);
                }
            })
            return sum;
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
            this.isCalculateTab = false;
            this.isCommentTab = false;
            this.isExcludeTab = false;
            this.isLinkTab = false;
            if (selectedTab.name === 'Kalkulation') {
                this.isCalculateTab = true;
            } else if (selectedTab.name === 'Kommentar') {
                this.isCommentTab = true;
            } else if (selectedTab.name === 'Ausklammern') {
                this.isExcludeTab = true;
            } else {
                this.isLinkTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        updateMoneySourceLink() {
            if (this.isLinked) {
                this.$inertia.patch(route('project.budget.cell-source.update'), {
                    cell_id: this.cell.id,
                    linked_type: this.linkedType.type,
                    money_source_id: this.selectedMoneySource.id
                }, {
                    preserveScroll: true
                });
            } else {
                this.$inertia.patch(route('project.budget.cell-source.update'), {
                    cell_id: this.cell.id,
                    linked_type: null,
                    money_source_id: null,
                }, {
                    preserveScroll: true
                });
            }

            this.closeModal(true);
        },
        addCalculation(cellId) {
            this.$inertia.post(route('project.budget.cell-calculation.add', cellId), {}, {
                preserveScroll: true
            })
        },
        saveCalculation() {
            this.$inertia.patch(route('project.budget.cell-calculation.update'), {
                calculations: this.cell.calculations,
            }, {preserveState: true, preserveScroll: true});
            this.closeModal(true);
        },
        deleteCommentFromCell(comment) {
            this.$inertia.delete(route('project.budget.cell.comment.delete', {cellComment: comment.id}), {
                preserveState: true,
                preserveScroll: true
            });
        },
        deleteCalculationFromCell(calculation) {
            this.$inertia.delete(route('project.budget.cell.calculation.delete', {cellCalculation: calculation.id}), {
                preserveState: true,
                preserveScroll: true
            });
        },
        addCommentToCell() {
            this.commentForm.post(route('project.budget.cell.comment.store', {columnCell: this.cell.id}), {
                preserveState: true,
                preserveScroll: true
            });
            this.commentForm.reset('description');
        },
        updateCommentedStatus() {
            this.$inertia.patch(route('project.budget.cell.commented', {columnCell: this.cell.id}), {
                commented: this.isExcluded
            }, {
                preserveState: true,
                preserveScroll: true
            });
            this.closeModal(true);
        }
    },
}
</script>

<style scoped></style>
