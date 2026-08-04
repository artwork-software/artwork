<template>
    <th class="bg-border text-xs/[15px] text-text w-full">
        <div class="flex" @mouseover="showMenu = 'subPosition' + subPosition.id" @mouseout="showMenu = null">
            <div class="pl-2 text-xs/[15px] text-text w-full flex items-center h-10" v-if="!subPosition.clicked">
                <div @click="subPosition.clicked = !subPosition.clicked">
                    {{ subPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3" @click="openCloseMainPosition">
                    <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="!subPosition.closed" class="h-6 w-6 text-text my-auto"/>
                    <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class="h-6 w-6 text-text my-auto"/>
                </button>
            </div>
            <div v-else class="flex w-full">
                <input class="my-2 ml-1 text-xs/[15px] text-text rounded-md border border-border px-1 focus:border-accent-600 focus:ring-1 focus:ring-accent-600"
                       type="text" v-model="subPosition.name"
                       @keyup.enter="$event.target.blur()"
                       @keyup.esc="subPosition.name = editedNameOriginalValue ?? subPosition.name; subPosition.clicked = false"
                       @focus="editedNameOriginalValue = subPosition.name"
                       @focusout="updateSubPositionName(subPosition); subPosition.clicked = !subPosition.clicked">
                <button class="my-auto w-6 ml-3" @click="subPosition.closed = !subPosition.closed">
                    <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="!subPosition.closed" class="h-6 w-6 text-text my-auto"/>
                    <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class="h-6 w-6 text-text my-auto"/>
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="flex flex-wrap w-8">
                    <div class="flex">
                        <BaseMenu
                            v-if="hasBudgetAccess || $can('edit budget templates')"
                            white-menu-background
                        >
                            <!-- Commitment / Unfix -->
                            <BaseMenuItem
                                v-show="$can('can add and remove verified states') || hasAdminRole()"
                                v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !subPosition.is_fixed"
                                :title="$t('Commitment')"
                                icon="IconLock"
                                white-menu-background
                                @click="fixSubPosition(subPosition.id)"
                            />

                            <BaseMenuItem
                                v-show="$can('can add and remove verified states') || hasAdminRole()"
                                v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && subPosition.is_fixed"
                                :title="$t('Canceling a fixed term')"
                                icon="IconLockOpen"
                                white-menu-background
                                @click="unfixSubPosition(subPosition.id)"
                            />

                            <!-- Duplicate -->
                            <BaseMenuItem
                                :title="$t('Duplicate')"
                                icon="IconCopy"
                                white-menu-background
                                @click="duplicateSubpostion(subPosition.id)"
                            />

                            <!-- Delete -->
                            <BaseMenuItem
                                :title="$t('Delete')"
                                icon="IconTrash"
                                white-menu-background
                                @click="openDeleteSubPositionModal(subPosition)"
                            />
                        </BaseMenu>

                    </div>
                </div>
            </div>
        </div>
        <table class="w-full" v-if="!subPosition.closed">
            <tbody class="bg-surface w-full">
            <SageDataDropElement v-if="$page.props.sageApiEnabled" :row="null" :tableId="table.id"
                                 :sub-position-id="subPosition.id" @budget-updated="$emit('budget-updated')"/>
            <draggable
                v-if="subPosition.sub_position_rows?.length > 0"
                v-model="subPosition.sub_position_rows"
                item-key="id"
                tag="div"
                :group="{ name: 'sub-position-rows', pull: true, put: true }"
                handle=".sub-position-row-drag-handle"
                :disabled="!canReorderSubPositionRows"
                @change="persistSubPositionRowOrder($event)"
            >
                <template #item="{ element: row, index: rowIndex }">
                    <div>
                        <tr v-show="!(row.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)"
                            :class="[rowIndex !== 0 && hoveredRow !== row.id ? '': '', hoveredRow === row.id && (this.$can('edit budget templates') || !table.is_template) ? 'border-accent-600' : '']"
                            @mouseover="hoveredRow = row.id" @mouseout="hoveredRow = null"
                            class="bg-surface flex justify-between items-center border border-border-subtle group">
                            <div class="flex items-center">
                                <td v-for="(cell,index) in row.cells"
                                    :key="cell.id"
                                    v-show="!(cell.column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)"
                                    :class="[index <= 1 ? 'w-48' : index === 2 ? 'w-72 ' : 'w-48 ', index === 0 ? 'relative' : '', checkCellColor(cell,mainPosition,subPosition), cell.column.is_locked ? 'bg-[#A7A6B120]' : '']">
                                    <div
                                        v-if="index === 0 && canReorderSubPositionRows"
                                        class="sub-position-row-drag-handle absolute left-0 top-1/2 -translate-y-1/2 cursor-grab text-text-subtle hover:text-primaryText"
                                        @mousedown.stop
                                    >
                                        <PropertyIcon name="IconGripVertical" class="h-4 w-4" aria-hidden="true" />
                                    </div>
                            <div v-if="(index === 0 || index === 1) && this.$page.props.budgetAccountManagementGlobal">
                                <div
                                    :class="[row.commented || cell.commented || cell.column.commented ? 'text-sm/5 font-bold text-text-subtle' : '', index === 0 ? 'w-44 max-w-44 justify-start pl-8' : index === 1 ? 'w-44 max-w-44 justify-start pl-3' : index === 2 ? 'w-72 max-w-72 justify-start pl-3' : 'w-48 max-w-48 pr-2 justify-end', cell.value < 0 ? 'text-danger' : '', cell.value === '' || cell.value === null ? 'border border-border ' : '']"
                                    class="my-4 h-6 flex items-center"
                                    v-if="!cell.clicked">
                                    <div class="flex items-center cell-button">
                                        <span
                                            class="relative group/tt block min-w-0"
                                            @mouseenter="maybeUpdateTruncation(cell.id, index)"
                                        >
                                            <span
                                                :ref="el => setTruncEl(cell.id, el)"
                                                :class="(userShowAccountName ? (cell.display_value ?? cell.value) : cell.value) === '' ? 'w-6 cursor-pointer h-6' : 'truncate w-42 cursor-pointer block'"
                                                @mousedown="storeFocus(cell.id)"
                                                @click="this.handleCellClick(cell, '', index, row)"
                                            >
                                                {{ userShowAccountName ? (cell.display_value ?? cell.value) : cell.value }}
                                            </span>
                                            <span
                                                v-if="isTruncated[cell.id]"
                                                class="pointer-events-none absolute left-0 top-full z-50 mt-2 w-max max-w-md
                                                       rounded-xl border border-border-subtle bg-white/95 px-3 py-2 text-xs text-text shadow-lg
                                                       opacity-0 translate-y-1 transition-all duration-150
                                                       group-hover/tt:opacity-100 group-hover/tt:translate-y-0
                                                       whitespace-normal wrap-break-word"
                                            >
                                                <span
                                                    class="absolute -top-1 left-3 h-2 w-2 rotate-45 bg-white/95
                                                           border-l border-t border-border-subtle"
                                                />
                                                {{ String(userShowAccountName ? (cell.display_value ?? cell.value) : (cell.value ?? '')) }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                <div
                                    :class="[row.commented || cell.commented || cell.column.commented ? 'text-sm/5 font-bold text-text-subtle' : '', index === 0 ? 'w-44 max-w-44 justify-start pl-8' : index === 1 ? 'w-44 max-w-44 justify-start pl-3' : index === 2 ? 'w-72 max-w-72 justify-start pl-3' : 'w-48 max-w-48 pr-2 justify-end', cell.value < 0 ? 'text-danger' : '', cell.value === '' || cell.value === null ? 'border border-border ' : '']"
                                    class="my-4 h-6 flex items-center" v-else>
                                    <div class="flex flex-row items-center relative">
                                        <input v-model="cell.searchValue"
                                               :placeholder="userShowAccountName ? (cell.display_value ?? cell.value) : cell.value"
                                               :ref="`cell-${cell.id}`"
                                               type="text"
                                               class="w-full"
                                               @input="this.handleBudgetManagementSearch(index, cell, (this.mainPosition.type !== 'BUDGET_TYPE_COST'))"
                                               @focusout="this.handleBudgetManagementSearchBlur(cell)"
                                        />
                                        <PropertyIcon name="IconX" class="w-10 h-10 cursor-pointer"
                                               @click="this.handleBudgetManagementSearchCancel(cell)"
                                        />
                                        <div v-if="cell.accountSearchResults" class="absolute w-96 z-20 top-10">
                                            <div v-if="cell.accountSearchResults.length > 0"
                                                 v-for="account in cell.accountSearchResults"
                                                 class="flex flex-col"
                                            >
                                                <div
                                                    class="group/account relative p-3 cursor-pointer bg-surface-inverse hover:bg-accent-700 text-white"
                                                    @mousedown="this.handleBudgetManagementSearchSelect(index, cell, account.account_number, account.title, mainPosition.is_verified, subPosition.is_verified)">
                                                    <div class="flex gap-2">
                                                        <div class="shrink-0 text-left">
                                                            {{ account.account_number }}
                                                        </div>
                                                        <div class="text-right truncate">
                                                            {{ account.title }}
                                                        </div>
                                                    </div>
                                                    <div class="hidden group-hover/account:block absolute left-0 bottom-full mb-1 w-max max-w-sm px-2 py-1 text-xs bg-black text-white rounded shadow-lg z-30 whitespace-normal">
                                                        {{ account.account_number }} - {{ account.title }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else
                                                 class="text-nowrap p-3 cursor-pointer bg-surface-inverse hover:bg-accent-700 text-white">
                                                {{ $t('No Accounts found') }}
                                            </div>
                                        </div>
                                        <div v-if="cell.costUnitSearchResults" class="absolute w-96 z-20 top-10">
                                            <div v-if="cell.costUnitSearchResults.length > 0"
                                                 v-for="cost_unit in cell.costUnitSearchResults"
                                                 class="flex flex-col"
                                            >
                                                <div
                                                    class="group/costunit relative p-3 cursor-pointer bg-surface-inverse hover:bg-accent-700 text-white"
                                                    @mousedown="this.handleBudgetManagementSearchSelect(index, cell, cost_unit.cost_unit_number, cost_unit.title, mainPosition.is_verified, subPosition.is_verified)">
                                                    <div class="flex gap-2">
                                                        <div class="shrink-0 text-left">
                                                            {{ cost_unit.cost_unit_number }}
                                                        </div>
                                                        <div class="text-right truncate">
                                                            {{ cost_unit.title }}
                                                        </div>
                                                    </div>
                                                    <div class="hidden group-hover/costunit:block absolute left-0 bottom-full mb-1 w-max max-w-sm px-2 py-1 text-xs bg-black text-white rounded shadow-lg z-30 whitespace-normal">
                                                        {{ cost_unit.cost_unit_number }} - {{ cost_unit.title }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else
                                                 class="text-nowrap p-3 cursor-pointer bg-surface-inverse hover:bg-accent-700 text-white">
                                                {{ $t('No Cost Units found') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="group">
                                <div :class="[row.commented || cell.commented || cell.column.commented ? 'text-sm/5 font-bold text-text-subtle' : '',
                                    index <= 1 ? 'w-44 max-w-44 justify-start pl-3' : index === 2 ? 'w-72 max-w-72 justify-start pl-3' : 'w-48 max-w-48 pr-2 justify-end',
                                    cell.value < 0 ? 'text-danger' : '', cell.value === '' || cell.value === null ? 'border border-border ' : '']"
                                     class="my-4 h-6 flex items-center cell-button" v-if="!cell.clicked">
                                    <div
                                        v-if="cell.column.type !== 'subprojects_column_for_group'"
                                        class="flex items-start gap-1 min-w-0"
                                    >
                                        <!-- Icons -->
                                        <div class="flex items-center gap-1 shrink-0 pt-0.5">
                                            <div
                                                v-if="cell.comments_count > 0"
                                                class="cursor-pointer"
                                                @click="handleCellClick(cell, 'comment', index, row)"
                                            >
                                                <PropertyIcon
                                                    name="IconMessageDots"
                                                    class="h-5 w-5 cursor-pointer border-2 rounded-md bg-accent-50 text-accent-600 border-accent-600"
                                                />
                                            </div>

                                            <PropertyIcon
                                                v-if="cell.calculations_count > 0"
                                                name="IconCalculator"
                                                @click="handleCellClick(cell, 'calculation', index, row)"
                                                class="h-5 w-5 cursor-pointer border-2 rounded-md bg-accent-50 text-accent-600 border-accent-600"
                                            />

                                            <PropertyIcon
                                                v-if="cell.linked_money_source_id !== null"
                                                name="IconLink"
                                                @click="handleCellClick(cell, 'moneysource', index, row)"
                                                class="h-5 w-5 cursor-pointer border-2 rounded-md bg-accent-50 text-accent-600 border-accent-600"
                                            />

                                            <PropertyIcon
                                                v-if="cell.sage_assigned_data?.length >= 1 && cell.sage_assigned_data[0].is_collective_booking"
                                                name="IconAbacus"
                                            />

                                            <PropertyIcon
                                                v-if="cell.sage_assigned_data?.length >= 1"
                                                name="IconAdjustmentsAlt"
                                                @click="handleCellClick(cell, 'sageAssignedData', index, row)"
                                                class="h-5 w-5 cursor-pointer border-2 rounded-md"
                                                :class="cell.sage_assigned_data?.length === 1
        ? 'bg-accent-50 text-accent-600 border-accent-600'
        : 'bg-success-surface text-success border-success'"
                                                stroke-width="1.5"
                                            />
                                        </div>

                                        <!-- Text / Content (clamped) -->
                                        <div class="min-w-0 flex-1">
                                            <div v-if="cell.column.type === 'sage'" class="flex items-center min-w-0">
                                                <SageDropCellElement :cell="cell" :value="toCurrencyString(cell.sage_value)" @budget-updated="$emit('budget-updated')" />
                                                <SageDragCellElement
                                                    v-if="cell.sage_assigned_data?.length >= 1"
                                                    :cell="cell"
                                                    class="hidden group-hover:block shrink-0"
                                                />
                                            </div>
                                            <span
                                                v-else
                                                class="relative group/tt block min-w-0"
                                                @mouseenter="maybeUpdateTruncation(cell.id, index)"
                                            >
                                                <span
                                                    :ref="el => setTruncEl(cell.id, el)"
                                                    @mousedown="storeFocus(cell.id)"
                                                    @click="handleCellClick(cell, '', index, row)"
                                                    class="block min-w-0 overflow-hidden text-ellipsis truncate"
                                                >
                                                    {{ index < 3 ? (cell.display_value ?? cell.value) : toCurrencyString(cell.value) }}
                                                </span>
                                                <span
                                                    v-if="index < 3 && isTruncated[cell.id]"
                                                    class="pointer-events-none absolute left-0 top-full z-50 mt-2 w-max max-w-md
                                                           rounded-xl border border-border-subtle bg-white/95 px-3 py-2 text-xs text-text shadow-lg
                                                           opacity-0 translate-y-1 transition-all duration-150
                                                           group-hover/tt:opacity-100 group-hover/tt:translate-y-0
                                                           whitespace-normal wrap-break-word"
                                                >
                                                    <span
                                                        class="absolute -top-1 left-3 h-2 w-2 rotate-45 bg-white/95
                                                               border-l border-t border-border-subtle"
                                                    />
                                                    {{ String(cell.display_value ?? cell.value ?? '') }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>

                                    <div v-else class="flex items-center gap-x-1" :class="cell.column.color !== 'whiteColumn' ? cell.column.color : ''">
                                        <PropertyIcon name="IconList" @click="openRelevantBudgetDataSumModalForCell(cell)"
                                                   v-if="calculateRelevantBudgetDataSumFormProjectsInGroup(cell) > 0"
                                                   class="h-5 w-5 mr-1 cursor-pointer border-2 rounded-md bg-accent-50 text-accent-600 border-accent-600"/>
                                        {{ toCurrencyString(calculateRelevantBudgetDataSumFormProjectsInGroup(cell)) }}
                                    </div>
                                </div>
                                <div class="flex items-center relative group"
                                     :class="index <= 1 ? 'w-24 mr-5' : index === 2 ? 'w-72 mr-12' : 'w-48 ml-5'"
                                     v-else-if="cell.clicked && cell.column.type === 'empty' && !cell.column.is_locked">
                                    <input :ref="`cell-${cell.id}`"
                                           :class="index <= 1 ? 'w-20 mr-2' : index === 2 ? 'w-60 mr-2' : 'w-44 text-right'"
                                           class="my-2 text-sm/5 font-semibold text-text appearance-none z-10 rounded-md border border-border px-1 focus:border-accent-600 focus:ring-1 focus:ring-accent-600"
                                           type="text"
                                           :disabled="!this.$can('edit budget templates') && table.is_template"
                                           v-model="cell.value"
                                           @keydown="handleCellKeydown($event, cell, row, index)"
                                           @focusout="updateCellValue(cell, mainPosition.is_verified, subPosition.is_verified)">

                                </div>
                                <div
                                    :class="[row.commented ? 'text-sm/5 font-bold text-text-subtle' : 'text-sm/5 font-semibold text-text', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48 text-right', cell.value < 0 ? 'text-danger' : '', savingCellIds[cell.id] ? 'opacity-50 animate-pulse' : '']"
                                    class="my-4 h-6 flex items-center justify-end group"
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

                                </div>

                            </div>
                            <PropertyIcon name="IconCirclePlus" stroke-width="1.5" v-if="index > 2 "
                                            @click="openCellDetailModal(cell)"
                                            class="hidden group-hover:block h-6 w-6 absolute -mt-10 ml-4 z-50 cursor-pointer text-white bg-accent-600 rounded-full"/>
                        </td>
                    </div>
                            <BaseMenu
                                dots-color="text-text-muted"
                                class="invisible group-hover:visible"
                                v-if="hasBudgetAccess || $can('edit budget templates')"
                                white-menu-background
                            >
                                <!-- Exclude / Include -->
                                <BaseMenuItem
                                    v-if="row.commented === false"
                                    :title="$t('Exclude')"
                                    icon="IconLock"
                                    white-menu-background
                                    @click="updateRowCommented(row.id, true)"
                                />

                                <BaseMenuItem
                                    v-else
                                    :title="$t('Include positions')"
                                    icon="IconLockOpen"
                                    white-menu-background
                                    @click="updateRowCommented(row.id, false)"
                                />

                                <!-- Duplicate -->
                                <BaseMenuItem
                                    :title="$t('Duplicate')"
                                    icon="IconCopy"
                                    white-menu-background
                                    @click="duplicateRow(row.id)"
                                />

                                <!-- Delete -->
                                <BaseMenuItem
                                    :title="$t('Delete')"
                                    icon="IconTrash"
                                    white-menu-background
                                    @click="openDeleteRowModal(row)"
                                />
                            </BaseMenu>

                        </tr>
                        <SageDataDropElement v-if="$page.props.sageApiEnabled" :row="row" :tableId="table.id"
                                             :sub-position-id="subPosition.id" @budget-updated="$emit('budget-updated')"/>
                        <div @click="addRowToSubPosition(subPosition, row)"
                             v-if="this.hasBudgetAccess || this.$can('edit budget templates')"
                             class="group cursor-pointer z-10 relative h-0.5 flex justify-center hover:border-dashed border-1 border-accent-600 hover:border-t-2 hover:border-accent-600">
                            <div class="group-hover:block hidden uppercase text-accent-600 text-sm -mt-8">
                                {{ $t('Row') }}
                                <PropertyIcon name="IconCirclePlus" stroke-width="1.5"
                                                class="h-6 w-6 ml-2 text-white bg-accent-600 rounded-full"/>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>

            <div v-if="!(subPosition.sub_position_rows?.length > 0) && (this.hasBudgetAccess || this.$can('edit budget templates'))"
                 @click="addRowToSubPosition(subPosition)"
                 class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-accent-600">
                <div class="group-hover:block hidden uppercase text-accent-600 text-sm -mt-8">
                    {{ $t('Row') }}
                    <PropertyIcon name="IconCirclePlus" stroke-width="1.5"
                                    class="h-6 w-6 ml-2 text-white bg-accent-600 rounded-full"/>
                </div>
            </div>
            <SageDataDropElement v-if="$page.props.sageApiEnabled" :row="null" :tableId="table.id"
                                 :sub-position-id="subPosition.id" @budget-updated="$emit('budget-updated')"/>
            <tr class="bg-silverGray text-sm/5 font-semibold text-text flex h-10 w-full text-right">
                <td class="w-48"></td>
                <td class="w-48"></td>
                <td class="w-72 my-2">{{ $t('SUM') }}</td>
                <td v-if="subPosition.sub_position_rows?.length > 0" class="flex items-center w-48"
                    v-for="column in valueColumns"
                    :key="column.id"
                    v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                    <div class="my-4 w-48 p-1"
                         :class="subPosition.columnSums?.[column.id]?.sum < 0 ? 'text-danger' : ''">
                        <div class="flex group relative justify-end items-center">
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'comment')"
                                 v-if="subPosition.columnSums?.[column.id]?.hasComments && subPosition.columnSums?.[column.id]?.hasMoneySource"
                                 src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                 class="h-6 w-6 mr-1 cursor-pointer"/>
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'comment')"
                                 v-else-if="subPosition.columnSums?.[column.id]?.hasComments"
                                 src="/Svgs/IconSvgs/icon_linked_adjustments.svg" class="h-5 w-5 mr-1 cursor-pointer"/>
                            <img @click="openSubPositionSumDetailModal(subPosition, column, 'moneySource')"
                                 v-else-if="subPosition.columnSums?.[column.id]?.hasMoneySource"
                                 src="/Svgs/IconSvgs/icon_linked_money_source.svg" class="h-6 w-6 mr-1 cursor-pointer"/>
                            <span v-if="column.type !== 'sage' && column.type !== 'subprojects_column_for_group'">
                                {{ this.toCurrencyString(subPosition.columnSums?.[column.id]?.sum) }}
                            </span>
                            <span v-if="column.type === 'sage'">
                                {{ toCurrencyString(calculateSageColumnWithCellSageDataValue) }}
                            </span>
                            <span v-if="column.type === 'subprojects_column_for_group'">
                                {{ calculateRelevantBudgetDataSumFormProjectsInGroupSubPosition() }}
                            </span>
                            <div class="hidden group-hover:block absolute right-0 z-50 -mr-6"
                                 @click="openSubPositionSumDetailModal(subPosition, column)"
                                 v-if="this.hasBudgetAccess || this.$can('edit budget templates')">
                                <PropertyIcon name="IconCirclePlus" stroke-width="1.5"
                                                class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-accent-600 rounded-full "/>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <div @click="addSubPosition(mainPosition.id, subPosition)"
             v-if="this.hasBudgetAccess || this.$can('edit budget templates')"
             class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-accent-600">
            <div class="group-hover:block hidden uppercase text-accent-600 text-sm -mt-8">
                {{ $t('Sub position') }}
                <PropertyIcon name="IconCirclePlus" stroke-width="1.5"
                                class="h-6 w-6 ml-12 text-white bg-accent-600 rounded-full"/>
            </div>
        </div>
    </th>

    <RelevantBudgetDataSumModal
        v-if="showRelevantBudgetDataSumModal"
        :data="dataToDisplayInRelevantDataModal"
        @closed="showRelevantBudgetDataSumModal = false"
    />

    <confirmation-component
        v-if="showDeleteModal"
        :confirm="$t('Delete')"
        :titel="this.confirmationTitle"
        :description="this.confirmationDescription"
        @closed="afterConfirm"
    />
</template>
<script>
import {useForm, usePage} from "@inertiajs/vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {nextTick} from "vue";
import Permissions from "@/Mixins/Permissions.vue";
import SageDataDropElement from "@/Pages/Projects/Components/SageDataDropElement.vue";
import SageDropCellElement from "@/Pages/Projects/Components/SageDropCellElement.vue";
import SageDragCellElement from "@/Pages/Projects/Components/SageDragCellElement.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import RelevantBudgetDataSumModal from "@/Pages/Projects/Components/Budget/RelevantBudgetDataSumModal.vue";
import {IconList} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import draggable from 'vuedraggable';
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {budgetCellFocus} from "@/Layouts/Components/Budget/budgetCellFocus.js";
import axios from "axios";

export default {
    mixins: [Permissions, CurrencyFloatToStringFormatter],
    name: "SubPositionComponent",
    components: {
        BaseMenuItem,
        PropertyIcon,
        draggable,
        RelevantBudgetDataSumModal,
        BaseMenu,
        SageDragCellElement,
        SageDropCellElement,
        SageDataDropElement,
        ConfirmationComponent,
    },
    props: ['subPosition', 'mainPosition', 'allMainPositions', 'columns', 'project', 'table', 'projectManagers', 'hasBudgetAccess', 'userShowAccountName'],
    emits: [
        'openDeleteModal',
        'openVerifiedModal',
        'openErrorModal',
        'openCellDetailModal',
        'openSubPositionSumDetailModal',
        'openSageAssignedDataModal',
        'budget-updated',
        'budget-patched'
    ],
    data() {
        return {
            editedCellOriginalValue: null,
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
            dataToDisplayInRelevantDataModal: null,
            showRelevantBudgetDataSumModal: false,
            savingCellIds: {},
            editedNameOriginalValue: null,
            truncEls: {},
            isTruncated: {},
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
        },

        canReorderSubPositionRows() {
            return (this.hasBudgetAccess || this.$can('edit budget templates'))
                && (!this.table?.is_template || this.$can('edit budget templates'));
        },

        valueColumns() {
            return (this.columns ?? []).slice(3);
        },

        closedSubPositionsStorageKey() {
            // pro Tabelle getrennt, sonst kollidieren die Zustaende zwischen Projekten
            return `closedSubPositions:${this.table?.id}`;
        },
    },
    mounted() {
        this.checkIfSubPositionClosed();

        this._onResize = () => {
            Object.keys(this.truncEls || {}).forEach((id) => this.updateTruncation(id));
        };
        window.addEventListener("resize", this._onResize);
    },
    beforeUnmount() {
        window.removeEventListener("resize", this._onResize);
    },
    watch: {
        'subPosition.id'() {
            this.checkIfSubPositionClosed();
        },
    },
    methods: {
        setTruncEl(id, el) {
            if (!el) return;
            this.truncEls[id] = el;
            this.updateTruncation(id);
        },

        updateTruncation(id) {
            const el = this.truncEls?.[id];
            if (!el) return;
            this.$set
                ? this.$set(this.isTruncated, id, el.scrollWidth > el.clientWidth)
                : (this.isTruncated[id] = el.scrollWidth > el.clientWidth);
        },

        maybeUpdateTruncation(id, index) {
            if (index >= 3) return;
            requestAnimationFrame(() => this.updateTruncation(id));
        },
        IconList,
        usePage,
        persistSubPositionRowOrder(evt = null) {
            if (!this.canReorderSubPositionRows) {
                return;
            }

            // Important for cross-subposition drag&drop:
            // both source and target lists emit events. The source emits `removed`.
            // If we persist on the source event as well, it can overwrite the target
            // update and the row jumps back after reload.
            // Therefore: ignore pure `removed` events and only persist on `added`
            // (target list) or `moved` (same list).
            if (evt?.removed && !evt?.added && !evt?.moved) {
                return;
            }

            // debounce, because `@change` can fire multiple times during a drag
            window.clearTimeout(this._reorderSaveTimeout);
            this._reorderSaveTimeout = window.setTimeout(() => {
                // wait for Vue to apply list mutations before reading them
                nextTick(() => {
                // For cross-sub-position drag&drop we need to persist BOTH lists:
                // - source subPosition (removed)
                // - target subPosition (added)
                // Otherwise the `sub_position_id` of the moved row is not persisted.

                const affectedSubPositionIds = new Set([this.subPosition?.id]);

                const movedRowId = evt?.added?.element?.id
                    ?? evt?.removed?.element?.id
                    ?? evt?.moved?.element?.id
                    ?? null;

                const oldSubPositionId = evt?.added?.element?.sub_position_id
                    ?? evt?.removed?.element?.sub_position_id
                    ?? null;

                if (oldSubPositionId) {
                    affectedSubPositionIds.add(oldSubPositionId);
                }

                // Try to find source/target subPositions across ALL main positions.
                // This is required for drag&drop across different main positions.
                const allSubPositions = (this.allMainPositions ?? [this.mainPosition])
                    .flatMap(mp => mp?.sub_positions ?? []);
                if (movedRowId && Array.isArray(allSubPositions)) {
                    const currentContainer = allSubPositions.find(sp =>
                        (sp?.sub_position_rows ?? []).some(r => r.id === movedRowId)
                    );
                    if (currentContainer?.id) {
                        affectedSubPositionIds.add(currentContainer.id);
                    }
                }

                const updates = Array.from(affectedSubPositionIds)
                    .filter(Boolean)
                    .map((subPositionId) => {
                        const sp = allSubPositions.find(s => s?.id === subPositionId) || this.subPosition;
                        return {
                            sub_position_id: subPositionId,
                            row_ids: (sp?.sub_position_rows ?? []).map(r => r.id),
                        };
                    });

                this.$inertia.patch(
                    route('project.budget.sub-position-row.reorder'),
                    {
                        updates,
                    },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => this.$emit('budget-updated'),
                    }
                );
                });
            }, 150);
        },
        openRelevantBudgetDataSumModalForCell(cell) {
            const data = this.$page.props.loadedProjectInformation?.BudgetTab?.projectGroupRelevantBudgetData;
            if (!data || !Array.isArray(data[this.mainPosition?.type])) return this.toCurrencyString(0);
            const relevantData = data[this.mainPosition.type].filter(item => item?.groupRowId === cell?.sub_position_row_id);

            if (!relevantData.length) return false;
            this.dataToDisplayInRelevantDataModal = relevantData;
            this.showRelevantBudgetDataSumModal = true;
        },
        calculateRelevantBudgetDataSumFormProjectsInGroup(cell) {
            const data = this.$page.props.loadedProjectInformation?.BudgetTab?.projectGroupRelevantBudgetData;
            // Fallback: wenn keine DTO-Daten vorhanden/filtrierbar sind, den bereits vom Backend
            // angereicherten Zellwert nutzen.
            const fallback = parseFloat(String(cell?.value ?? '0').replace(',', '.'));

            if (!data || !Array.isArray(data[this.mainPosition?.type])) return isNaN(fallback) ? 0 : fallback;

            const relevantData = data[this.mainPosition.type].filter(item => item?.groupRowId === cell?.sub_position_row_id);
            if (!relevantData.length) return isNaN(fallback) ? 0 : fallback;
            const sum = relevantData.reduce((acc, item) => {
                const value = parseFloat(item.value?.replace(',', '.') || '0');
                return acc + (isNaN(value) ? 0 : value);
            }, 0);
            return sum;
        },
        calculateRelevantBudgetDataSumFormProjectsInGroupSubPosition() {
            const data = this.$page.props.loadedProjectInformation?.BudgetTab?.projectGroupRelevantBudgetData;
            if (!data || !Array.isArray(data[this.mainPosition?.type])) return this.toCurrencyString(0);
            const relevantData = data[this.mainPosition.type].filter(item =>
                item?.subPositionId === this.subPosition?.id && item?.type === this.mainPosition?.type
            );
            if (!relevantData.length) return this.toCurrencyString(0);
            const sum = relevantData.reduce((acc, item) => {
                const value = parseFloat(item.value?.replace(',', '.') || '0');
                return acc + (isNaN(value) ? 0 : value);
            }, 0);
            return this.toCurrencyString(sum);
        },
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
                    preserveScroll: true,
                    onSuccess: () => this.$emit('budget-updated')
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
                    preserveScroll: true,
                    onSuccess: () => this.$emit('budget-updated')
                }
            )
        },
        readClosedSubPositions() {
            try {
                const stored = JSON.parse(localStorage.getItem(this.closedSubPositionsStorageKey));
                return Array.isArray(stored) ? stored : [];
            } catch (e) {
                return [];
            }
        },
        checkIfSubPositionClosed() {
            const closedSubPositions = this.readClosedSubPositions();
            const entry = closedSubPositions.find((subPosition) => subPosition.id === this.subPosition.id);
            if (entry) {
                this.subPosition.closed = entry.closed;
            }
        },
        openCloseMainPosition() {
            this.subPosition.closed = !this.subPosition.closed;
            const closedSubPositions = this.readClosedSubPositions();
            const entry = closedSubPositions.find((subPosition) => subPosition.id === this.subPosition.id);
            if (entry) {
                entry.closed = this.subPosition.closed;
            } else {
                closedSubPositions.push({
                    id: this.subPosition.id,
                    closed: this.subPosition.closed
                });
            }
            localStorage.setItem(this.closedSubPositionsStorageKey, JSON.stringify(closedSubPositions));
        },
        duplicateSubpostion(subPositionId) {
            this.$inertia.post(route('project.budget.sub-position.duplicate', subPositionId), {}, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            })
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
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        verifiedSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.sub-position'), {
                subPositionId: subPositionId,
                project_id: this.project?.id,
                table_id: this.table.id,
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
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
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            })
        },
        removeVerification(position, type) {
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
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
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        updateCellValue(cell, mainPositionVerified, subPositionVerified) {
            const originalValue = this.editedCellOriginalValue;

            const openNextCell = () => {
                cell.clicked = false;
                const nextCellId = budgetCellFocus.nextCellId;
                if (nextCellId && nextCellId !== cell.id) {
                    const nextCell = this.subPosition.sub_position_rows
                        .find(row => row.cells.some(c => c.id === nextCellId))
                        ?.cells.find(c => c.id === nextCellId);
                    if (nextCell) {
                        budgetCellFocus.nextCellId = null;
                        this.editedCellOriginalValue = nextCell.value;
                        nextCell.clicked = true;
                        nextTick(() => {
                            this.$refs[`cell-${nextCell.id}`]?.[0]?.select();
                        });
                    }
                }
            };

            if (cell.value === originalValue) {
                openNextCell();
                return;
            }

            if ((cell.value === null || cell.value === '') && cell.column.type !== 'empty') {
                cell.value = 0;
            }

            // Optimistisch: Wert bleibt sofort sichtbar, waehrenddessen dezenter
            // Saving-Indikator. Die Antwort ist ein schlanker Patch (Zellen der
            // Zeile + neu berechnete Summen) statt eines Full-Refetches.
            this.savingCellIds[cell.id] = true;
            openNextCell();

            axios.patch(route('project.budget.cell.update'), {
                column_id: cell.column.id,
                value: cell.value,
                sub_position_row_id: cell.sub_position_row_id,
                is_verified: mainPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED'
                    || subPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED'
            }).then(({data}) => {
                this.applyCellPatch(data);
            }).catch(() => {
                cell.value = originalValue;
                this.$emit(
                    'openErrorModal',
                    this.$t('An error has occurred'),
                    this.$t('The value could not be saved. Please try again.')
                );
            }).finally(() => {
                delete this.savingCellIds[cell.id];
            });
        },
        applyCellPatch(patch) {
            if (!patch || !Array.isArray(patch.rowCells)) {
                return;
            }

            const freshCellsById = new Map(patch.rowCells.map(c => [c.id, c]));

            for (const row of this.subPosition.sub_position_rows ?? []) {
                for (const cell of row.cells ?? []) {
                    const fresh = freshCellsById.get(cell.id);
                    if (!fresh) {
                        continue;
                    }
                    // Zellen, die der Nutzer inzwischen selbst editiert, nicht
                    // ueberschreiben - sonst verliert er seine Eingabe.
                    if (!cell.clicked) {
                        cell.value = fresh.value;
                        cell.display_value = fresh.display_value ?? cell.display_value;
                    }
                    cell.verified_value = fresh.verified_value;
                    cell.commented = fresh.commented;
                    cell.sage_value = fresh.sage_value;
                    cell.current_value = fresh.current_value;
                }
            }

            const subPositionSums = patch.subPositionSums?.[this.subPosition.id];
            if (subPositionSums) {
                this.subPosition.columnSums = subPositionSums;
            }

            // Haupt-/Tabellensummen wendet die BudgetComponent an
            this.$emit('budget-patched', patch);
        },
        handleCellKeydown(event, cell, row, index) {
            if (event.key === 'Escape') {
                event.preventDefault();
                cell.value = this.editedCellOriginalValue;
                budgetCellFocus.nextCellId = null;
                event.target.blur();
                return;
            }

            if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                const target = event.key === 'Enter'
                    ? this.findEditableCellBelow(cell, row)
                    : this.findEditableCellSideways(cell, row, event.shiftKey ? -1 : 1);
                budgetCellFocus.nextCellId = target?.id ?? null;
                event.target.blur();
                return;
            }

            // Wertspalten: nur Zahlen-Eingaben zulassen (Steuertasten bleiben frei)
            if (
                index > 2
                && event.key.length === 1
                && !event.ctrlKey && !event.metaKey
                && !/^[0-9,.\-]$/.test(event.key)
            ) {
                event.preventDefault();
            }
        },
        isKeyboardEditableCell(cell, index) {
            return index > 2
                && cell.column.type === 'empty'
                && !cell.column.is_locked
                && !(cell.column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1);
        },
        findEditableCellSideways(cell, row, direction) {
            const cells = row.cells ?? [];
            const start = cells.findIndex(c => c.id === cell.id);
            if (start === -1) {
                return null;
            }
            for (let i = start + direction; i >= 0 && i < cells.length; i += direction) {
                if (this.isKeyboardEditableCell(cells[i], i)) {
                    return cells[i];
                }
            }
            // Zeilenende: in der naechsten/vorherigen Zeile weitermachen
            const rows = this.subPosition.sub_position_rows ?? [];
            const rowIndex = rows.findIndex(r => r.id === row.id);
            const nextRow = rows[rowIndex + direction];
            if (!nextRow) {
                return null;
            }
            const nextCells = nextRow.cells ?? [];
            const range = direction === 1
                ? nextCells.map((c, i) => [c, i])
                : nextCells.map((c, i) => [c, i]).reverse();
            for (const [c, i] of range) {
                if (this.isKeyboardEditableCell(c, i)) {
                    return c;
                }
            }
            return null;
        },
        findEditableCellBelow(cell, row) {
            const rows = this.subPosition.sub_position_rows ?? [];
            const rowIndex = rows.findIndex(r => r.id === row.id);
            for (let i = rowIndex + 1; i < rows.length; i++) {
                const belowCells = rows[i].cells ?? [];
                const belowIndex = belowCells.findIndex(c => c.column_id === cell.column_id);
                if (belowIndex !== -1 && this.isKeyboardEditableCell(belowCells[belowIndex], belowIndex)) {
                    return belowCells[belowIndex];
                }
            }
            return null;
        },
        storeFocus(cellId) {
            budgetCellFocus.nextCellId = cellId;
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
            return row.cells?.some(cell => cell.column.type === 'sage' && cell.sage_assigned_data?.length > 0) ?? false;
        },
        async handleCellClick(cell, type = '', index = null, row = null) {
            const isSageInteraction = type === 'sageAssignedData' || cell.column.type === 'sage';
            if (!this.hasBudgetAccess && !(isSageInteraction && this.$canAny(['can view project sage data', 'can view global sage data']))) {
                return;
            }
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
            } else if (!cell.clicked) {
                // Nur oeffnen, nie togglen: beim Zellwechsel per Klick oeffnet
                // bereits die Fokus-Kette (storeFocus -> focusout -> openNextCell)
                // die Zielzelle - ein Toggle wuerde sie sofort wieder schliessen.
                cell.clicked = true;
                this.editedCellOriginalValue = cell.value;

                await nextTick()

                this.$refs[`cell-${cell.id}`]?.[0]?.select();
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
                preserveState: false,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        checkCellColor(cell, mainPosition, subPosition) {
            let cssString = '';
            if (cell.value !== cell.verified_value) {
                if (mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'
                    || mainPosition.is_fixed || subPosition.is_fixed) {
                    cssString += ' bg-danger '
                    cssString += ' text-sm/5 font-bold text-white '
                } else {
                    if (cell.column.color !== 'whiteColumn') {
                        cssString += ' text-sm/5 font-semibold text-text '
                        cssString += cell.column.color;
                        if (cell.column.is_locked) {
                            cssString += ' lockedColumn '
                        }
                    } else {
                        cssString += ' text-sm/5 font-semibold text-text '
                    }
                }
            } else {
                if (cell.column.color !== 'whiteColumn') {
                    cssString += ' text-sm/5 font-semibold text-text '
                    cssString += cell.column.color;
                    if (cell.column.is_locked) {
                        cssString += ' lockedColumn '
                    }
                } else {
                    cssString += ' text-sm/5 font-semibold text-text '
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
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            })
        },
        unfixSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.unfix.sub-position'), {
                subPositionId: subPositionId,
                project_id: this.project?.id
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            })
        },
        handleBudgetManagementSearchBlur(cell) {
            // Auto-close the search input if user leaves the field without making any change
            // Use a short timeout so click on a suggestion can be processed before we possibly cancel
            setTimeout(() => {
                if (cell?.value === this.editedCellOriginalValue) {
                    this.handleBudgetManagementSearchCancel(cell);
                }
            }, 0);
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
        handleBudgetManagementSearchSelect(index, cell, value, displayValue, mainPositionIsVerified, subPositionIsVerified) {
            if (index === 0) {
                cell.accountSearchResults = null;
            } else {
                cell.costUnitSearchResults = null;
            }

            cell.value = value;
            cell.display_value = displayValue;

            this.updateCellValue(cell, mainPositionIsVerified, subPositionIsVerified);
        },
        handleBudgetManagementSearchCancel(cell) {
            cell.clicked = false;
            cell.searchValue = '';
            cell.accountSearchResults = null;
            cell.costUnitSearchResults = null;
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

.softSkyColumn {
    background-color: #93C5FD; /* sky */
}

.softAquaColumn {
    background-color: #67E8F9; /* aqua */
}

.softTealColumn {
    background-color: #5EEAD4; /* teal */
}

.softMintColumn {
    background-color: #86EFAC; /* mint */
}

.softLimeColumn {
    background-color: #BEF264; /* lime */
}

.softAmberColumn {
    background-color: #FCD34D; /* amber */
}

.softPeachColumn {
    background-color: #FDBA74; /* peach */
}

.softRoseColumn {
    background-color: #FDA4AF; /* rose */
}

.softLavenderColumn {
    background-color: #C4B5FD; /* lavender */
}

.softSlateColumn {
    background-color: #CBD5E1; /* slate/gray */
}


</style>
