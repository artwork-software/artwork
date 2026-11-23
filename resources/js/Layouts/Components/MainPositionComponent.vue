<template>
    <th class="p-0 w-full rounded-t-lg"
        :id="'budget-main-' + mainPosition.id"
        :class="[mainPosition.verified?.requested === this.$page.props.auth.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-accent-600' : 'bg-surface-inverse', pendingDeleteKeys['main:' + mainPosition.id] ? 'opacity-40 pointer-events-none' : '']">
        <div class="flex" @mouseover="showMenu = 'MainPosition' + mainPosition.id" @mouseout="showMenu = null">
            <div class="w-full flex">
            <div class="pl-2 text-sm/5 font-bold text-white flex items-center h-10 sticky left-0 lg:left-20">
                <input v-if="isEditingName"
                       ref="nameInput"
                       class="w-72 max-w-full text-sm/5 font-semibold text-text bg-white rounded-md border border-border px-1 focus:border-accent-600 focus:ring-1 focus:ring-accent-600 focus:outline-none"
                       type="text" v-model="editedName"
                       @keydown.enter.prevent="$event.target.blur()"
                       @keydown.esc.prevent="cancelNameEdit"
                       @blur="saveName">
                <div v-else-if="canEditStructure"
                     class="cursor-text rounded px-1 -ml-1 hover:bg-white/15"
                     :class="[mainPosition.name ? '' : 'italic font-normal opacity-70', searchHighlightClass]"
                     :title="$t('Rename')"
                     @click="startNameEdit">
                    {{ mainPosition.name || $t('No name') }}
                </div>
                <div v-else class="rounded px-1 -ml-1" :class="searchHighlightClass">
                    {{ mainPosition.name }}
                </div>
                <button type="button" class="my-auto w-6 ml-3"
                        :title="isClosed ? $t('Expand') : $t('Collapse')"
                        :aria-expanded="!isClosed"
                        @click="openCloseMainPosition">
                    <PropertyIcon name="IconChevronUp" v-if="!isClosed" class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                    <PropertyIcon name="IconChevronDown" v-else class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                </button>
            </div>
            </div>
            <div class="flex items-center justify-end">
                <div class="text-white items-center text-sm/5 font-bold text-white flex w-44 justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested !== this.$page.props.auth.user.id">
                    <PropertyIcon name="IconLockCog" class="w-5 h-5" stroke-width="1.5"/>
                    <p class="ml-2">{{ $t('requested to be verified') }}</p>
                </div>
                <div v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" class="text-white w-44 flex items-center text-center cursor-pointer justify-end mr-2" @click="verifiedMainPosition(mainPosition.verified?.main_position_id)" v-if="mainPosition.verified?.requested === this.$page.props.auth.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="text-xs/[18px] text-text-subtle">{{ $t('Mark as verified') }}</p>
                    <PropertyIcon name="IconCircleCheck" class="ml-1 h-5 w-5" stroke-width="1.5"/>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && !mainPosition.columnVerifiedChanges">
                    <p class="text-sm/5 font-bold text-white mr-1">{{ $t('verified') }}</p>
                    <PropertyIcon name="IconLock" class="w-5 h-5" stroke-width="1.5"/>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && mainPosition.columnVerifiedChanges">
                    <PropertyIcon name="IconLockExclamation"  class="w-5 h-5" stroke-width="1.5" />
                </div>
                <div class="flex flex-wrap w-8">
                    <div class="flex w-full">
                        <BaseMenu
                            v-if="hasBudgetAccess || $can('edit budget templates')"
                            dots-color="text-border-subtle"
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
        <div @click="addSubPosition(mainPosition.id)" v-if="!isClosed && canEditStructure" class="group relative  cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-accent-600 before:absolute before:inset-x-0 before:-inset-y-1.5 before:content-['']">
            <div class="group-hover:flex hidden items-center gap-x-1 -mt-3 h-6 pl-3 pr-1 rounded-full bg-accent-600 text-white text-xs font-semibold uppercase shadow-md relative z-20 whitespace-nowrap">
                {{ $t('Sub position') }}
                <PropertyIcon name="IconCirclePlus" stroke-width="1.5" class="h-4 w-4"/>
            </div>
        </div>
        <div class="w-full">
            <draggable
                v-if="!isClosed"
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
                                 class="sub-position-drag-handle absolute left-[-16px] top-3 z-10 cursor-grab text-text-subtle hover:text-primaryText">
                                <PropertyIcon name="IconGripVertical" class="h-4 w-4" aria-hidden="true" />
                            </div>
                            <SubPositionComponent @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                  @openVerifiedModal="openVerifiedModal"
                                                  @openCellDetailModal="openCellDetailModal"
                                                  @open-error-modal="openErrorModal"
                                                  @openDeleteModal="openDeleteModal"
                                                  @openSageAssignedDataModal="openSageAssignedDataModal"
                                                  @budget-updated="handleBudgetUpdated"
                                                  @order-saved="$emit('order-saved')"
                                                  @budget-patched="$emit('budget-patched', $event)"
                                                  :main-position="mainPosition"
                                                  :all-main-positions="table.main_positions"
                                                  :sub-position="subPosition"
                                                  :columns="table.columns"
                                                  :project="project"
                                                  :table="table"
                                                  :project-managers="projectManagers"
                                                  :hasBudgetAccess="this.hasBudgetAccess"
                            />
                        </div>
                    </div>
                </template>
            </draggable>
            <table class="w-full">
            <thead class="">
            <tr class=" text-sm/5 font-bold text-white flex h-10 w-full text-right text-lg items-center" :class="mainPosition.verified?.requested === this.$page.props.auth.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-accent-600' : 'bg-surface-inverse'">
                <td class="w-48"></td>
                <td class="w-48"></td>
                <td class="w-72 py-2 bg-inherit sticky left-0 lg:left-20 z-10">{{ $t('SUM') }}</td>
                <td v-if="mainPosition.sub_positions.length > 0" class="w-48 flex items-center" v-for="column in valueColumns" :key="column.id" v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                    <div class="w-48 my-4 p-1 flex group relative justify-end items-center" :class="[
                        mainPosition.columnSums?.[column.id]?.sum < 0 ? 'text-danger' : '',
                        column.color !== 'whiteColumn' ? column.color : ''
                    ]">
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-if="mainPosition.columnSums?.[column.id]?.hasComments && mainPosition.columnSums?.[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_and_adjustments_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-else-if="mainPosition.columnSums?.[column.id]?.hasComments" src="/Svgs/IconSvgs/icon_linked_adjustments_white.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'moneySource')" v-else-if="mainPosition.columnSums?.[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_money_source_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <span v-if="column.type !== 'sage' && column.type !== 'subprojects_column_for_group'">
                            {{ this.toCurrencyString(mainPosition.columnSums?.[column.id]?.sum) }}
                        </span>
                        <span v-if="column.type === 'sage'">
                            {{ toCurrencyString(calculateSageColumnWithCellSageDataValue) }}
                        </span>
                        <span v-if="column.type === 'subprojects_column_for_group'">
                            {{ calculateRelevantBudgetDataSumFormProjectsInGroupMainPosition() }}
                        </span>
                        <div v-if="this.hasBudgetAccess" class="hidden group-hover:block absolute right-0 z-50 -mr-6" @click="openMainPositionSumDetailModal(mainPosition, column)">
                            <PropertyIcon name="IconCirclePlus" stroke-width="1.5" class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-accent-600 rounded-full " />
                        </div>
                    </div>
                </td>
            </tr>
            </thead>
            </table>
            <div @click="addMainPosition(mainPosition)" v-if="canEditStructure" class="group relative  cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-accent-600 before:absolute before:inset-x-0 before:-inset-y-1.5 before:content-['']">
                <div class="group-hover:flex hidden items-center gap-x-1 -mt-3 h-6 pl-3 pr-1 rounded-full bg-accent-600 text-white text-xs font-semibold uppercase shadow-md relative z-20 whitespace-nowrap">
                    {{ $t('Main position') }}
                    <PropertyIcon name="IconCirclePlus" stroke-width="1.5" class="h-4 w-4"/>
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
import SubPositionComponent from "@/Layouts/Components/SubPositionComponent.vue";
import {useForm} from "@inertiajs/vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import SageAssignedDataModal from "@/Layouts/Components/SageAssignedDataModal.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import draggable from 'vuedraggable';
import {nextTick} from 'vue';
import {isBudgetEntryClosed, toggleBudgetEntryClosed} from "@/Layouts/Components/Budget/budgetCollapseState.js";
import {budgetPendingDeletes, budgetSearchState} from "@/Layouts/Components/Budget/budgetTableState.js";


export default {
    mixins: [Permissions, CurrencyFloatToStringFormatter],
    name: "MainPositionComponent",
    components: {
        draggable,
        BaseMenuItem,
        PropertyIcon,
        BaseMenu,
        SageAssignedDataModal,
        SubPositionComponent,
        ConfirmationComponent,
    },
    props: [
        'mainPosition',
        'table',
        'project',
        'projectManagers',
        'type',
        'hasBudgetAccess',
    ],
    emits:[
        'openDeleteModal',
        'openErrorModal',
        'openSubPositionSumDetailModal',
        'openMainPositionSumDetailModal',
        'openCellDetailModal',
        'openVerifiedModal',
        'budget-updated',
        'budget-patched',
        'order-saved',
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
            isEditingName: false,
            editedName: '',
        }
    },
    watch: {
        // Bewusst NICHT deep: strukturelle Aenderungen kommen als neue Objekte
        // aus dem Refetch; Zell-Patches mutieren in-place und duerfen die
        // Draggable-Liste nicht neu aufbauen.
        'mainPosition.sub_positions': {
            handler(positions) {
                if (!positions) return;
                this.localSubPositions = [...positions];
            },
            immediate: true,
        },
    },
    computed: {
        valueColumns() {
            return (this.table?.columns ?? []).slice(3);
        },
        isClosed() {
            return isBudgetEntryClosed('main', this.table?.id, this.mainPosition.id);
        },
        canEditStructure() {
            return this.hasBudgetAccess || this.$can('edit budget templates');
        },
        pendingDeleteKeys() {
            return budgetPendingDeletes.keys;
        },
        searchHighlightClass() {
            const key = 'main:' + this.mainPosition.id;
            if (budgetSearchState.activeKey === key) {
                return 'bg-warning text-text';
            }
            return budgetSearchState.matchedKeys[key] ? 'bg-warning/40' : '';
        },
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
                            onSuccess: () => {
                                this.$emit('budget-updated');
                                this.$emit('order-saved');
                            },
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
        openCloseMainPosition(){
            toggleBudgetEntryClosed('main', this.table?.id, this.mainPosition.id);
        },
        startNameEdit() {
            this.editedName = this.mainPosition.name ?? '';
            this.isEditingName = true;
            nextTick(() => this.$refs.nameInput?.select());
        },
        cancelNameEdit() {
            this.isEditingName = false;
        },
        saveName() {
            if (!this.isEditingName) {
                return;
            }
            this.isEditingName = false;
            const name = this.editedName.trim();
            // Leerer oder unveraenderter Name: nichts speichern (spart Request + Neuladen)
            if (name === '' || name === this.mainPosition.name) {
                return;
            }
            this.mainPosition.name = name;
            this.updateMainPositionName(this.mainPosition);
        },
        duplicateMainPosition(mainPositionId){
            this.$inertia.post(this.route('project.budget.main-position.duplicate', mainPositionId), { }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
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
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        verifiedMainPosition(mainPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.main-position'), {
                mainPositionId: mainPositionId,
                project_id: this.project?.id,
                table_id: this.table.id,
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
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
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            })
        },
        requestRemove(position, type){
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
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
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        addMainPosition(mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                table_id: this.table.id,
                type: this.type,
                positionBefore: mainPosition.position
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
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
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            })
        },
        unfixMainPosition(mainPositionId){
            this.$inertia.patch(this.route('project.budget.unfix.main-position'), {
                mainPositionId: mainPositionId,
                project_id: this.project?.id
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
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
