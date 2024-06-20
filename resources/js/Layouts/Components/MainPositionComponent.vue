<template>
    <th class="p-0" :class="[mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-artwork-buttons-update' : 'bg-primary', mainPosition.closed ? 'rounded-lg' : 'rounded-t-lg']">
        <div class="flex" @mouseover="showMenu = 'MainPosition' + mainPosition.id" @mouseout="showMenu = null">
            <div class="pl-2 xsWhiteBold flex w-full items-center h-10" v-if="!mainPosition.clicked">
                <div @click="mainPosition.clicked = !mainPosition.clicked">
                    {{ mainPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3" @click="openCloseMainPosition">
                    <IconChevronUp v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                    <IconChevronDown v-else class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                </button>
            </div>
            <div v-else class="flex items-center w-full">
                <input class="my-2 ml-1 xsDark" type="text" v-model="mainPosition.name" @focusout="updateMainPositionName(mainPosition); mainPosition.clicked = !mainPosition.clicked">
                <button class="my-auto w-6 ml-3" @click="mainPosition.closed = !mainPosition.closed">
                    <IconChevronUp v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                    <IconChevronDown v-else class="h-6 w-6 text-white my-auto" stroke-width="1.5" />
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="text-white items-center xsWhiteBold flex w-44 justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested !== this.$page.props.user.id">
                    <IconLockCog class="w-5 h-5" stroke-width="1.5"/>
                    <p class="ml-2">{{ $t('requested to be verified') }}</p>
                </div>
                <div v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" class="text-white w-44 flex items-center text-center cursor-pointer justify-end mr-2" @click="verifiedMainPosition(mainPosition.verified?.main_position_id)" v-if="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="xxsLight">{{ $t('Mark as verified') }}</p>
                    <IconCircleCheck class="ml-1 h-5 w-5" stroke-width="1.5"/>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && !mainPosition.columnVerifiedChanges">
                    <p class="xsWhiteBold mr-1">{{ $t('verified') }}</p>
                    <IconLock class="w-5 h-5" stroke-width="1.5"/>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.columnVerifiedChanges">
                    <IconLockExclamation  class="w-5 h-5" stroke-width="1.5" />
                </div>
                <div class="flex flex-wrap w-8">
                    <div class="flex w-full">
                        <BaseMenu  v-if="this.$can('edit budget templates') || !table.is_template" dots-color="text-artwork-context-light">
                            <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED'">
                                            <span @click="openVerifiedModal(true, false, mainPosition.id, mainPosition)" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconLock stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" />
                                                {{ $t('Get verified by user') }}
                                            </span>
                            </MenuItem>
                            <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && (mainPosition.verified?.requested === this.$page.props.user.id || projectManagers.includes(this.$page.props.user.id))">
                                            <span @click="removeVerification(mainPosition, 'main')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconLockOpen stroke-width="1.5" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" />
                                                {{ $t('Cancel verification') }}
                                            </span>
                            </MenuItem>
                            <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && (mainPosition.verified?.requested_by === this.$page.props.user.id || projectManagers.includes(this.$page.props.user.id))">
                                            <span @click="requestRemove(mainPosition, 'main')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconLockOpen stroke-width="1.5" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" />
                                                {{ $t('Withdraw verification request') }}
                                            </span>
                            </MenuItem>
                            <MenuItem v-slot="{ active }" v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !mainPosition.is_fixed">
                                            <span @click="fixMainPosition(mainPosition.id)" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconLock stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" />
                                                {{ $t('Commitment') }}
                                            </span>
                            </MenuItem>
                            <MenuItem v-slot="{ active }" v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && mainPosition.is_fixed">
                                            <span @click="unfixMainPosition(mainPosition.id)" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconLockOpen class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" stroke-width="1.5"  />
                                                {{ $t('Canceling a fixed term') }}
                                            </span>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                            <span @click="openDeleteMainPositionModal(mainPosition)" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconTrash class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" stroke-width="1.5" aria-hidden="true"/>
                                                {{ $t('Delete') }}
                                            </span>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a @click="duplicateMainPosition(mainPosition.id)" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconCopy class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" stroke-width="1.5" aria-hidden="true"/>
                                    {{ $t('Duplicate') }}
                                </a>
                            </MenuItem>
                        </BaseMenu>
                    </div>
                </div>
            </div>
        </div>
        <div @click="addSubPosition(mainPosition.id)" v-if="this.$can('edit budget templates') || !table.is_template" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-artwork-buttons-create">
            <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                {{ $t('Sub position') }}
                <IconCirclePlus stroke-width="1.5" class="h-6 w-6 ml-12 text-white bg-artwork-buttons-create rounded-full" />
            </div>
        </div>
        <table v-if="!mainPosition.closed" class="w-full">
            <thead class="">
            <tr class="" v-for="(subPosition) in mainPosition.sub_positions">
                <SubPositionComponent @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                      @openVerifiedModal="openVerifiedModal"
                                      @openCellDetailModal="openCellDetailModal"
                                      @open-error-modal="openErrorModal"
                                      @openDeleteModal="openDeleteModal"
                                      @openSageAssignedDataModal="openSageAssignedDataModal"
                                      :main-position="mainPosition"
                                      :sub-position="subPosition"
                                      :columns="table.columns"
                                      :project="project"
                                      :table="table"
                                      :project-managers="projectManagers"
                />
            </tr>
            <tr class=" xsWhiteBold flex h-10 w-full text-right text-lg items-center" :class="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-artwork-buttons-create' : 'bg-primary'">
                <td class="w-28"></td>
                <td class="w-28"></td>
                <td class="w-72">SUM</td>
                <td v-if="mainPosition.sub_positions.length > 0" class="w-48 flex items-center" v-for="column in table.columns.slice(3)" v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                    <div class="w-48 my-4 p-1 flex group relative justify-end items-center" :class="mainPosition.columnSums[column.id]?.sum < 0 ? 'text-red-500' : ''">
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-if="mainPosition.columnSums[column.id]?.hasComments && mainPosition.columnSums[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_and_adjustments_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-else-if="mainPosition.columnSums[column.id]?.hasComments" src="/Svgs/IconSvgs/icon_linked_adjustments_white.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'moneySource')" v-else-if="mainPosition.columnSums[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_money_source_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <span v-if="column.type !== 'sage'">{{ this.toCurrencyString(mainPosition.columnSums[column.id]?.sum) }}</span>
                        <span v-else>{{ calculateSageColumnWithCellSageDataValue.toLocaleString() }}</span>
                        <div class="hidden group-hover:block absolute right-0 z-50 -mr-6" @click="openMainPositionSumDetailModal(mainPosition, column)">
                            <IconCirclePlus stroke-width="1.5" class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-artwork-buttons-create rounded-full " />
                        </div>
                    </div>
                </td>
            </tr>
            </thead>
            <div @click="addMainPosition(mainPosition)" v-if="this.$can('edit budget templates') || !table.is_template" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-artwork-buttons-create">
                <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                    {{ $t('Main position') }}
                    <IconCirclePlus stroke-width="1.5" class="h-6 w-6 ml-12 text-white bg-artwork-buttons-create rounded-full" />
                </div>
            </div>
        </table>
    </th>
    <sage-assigned-data-modal v-if="this.showSageAssignedDataModal"
                              :show="this.showSageAssignedDataModal"
                              :cell="this.showSageAssignedDataModalCell"
                              @close="this.closeSageAssignedDataModal"
    />
</template>

<script>
import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import SubPositionComponent from "@/Layouts/Components/SubPositionComponent.vue";
import {useForm} from "@inertiajs/vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import SageAssignedDataModal from "@/Layouts/Components/SageAssignedDataModal.vue";
import IconLib from "@/Mixins/IconLib.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";


export default {
    mixins: [Permissions, IconLib, CurrencyFloatToStringFormatter],
    name: "MainPositionComponent",
    components: {
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
        'type'
    ],
    emits:[
        'openDeleteModal',
        'openErrorModal',
        'openSubPositionSumDetailModal',
        'openMainPositionSumDetailModal',
        'openCellDetailModal',
        'openVerifiedModal'
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
        }
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
        }

    },
    methods: {
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
                preserveState: true
            });
        },
        addMainPosition(mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                table_id: this.table.id,
                type: this.type,
                positionBefore: mainPosition.position
            }, {
                preserveScroll: true,
                preserveState: true
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
        }
    },

}
</script>
