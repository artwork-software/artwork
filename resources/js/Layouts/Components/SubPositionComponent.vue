<template>
    <th class="bg-silverGray xxsDark w-full">
        <div class="flex" @mouseover="showMenu = 'subPosition' + subPosition.id" @mouseout="showMenu = null">
            <div class="pl-2 xxsDark w-full flex items-center h-10" v-if="!subPosition.clicked">
                <div @click="subPosition.clicked = !subPosition.clicked">
                    {{ subPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3" @click="openCloseMainPosition">
                    <IconChevronUp stroke-width="1.5" v-if="!subPosition.closed" class="h-6 w-6 text-primary my-auto"/>
                    <IconChevronDown stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                </button>
            </div>
            <div v-else class="flex w-full">
                <input class="my-2 ml-1 xxsDark" type="text" v-model="subPosition.name"
                       @focusout="updateSubPositionName(subPosition); subPosition.clicked = !subPosition.clicked">
                <button class="my-auto w-6 ml-3" @click="subPosition.closed = !subPosition.closed">
                    <IconChevronUp stroke-width="1.5" v-if="!subPosition.closed" class="h-6 w-6 text-primary my-auto"/>
                    <IconChevronDown stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="flex flex-wrap w-8">
                    <div class="flex">
                        <Menu as="div" class="my-auto relative"
                              v-if="this.$can('edit budget templates') || !table.is_template">
                            <div class="flex">
                                <MenuButton class="flex bg-tagBg p-0.5 rounded-full">
                                    <IconDotsVertical stroke-width="1.5"
                                                      class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                                      aria-hidden="true"/>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="z-50 origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem
                                            v-show="this.$can('can add and remove verified states') || this.hasAdminRole()"
                                            v-slot="{ active }"
                                            v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !subPosition.is_fixed">
                                            <span @click="fixSubPosition(subPosition.id)"
                                                  :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconLock stroke-width="1.5"
                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                          aria-hidden="true"/>
                                                {{ $t('Commitment') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem
                                            v-show="this.$can('can add and remove verified states') || this.hasAdminRole()"
                                            v-slot="{ active }"
                                            v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && subPosition.is_fixed">
                                            <span @click="unfixSubPosition(subPosition.id)"
                                                  :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconLockOpen stroke-width="1.5"
                                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                              aria-hidden="true"/>
                                                {{ $t('Canceling a fixed term') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <span @click="openDeleteSubPositionModal(subPosition)"
                                                  :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconTrash stroke-width="1.5"
                                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                           aria-hidden="true"/>
                                                {{ $t('Delete') }}
                                            </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a @click="duplicateSubpostion(subPosition.id)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconCopy stroke-width="1.5"
                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                          aria-hidden="true"/>
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
        <table class="w-full" v-if="!subPosition.closed">
            <tbody class="bg-secondaryHover w-full">
            <SageDataDropElement v-if="$page.props.sageApiEnabled" :row="null" :tableId="table.id"
                                 :sub-position-id="subPosition.id"/>
            <div v-if="subPosition.sub_position_rows?.length > 0"
                 v-for="(row,rowIndex) in subPosition.sub_position_rows">
                <tr v-show="!(row.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)"
                    :class="[rowIndex !== 0 && hoveredRow !== row.id ? '': '', hoveredRow === row.id && (this.$can('edit budget templates') || !table.is_template) ? 'border-buttonBlue ' : '']"
                    @mouseover="hoveredRow = row.id" @mouseout="hoveredRow = null"
                    class="bg-secondaryHover flex justify-between items-center border-2">
                    <div class="flex items-center">
                        <td v-for="(cell,index) in row.cells"
                            v-show="!(cell.column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)"
                            :class="[index <= 1 ? 'w-28' : index === 2 ? 'w-72 ' : 'w-48 ', '', checkCellColor(cell,mainPosition,subPosition), cell.column.is_locked ? 'bg-[#A7A6B120]' : '']">
                            <div v-if="(index === 0 || index === 1) && this.$page.props.budgetAccountManagementGlobal">
                                <div
                                    :class="[row.commented || cell.commented || cell.column.commented ? 'xsLight' : '', index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end', cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']"
                                    class="my-4 h-6 flex items-center"
                                    v-if="!cell.clicked">
                                    <div class=" flex items-center">
                                        <div :class="cell.value === '' ? 'w-6 cursor-pointer h-6' : ''"
                                             @click="this.handleCellClick(cell, '', index, row)">
                                            {{ cell.value }}
                                        </div>
                                    </div>
                                </div>
                                <div :class="[row.commented || cell.commented || cell.column.commented ? 'xsLight' : '', index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end', cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']"
                                     class="my-4 h-6 flex items-center" v-else>
                                    <div class="flex flex-row items-center relative">
                                        <input v-model="cell.searchValue"
                                               :placeholder="cell.value"
                                               :ref="`cell-${cell.id}`"
                                               type="text"
                                               class="w-full"
                                               @input="this.handleBudgetManagementSearch(index, cell, (this.mainPosition.type !== 'BUDGET_TYPE_COST'))"
                                        />
                                        <XIcon class="w-10 h-10 cursor-pointer"
                                               @click="this.handleBudgetManagementSearchCancel(cell)"
                                        />
                                        <div v-if="cell.accountSearchResults" class="absolute w-64 z-20 top-10">
                                            <div v-if="cell.accountSearchResults.length > 0"
                                                 v-for="account in cell.accountSearchResults"
                                                 class="flex flex-col"
                                            >
                                                <div
                                                    class="p-3 cursor-pointer bg-primary hover:bg-buttonHover text-white"
                                                    @click="this.handleBudgetManagementSearchSelect(index, cell, account.account_number, mainPosition.is_verified, subPosition.is_verified)">
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
                                            <div v-else
                                                 class="text-nowrap p-3 cursor-pointer bg-primary hover:bg-buttonHover text-white">
                                                {{ $t('No Accounts found') }}
                                            </div>
                                        </div>
                                        <div v-if="cell.costUnitSearchResults" class="absolute w-64 z-20 top-10">
                                            <div v-if="cell.costUnitSearchResults.length > 0"
                                                 v-for="cost_unit in cell.costUnitSearchResults"
                                                 class="flex flex-col"
                                            >
                                                <div
                                                    class="p-3 cursor-pointer bg-primary hover:bg-buttonHover text-white"
                                                    @click="this.handleBudgetManagementSearchSelect(index, cell, cost_unit.cost_unit_number, mainPosition.is_verified, subPosition.is_verified)">
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
                                                 class="text-nowrap p-3 cursor-pointer bg-primary hover:bg-buttonHover text-white">
                                                {{ $t('No Cost Units found') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="group">
                                <div :class="[row.commented || cell.commented || cell.column.commented ? 'xsLight' : '',
                                    index <= 1 ? 'w-24 justify-start pl-3' : index === 2 ? 'w-72 justify-start pl-3' : 'w-48 pr-2 justify-end',
                                    cell.value < 0 ? 'text-red-500' : '', cell.value === '' || cell.value === null ? 'border-2 border-gray-300 ' : '']"
                                    class="my-4 h-6 flex items-center" v-if="!cell.clicked">
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
                                <div class="flex items-center"
                                     :class="index <= 1 ? 'w-24 mr-5' : index === 2 ? 'w-72 mr-12' : 'w-48 ml-5'"
                                     v-else-if="cell.clicked && cell.column.type === 'empty' && !cell.column.is_locked">
                                    <input :ref="`cell-${cell.id}`"
                                           :class="index <= 1 ? 'w-20 mr-2' : index === 2 ? 'w-60 mr-2' : 'w-44 text-right'"
                                           class="my-2 xsDark  appearance-none z-10" type="text"
                                           :disabled="!this.$can('edit budget templates') && table.is_template"
                                           v-model="cell.value"
                                           @keypress="isNumber($event, index)"
                                           @focusout="updateCellValue(cell, mainPosition.is_verified, subPosition.is_verified)">
                                    <IconCirclePlus stroke-width="1.5" v-if="index > 2"
                                                    @click="openCellDetailModal(cell)"
                                                    class="h-6 w-6 flex-shrink-0 -ml-3 relative z-50 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"/>
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
                                    <IconCirclePlus stroke-width="1.5" v-if="index > 2 && cell.clicked"
                                                    @click="openCellDetailModal(cell)"
                                                    class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"/>
                                </div>
                            </div>
                        </td>
                    </div>
                    <Menu as="div"
                          :class="[hoveredRow === row.id ? '' : 'hidden', 'my-auto mr-0.5 relative']"
                          v-if="this.$can('edit budget templates') || !table.is_template">
                        <div class="flex">
                            <MenuButton
                                class="flex bg-tagBg p-0.5 rounded-full">
                                <IconDotsVertical stroke-width="1.5"
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
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }"
                                              v-if="row.commented === false"
                                              @click="updateRowCommented(row.id, true)">
                                        <span
                                            @click=""
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <IconLock stroke-width="1.5"
                                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                      aria-hidden="true"/>
                                            {{ $t('Exclude') }}
                                        </span>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }"
                                              v-else
                                              @click="updateRowCommented(row.id, false)">
                                        <span
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <IconLockOpen stroke-width="1.5"
                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                          aria-hidden="true"/>
                                            {{ $t('Include positions') }}
                                        </span>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <span
                                            @click="duplicateRow(row.id)"
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <IconCopy stroke-width="1.5"
                                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                      aria-hidden="true"/>
                                            {{ $t('Duplicate') }}
                                        </span>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <span
                                            @click="openDeleteRowModal(row)"
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <IconTrash stroke-width="1.5"
                                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                       aria-hidden="true"/>
                                            {{ $t('Delete') }}
                                        </span>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </tr>
                <SageDataDropElement v-if="$page.props.sageApiEnabled" :row="row" :tableId="table.id" :sub-position-id="subPosition.id"/>
                <div @click="addRowToSubPosition(subPosition, row)"
                     v-if="this.$can('edit budget templates') || !table.is_template"
                     class="group cursor-pointer z-10 relative h-0.5 flex justify-center hover:border-dashed border-1 border-silverGray hover:border-t-2 hover:border-buttonBlue">
                    <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                        {{ $t('Row') }}
                        <IconCirclePlus stroke-width="1.5"
                                        class="h-6 w-6 ml-2 text-secondaryHover bg-buttonBlue rounded-full"/>
                    </div>
                </div>
            </div>
            <div v-else @click="addRowToSubPosition(subPosition)"
                 v-if="this.$can('edit budget templates') || !table.is_template"
                 class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                    {{ $t('Row') }}
                    <IconCirclePlus stroke-width="1.5"
                                    class="h-6 w-6 ml-2 text-secondaryHover bg-buttonBlue rounded-full"/>
                </div>
            </div>
            <SageDataDropElement v-if="$page.props.sageApiEnabled" :row="null" :tableId="table.id"
                                 :sub-position-id="subPosition.id"/>
            <tr class="bg-silverGray xsDark flex h-10 w-full text-right">
                <td class="w-28"></td>
                <td class="w-28"></td>
                <td class="w-72 my-2">SUM</td>
                <td v-if="subPosition.sub_position_rows.length > 0" class="flex items-center w-48"
                    v-for="column in columns.slice(3)"
                    v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                    <div class="my-4 w-48 p-1"
                         :class="subPosition.columnSums[column.id]?.sum < 0 ? 'text-red-500' : ''">
                        <div class="flex group relative justify-end items-center">
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'comment')"
                                 v-if="subPosition.columnSums[column.id]?.hasComments && subPosition.columnSums[column.id]?.hasMoneySource"
                                 src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                 class="h-6 w-6 mr-1 cursor-pointer"/>
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'comment')"
                                 v-else-if="subPosition.columnSums[column.id]?.hasComments"
                                 src="/Svgs/IconSvgs/icon_linked_adjustments.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'moneySource')"
                                 v-else-if="subPosition.columnSums[column.id]?.hasMoneySource"
                                 src="/Svgs/IconSvgs/icon_linked_money_source.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                            <span v-if="column.type !== 'sage'">
                                {{ this.toCurrencyString(subPosition.columnSums[column.id]?.sum) }}
                            </span>
                            <span v-else>
                                {{ calculateSageColumnWithCellSageDataValue.toLocaleString() }}
                            </span>
                            <div class="hidden group-hover:block absolute right-0 z-50 -mr-6"
                                 @click="openSubPositionSumDetailModal(subPosition, column)"
                                 v-if="this.$can('edit budget templates') || !table.is_template">
                                <IconCirclePlus stroke-width="1.5"
                                                class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full "/>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <div @click="addSubPosition(mainPosition.id, subPosition)"
             v-if="this.$can('edit budget templates') || !table.is_template"
             class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
            <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                {{ $t('Sub position') }}
                <IconCirclePlus stroke-width="1.5"
                                class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"/>
            </div>
        </div>
    </th>
    <confirmation-component
        v-if="showDeleteModal"
        :confirm="$t('Delete')"
        :titel="this.confirmationTitle"
        :description="this.confirmationDescription"
        @closed="afterConfirm"
    />
</template>
<script>
import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {nextTick} from "vue";
import Permissions from "@/mixins/Permissions.vue";
import SageDataDropElement from "@/Pages/Projects/Components/SageDataDropElement.vue";
import IconLib from "@/mixins/IconLib.vue";
import SageDropCellElement from "@/Pages/Projects/Components/SageDropCellElement.vue";
import SageDragCellElement from "@/Pages/Projects/Components/SageDragCellElement.vue";
import CurrencyFloatToStringFormatter from "@/mixins/CurrencyFloatToStringFormatter.vue";

export default {
    mixins: [Permissions, IconLib, CurrencyFloatToStringFormatter],
    name: "SubPositionComponent",
    components: {
        SageDragCellElement,
        SageDropCellElement,
        SageDataDropElement,
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
        'openSubPositionSumDetailModal',
        'openSageAssignedDataModal'
    ],
    data() {
        return {
            editedCellOriginalValue: null,
            alreadyCellClicked: false,
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
            }),
        }
    },
    computed: {
        calculateSageColumnWithCellSageDataValue() {
            // Stellen Sie sicher, dass sub_position_rows existiert und ein Array ist.
            return this.subPosition?.sub_position_rows?.reduce((acc, row) => {
                // Überprüfen Sie, ob cells existiert und ein Array ist.
                return acc + row.cells?.reduce((acc, cell) => {
                    // Überprüfen Sie, ob die Zelle die spezifizierten Bedingungen erfüllt.
                    if (cell.column.type === 'sage' && !cell.commented && !cell.column.commented) {
                        // Addieren Sie den buchungsbetrag, wenn alle Bedingungen erfüllt sind.
                        return acc + Number(cell.sage_assigned_data?.reduce((acc, data) => {
                            // Konvertieren Sie buchungsbetrag sicher in eine Zahl und addieren Sie sie.
                            const buchungsbetrag = Number(data.buchungsbetrag);
                            // Überprüfen Sie, ob buchungsbetrag eine gültige Zahl ist, sonst verwenden Sie 0.
                            return acc + (isNaN(buchungsbetrag) ? 0 : buchungsbetrag);
                        }, 0) ?? 0);
                    }
                    return acc;
                }, 0) ?? 0;
            }, 0) ?? 0;
        }

    },
    mounted() {
        // check if main Position in localStorage in "closedSubPositions"
        this.checkIfSubPositionClosed()
    },
    updated() {
        this.checkIfSubPositionClosed();
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
        sortCells() {
            // Iteriere über jedes Element in subPosition.sub_position_rows
            this.subPosition.sub_position_rows.forEach(row => {
                // Sortiere das cells Array des aktuellen Elements
                row.cells.sort((a, b) => {
                    // Wenn die column der aktuellen Zelle den Typ 'sage' hat, verschiebe sie ans Ende
                    if (a.column.type === 'sage') return 1;
                    // Wenn die column der zu vergleichenden Zelle den Typ 'sage' hat, behalte die aktuelle Zelle vor dieser
                    if (b.column.type === 'sage') return -1;
                    // Wenn keine der Zellen den Typ 'sage' hat, behalte die aktuelle Reihenfolge bei
                    return 0;
                });
            });
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
        checkIfSubPositionClosed() {
            if (localStorage.getItem('closedSubPositions') !== null) {
                let closedSubPositions = JSON.parse(localStorage.getItem('closedSubPositions'))
                // add fail over if closedMainPositions is not an array
                if (!Array.isArray(closedSubPositions)) {
                    closedSubPositions = []
                }
                let index = closedSubPositions.findIndex((subPosition) => subPosition.id === this.subPosition.id)
                if (index !== -1) {
                    this.subPosition.closed = closedSubPositions[index].closed
                }
            }
        },
        openCloseMainPosition() {
            this.subPosition.closed = !this.subPosition.closed
            if (localStorage.getItem('closedSubPositions') === null) {
                localStorage.setItem('closedSubPositions', JSON.stringify([{
                    id: this.subPosition.id,
                    closed: this.subPosition.closed
                }]))
            } else {
                let closedSubPositions = JSON.parse(localStorage.getItem('closedSubPositions'))
                // add fail over if closedMainPositions is not an array
                if (!Array.isArray(closedSubPositions)) {
                    closedSubPositions = []
                }
                let index = closedSubPositions.findIndex((subPosition) => subPosition.id === this.subPosition.id)
                if (index === -1) {
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
            if (index > 2 && !(new RegExp('^([0-9,])$')).test(event.key)) {
                event.preventDefault();
            }
        },
        afterConfirm(bool) {
            if (!bool) return this.showDeleteModal = false;

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
        checkColumnsLocked() {
            return this.columns.some(column => column.is_locked === true);
        },
        openDeleteSubPositionModal(subPosition) {
            this.confirmationTitle = this.$t('Delete sub-item');
            this.confirmationDescription = this.$t('Are you sure you want to delete the sub-item', [subPosition.name]);
            this.subPositionToDelete = subPosition;
            this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.subPositionToDelete, 'sub')
        },
        addRowToSubPosition(subPosition, row = null) {
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
            let onFinish = () => {
                cell.clicked = false;
                this.alreadyCellClicked = false;
                this.editedCellOriginalValue = null;
            };

            if (cell.value === this.editedCellOriginalValue) {
                onFinish();
                return;
            }

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
            if (!this.checkColumnsLocked()) {
                this.confirmationTitle = this.$t('Delete row');
                this.confirmationDescription = this.$t('Are you sure you want to delete this line? All links etc. will also be deleted.');
                this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.rowToDelete, 'row')
            } else {
                this.confirmationTitle = this.$t('Delete line not possible');
                this.confirmationDescription = this.$t('As long as a column is locked, you cannot delete a row.');
                this.$emit('openErrorModal', this.confirmationTitle, this.confirmationDescription)
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
                if (this.alreadyCellClicked && cell.clicked !== true) {
                    return;
                }
                cell.clicked = !cell.clicked

                if (cell.clicked) {
                    this.alreadyCellClicked = true;
                    this.editedCellOriginalValue = cell.value;

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
                        if (cell.column.is_locked) {
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
                    if (cell.column.is_locked) {
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
        -moz-appearance: textfield;
    }
}

</style>
