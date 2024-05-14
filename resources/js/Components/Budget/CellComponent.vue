<script>
import {IconAdjustmentsAlt, IconCalculator, IconCirclePlus, IconLink, IconMessageDots} from "@tabler/icons-vue";
import {XIcon} from "@heroicons/vue/outline";
import SageDropCellElement from "@/Pages/Projects/Components/SageDropCellElement.vue";
import SageDragCellElement from "@/Pages/Projects/Components/SageDragCellElement.vue";
import {nextTick} from "vue";
import {useForm} from "@inertiajs/inertia-vue3";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [CurrencyFloatToStringFormatter, Permissions],
    name: "CellComponent",
    components: {
        SageDragCellElement,
        IconAdjustmentsAlt,
        IconLink,
        IconCalculator,
        SageDropCellElement,
        XIcon,
        IconCirclePlus,
        IconMessageDots
    },
    props: [
        'cell',
        'row',
        'index',
        'mainPosition',
        'subPosition',
        'table'
    ],
    emits: [
        'openCellDetailModal',
        'openSageAssignedDataModal'
    ],
    data(){
        return {
            updateCellForm: useForm({
                column_id: null,
                value: null,
                sub_position_row_id: null,
                is_verified: false
            }),
        }
    },
    methods: {
        isNumber(event, index) {
            if (index > 2 && !(new RegExp('^([0-9,])$')).test(event.key)) {
                event.preventDefault();
            }
        },
        checkIfRowHasSageData(row) {
            return row.cells.some(cell => cell.column.type === 'sage' && cell.sage_assigned_data.length > 0);
        },
        async handleCellClick(cell, type = '', index = null, row = null) {
            if ((index === 0 || index === 1) && this.checkIfRowHasSageData(row)) {
                return
            }
            if (type === 'comment') {
                this.$emit('openCellDetailModal', cell, 'comment');
            } else if (type === 'moneysource') {
                this.$emit('openCellDetailModal', cell, 'moneySource');
            } else if (type === 'calculation') {
                this.$emit('openCellDetailModal', cell, 'calculation');
            } else if (type === 'sageAssignedData' || cell.column.type === 'sage') {
                this.$emit('openSageAssignedDataModal', cell);
            } else if (cell.calculations_count > 0) {
                this.$emit('openCellDetailModal', cell, 'calculation')
            } else {
                //if already a cell is clicked and another one is also clicked do nothing
                /*if (this.alreadyCellClicked && cell.clicked !== true) {
                    return;
                }*/

                cell.clicked = !cell.clicked

                if (cell.clicked) {
                    //this.alreadyCellClicked = true;
                    //this.editedCellOriginalValue = cell.value;

                    await nextTick()

                    this.$refs[`cell-${cell.id}`][0].select();
                }
            }
        },
        handleBudgetManagementSearch(index, cell, is_account_for_revenue) {
            if (cell.searchValue === '') {
                //return if search input is emptied, reset search results
                cell.accountSearchResults = null;
                cell.costUnitSearchResults = null;
                return;
            }
            if (index === 0) {
                axios.get(
                    route('budget-settings.account-management.search-accounts'),
                    {
                        params: {
                            search: cell.searchValue,
                            is_account_for_revenue: is_account_for_revenue
                        }
                    }
                ).then((response) => cell.accountSearchResults = response.data);
            } else {
                axios.get(
                    route('budget-settings.account-management.search-cost-units'),
                    {
                        params: {
                            search: cell.searchValue
                        }
                    }
                ).then(response => cell.costUnitSearchResults = response.data);
            }
        },
        handleBudgetManagementSearchSelect(index, cell, value, mainPositionIsVerified, subPositionIsVerified) {
            if (index === 0) {
                cell.accountSearchResults = null;
            } else {
                cell.costUnitSearchResults = null;
            }

            cell.value = value;

            this.updateCellValue(cell, mainPositionIsVerified, subPositionIsVerified);
        },
        handleBudgetManagementSearchCancel(cell) {
            cell.clicked = false;
            cell.searchValue = '';
            cell.accountSearchResults = null;
            cell.costUnitSearchResults = null;
            this.alreadyCellClicked = false;
            this.editedCellOriginalValue = null;
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        updateCellValue(cell, mainPositionVerified, subPositionVerified) {


            let onFinish = () => {
                cell.clicked = false;
                //this.alreadyCellClicked = false;
                //this.editedCellOriginalValue = null;
            };

            /*if (cell.value === this.editedCellOriginalValue) {
                onFinish();
                return;
            }*/

            if (cell.value === null || cell.value === '') {
                cell.value = 0;
            }

            this.updateCellForm.column_id = cell.column.id;
            this.updateCellForm.value = cell.value;
            this.updateCellForm.sub_position_row_id = cell.sub_position_row_id;
            this.updateCellForm.is_verified = mainPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED';
            //
            this.updateCellForm.patch(route('project.budget.cell.update'), {
                preserveState: true,
                preserveScroll: true,
                onFinish: onFinish
            });
        },
    }
}
</script>

