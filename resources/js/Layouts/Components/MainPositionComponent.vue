<template>
    <th class="p-0" :class="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-buttonBlue' : 'bg-primary'">
        <div class="flex" @mouseover="showMenu = 'MainPosition' + mainPosition.id" @mouseout="showMenu = null">
            <div class="pl-2 xsWhiteBold flex w-full items-center h-10" v-if="!mainPosition.clicked">
                <div @click="mainPosition.clicked = !mainPosition.clicked">
                    {{ mainPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3" @click="openCloseMainPosition">
                    <ChevronUpIcon v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto" />
                    <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto" />
                </button>
            </div>
            <div v-else class="flex items-center w-full">
                <input class="my-2 ml-1 xsDark" type="text" v-model="mainPosition.name" @focusout="updateMainPositionName(mainPosition); mainPosition.clicked = !mainPosition.clicked">
                <button class="my-auto w-6 ml-3" @click="mainPosition.closed = !mainPosition.closed">
                    <ChevronUpIcon v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto" />
                    <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto" />
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="text-white items-center" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested !== this.$page.props.user.id">
                    <div class="xsWhiteBold flex w-44">
                        <img alt="Gesperrt" src="/Svgs/IconSvgs/icon_verify.svg" class="-ml-20"/>
                        <p class="ml-2">{{ $t('requested to be verified') }}</p>
                    </div>
                </div>
                <div v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" class="text-white w-44 flex items-center text-center cursor-pointer" @click="verifiedMainPosition(mainPosition.verified?.main_position_id)" v-if="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="xxsLight">{{ $t('Mark as verified') }}</p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" class="ml-1" height="20" viewBox="0 0 20 20">
                        <g id="check_btn" transform="translate(-1234 -671.05)">
                            <g id="Pfad_1370" data-name="Pfad 1370" transform="translate(1234 671.05)" fill="none">
                                <path d="M10,0A10,10,0,1,1,0,10,10,10,0,0,1,10,0Z" stroke="none"/>
                                <path d="M 10 1 C 5.037380218505859 1 1 5.037380218505859 1 10 C 1 14.96261978149414 5.037380218505859 19 10 19 C 14.96261978149414 19 19 14.96261978149414 19 10 C 19 5.037380218505859 14.96261978149414 1 10 1 M 10 0 C 15.52285003662109 0 20 4.477149963378906 20 10 C 20 15.52285003662109 15.52285003662109 20 10 20 C 4.477149963378906 20 0 15.52285003662109 0 10 C 0 4.477149963378906 4.477149963378906 0 10 0 Z" stroke="none" fill="#fcfcfb"/>
                            </g>
                            <path id="Pfad_157" data-name="Pfad 157" d="M-1151.25,4789.252l3.142,3.142,6.013-6.013" transform="translate(2390.673 -4108.337)" fill="none" stroke="#fcfcfb" stroke-width="1.5"/>
                        </g>
                    </svg>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && !mainPosition.columnVerifiedChanges">
                    <p class="xsWhiteBold mr-1">{{ $t('verified') }}</p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11.975" height="13.686" class="ml-1" viewBox="0 0 11.975 13.686">
                        <path id="Icon_awesome-lock" data-name="Icon awesome-lock" d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z" fill="#fcfcfb"/>
                    </svg>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2" v-if="mainPosition.columnVerifiedChanges">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14.996" height="16.309" viewBox="0 0 14.996 16.309">
                        <g id="broken_lock" transform="translate(-1218 -672.034)">
                            <path id="Icon_awesome-lock" data-name="Icon awesome-lock" d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z" transform="translate(1218 674.657)" fill="#fcfcfb"/>
                            <path id="Icon_metro-warning" data-name="Icon metro-warning" d="M11.85,14.748a.921.921,0,1,1-.921-.921A.921.921,0,0,1,11.85,14.748Zm-.921-1.842a.921.921,0,0,1-.921-.921V9.224a.921.921,0,0,1,1.842,0v2.762A.921.921,0,0,1,10.929,12.907Z" transform="translate(1221.046 663.831)" fill="#fcfcfb" stroke="#fcfcfb" stroke-width="0.2"/>
                        </g>
                    </svg>
                </div>
                <div class="flex flex-wrap w-8">
                    <div class="flex w-full">
                        <Menu as="div" class="my-auto relative" v-if="this.$can('edit budget templates') || !table.is_template">
                            <div class="flex">
                                <MenuButton class="flex bg-tagBg p-0.5 rounded-full">
                                    <DotsVerticalIcon class=" flex-shrink-0 h-6 w-6 text-secondaryHover my-auto" aria-hidden="true"/>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                <MenuItems class="z-50 origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED'">
                                            <span @click="openVerifiedModal(true, false, mainPosition.id, mainPosition)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                {{ $t('Get verified by user') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && (mainPosition.verified?.requested === this.$page.props.user.id || projectManagers.includes(this.$page.props.user.id))">
                                            <span @click="removeVerification(mainPosition, 'main')" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                {{ $t('Cancel verification') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && (mainPosition.verified?.requested_by === this.$page.props.user.id || projectManagers.includes(this.$page.props.user.id))">
                                            <span @click="requestRemove(mainPosition, 'main')" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                {{ $t('Withdraw verification request') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }" v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !mainPosition.is_fixed">
                                            <span @click="fixMainPosition(mainPosition.id)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                {{ $t('Commitment') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }" v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && mainPosition.is_fixed">
                                            <span @click="unfixMainPosition(mainPosition.id)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                {{ $t('Canceling a fixed term') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <span @click="openDeleteMainPositionModal(mainPosition)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true"/>
                                                {{ $t('Delete') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a @click="duplicateMainPosition(mainPosition.id)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z" />
                                                </svg>
                                                {{ $t('Duplicate') }}
                                            </a>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>
        </div>
        <div @click="addSubPosition(mainPosition.id)" v-if="this.$can('edit budget templates') || !table.is_template" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
            <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                {{ $t('Sub position') }}
                <PlusCircleIcon class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full" />
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
            <tr class=" xsWhiteBold flex h-10 w-full text-right text-lg items-center" :class="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-buttonBlue' : 'bg-primary'">
                <td class="w-28"></td>
                <td class="w-28"></td>
                <td class="w-72">SUM</td>
                <td v-if="mainPosition.sub_positions.length > 0" class="w-48 flex items-center" v-for="column in table.columns.slice(3)" v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                    <div class="w-48 my-4 p-1 flex group relative justify-end items-center" :class="mainPosition.columnSums[column.id]?.sum < 0 ? 'text-red-500' : ''">
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-if="mainPosition.columnSums[column.id]?.hasComments && mainPosition.columnSums[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_and_adjustments_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'comment')" v-else-if="mainPosition.columnSums[column.id]?.hasComments" src="/Svgs/IconSvgs/icon_linked_adjustments_white.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                        <img @click="openMainPositionSumDetailModal(mainPosition, column, 'moneySource')" v-else-if="mainPosition.columnSums[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_money_source_white.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                        <span>{{mainPosition.columnSums[column.id]?.sum.toLocaleString() }}</span>
                        <div class="hidden group-hover:block absolute right-0 z-50 -mr-6" @click="openMainPositionSumDetailModal(mainPosition, column)">
                            <PlusCircleIcon class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full " />
                        </div>
                    </div>
                </td>
            </tr>
            </thead>
            <div @click="addMainPosition(mainPosition)" v-if="this.$can('edit budget templates') || !table.is_template" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                    {{ $t('Main position') }}
                    <PlusCircleIcon class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full" />
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
import {useForm} from "@inertiajs/inertia-vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import Permissions from "@/mixins/Permissions.vue";
import SageAssignedDataModal from "@/Layouts/Components/SageAssignedDataModal.vue";

export default {
    mixins: [Permissions],
    name: "MainPositionComponent",
    components: {
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
        ConfirmationComponent
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
        'openCellDetailModal'
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
