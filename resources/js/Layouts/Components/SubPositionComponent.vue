<template>
    <th class="bg-silverGray xxsDark w-full">
        <div class="flex" @mouseover="showMenu = 'subPosition' + subPosition.id" @mouseout="showMenu = null">
            <div class="pl-2 xxsDark w-full flex items-center h-10" v-if="!subPosition.clicked">
                <div @click="subPosition.clicked = !subPosition.clicked">
                    {{ subPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3" @click="openCloseMainPosition">
                    <ChevronUpIcon v-if="!subPosition.closed" class="h-6 w-6 text-primary my-auto" />
                    <ChevronDownIcon v-else class="h-6 w-6 text-primary my-auto" />
                </button>
            </div>
            <div v-else class="flex w-full">
                <input class="my-2 ml-1 xxsDark" type="text" v-model="subPosition.name" @focusout="updateSubPositionName(subPosition); subPosition.clicked = !subPosition.clicked">
                <button class="my-auto w-6 ml-3" @click="subPosition.closed = !subPosition.closed">
                    <ChevronUpIcon v-if="!subPosition.closed" class="h-6 w-6 text-primary my-auto" />
                    <ChevronDownIcon v-else class="h-6 w-6 text-primary my-auto" />
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="flex flex-wrap w-8">
                    <div class="flex">
                        <Menu as="div" class="my-auto relative" v-if="this.$page.props.can.edit_budget_templates || !table.is_template">
                             <!-- v-show="showMenu === 'subPosition' + subPosition.id"-->
                            <div class="flex">
                                <MenuButton class="flex bg-tagBg p-0.5 rounded-full">
                                    <DotsVerticalIcon class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto" aria-hidden="true"/>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                <MenuItems class="origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !subPosition.is_fixed">
                                            <span @click="fixSubPosition(subPosition.id)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                Festschreiben
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-slot="{ active }" v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && subPosition.is_fixed">
                                            <span @click="unfixSubPosition(subPosition.id)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                Festschreibung aufheben
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <span @click="openDeleteSubPositionModal(subPosition)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true"/>
                                                Löschen
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a @click="duplicateSubpostion(subPosition.id)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z" />
                                                </svg>
                                                Duplizieren
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
        <table class="w-full" v-if="!subPosition.closed">
            <tbody class="bg-secondaryHover w-full">
            <div v-if="subPosition.sub_position_rows?.length > 0" v-for="(row,rowIndex) in subPosition.sub_position_rows">
                <tr v-show="!(row.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)" :class="[rowIndex !== 0 && hoveredRow !== row.id ? '': '', hoveredRow === row.id && (this.$page.props.can.edit_budget_templates || !table.is_template) ? 'border-buttonBlue ' : '']" @mouseover="hoveredRow = row.id" @mouseout="hoveredRow = null" class="bg-secondaryHover flex justify-between items-center border-2">
                    <div class="flex items-center">
                        <td v-for="(cell,index) in row.cells"
                            v-show="!(cell.column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)"
                            :class="[index <= 1 ? 'w-28' : index === 2 ? 'w-72 ' : 'w-48 ', '', checkCellColor(cell,mainPosition,subPosition), cell.column.is_locked ? 'bg-[#A7A6B120]' : '']">
                            <div
                                :class="[row.commented || cell.commented || cell.column.commented ? 'xsLight' : '', index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end', cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']"
                                class="my-4 h-6 flex items-center"
                                v-if="!cell.clicked">
                                <div class=" flex items-center">
                                    <div class="cursor-pointer" @click="handleCellClick(cell, 'comment')" v-if="cell.comments_count > 0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.068.157 2.148.279 3.238.364.466.037.893.281 1.153.671L12 21l2.652-3.978c.26-.39.687-.634 1.153-.67 1.09-.086 2.17-.208 3.238-.365 1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                    </div>
                                    <img @click="handleCellClick(cell, 'calculation')" v-if="cell.calculations_count > 0" src="/Svgs/IconSvgs/icon_linked_adjustments.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                                    <img @click="handleCellClick(cell, 'moneysource')" v-if="cell.linked_money_source_id !== null" src="/Svgs/IconSvgs/icon_linked_money_source.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                                    <div @click="handleCellClick(cell)">
                                    {{ index < 3 ? cell.value : Number(cell.value)?.toLocaleString() }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center" :class="index <= 1 ? 'w-24 mr-5' : index === 2 ? 'w-72 mr-12' : 'w-48 ml-5'" v-else-if="cell.clicked && cell.column.type === 'empty' && !cell.column.is_locked">
                                <input :ref="`cell-${cell.id}`" :class="index <= 1 ? 'w-20 mr-2' : index === 2 ? 'w-60 mr-2' : 'w-44 text-right'" class="my-2 xsDark  appearance-none z-10" type="text" :disabled="!this.$page.props.can.edit_budget_templates && table.is_template" v-model="cell.value" @keypress="isNumber($event, index)" @focusout="updateCellValue(cell, mainPosition.is_verified, subPosition.is_verified)">
                                <PlusCircleIcon v-if="index > 2" @click="openCellDetailModal(cell)" class="h-6 w-6 flex-shrink-0 -ml-3 relative z-50 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full" />
                            </div>
                            <div :class="[row.commented ? 'xsLight' : 'xsDark', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48 text-right', cell.value < 0 ? 'text-red-500' : '']" class="my-4 h-6 flex items-center justify-end" @click="cell.clicked = !cell.clicked && cell.column.is_locked" v-else>
                                <img v-if="cell.linked_money_source_id !== null && (cell.comments_count > 0 || cell.calculations_count > 0)" src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg" class="h-6 w-6 mr-1"/>
                                <img v-if="cell.comments_count > 0 || cell.calculations_count > 0" src="/Svgs/IconSvgs/icon_linked_adjustments.svg" class="h-5 w-5 mr-1"/>
                                <img v-if="cell.linked_money_source_id !== null" src="/Svgs/IconSvgs/icon_linked_money_source.svg" class="h-6 w-6 mr-1"/>
                                {{ index < 3 ? cell.value : Number(cell.value)?.toLocaleString() }}
                                <PlusCircleIcon v-if="index > 2 && cell.clicked" @click="openCellDetailModal(cell)" class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full" />
                            </div>
                        </td>
                    </div>
                    <Menu as="div"
                          :class="[hoveredRow === row.id ? '' : 'hidden', 'my-auto mr-0.5 relative']" v-if="this.$page.props.can.edit_budget_templates || !table.is_template">
                        <div class="flex">
                            <MenuButton
                                class="flex bg-tagBg p-0.5 rounded-full">
                                <DotsVerticalIcon
                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
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
                                class="z-20 origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1" >
                                    <MenuItem v-slot="{ active }"
                                              v-if="row.commented === false"
                                              @click="updateRowCommented(row.id, true)">
                                        <span
                                            @click=""
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                            Ausklammern
                                        </span>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }"
                                              v-else
                                              @click="updateRowCommented(row.id, false)">
                                        <span
                                            @click=""
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                            Positionen einbeziehen
                                        </span>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <span
                                            @click="duplicateRow(row.id)"
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                            Duplizieren
                                        </span>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <span
                                            @click="openDeleteRowModal(row)"
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                            Löschen
                                        </span>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </tr>
                <div @click="addRowToSubPosition(subPosition, row)" v-if="this.$page.props.can.edit_budget_templates || !table.is_template" class="group cursor-pointer z-10 relative h-0.5 flex justify-center hover:border-dashed border-1 border-silverGray hover:border-t-2 hover:border-buttonBlue">
                    <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                        Zeile
                        <PlusCircleIcon class="h-6 w-6 ml-2 text-secondaryHover bg-buttonBlue rounded-full" />
                    </div>
                </div>
            </div>
            <div v-else @click="addRowToSubPosition(subPosition, row)" v-if="this.$page.props.can.edit_budget_templates || !table.is_template" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                    Zeile
                    <PlusCircleIcon class="h-6 w-6 ml-2 text-secondaryHover bg-buttonBlue rounded-full" />
                </div>
            </div>
            <tr class="bg-silverGray xsDark flex h-10 w-full text-right">
                <td class="w-28"></td>
                <td class="w-28"></td>
                <td class="w-72 my-2">SUM</td>
                <td v-if="subPosition.sub_position_rows.length > 0" class="flex items-center w-48" v-for="column in columns.slice(3)" v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                    <div class="my-4 w-48 p-1" :class="subPosition.columnSums[column.id]?.sum < 0 ? 'text-red-500' : ''">
                        <div class="flex group relative justify-end items-center">
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'comment')" v-if="subPosition.columnSums[column.id]?.hasComments && subPosition.columnSums[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'comment')" v-else-if="subPosition.columnSums[column.id]?.hasComments" src="/Svgs/IconSvgs/icon_linked_adjustments.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'moneySource')" v-else-if="subPosition.columnSums[column.id]?.hasMoneySource" src="/Svgs/IconSvgs/icon_linked_money_source.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                            <span>
                                {{subPosition.columnSums[column.id]?.sum.toLocaleString() }}
                            </span>
                            <div class="hidden group-hover:block absolute right-0 z-50 -mr-6" @click="openSubPositionSumDetailModal(subPosition, column)" v-if="this.$page.props.can.edit_budget_templates || !table.is_template">
                                <PlusCircleIcon class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full " />
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <div @click="addSubPosition(mainPosition.id, subPosition)" v-if="this.$page.props.can.edit_budget_templates || !table.is_template" class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
            <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                Unterposition
                <PlusCircleIcon class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full" />
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
import {Link, useForm} from "@inertiajs/inertia-vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {nextTick} from "vue";
import Permissions from "@/mixins/Permissions.vue";