<template>
    <div v-if="(index === 0 || index === 1) && this.$page.props.budgetAccountManagementGlobal">
        <div :class="[row.commented || cell.commented || cell.column.commented ? 'xsLight' : '', index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end', cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']" class="my-4 h-6 flex items-center" v-if="!cell.clicked">
            <div class=" flex items-center">
                <div :class="cell.value === '' ? 'w-6 cursor-pointer h-6' : ''" @click="this.handleCellClick(cell, '', index, row)">
                    {{ cell.value }}
                </div>
            </div>
        </div>
        <div :class="[row.commented || cell.commented || cell.column.commented ? 'xsLight' : '', index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end', cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']"
             class="my-4 h-6 flex items-center" v-else>
            <div class="flex flex-row items-center relative">
                <input v-model="cell.searchValue" :placeholder="cell.value" :ref="`cell-${cell.id}`" type="text" class="w-full" @input="this.handleBudgetManagementSearch(index, cell, (this.mainPosition.type !== 'BUDGET_TYPE_COST'))"/>
                <XIcon class="w-10 h-10 cursor-pointer" @click="this.handleBudgetManagementSearchCancel(cell)"/>
                <div v-if="cell.accountSearchResults" class="absolute w-64 z-20 top-10">
                    <div v-if="cell.accountSearchResults.length > 0" v-for="account in cell.accountSearchResults" class="flex flex-col">
                        <div class="p-3 cursor-pointer bg-artwork-navigation-background hover:bg-artwork-buttons-hover text-white" @click="this.handleBudgetManagementSearchSelect(index, cell, account.account_number, mainPosition.is_verified, subPosition.is_verified)">
                            <div class="flex">
                                <div class="w-1/2 text-left truncate">
                                    {{ account.account_number }}
                                </div>
                                <div class="w-1/2 text-right truncate">
                                    {{ account.title }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-nowrap p-3 cursor-pointer bg-artwork-navigation-background hover:bg-artwork-buttons-hover text-white">
                        {{ $t('No Accounts found') }}
                    </div>
                </div>
                <div v-if="cell.costUnitSearchResults" class="absolute w-64 z-20 top-10">
                    <div v-if="cell.costUnitSearchResults.length > 0" v-for="cost_unit in cell.costUnitSearchResults" class="flex flex-col">
                        <div class="p-3 cursor-pointer bg-artwork-navigation-background hover:bg-artwork-buttons-hover text-white" @click="this.handleBudgetManagementSearchSelect(index, cell, cost_unit.cost_unit_number, mainPosition.is_verified, subPosition.is_verified)">
                            <div class="flex">
                                <div class="w-1/2 text-left truncate">
                                    {{ cost_unit.cost_unit_number }}
                                </div>
                                <div class="w-1/2 text-right truncate">
                                    {{ cost_unit.title }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else
                         class="text-nowrap p-3 cursor-pointer bg-artwork-navigation-background hover:bg-artwork-buttons-hover text-white">
                        {{ $t('No Cost Units found') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="group">
        <div :class="[row.commented || cell.commented || cell.column.commented ? 'xsLight' : '', index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end', cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']" class="my-4 h-6 flex items-center" v-if="!cell.clicked">
            <div class=" flex items-center">
                <div class="cursor-pointer" @click="handleCellClick(cell, 'comment', index, row)" v-if="cell.comments_count > 0">
                    <IconMessageDots class="h-5 w-5 mr-1 cursor-pointer border-2 rounded-md bg-artwork-icons-default-background text-artwork-icons-default-color border-artwork-icons-default-color"/>
                </div>
                <IconCalculator @click="handleCellClick(cell, 'calculation', index, row)" v-if="cell.calculations_count > 0" class="h-5 w-5 mr-1 cursor-pointer border-2 rounded-md bg-artwork-icons-default-background text-artwork-icons-default-color border-artwork-icons-default-color"/>
                <IconLink @click="handleCellClick(cell, 'moneysource', index, row)" v-if="cell.linked_money_source_id !== null" class="h-5 w-5 mr-1 cursor-pointer border-2 rounded-md bg-artwork-icons-default-background text-artwork-icons-default-color border-artwork-icons-default-color"/>
                <IconAdjustmentsAlt v-if="cell.sage_assigned_data.length >= 1" @click="handleCellClick(cell, 'sageAssignedData', index, row)" class="h-5 w-5 mr-1 cursor-pointer border-2 rounded-md" :class="cell.sage_assigned_data.length === 1 ? 'bg-artwork-icons-default-background text-artwork-icons-default-color border-artwork-icons-default-color' : 'bg-artwork-icons-darkGreen-background text-artwork-icons-darkGreen-color border-artwork-icons-darkGreen-color'" stroke-width="1.5"/>
                <div>
                    <div v-if="cell.column.type === 'sage'" class="flex items-center">
                        <SageDropCellElement :cell="cell" :value="this.toCurrencyString(cell.sage_value)"/>
                        <SageDragCellElement v-if="cell.sage_assigned_data.length >= 1" :cell="cell" class="hidden group-hover:block"/>
                    </div>
                    <span @click="handleCellClick(cell, '', index, row)" v-else>{{ index < 3 ? cell.value : this.toCurrencyString(cell.value) }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center relative"
             :class="index <= 1 ? 'w-24 mr-5' : index === 2 ? 'w-72 mr-12' : 'w-48 ml-5'"
             v-else-if="cell.clicked && cell.column.type === 'empty' && !cell.column.is_locked">
            <input :ref="`cell-${cell.id}`"
                   :class="index <= 1 ? 'w-20 mr-2' : index === 2 ? 'w-60 mr-2' : 'w-44 text-right'"
                   class="my-2 xsDark  appearance-none z-10 " type="text"
                   :disabled="!this.$can('edit budget templates') && table.is_template"
                   v-model="cell.value"
                   @keyup="isNumber($event, index)"
                   @focusout="updateCellValue(cell, mainPosition.is_verified, subPosition.is_verified)">
            <IconCirclePlus stroke-width="1.5" v-if="index > 2 " @click="openCellDetailModal(cell)" class="h-6 w-6 flex-shrink-0 -ml-3 absolute right-4 translate-x-1/2 z-50 cursor-pointer text-white bg-artwork-buttons-create rounded-full"/>
        </div>
        <div
            :class="[row.commented ? 'xsLight' : 'xsDark', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48 text-right', cell.value < 0 ? 'text-red-500' : '']"
            class="my-4 h-6 flex items-center justify-end"
            @click="cell.clicked = !cell.clicked && cell.column.is_locked"
            v-else>
            <img
                v-if="cell.linked_money_source_id !== null && (cell.comments_count > 0 || cell.calculations_count > 0)"
                src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg" class="h-6 w-6 mr-1"/>
            <img v-if="cell.comments_count > 0 || cell.calculations_count > 0"
                 src="/Svgs/IconSvgs/icon_linked_adjustments.svg" class="h-5 w-5 mr-1"/>
            <img v-if="cell.linked_money_source_id !== null"
                 src="/Svgs/IconSvgs/icon_linked_money_source.svg" class="h-6 w-6 mr-1"/>
            {{ index < 3 ? cell.value : this.toCurrencyString(cell.value) }}
            <IconCirclePlus stroke-width="1.5" v-if="index > 2 && cell.clicked" @click="openCellDetailModal(cell)" class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-artwork-buttons-create rounded-full"/>
        </div>
    </div>
</template>

<style scoped>

</style>
