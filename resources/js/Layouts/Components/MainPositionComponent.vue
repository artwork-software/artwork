<template>
    <th class="p-0" :class="[mainPosition.verified?.requested === this.$page.props.auth.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-artwork-buttons-update' : 'bg-primary', mainPosition.closed ? 'rounded-lg' : 'rounded-t-lg']">
        <div class="flex" @mouseover="showMenu = 'MainPosition' + mainPosition.id" @mouseout="showMenu = null">
            <div class="pl-2 xsWhiteBold flex w-full items-center h-10" v-if="!mainPosition.clicked">
                <div @click="mainPosition.clicked = !mainPosition.clicked">
                    {{ mainPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3" @click="openCloseMainPosition">
                    <PropertyIcon name="IconChevronUp" v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                    <PropertyIcon name="IconChevronDown" v-else class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                </button>
            </div>
            <div v-else class="flex items-center w-full">
                <input class="my-2 ml-1 xsDark bg-white" type="text" v-model="mainPosition.name" @focusout="updateMainPositionName(mainPosition); mainPosition.clicked = !mainPosition.clicked">
                <button class="my-auto w-6 ml-3" @click="mainPosition.closed = !mainPosition.closed">
                    <PropertyIcon name="IconChevronUp" v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                    <PropertyIcon name="IconChevronDown" v-else class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="text-white items-center xsWhiteBold flex w-44 justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested !== this.$page.props.auth.user.id">
                    <PropertyIcon name="IconLockCog" class="w-5 h-5" stroke-width="1.5"/>
                    <p class="ml-2">{{ $t('requested to be verified') }}</p>
                </div>
                <div v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" class="text-white w-44 flex items-center text-center cursor-pointer justify-end mr-2" @click="verifiedMainPosition(mainPosition.verified?.main_position_id)" v-if="mainPosition.verified?.requested === this.$page.props.auth.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="xxsLight">{{ $t('Mark as verified') }}</p>
                    <PropertyIcon name="IconCircleCheck" class="ml-1 h-5 w-5" stroke-width="1.5"/>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && !mainPosition.columnVerifiedChanges">
                    <p class="xsWhiteBold mr-1">{{ $t('verified') }}</p>
                    <PropertyIcon name="IconLock" class="w-5 h-5" stroke-width="1.5"/>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && mainPosition.columnVerifiedChanges">
                    <PropertyIcon name="IconLockExclamation"  class="w-5 h-5" stroke-width="1.5" />
                </div>
                <div class="flex flex-wrap w-8">
                    <div class="flex w-full">
                        <BaseMenu
                            v-if="hasBudgetAccess || $can('edit budget templates')"
                            dots-color="text-artwork-context-light"
                            white-menu-background
                        >
                            <!-- Get verified by user -->
                            <BaseMenuItem
                                v-show="$can('can add and remove verified states') || hasAdminRole()"
                                v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED'"
                                :title="$t('Get verified by user')"
                                icon="IconLock"
                                white-menu-background
                                @click="openVerifiedModal(true, false, mainPosition.id, mainPosition)"
                            />

                            <!-- Cancel verification -->
                            <BaseMenuItem
                                v-show="$can('can add and remove verified states') || hasAdminRole()"
                                v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'
                                        && (mainPosition.verified?.requested === $page.props.auth.user.id
                                        || projectManagers.includes($page.props.auth.user.id))"
                                :title="$t('Cancel verification')"
                                icon="IconLockOpen"
                                white-menu-background
                                @click="removeVerification(mainPosition, 'main')"
                            />

                            <!-- Withdraw verification request -->
                            <BaseMenuItem
                                v-show="$can('can add and remove verified states') || hasAdminRole()"
                                v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED'
                                        && (mainPosition.verified?.requested_by === $page.props.auth.user.id
                                        || projectManagers.includes($page.props.auth.user.id))"
                                :title="$t('Withdraw verification request')"
                                icon="IconLockOpen"
                                white-menu-background
                                @click="requestRemove(mainPosition, 'main')"
                            />

                            <!-- Commitment (fix) -->
                            <BaseMenuItem
                                v-show="$can('can add and remove verified states') || hasAdminRole()"
                                v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !mainPosition.is_fixed"
                                :title="$t('Commitment')"
                                icon="IconLock"
                                white-menu-background
                                @click="fixMainPosition(mainPosition.id)"
                            />

                            <!-- Canceling a fixed term (unfix) -->
                            <BaseMenuItem
                                v-show="$can('can add and remove verified states') || hasAdminRole()"
                                v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && mainPosition.is_fixed"
                                :title="$t('Canceling a fixed term')"
                                icon="IconLockOpen"
                                white-menu-background
                                @click="unfixMainPosition(mainPosition.id)"
                            />

                            <!-- Duplicate -->
                            <BaseMenuItem
                                :title="$t('Duplicate')"
                                icon="IconCopy"
                                white-menu-background
                                @click="duplicateMainPosition(mainPosition.id)"
                            />

                            <!-- Delete -->
                            <BaseMenuItem
                                :title="$t('Delete')"
                                icon="IconTrash"
                                white-menu-background
                                @click="openDeleteMainPositionModal(mainPosition)"
                            />
                        </BaseMenu>

                    </div>
                </div>
            </div>
        </div>
        <div @click="addSubPosition(mainPosition.id)" v-if="this.hasBudgetAccess || this.$can('edit budget templates')" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-artwork-buttons-create">
            <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                {{ $t('Sub position') }}
                <PropertyIcon name="IconCirclePlus" stroke-width="1.5" class="h-6 w-6 ml-12 text-white bg-artwork-buttons-create rounded-full" />
            </div>
        </div>
        <div v-if="!mainPosition.closed" class="w-full">
            <draggable
                v-model="localSubPositions"
                item-key="id"
                handle=".sub-position-drag-handle"
                ghost-class="opacity-50"
                :group="{ name: 'sub-positions-' + type, pull: true, put: true }"
                :disabled="!canReorderSubPositions"
                @change="persistSubPositionOrder($event)"
            >
                <template #item="{ element: subPosition }">
                    <div class="mb-1">
                        <div class="relative">
                            <div v-if="canReorderSubPositions"
                                 class="sub-position-drag-handle absolute left-[-16px] top-3 z-10 cursor-grab text-secondary hover:text-primaryText">
                                <PropertyIcon name="IconGripVertical" class="h-4 w-4" aria-hidden="true" />
                            </div>
                            <SubPositionComponent @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                  @openVerifiedModal="openVerifiedModal"
                                                  @openCellDetailModal="openCellDetailModal"
                                                  @open-error-modal="openErrorModal"
                                                  @openDeleteModal="openDeleteModal"
                                                  @openSageAssignedDataModal="openSageAssignedDataModal"
                                                  @budget-updated="handleBudgetUpdated"
                                                  :main-position="mainPosition"
                                                  :all-main-positions="table.main_positions"
                                                  :sub-position="subPosition"
                                                  :columns="table.columns"
                                                  :project="project"
                                                  :table="table"
                                                  :project-managers="projectManagers"
                                                  :hasBudgetAccess="this.hasBudgetAccess"
                                                  :user-show-account-name="userShowAccountName"
                            />
                        </div>
                    </div>
                </template>
            </draggable>
            <table class="w-full">
            <thead class="">
            <tr class=" xsWhiteBold flex h-10 w-full text-right text-lg items-center" :class="mainPosition.verified?.requested === this.$page.props.auth.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-artwork-buttons-create' : 'bg-primary'">
                <td class="w-48"></td>
                <td class="w-48"></td>
                <td class="w-72">SUM</td>
                <td v-if="mainPosition.sub_positions.length > 0" class="w-48 flex items-center" v-for="column in table.columns.slice(3)" v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                    <div class="w-48 my-4 p-1 flex group relative justify-end items-center" :class="[
                        mainPosition.columnSums?.[column.id]?.sum < 0 ? 'text-red-500' : '',
                        column.color !== 'whiteColumn' ? column.color : ''
                    ]">
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-if="mainPosition.columnSums?.[column.id]?.hasComments && mainPosition.columnSums?.[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_and_adjustments_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-else-if="mainPosition.columnSums?.[column.id]?.hasComments" src="/Svgs/IconSvgs/icon_linked_adjustments_white.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'moneySource')" v-else-if="mainPosition.columnSums?.[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_money_source_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <span v-if="column.type !== 'sage' && column.type !== 'subprojects_column_for_group'">
                            {{ this.toCurrencyString(mainPosition.columnSums?.[column.id]?.sum) }}
                        </span>
                        <span v-if="column.type === 'sage'">
                            {{ calculateSageColumnWithCellSageDataValue.toLocaleString() }}
                        </span>
                        <span v-if="column.type === 'subprojects_column_for_group'">
                            {{ calculateRelevantBudgetDataSumFormProjectsInGroupMainPosition() }}
                        </span>
                        <div v-if="this.hasBudgetAccess" class="hidden group-hover:block absolute right-0 z-50 -mr-6" @click="openMainPositionSumDetailModal(mainPosition, column)">
                            <PropertyIcon name="IconCirclePlus" stroke-width="1.5" class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-artwork-buttons-create rounded-full " />
                        </div>
                    </div>
                </td>
            </tr>
            </thead>
            </table>
            <div @click="addMainPosition(mainPosition)" v-if="this.hasBudgetAccess || this.$can('edit budget templates')" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-artwork-buttons-create">
                <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                    {{ $t('Main position') }}
                    <PropertyIcon name="IconCirclePlus" stroke-width="1.5" class="h-6 w-6 ml-12 text-white bg-artwork-buttons-create rounded-full" />
                </div>
            </div>
        </div>
    </th>
    <sage-assigned-data-modal v-if="this.showSageAssignedDataModal"
                              :show="this.showSageAssignedDataModal"
                              :cell="this.showSageAssignedDataModalCell"
                              @close="this.closeSageAssignedDataModal"
                              @budget-updated="this.handleBudgetUpdated"
    />
</template>

<script>
import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {CheckIcon, ChevronDownIcon, ChevronUpIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import SubPositionComponent from "@/Layouts/Components/SubPositionComponent.vue";
import {useForm} from "@inertiajs/vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import SageAssignedDataModal from "@/Layouts/Components/SageAssignedDataModal.vue";
import IconLib from "@/Mixins/IconLib.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import draggable from 'vuedraggable';
import {nextTick} from 'vue';


export default {
    mixins: [Permissions, IconLib, CurrencyFloatToStringFormatter],
    name: "MainPositionComponent",
    components: {
        draggable,
        BaseMenuItem,
        PropertyIcon,
        BaseMenu,
        SageAssignedDataModal,
        SubPositionComponent,
        PlusCircleIcon,
        ChevronUpIcon,
        ChevronDownIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        XIcon,
        DotsVerticalIcon,
        CheckIcon,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        ConfirmationComponent,
    },
    props: [
        'mainPosition',
        'table',
        'project',
        'projectManagers',
        'type',
        'hasBudgetAccess',
        'userShowAccountName',
    ],
    emits:[
        'openDeleteModal',
        'openErrorModal',
        'openSubPositionSumDetailModal',
        'openMainPositionSumDetailModal',
        'openCellDetailModal',
        'openVerifiedModal',
        'budget-updated',
    ],
    data(){
        return{
            showMenu: null,
            showDeleteModal: false,
            confirmationTitle:'',
            positionToDelete:'',
            mainPositionToDelete: null,
            showSuccessModal: false,
            confirmationDescription: '',
            successHeading: '',
            successDescription: '',
            showVerifiedModal: false,
            showSageAssignedDataModal: false,
            showSageAssignedDataModalCell: null,
            positionDefault: {
              position: 0
            },
            verifiedTexts: {
                title: this.$t('Verification'),
                positionTitle: '',
                description: this.$t('Have all figures been calculated correctly? Is the calculation plausible? Have your main item verified by a user.')
            },
            submitVerifiedModalData: useForm({
              is_main: false,
              is_sub: false,
              id: null,
              user: '',
              position: [],
              project_title: this.project?.name,
              project_id: this.project?.id,
              table_id: this.table.id,
            }),
            colors: {
              whiteColumn: 'whiteColumn',
              greenColumn: 'greenColumn',
              yellowColumn: 'yellowColumn',
              redColumn: 'redColumn',
              lightGreenColumn: 'lightGreenColumn'
            },
            localSubPositions: [],
        }
    },
    watch: {
        'mainPosition.sub_positions': {
            handler(positions) {
                if (!positions) return;
                this.localSubPositions = [...positions];
            },
            immediate: true,
            deep: true
        },
    },
    mounted() {
        // check if main Position in localStorage in "closedMainPositions"
        this.checkIfMainPositionClosed()
    },
    updated() {
        this.checkIfMainPositionClosed()
    },
    beforeUnmount() {
        // remove localeStorage key "closedMainPositions"
        localStorage.removeItem('closedMainPositions')
    },
    computed: {
        calculateSageColumnWithCellSageDataValue() {
            // Returniert die Summe aller buchungsbetrag in den sage_assigned_data Arrays.
            return this.mainPosition?.sub_positions?.reduce((acc, subPosition) => {
                return acc + subPosition.sub_position_rows?.reduce((acc, row) => {
                    return acc + row.cells?.reduce((acc, cell) => {
                        // Prüft, ob die Zelle die definierten Bedingungen erfüllt.
                        if (cell.column.type === 'sage' && !cell.commented && !cell.column.commented) {
                            // Addiert den buchungsbetrag, wenn alle Bedingungen erfüllt sind.
                            return acc + Number(cell.sage_assigned_data?.reduce((acc, data) => {
                                // Stellt sicher, dass buchungsbetrag eine Zahl ist und fügt sie hinzu.
                                const buchungsbetrag = Number(data.buchungsbetrag);
                                // Überprüft, ob buchungsbetrag eine gültige Zahl ist, sonst wird 0 verwendet.
                                return acc + (isNaN(buchungsbetrag) ? 0 : buchungsbetrag);
                            }, 0) ?? 0);
                        }
                        return acc;
                    }, 0) ?? 0;
                }, 0) ?? 0;
            }, 0) ?? 0;
        },
        canReorderSubPositions() {
            return (this.hasBudgetAccess || this.$can('edit budget templates'))
                && (!this.table?.is_template || this.$can('edit budget templates'));
        },
    },
    methods: {
        persistSubPositionOrder(evt = null) {
            if (!this.canReorderSubPositions) return;

            // For cross-mainposition drag: ignore pure 'removed' events,
            // only persist on 'added' (target) or 'moved' (same list).
            if (evt?.removed && !evt?.added && !evt?.moved) return;

            window.clearTimeout(this._subPositionReorderTimeout);
            this._subPositionReorderTimeout = window.setTimeout(() => {
                nextTick(() => {
                    this.$inertia.patch(
                        route('project.budget.sub-position.reorder'),
                        {
                            main_position_id: this.mainPosition.id,
                            sub_position_ids: this.localSubPositions.map(sp => sp.id),
                        },
                        {
                            preserveScroll: true,
                            preserveState: true,
                        }
                    );
                });
            }, 150);
        },
        calculateRelevantBudgetDataSumFormProjectsInGroupMainPosition() {
            const data = this.$page.props.loadedProjectInformation?.BudgetTab?.projectGroupRelevantBudgetData;
            if (!data || !Array.isArray(data[this.mainPosition?.type])) return this.toCurrencyString(0);
            const relevantData = data[this.mainPosition.type].filter(item =>
                item?.mainPositionId === this.mainPosition?.id && item?.type === this.mainPosition?.type
            );
            if (!relevantData.length) return this.toCurrencyString(0);
            const sum = relevantData.reduce((acc, item) => {
                const value = parseFloat(item.value?.replace(',', '.') || '0');
                return acc + (isNaN(value) ? 0 : value);
            }, 0);
            return this.toCurrencyString(sum);
        },
        checkIfMainPositionClosed(){
            if(localStorage.getItem('closedMainPositions') !== null){
                let closedMainPositions = JSON.parse(localStorage.getItem('closedMainPositions'))
                // add fail over if closedMainPositions is not an array
                if(!Array.isArray(closedMainPositions)){
                    closedMainPositions = []
                }
                let index = closedMainPositions.findIndex((mainPosition) => mainPosition.id === this.mainPosition.id)
                if (index !== -1) {
                    this.mainPosition.closed = closedMainPositions[index].closed
                }
            }
        },
        openCloseMainPosition(){
            this.mainPosition.closed = !this.mainPosition.closed
            if(localStorage.getItem('closedMainPositions') === null){
                localStorage.setItem('closedMainPositions', JSON.stringify([{
                    id: this.mainPosition.id,
                    closed: this.mainPosition.closed
                }]))
            } else {
                let closedMainPositions = JSON.parse(localStorage.getItem('closedMainPositions'))
                // add fail over if closedMainPositions is not an array
                if(!Array.isArray(closedMainPositions)){
                    closedMainPositions = []
                }
                let index = closedMainPositions.findIndex((mainPosition) => mainPosition.id === this.mainPosition.id)
                if(index === -1){
                    closedMainPositions.push({
                        id: this.mainPosition.id,
                        closed: this.mainPosition.closed
                    })
                } else {
                    closedMainPositions[index].closed = this.mainPosition.closed
                }
                localStorage.setItem('closedMainPositions', JSON.stringify(closedMainPositions))
            }
        },
        duplicateMainPosition(mainPositionId){
            this.$inertia.post(this.route('project.budget.main-position.duplicate', mainPositionId), { }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        openDeleteModal(title, description, position, type) {
            this.$emit(
                'openDeleteModal',
                title,
                description,
                position,
                type
            );
        },
        updateMainPositionName(mainPosition) {
            this.$inertia.patch(route('project.budget.main-position.update-name'), {
                mainPosition_id: mainPosition.id,
                mainPositionName: mainPosition.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        verifiedMainPosition(mainPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.main-position'), {
                mainPositionId: mainPositionId,
                project_id: this.project?.id,
                table_id: this.table.id,
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        openVerifiedModal(is_main,is_sub,id,position) {
            this.verifiedTexts.positionTitle = position.name;
            this.submitVerifiedModalData.is_main = is_main;
            this.submitVerifiedModalData.is_sub = is_sub;
            this.submitVerifiedModalData.id = id;
            this.submitVerifiedModalData.position = position;
            this.showVerifiedModal = true;
            this.$emit(
                'openVerifiedModal',
                this.submitVerifiedModalData.is_main,
                this.submitVerifiedModalData.is_sub,
                this.submitVerifiedModalData.id,
                this.submitVerifiedModalData.position
            );
        },
        removeVerification(position, type){
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        requestRemove(position, type){
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        openDeleteMainPositionModal(mainPosition) {
            this.confirmationTitle = this.$t('Delete main position');
            this.confirmationDescription = this.$t(
                'Are you sure you want to delete the main position?',
                [mainPosition.name]
            );
            this.mainPositionToDelete = mainPosition;
            this.showDeleteModal = true;
            this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.mainPositionToDelete, 'main')
        },
        addSubPosition(mainPositionId, subPosition = null) {
            let subPositionBefore = subPosition

            if (!subPositionBefore) {
                subPositionBefore = {
                    position: 0
                }
            }

            this.$inertia.post(route('project.budget.sub-position.add'), {
                table_id: this.table.id,
                main_position_id: mainPositionId,
                positionBefore: subPositionBefore.position
            }, {
                preserveScroll: true,
                preserveState: false
            });
        },
        addMainPosition(mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                table_id: this.table.id,
                type: this.type,
                positionBefore: mainPosition.position
            }, {
                preserveScroll: true,
                preserveState: false
            });
        },
        openSubPositionSumDetailModal(subPosition, column, type) {
            this.$emit('openSubPositionSumDetailModal', subPosition, column, type);
        },
        openMainPositionSumDetailModal(mainPosition, column, type='comment') {
            this.$emit('openMainPositionSumDetailModal', mainPosition, column, type);
        },
        openCellDetailModal(column, type) {
            this.$emit('openCellDetailModal', column, type);
        },
        fixMainPosition(mainPositionId){
            this.$inertia.patch(this.route('project.budget.fix.main-position'), {
                mainPositionId: mainPositionId,
                project_id: this.project?.id
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        unfixMainPosition(mainPositionId){
            this.$inertia.patch(this.route('project.budget.unfix.main-position'), {
                mainPositionId: mainPositionId,
                project_id: this.project?.id
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        openErrorModal(title, description) {
            this.confirmationTitle = title;
            this.confirmationDescription = description
            this.$emit('openErrorModal', this.confirmationTitle, this.confirmationDescription)
        },
        openSageAssignedDataModal(cell) {
            this.showSageAssignedDataModalCell = cell;
            this.showSageAssignedDataModal = true;
        },
        closeSageAssignedDataModal() {
            this.showSageAssignedDataModal = false;
            this.showSageAssignedDataModalCell = null;
        },
        handleBudgetUpdated() {
            this.$emit('budget-updated');
        }
    },

}
</script>
