<template>
    <th class="bg-silverGray xxsDark w-full">
        <div class="flex" @mouseover="showMenu = 'subPosition' + subPosition.id"
             @mouseout="showMenu = null">
            <div
                class="pl-2 xxsDark w-full flex items-center h-10"
                v-if="!subPosition.clicked">
                <div @click="subPosition.clicked = !subPosition.clicked">
                    {{ subPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3"
                        @click="subPosition.closed = !subPosition.closed">
                    <ChevronUpIcon v-if="!subPosition.closed"
                                   class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                </button>
            </div>
            <div v-else class="flex w-full">
                <input
                    class="my-2 ml-1 xxsDark" type="text"
                    v-model="subPosition.name"
                    @focusout="updateSubPositionName(subPosition); subPosition.clicked = !subPosition.clicked">
                <button class="my-auto w-6 ml-3"
                        @click="subPosition.closed = !subPosition.closed">
                    <ChevronUpIcon v-if="!subPosition.closed"
                                   class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="text-white w-28 flex items-center"
                     v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && subPosition.verified?.requested !== this.$page.props.user.id">
                    <p class="xxsLight">wird verifiziert </p>
                    <!-- TODO: SVG ersetzen mit IMG TAG -->
                    <svg xmlns="http://www.w3.org/2000/svg"
                         xmlns:xlink="http://www.w3.org/1999/xlink" class="ml-1"
                         width="19"
                         height="14.292" viewBox="0 0 19 14.292">
                        <defs>
                            <clipPath id="clip-path">
                                <rect id="Rechteck_458" data-name="Rechteck 458"
                                      width="5.138"
                                      height="3.634" fill="#fcfcfb"/>
                            </clipPath>
                        </defs>
                        <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                              d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                              transform="translate(0 0.607)" fill="#fcfcfb"/>
                        <g id="Gruppe_962" data-name="Gruppe 962"
                           transform="translate(-412 -311)">
                            <g id="Ellipse_147" data-name="Ellipse 147"
                               transform="translate(418 311)" fill="#27233c"
                               stroke="#fcfcfb"
                               stroke-width="1">
                                <circle cx="6.5" cy="6.5" r="6.5" stroke="none"/>
                                <circle cx="6.5" cy="6.5" r="6" fill="none"/>
                            </g>
                            <g id="Gruppe_962-2" data-name="Gruppe 962"
                               transform="translate(423 314.945)"
                               clip-path="url(#clip-path)">
                                <path id="Pfad_1344" data-name="Pfad 1344"
                                      d="M5.1,1.418a.534.534,0,0,0-.7-.286L1.775,2.23,1.029.337a.533.533,0,1,0-.992.39L1.183,3.633,4.811,2.115a.533.533,0,0,0,.286-.7"
                                      transform="translate(0 0)" fill="#fcfcfb"/>
                            </g>
                        </g>
                    </svg>
                </div>
                <div
                    class="text-white w-44 flex items-center text-center cursor-pointer"
                    @click="verifiedSubPosition(subPosition.verified?.sub_position_id)"
                    v-if="subPosition.verified?.requested === this.$page.props.user.id && subPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="xxsLight">Als verifiziert markieren</p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" class="ml-1"
                         height="20" viewBox="0 0 20 20">
                        <g id="check_btn" transform="translate(-1234 -671.05)">
                            <g id="Pfad_1370" data-name="Pfad 1370"
                               transform="translate(1234 671.05)" fill="none">
                                <path d="M10,0A10,10,0,1,1,0,10,10,10,0,0,1,10,0Z"
                                      stroke="none"/>
                                <path
                                    d="M 10 1 C 5.037380218505859 1 1 5.037380218505859 1 10 C 1 14.96261978149414 5.037380218505859 19 10 19 C 14.96261978149414 19 19 14.96261978149414 19 10 C 19 5.037380218505859 14.96261978149414 1 10 1 M 10 0 C 15.52285003662109 0 20 4.477149963378906 20 10 C 20 15.52285003662109 15.52285003662109 20 10 20 C 4.477149963378906 20 0 15.52285003662109 0 10 C 0 4.477149963378906 4.477149963378906 0 10 0 Z"
                                    stroke="none" fill="#fcfcfb"/>
                            </g>
                            <path id="Pfad_157" data-name="Pfad 157"
                                  d="M-1151.25,4789.252l3.142,3.142,6.013-6.013"
                                  transform="translate(2390.673 -4108.337)"
                                  fill="none" stroke="#fcfcfb" stroke-width="1.5"/>
                        </g>
                    </svg>
                </div>
                <div
                    class="text-white w-44 flex items-center text-center justify-end mr-2"
                    v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="xxsLight">verifiziert</p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11.975"
                         height="13.686" class="ml-1" viewBox="0 0 11.975 13.686">
                        <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                              d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                              fill="#fcfcfb"/>
                    </svg>
                </div>
                <div class="flex flex-wrap w-8">
                    <div class="flex">
                        <Menu as="div" class="my-auto relative"
                              v-show="showMenu === 'subPosition' + subPosition.id">
                            <div class="flex">
                                <MenuButton
                                    class="flex">
                                    <DotsVerticalIcon
                                        class="mr-3 flex-shrink-0 h-6 w-6 text-darkGray my-auto"
                                        aria-hidden="true"/>
                                </MenuButton>
                            </div>
                            <transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">

                                        <MenuItem v-slot="{ active }"
                                                  v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED'">
                                                                                    <span
                                                                                        @click="openVerifiedModalSub(subPosition)"
                                                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                        <TrashIcon
                                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                            aria-hidden="true"/>
                                                                                        Von User verifizieren lassen
                                                                                    </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && subPosition.verified?.requested_by === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="requestRemove(subPosition, 'sub')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierungsanfrage zurücknehmen
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && subPosition.verified?.requested === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="removeVerification(subPosition, 'sub')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierung aufheben
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !subPosition.is_fixed">
                                                                                <span
                                                                                    @click="fixSubPosition(subPosition.id)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Festschreiben
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && subPosition.is_fixed">
                                                                                <span
                                                                                    @click="unfixSubPosition(subPosition.id)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Festschreibung aufheben
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                                                                    <span
                                                                                        @click="openDeleteSubPositionModal(subPosition)"
                                                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                        <TrashIcon
                                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                            aria-hidden="true"/>
                                                                                        Löschen
                                                                                    </span>
                                        </MenuItem>


                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>
        </div>
        <table class="w-full" v-if="!subPosition.closed">
            <tbody class="bg-secondaryHover w-full">
            <div v-if="subPosition.sub_position_rows?.length > 0"
                 v-for="(row,rowIndex) in subPosition.sub_position_rows">
                <tr
                    :class="[rowIndex !== 0 && hoveredRow !== row.id ? '': '', hoveredRow === row.id ? 'border-buttonBlue ' : '']"
                    @mouseover="hoveredRow = row.id" @mouseout="hoveredRow = null"
                    class="bg-secondaryHover flex justify-between items-center border-2"
                >
                    <div class="flex items-center">
                        <PlusCircleIcon @click="openRowDetailModal(row)"
                                        :class="hoveredRow === row.id ? '' : 'hidden'"
                                        class="h-6 w-6 absolute -ml-3 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                        <td v-for="(cell,index) in row.cells"
                            :class="[index <= 1 ? 'w-28' : index === 2 ? 'w-72' : 'w-48', checkCellColor(cell,mainPosition,subPosition)]">
                            <div
                                :class="[row.commented ? 'xsLight' : '', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48', cell.value < 0 ? 'text-red-500' : '']"
                                class="my-4 h-6 flex items-center justify-end"
                                @click="cell.clicked = !cell.clicked"
                                v-if="!cell.clicked">
                                <div class="pr-2">
                                    <img v-if="cell.linked_money_source_id !== null"
                                         src="/Svgs/IconSvgs/icon_linked_moneySource.svg"
                                         class="h-6 w-6"/>
                                    {{ cell.value }}
                                </div>
                            </div>
                            <div class="flex items-center justify-end"
                                 :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'"
                                 v-else-if="cell.clicked && cell.column.type === 'empty' && !cell.column.is_locked">
                                <input
                                    :class="index <= 1 ? 'w-20 mr-2' : index === 2 ? 'w-60 mr-2' : 'w-44'"
                                    class="my-2 xsDark text-right"
                                    :type="index > 2 ? 'number' : 'text'"
                                    v-model="cell.value"
                                    @focusout="updateCellValue(cell, mainPosition.is_verified, subPosition.is_verified)">
                                <PlusCircleIcon v-if="index > 2"
                                                @click="openCellDetailModal(cell)"
                                                class="h-6 w-6 flex-shrink-0 -ml-3 relative cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                            </div>
                            <div
                                :class="[row.commented ? 'xsLight' : 'xsDark', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48', cell.value < 0 ? 'text-red-500' : '']"
                                class="my-4 h-6 flex items-center"
                                @click="cell.clicked = !cell.clicked && cell.column.is_locked"
                                v-else>
                                <img v-if="cell.linked_money_source_id !== null"
                                     src="/Svgs/IconSvgs/icon_linked_moneySource.svg"
                                     class="h-6 w-6"/>
                                {{ cell.value }}
                                <PlusCircleIcon v-if="index > 2 && cell.clicked"
                                                @click="openCellDetailModal(cell)"
                                                class="h-6 w-6 ml-3 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                            </div>

                        </td>

                    </div>
                    <XCircleIcon @click="openDeleteRowModal(row)"
                                 :class="hoveredRow === row.id ? '' : 'hidden'"
                                 class="h-6 w-6 -mr-3 cursor-pointer justify-end text-secondaryHover bg-error rounded-full"></XCircleIcon>

                </tr>
                <div @click="addRowToSubPosition(subPosition, row)"
                     class="group cursor-pointer z-10 relative h-0.5 flex justify-center hover:border-dashed border-1 border-silverGray hover:border-t-2 hover:border-buttonBlue">
                    <div
                        class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                        Zeile
                        <PlusCircleIcon
                            class="h-6 w-6 ml-2 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                    </div>
                </div>
            </div>
            <div v-else @click="addRowToSubPosition(subPosition, row)"
                 class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                <div
                    class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                    Zeile
                    <PlusCircleIcon
                        class="h-6 w-6 ml-2 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                </div>
            </div>
            <tr class="bg-silverGray xsDark flex h-10 w-full text-right">
                <td class="w-28"></td>
                <td class="w-28"></td>
                <td class="w-72 my-2">SUM</td>
                <td v-if="subPosition.sub_position_rows.length > 0"
                    class="flex items-center w-48"
                    v-for="column in columns.slice(3)">
                    <div class="my-4 w-48 p-1"
                         :class="subPosition.columnSums[column.id] < 0 ? 'text-red-500' : ''">
                        {{
                            subPosition.columnSums[column.id]
                        }}
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <div @click="addSubPosition(mainPosition.id, subPosition)"
             class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
            <div
                class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                Unterposition
                <PlusCircleIcon
                    class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
            </div>
        </div>
    </th>
</template>

<confirmation-component
    v-if="showDeleteModal"
    confirm="Löschen"
    :titel="this.confirmationTitle"
    :description="this.confirmationDescription"
    @closed="afterConfirm"/>

<script>

import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Inertia} from "@inertiajs/inertia";
import {useForm} from "@inertiajs/inertia-vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";


export default {
    name: "SubPositionComponent",
    components: {
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
    props: ['subPosition', 'mainPosition', 'columns', 'project', 'budget'],
    emits: ['openDeleteModal', 'openVerifiedModal','openRowDetailModal'],
    data() {
        return {
            showMenu: null,
            hoveredRow: null,
            showDeleteModal: false,
            rowToDelete: null,
            subPositionToDelete: null,
            showSuccessModal: false,
            confirmationTitle: '',
            confirmationDescription: '',
            successHeading: '',
            successDescription: '',
            showVerifiedModal: false,
            positionDefault: {
                position: 0
            },
            verifiedTexts: {
                title: 'Verifizierung',
                positionTitle: '',
                description: 'Sind alle Zahlen richtig kalkuliert? Ist die Kalkulation plausibel? Lasse deine Hauptposition durch eine Nutzer*in verifizieren. '
            },
            submitVerifiedModalData: useForm({
                is_main: false,
                is_sub: false,
                id: null,
                user: '',
                position: [],
                project_title: this.project.name,
                table_id: this.budget.table.id,
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
    methods: {
        afterConfirm(bool) {
            if (!bool) return this.showDeleteModal = false;

            this.deletePosition();

        },
        updateSubPositionName(subPosition) {
            this.$inertia.patch(route('project.budget.sub-position.update-name'), {
                subPosition_id: subPosition.id,
                subPositionName: subPosition.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        verifiedSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.sub-position'), {
                subPositionId: subPositionId,
                table_id: this.budget.table.id,
            })
        },
        openVerifiedModalSub(subPosition) {
            this.verifiedTexts.positionTitle = subPosition.name
            this.submitVerifiedModalData.is_sub = true
            this.submitVerifiedModalData.id = subPosition.id
            this.submitVerifiedModalData.position = subPosition
            this.showVerifiedModal = true
            this.$emit('openVerifiedModal', this.submitVerifiedModalData.is_main, this.submitVerifiedModalData.is_sub, this.submitVerifiedModalData.id, this.submitVerifiedModalData.position)
        },
        requestRemove(position, type) {
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            })
        },
        removeVerification(position, type) {
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            })
        },
        openDeleteSubPositionModal(subPosition) {
            this.confirmationTitle = 'Unterposition löschen';
            this.confirmationDescription = 'Bist du sicher, dass du die Unterposition ' + subPosition.name + ' löschen möchtest?'
            this.subPositionToDelete = subPosition;
            this.showDeleteModal = true;
            this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.subPositionToDelete, 'sub')
        },
        addRowToSubPosition(subPosition, row) {
            this.$inertia.post(route('project.budget.sub-position-row.add'), {
                table_id: this.budget.table.id,
                sub_position_id: subPosition.id,
                positionBefore: row ? row.position : -1
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },
        updateCellValue(cell, mainPositionVerified, subPositionVerified) {
            cell.clicked = !cell.clicked;
            if (cell.value === null || cell.value === '') {
                cell.value = 0;
            }

            this.$inertia.patch(route('project.budget.cell.update'), {
                column_id: cell.column.id,
                value: cell.value,
                sub_position_row_id: cell.sub_position_row_id,
                is_verified: mainPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED'
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },
        openRowDetailModal(row){
          this.$emit('openRowDetailModal',row)
        },
        openCellDetailModal(column) {
            this.$emit('openCellDetailModal', column)
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        openDeleteRowModal(row) {
            this.confirmationTitle = 'Zeile löschen';
            this.confirmationDescription = 'Bist du sicher, dass du diese Zeile löschen möchtest? Sämtliche Verlinkungen etc. werden ebenfalls gelöscht.';
            this.rowToDelete = row;
            this.showDeleteModal = true;
            this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.rowToDelete, 'row')
        },
        addSubPosition(mainPositionId, subPosition = null) {

            let subPositionBefore = subPosition

            if (!subPositionBefore) {
                subPositionBefore = {
                    position: 0
                }
            }

            this.$inertia.post(route('project.budget.sub-position.add'), {
                table_id: this.budget.table.id,
                main_position_id: mainPositionId,
                positionBefore: subPositionBefore.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        checkCellColor(cell, mainPosition, subPosition) {
            let cssString = '';
            if (cell.value !== cell.verified_value) {
                if (mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'
                    || mainPosition.is_fixed || subPosition.is_fixed) {
                    cssString += ' bg-red-300 '
                    cssString += ' xsWhiteBold '
                } else {
                    if (cell.column.color !== 'whiteColumn') {
                        cssString += ' xsWhiteBold '
                        cssString += cell.column.color;
                    } else {
                        cssString += ' xsDark '
                    }
                }
            } else {
                if (cell.column.color !== 'whiteColumn') {
                    cssString += ' xsWhiteBold '
                } else {
                    cssString += ' xsDark '
                }
            }


            return cssString
        },
        fixSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.fix.sub-position'), {
                subPositionId: subPositionId
            })
        },
        unfixSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.unfix.sub-position'), {
                subPositionId: subPositionId
            })
        }

    },

}
</script>

<style scoped>

/*
 greenColumn: '#50908E',
                yellowColumn: '#F0B54C',
                redColumn: '#D84387',
                lightGreenColumn: '#35A965'
 */
.whiteColumn {
    background-color: #FCFCFBFF;
}

.greenColumn {
    background-color: #50908E;
    border: 2px solid #1FC687;
}

.yellowColumn {
    background-color: #F0B54C;
}

.redColumn {
    background-color: #D84387;
}

.lightGreenColumn {
    background-color: #35A965;
}

</style>