export default {
    mixins: [Permissions],
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
        ConfirmationComponent,
        Link
    },
    props: ['subPosition', 'mainPosition', 'columns', 'project', 'table', 'projectManagers'],
    emits: [
        'openDeleteModal',
        'openVerifiedModal',
        'openErrorModal',
        'openCellDetailModal',
        'openSubPositionSumDetailModal'
    ],
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
                project_title: this.project?.name,
                table_id: this.table.id,
            }),
            colors: {
                whiteColumn: 'whiteColumn',
                darkBlueColumn: 'darkBlueColumn',
                darkGreenColumn: 'darkGreenColumn',
                darkLightBlueColumn: 'darkLightBlueColumn',
                lightBlueNew: 'lightBlueNew',
                greenColumn: 'greenColumn',
                lightGreenColumn: 'lightGreenColumn',
                orangeColumn: 'orangeColumn',
                redColumn: 'redColumn',
                pinkColumn: 'pinkColumn'
            },
            updateCellForm: useForm({
                column_id: null,
                value: null,
                sub_position_row_id: null,
                is_verified: false
            })
        }
    },
    mounted() {
        // check if main Position in localStorage in "closedSubPositions"
        this.checkIfSubPositionClosed()
    },
    updated() {
        this.checkIfSubPositionClosed()
    },
    beforeUnmount() {
        // remove localeStorage key "closedSubPositions"
        localStorage.removeItem('closedSubPositions')
    },
    methods: {
        updateRowCommented(rowId, bool) {
            this.$inertia.patch(
                route(
                    'project.budget.row.commented',
                    {
                        row: rowId
                    }
                ),
                {
                    commented: bool
                },
                {
                    preserveScroll: true
                }
            );
        },
        duplicateRow(rowId) {
            this.$inertia.post(
                route(
                    'project.budget.sub-position.duplicate.row',
                    {
                        subPositionRow: rowId
                    }
                ),
                null,
                {
                    preserveScroll: true
                }
            )
        },
        checkIfSubPositionClosed(){
            if(localStorage.getItem('closedSubPositions') !== null){
                let closedSubPositions = JSON.parse(localStorage.getItem('closedSubPositions'))
                // add fail over if closedMainPositions is not an array
                if(!Array.isArray(closedSubPositions)){
                    closedSubPositions = []
                }
                let index = closedSubPositions.findIndex((subPosition) => subPosition.id === this.subPosition.id)
                if(index !== -1){
                    this.subPosition.closed = closedSubPositions[index].closed
                }
            }
        },
        openCloseMainPosition(){
            this.subPosition.closed = !this.subPosition.closed
            if(localStorage.getItem('closedSubPositions') === null){
                localStorage.setItem('closedSubPositions', JSON.stringify([{
                    id: this.subPosition.id,
                    closed: this.subPosition.closed
                }]))
            } else {
                let closedSubPositions = JSON.parse(localStorage.getItem('closedSubPositions'))
                // add fail over if closedMainPositions is not an array
                if(!Array.isArray(closedSubPositions)){
                    closedSubPositions = []
                }
                let index = closedSubPositions.findIndex((subPosition) => subPosition.id === this.subPosition.id)
                if(index === -1){
                    closedSubPositions.push({
                        id: this.subPosition.id,
                        closed: this.subPosition.closed
                    })
                } else {
                    closedSubPositions[index].closed = this.subPosition.closed
                }
                localStorage.setItem('closedSubPositions', JSON.stringify(closedSubPositions))
            }
        },
        duplicateSubpostion(subPositionId) {
            this.$inertia.post(route('project.budget.sub-position.duplicate', subPositionId), {}, {
                preserveScroll: true,
                preserveState: true
            })
        },
        isNumber(event, index) {
            if (index > 2 && !(new RegExp('^([0-9])$')).test(event.key)) {
                event.preventDefault();
            }
        },
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
                project_id: this.project?.id,
                table_id: this.table.id,
            }, {
                preserveScroll: true,
                preserveState: true
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
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        removeVerification(position, type) {
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        checkColumnsLocked(){
            return this.columns.some(column => column.is_locked === true);
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
                table_id: this.table.id,
                sub_position_id: subPosition.id,
                positionBefore: row ? row.position : -1
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },
        updateCellValue(cell, mainPositionVerified, subPositionVerified) {
            if (cell.value === null || cell.value === '') {
                cell.value = 0;
            }

            this.updateCellForm.column_id = cell.column.id;
            this.updateCellForm.value = cell.value;
            this.updateCellForm.sub_position_row_id = cell.sub_position_row_id;
            this.updateCellForm.is_verified =  mainPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED';
            //
            this.updateCellForm.patch(route('project.budget.cell.update'), {
                preserveState: true,
                preserveScroll: true,
            })
        },
        openCellDetailModal(cell) {
            this.$emit('openCellDetailModal', cell)
        },
        openSubPositionSumDetailModal(subPosition, column, type = 'comment') {
            this.$emit('openSubPositionSumDetailModal', subPosition, column, type)
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        openDeleteRowModal(row) {
            this.rowToDelete = row;
            this.showDeleteModal = true;
            if(!this.checkColumnsLocked()){
                this.confirmationTitle = 'Zeile löschen';
                this.confirmationDescription = 'Bist du sicher, dass du diese Zeile löschen möchtest? Sämtliche Verlinkungen etc. werden ebenfalls gelöscht.';
                this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.rowToDelete, 'row')
            }else{
                this.confirmationTitle = 'Zeile löschen nicht möglich'
                this.confirmationDescription = 'Solange eine Spalte gesperrt ist, kannst du keine Zeile löschen.'
                this.$emit('openErrorModal', this.confirmationTitle, this.confirmationDescription)
            }

        },
        async handleCellClick(cell, type = '') {

            if(type === 'comment'){
                this.$emit('openCellDetailModal', cell, 'comment');
            } else if(type === 'moneysource'){
                this.$emit('openCellDetailModal', cell, 'moneySource');
            } else if(type === 'calculation'){
                this.$emit('openCellDetailModal', cell, 'calculation');
            } else if(cell.calculations_count > 0){
                this.$emit('openCellDetailModal', cell, 'calculation')
            } else {
                cell.clicked = !cell.clicked

                if(cell.clicked) {
                    await nextTick()

                    this.$refs[`cell-${cell.id}`][0].select();
                }
            }

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
        checkCellColor(cell, mainPosition, subPosition) {
            let cssString = '';
            if (cell.value !== cell.verified_value) {
                if (mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'
                    || mainPosition.is_fixed || subPosition.is_fixed) {
                    cssString += ' bg-red-300 '
                    cssString += ' xsWhiteBold '
                } else {
                    if (cell.column.color !== 'whiteColumn') {
                        cssString += ' xsDark '
                        cssString += cell.column.color;
                        if(cell.column.is_locked) {
                            cssString += ' lockedColumn '
                        }
                    } else {
                        cssString += ' xsDark '
                    }
                }
            } else {
                if (cell.column.color !== 'whiteColumn') {
                    cssString += ' xsDark '
                    cssString += cell.column.color;
                    if(cell.column.is_locked) {
                        cssString += ' lockedColumn '
                    }
                } else {
                    cssString += ' xsDark '
                }
            }


            return cssString
        },
        fixSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.fix.sub-position'), {
                subPositionId: subPositionId,
                project_id: this.project?.id
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
        unfixSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.unfix.sub-position'), {
                subPositionId: subPositionId,
                project_id: this.project?.id
            }, {
                preserveScroll: true,
                preserveState: true
            })
        }

    }

}
</script>

<style scoped>
.lockedColumn {
    filter: brightness(0.9);
}

.whiteColumn {
    background-color: #FCFCFBFF;
}

.darkBlueColumn {
    background-color: #D3DADE;
}

.darkGreenColumn {
    background-color: #DBE9E8;
}

.darkLightBlueColumn {
    background-color: #D2E9F3;
}

.lightBlueNew {
    background-color: #DAF3F6;
}

.greenColumn {
    background-color: #D7EEE0;
}

.lightGreenColumn {
    background-color: #E7F3DE;
}

.yellowColumn {
    background-color: #FCF0DB;
}

.orangeColumn {
    background-color: #FBE4DA;
}

.redColumn {
    background-color: #F7D9E7;
}

.pinkColumn {
    background-color: #E1D1DC;
}

@layer base {
    input[type=number].appearance-none::-webkit-inner-spin-button,
    input[type=number].appearance-none::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number].appearance-none {
        -moz-appearance:textfield;
    }
}

</style>
