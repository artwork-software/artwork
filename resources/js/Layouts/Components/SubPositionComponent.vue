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
                <div class="flex flex-wrap w-8 z-50">
                    <div class="flex">
                        <Menu as="div" class="my-auto relative"
                              v-show="showMenu === 'subPosition' + subPosition.id">
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
                                    class="origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
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
                                        class="h-6 w-6 z-20 absolute -ml-3 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                        <td v-for="(cell,index) in row.cells"
                            :class="[index <= 1 ? 'w-28' : index === 2 ? 'w-72 ' : 'w-48 ', '', checkCellColor(cell,mainPosition,subPosition)]">
                            <div
                                :class="[row.commented || cell.commented ? 'xsLight' : '', index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end', cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']"
                                class="my-4 h-6 flex items-center"
                                @click="handleCellClick(cell)"
                                v-if="!cell.clicked">
                                <div class=" flex items-center">
                                    <img v-if="cell.linked_money_source_id !== null && (cell.comments_count > 0 || cell.calculations_count > 0)"
                                    src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                    class="h-6 w-6 mr-1"/>
                                    <img v-else-if="cell.comments_count > 0 || cell.calculations_count > 0"
                                         src="/Svgs/IconSvgs/icon_linked_adjustments.svg"
                                         class="h-5 w-5 mr-1"/>
                                    <img v-else-if="cell.linked_money_source_id !== null"
                                         src="/Svgs/IconSvgs/icon_linked_money_source.svg"
                                         class="h-6 w-6 mr-1"/>
                                    {{ index < 3 ? cell.value : Number(cell.value)?.toLocaleString() }}
                                </div>
                            </div>
                            <div class="flex items-center"
                                 :class="index <= 1 ? 'w-24 mr-5' : index === 2 ? 'w-72 mr-12' : 'w-48 ml-5'"
                                 v-else-if="cell.clicked && cell.column.type === 'empty' && !cell.column.is_locked">
                                <input
                                    :ref="`cell-${cell.id}`"
                                    :class="index <= 1 ? 'w-20 mr-2' : index === 2 ? 'w-60 mr-2' : 'w-44 text-right'"
                                    class="my-2 xsDark  appearance-none z-10"
                                    type="text"
                                    v-model="cell.value"
                                    @keypress="isNumber($event, index)"
                                    @focusout="updateCellValue(cell, mainPosition.is_verified, subPosition.is_verified)">
                                <PlusCircleIcon v-if="index > 2"
                                                @click="openCellDetailModal(cell)"
                                                class="h-6 w-6 flex-shrink-0 -ml-3 relative z-50 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full">
                                </PlusCircleIcon>
                            </div>
                            <div
                                :class="[row.commented ? 'xsLight' : 'xsDark', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48 text-right', cell.value < 0 ? 'text-red-500' : '']"
                                class="my-4 h-6 flex items-center justify-end"
                                @click="cell.clicked = !cell.clicked && cell.column.is_locked"
                                v-else>
                                <img v-if="cell.linked_money_source_id !== null && (cell.comments_count > 0 || cell.calculations_count > 0)"
                                     src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                     class="h-6 w-6 mr-1"/>
                                <img v-else-if="cell.comments_count > 0 || cell.calculations_count > 0"
                                     src="/Svgs/IconSvgs/icon_linked_adjustments.svg"
                                     class="h-5 w-5 mr-1"/>
                                <img v-else-if="cell.linked_money_source_id !== null"
                                     src="/Svgs/IconSvgs/icon_linked_money_source.svg"
                                     class="h-6 w-6 mr-1"/>
                                {{ index < 3 ? cell.value : Number(cell.value)?.toLocaleString() }}
                                <PlusCircleIcon v-if="index > 2 && cell.clicked"
                                                @click="openCellDetailModal(cell)"
                                                class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
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
                <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
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
                    class="flex items-center w-48" v-for="column in columns.slice(3)">
                    <div class="my-4 w-48 p-1" :class="subPosition.columnSums[column.id]?.sum < 0 ? 'text-red-500' : ''">
                        <div class="flex group relative justify-end items-center">

                            <img v-if="subPosition.columnSums[column.id]?.hasComments && subPosition.columnSums[column.id]?.hasMoneySource"
                                 src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                 class="h-6 w-6 mr-1"/>
                            <img v-else-if="subPosition.columnSums[column.id]?.hasComments"
                                 src="/Svgs/IconSvgs/icon_linked_adjustments.svg"
                                 class="h-5 w-5 mr-1"/>
                            <img v-else-if="subPosition.columnSums[column.id]?.hasMoneySource"
                                 src="/Svgs/IconSvgs/icon_linked_money_source.svg"
                                 class="h-6 w-6 mr-1"/>

                            <span>
                                {{subPosition.columnSums[column.id]?.sum.toLocaleString() }}
                            </span>
                            <div class="hidden group-hover:block absolute right-0 z-50 -mr-6" @click="openSubPositionSumDetailModal(subPosition, column)">
                                <PlusCircleIcon class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full " />
                            </div>
                        </div>
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
        'openRowDetailModal',
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
    methods: {
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
                onSuccess: () => {
                    console.log("updated", cell.value)
                }
            })
        },
        openRowDetailModal(row) {
            this.$emit('openRowDetailModal', row)
        },
        openCellDetailModal(cell) {
            this.$emit('openCellDetailModal', cell)
        },
        openSubPositionSumDetailModal(subPosition, column) {
            this.$emit('openSubPositionSumDetailModal', subPosition, column)
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
        async handleCellClick(cell) {

            if(cell.calculations_count > 0){
                this.$emit('openCellDetailModal', cell)
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
                    } else {
                        cssString += ' xsDark '
                    }
                }
            } else {
                if (cell.column.color !== 'whiteColumn') {
                    cssString += ' xsDark '
                    cssString += cell.column.color;
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

    },

}
</script>

<style scoped>

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
