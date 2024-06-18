<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
            <div class="mx-4">
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow flex items-center headline1">
                            Details
                            <IconLock stroke-width="1.5" class="mr-2 ml-4 flex items-center mt-0.5" v-if="cell.column.is_locked"/>
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
                    <!-- Calculate Tab -->
                    <div v-if="isCalculateTab">
                        <div v-if="this.cell.calculations?.length > 0"
                             v-for="(calculation,index) in this.cell.calculations">
                            <div @mouseover="!cell.column.is_locked ? calculationHovered = calculation.id : null"
                                 @mouseout="calculationHovered = null">
                                <div class="h-1.5 my-2 bg-silverGray"/>
                                <div class="flex space-x-4 mb-3">
                                    <div class="w-1/2">
                                        <input type="text"
                                               v-model="calculation.name"
                                               :placeholder="$t('Name')"
                                               :disabled="cell.column.is_locked"
                                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <div class="w-1/2">
                                        <input type="number" @focusout="this.refreshSumKey++"
                                               v-model="calculation.value"
                                               :disabled="cell.column.is_locked"
                                               :placeholder="$t('Value')"
                                               class="h-12 sDark text-right inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                </div>
                                <textarea :placeholder="$t('Comment')"
                                          v-model="calculation.description"
                                          :disabled="cell.column.is_locked"
                                          rows="4"
                                          class="inputMain resize-none xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <!-- add new Buttons with hover effect -->
                            <div class="grid grid-cols-2 group h-2">
                                <div class="hidden group-hover:block col-span-1">
                                    <div class="w-full relative">
                                        <div @click="addCalculation(cell.id, calculation.position)"
                                             v-if="!cell.column.is_locked"
                                             class="cursor-pointer h-1 border-dashed border-t-2 border-indigo-500">
                                            <div class="flex flex-col justify-center absolute -top-7 left-1/2">
                                                <div class="uppercase text-indigo-500 text-xs font-semibold -ml-16">
                                                    {{ $t('Add below') }}
                                                </div>
                                                <div class="shadow-[0px_0px_5px_0px_#7f9cf5] rounded-full text-white bg-indigo-500 w-fit ">
                                                    <IconCirclePlus stroke-width="1.5" class="w-6 h-6 object-cover"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="hidden group-hover:block col-span-1">
                                    <div class="w-full relative">
                                        <div @click="deleteCalculationFromCell(calculation)"
                                             v-if="!cell.column.is_locked"
                                             class="cursor-pointer h-1 border-dashed border-t-2 border-red-500">
                                            <div class="flex flex-col justify-center absolute -top-7 left-1/2">
                                                <div class="uppercase text-red-500 text-xs font-semibold -ml-12">
                                                    {{ $t('Delete above') }}
                                                </div>
                                                <div
                                                    class="shadow-[0px_0px_5px_0px_#f56565] rounded-full text-white bg-red-300 w-fit ">
                                                    <IconCircleX stroke-width="1.5" class="w-6 h-6 object-cover"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- if no calculations are present -->
                        <div class="group h-2" v-show="this.cell.calculations?.length < 0">
                            <div class="hidden group-hover:block col-span-1">
                                <div class="w-full relative">
                                    <div @click="addCalculation(cell.id)" v-if="!cell.column.is_locked"
                                         class="cursor-pointer h-1 border-dashed border-t-2 border-indigo-500">
                                        <div class="flex flex-col justify-center absolute -top-7 left-1/2">
                                            <div class="uppercase text-indigo-500 text-xs font-semibold -ml-16">
                                                {{ $t('Add below') }}
                                            </div>
                                            <div class="shadow-[0px_0px_5px_0px_#7f9cf5] rounded-full text-white bg-indigo-500 w-fit ">
                                                <IconCirclePlus stroke-width="1.5" class="w-6 h-6 object-cover"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                            <FormButton
                                @click="saveCalculation()"
                                :text="$t('Save')"
                                :disabled="cell.column.is_locked"
                            ></FormButton>
                        </div>
                    </div>
                    <!-- Commentary Tab -->
                    <div v-if="isCommentTab">
                         <textarea
                             :placeholder="$t('What do I need to know about this item?')"
                             v-model="commentForm.description"
                             rows="4"
                             class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 inputMain pt-3 mb-8 placeholder-secondary w-full"
                         />
                        <div>
                            <div class="my-6" v-for="comment in this.cell.comments"
                                 @mouseover="commentHovered = comment.id"
                                 @mouseout="commentHovered = null">
                                <div class="flex justify-between">
                                    <div class="flex items-center">
                                        <NewUserToolTip :id="comment.id"
                                                        :user="comment.user"
                                                        :height="8"
                                                        :width="8"
                                        />
                                        <div class="ml-2 text-secondary"
                                             :class="commentHovered === comment.id ? 'text-primary':'text-secondary'">
                                            {{ formatDate(comment.created_at) }}
                                        </div>
                                    </div>
                                    <button
                                        v-show="commentHovered === comment.id && comment.user_id === $page.props.user.id"
                                        type="button"
                                        @click="deleteCommentFromCell(comment)">
                                        <span class="sr-only">{{ $t('Remove comment from project') }}</span>
                                        <IconCircleX stroke-width="1.5" class="ml-2 h-7 w-7 hover:text-error"/>
                                    </button>
                                </div>
                                <div class="mt-2 mr-14 subpixel-antialiased text-primary font-semibold">
                                    {{ comment.description }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <FormButton @click="addCommentToCell()" :text="$t('Save')"
                                        :disabled="this.commentForm.description === null && this.commentForm.description === ''"
                            ></FormButton>
                        </div>
                    </div>
                    <!-- Link Tab -->
                    <div v-if="isLinkTab">
                        <h2 class="xsLight mb-2 mt-4">
                            {{ $t('Keep track of your funding sources. You can add the value to the source.') }}
                        </h2>
                        <div class="flex items-center justify-start my-6">
                            <input v-model="isLinked" type="checkbox" :disabled="cell.column.is_locked"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[isLinked ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto text-sm">{{ $t('Link to funding source') }}</p>
                        </div>
                        <div v-if="isLinked" class="flex w-full">
                            <div class="flex w-full" v-if="!cell.column.is_locked">
                                <div class="relative w-full">
                                    <div class="w-full flex">
                                        <Listbox as="div" v-model="linkedType" id="linked_type">
                                            <ListboxButton class="inputMain w-12 h-10 cursor-pointer truncate flex p-2">
                                                <div class="flex-grow xsLight text-left subpixel-antialiased">
                                                    {{ linkedType.name }}
                                                </div>
                                                <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                            </ListboxButton>
                                            <ListboxOptions
                                                class="w-12 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                                <ListboxOption v-for="type in this.linkTypes"
                                                               class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                               :key="type.name"
                                                               :value="type"
                                                               v-slot="{ active, selected }">
                                                    <div :class="[selected ? 'text-white' : '']">
                                                        {{ type.name }}
                                                    </div>
                                                    <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                                               aria-hidden="true"/>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </Listbox>
                                        <input id="userSearch" v-model="moneySource_query" type="text"
                                               autocomplete="off"
                                               :placeholder="$t('Which funding source do you want to link the value to?')"
                                               class="h-10 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                        />
                                    </div>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100"
                                                leave-to-class="opacity-0">
                                        <div
                                            v-if="moneySource_search_results.length > 0 && moneySource_query.length > 0"
                                            class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                            <div class="border-gray-200">
                                                <div v-for="(moneySource, index) in moneySource_search_results"
                                                     :key="index"
                                                     class="flex items-center cursor-pointer">
                                                    <div class="flex-1 text-sm py-4">
                                                        <p @click="selectMoneySource(moneySource)"
                                                           class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                            {{ moneySource.name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                    <div class="flex xsDark mt-2">
                                        {{ $t('Linked with') }}:
                                        <div class="xsDark mx-2">
                                            {{ selectedMoneySource?.name }}
                                        </div>
                                        {{ $t('as') }}
                                        <div v-if="linkedType.type === 'EARNING'" class="xsDark mx-2">
                                            {{ $t('Revenue') }}
                                        </div>
                                        <div v-else class="xsDark mx-2">
                                            {{ $t('Expenses') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex xsDark" v-else>
                                {{ $t('Linked with') }}:
                                <div class="xsDark mx-2">
                                    {{ selectedMoneySource.name }}
                                </div>
                                {{ $t('as') }}
                                <div v-if="linkedType.type === 'EARNING'" class="xsDark mx-2">
                                    {{ $t('Revenue') }}
                                </div>
                                <div v-else class="xsDark mx-2">
                                    {{ $t('Expenses') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <FormButton
                                :text="$t('Save')"
                                @click="updateMoneySourceLink()"
                                :disabled="cell.column.is_locked || selectedMoneySource === null"
                            />
                        </div>
                    </div>
                </div>
            </div>
    </BaseModal>
    <ConfirmDeleteModal
        v-if="showConfirmCalculationModal"
        :title="$t('Save calculation')"
        :description="$t('Would you like to save your calculation? The previous figure in the budget table will be overwritten with the new figure irrevocably.')"
        :button="$t('Save')"
        :is_budget="true"
        @closed="closeConfirmCalculationModal()"
        @delete="saveAllCalculations()"
    />
</template>

<script>
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    RadioGroup,
    RadioGroupOption
} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {CheckIcon, ChevronDownIcon, PlusCircleIcon, XIcon} from '@heroicons/vue/outline';
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'CellDetailComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        ConfirmDeleteModal,
        ConfirmationComponent,
        ConfirmationModal,
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
            isLinked: this.cell.linked_money_source_id !== null,
            linkedType: this.cell.linked_type === 'EARNING' ?
                {
                    name: '+',
                    type: 'EARNING'
                } : {
                    name: '-',
                    type: 'COST'
                },
            linkTypes: [
                {
                    name: '+',
                    type: 'EARNING'
                },
                {
                    name: '-',
                    type: 'COST'
                }
            ],
            selectedMoneySource: this.cell.linked_money_source_id !== null ? this.moneySources.find(moneySource => moneySource.id === this.cell.linked_money_source_id) : null,
            isCalculateTab: this.openTab === 'calculation' || (this.cell.column.type === 'empty' && this.openTab !== 'comment' && this.openTab !== 'moneySource'),
            isCommentTab: this.openTab === 'comment' || this.cell.column.type !== 'empty' && this.openTab !== 'moneySource' && this.openTab !== 'calculation',
            isExcludeTab: false,
            isLinkTab: this.openTab === 'moneySource',
            hoveredBorder: false,
            refreshSumKey: 0,
            isExcluded: this.cell.commented,
            cellComment: null,
            commentHovered: null,
            calculationHovered: null,
            commentForm: useForm({
                description: '',
                cellId: this.cell.id
            }),
            moneySource_query: '',
            moneySource_search_results: [],
            showConfirmCalculationModal: false
        }
    },
    props: [
        'cell',
        'moneySources',
        'projectId',
        'openTab'
    ],
    emits: ['closed'],
    mounted() {
        if (this.cell.calculations.length === 0) {
            this.$inertia.post(
                route('project.budget.cell-calculation.add', this.cell.id),
                {},
                {
                    preserveScroll: true
                }
            )
        }
    },
    watch: {
        moneySource_query: {
            handler() {
                if (this.moneySource_query.length > 0) {
                    axios.get('/money_sources/search/money_source', {
                        params: {
                            query: this.moneySource_query,
                            projectId: this.projectId
                        }
                    }).then(response => {
                        this.moneySource_search_results = response.data.filter((moneySource) =>
                            moneySource.is_group === 0 || moneySource.is_group === false)
                    })
                }
            },
            deep: true
        },
    },
    computed: {
        tabs() {
            if (this.cell.column.type === 'empty') {
                return [
                    {name: this.$t('Calculation'), href: '#', current: this.isCalculateTab},
                    {name: this.$t('Comment'), href: '#', current: this.isCommentTab},
                    {name: this.$t('Linking'), href: '#', current: this.isLinkTab},
                ]
            } else {
                return [
                    {name: this.$t('Comment'), href: '#', current: this.isCommentTab},
                    {name: this.$t('Linking'), href: '#', current: this.isLinkTab},
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
        closeConfirmCalculationModal() {
            this.showConfirmCalculationModal = false;
            this.closeModal(true);
        },
        openModal() {
            if (this.cell.calculations.length === 0) {
                this.$inertia.post(
                    route('project.budget.cell-calculation.add', this.cell.id),
                    {},
                    {
                        preserveScroll: true
                    }
                )
            }
        },
        selectMoneySource(moneySource) {
            this.selectedMoneySource = moneySource;
            this.moneySource_query = '';
        },
        changeTab(selectedTab) {
            this.isCalculateTab = false;
            this.isCommentTab = false;
            this.isExcludeTab = false;
            this.isLinkTab = false;
            if (selectedTab.name === this.$t('Calculation')) {
                this.isCalculateTab = true;
            } else if (selectedTab.name === this.$t('Comment')) {
                this.isCommentTab = true;
            } else {
                this.isLinkTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        updateMoneySourceLink() {
            this.$inertia.patch(
                route('project.budget.cell-source.update'),
                {
                    cell_id: this.cell.id,
                    linked_type: this.isLinked ? this.linkedType.type : null,
                    money_source_id: this.isLinked ? this.selectedMoneySource.id : null
                },
                {
                    preserveScroll: true
                }
            );

            this.closeModal(true);
        },
        addCalculation(cellId, position) {
            if (this.cell.calculations?.length > 0) {
                this.$inertia.patch(
                    route('project.budget.cell-calculation.update'),
                    {
                        calculations: this.cell.calculations,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true
                    }
                );
            }
            this.$inertia.post(route('project.budget.cell-calculation.add', cellId), {
                position: position
            }, {
                preserveScroll: true
            })
        },
        saveCalculation() {
            let canClosed = false;
            this.cell.calculations.forEach((calculation) => {
                if (!canClosed) {
                    if (Number(calculation.value) !== 0) {
                        this.showConfirmCalculationModal = true;
                        canClosed = true;
                    }
                }
            })
            if (!canClosed) {
                this.saveAllCalculations()
            }
        },
        saveAllCalculations() {
            this.$inertia.patch(
                route('project.budget.cell-calculation.update'),
                {
                    calculations: this.cell.calculations,
                },
                {
                    preserveState: true, preserveScroll: true
                }
            );
            this.closeModal(true);
        },
        deleteCommentFromCell(comment) {
            this.$inertia.delete(
                route('project.budget.cell.comment.delete', {cellComment: comment.id}),
                {
                    preserveScroll: true
                }
            );
        },
        deleteCalculationFromCell(calculation) {
            this.$inertia.delete(
                route('project.budget.cell.calculation.delete', {cellCalculation: calculation.id}),
                {
                    preserveScroll: true
                }
            );
        },
        addCommentToCell() {
            if (!this.commentForm.description) {
                return;
            }
            this.commentForm.post(
                route('project.budget.cell.comment.store', {columnCell: this.cell.id}),
                {
                    preserveScroll: true
                }
            );
            this.commentForm.reset('description');
        },
        updateCommentedStatus() {
            this.$inertia.patch(
                route('project.budget.cell.commented', {columnCell: this.cell.id}),
                {
                    commented: this.isExcluded
                }, {
                    preserveScroll: true
                }
            );
            this.closeModal(true);
        }
    },
}
</script>
