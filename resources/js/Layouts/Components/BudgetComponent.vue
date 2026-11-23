<template>

    <div :class="[table.is_template ? '' : 'bg-surface-canvas', hideProjectHeader ? '' : 'pt-6']"
         class="mx-1 pr-10 relative">
        <div class="flex justify-between ">
            <div v-if="table.is_template" class="flex justify-start mb-6 font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text">
                {{ table.name }}
                <BaseMenu class="ml-4" v-if="$can('edit budget templates')" white-menu-background>
                    <BaseMenuItem
                        :title="$t('Rename')"
                        icon="IconPencil"
                        white-menu-background
                        @click="openRenameTableModal()"
                    />

                    <BaseMenuItem
                        v-if="table.is_template && !isInTrash"
                        :title="$t('Delete')"
                        icon="IconTrash"
                        white-menu-background
                        @click="deleteBudgetTemplate()"
                    />


                    <BaseMenuItem
                        v-if="table.is_template && isInTrash"
                        :title="$t('Restore')"
                        icon="IconRepeat"
                        white-menu-background
                        @click="restoreBudgetTemplate()"
                    />

                    <BaseMenuItem
                        v-if="table.is_template && isInTrash"
                        :title="$t('Delete permanently')"
                        icon="IconTrash"
                        white-menu-background
                        @click="deleteForceBudgetTemplate()"
                    />
                </BaseMenu>
            </div>
        </div>
        <div class="w-full sticky z-40 flex justify-end items-center h-16 bg-surface-canvas" :style="{ top: 'calc(var(--project-header-height, 126px))' }">
            <!-- Kennzahlen der budgetrelevanten Spalte: ohne bis zum Tabellenende zu scrollen -->
            <div v-if="budgetKpi" class="sticky left-0 lg:left-20 mr-auto hidden min-[1200px]:flex items-center gap-x-5 pl-2 pr-6">
                <div v-for="kpi in budgetKpi.items" :key="kpi.label" class="leading-tight">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-text-subtle">{{ $t(kpi.label) }}</div>
                    <div class="text-sm font-semibold tabular-nums" :class="kpi.value < 0 ? 'text-danger' : 'text-text'">
                        {{ toCurrencyString(kpi.value) }}
                    </div>
                </div>
                <div class="hidden 2xl:block max-w-40 truncate rounded-full bg-surface-sunken px-2 py-0.5 text-[11px] text-text-muted"
                     :title="$t('Budget-relevant column tooltip')">
                    {{ budgetKpi.columnName }}
                </div>
            </div>
            <!-- sticky right: die Tabelle ist oft breiter als das Fenster, die Aktionen bleiben trotzdem im Sichtbereich -->
            <div class="sticky right-14 flex flex-row-reverse gap-x-4 items-center">
                <BaseUIButton v-if="this.$can('edit budget templates') || !table.is_template" @click="openAddColumnModal()"
                    label="New column"
                    use-translation
                    is-add-button
                />

                <BaseUIButton v-if="!table.is_template" @click="downloadBudgetExport(project.id)"
                              label="Excel-Export"
                              use-translation
                              icon="IconFileAnalytics"
                />

                <div v-if="!table.is_template">
                    <BaseUIButton
                        v-if="!hideProjectHeader"
                        @click="$emit('changeProjectHeaderVisualisation',true)"
                        label=""
                        :title="$t('Hide project header')"
                        :aria-label="$t('Hide project header')"
                        icon="IconArrowsDiagonal"
                    />
                    <BaseUIButton
                        v-else
                        @click="$emit('changeProjectHeaderVisualisation',false)"
                        label=""
                        :title="$t('Show project header')"
                        :aria-label="$t('Show project header')"
                        icon="IconArrowsDiagonalMinimize2"
                    />
                </div>

                <BaseUIButton
                    v-if="(table.main_positions?.length ?? 0) > 0"
                    @click="setAllPositionsClosed(!allMainPositionsClosed)"
                    label=""
                    :title="allMainPositionsClosed ? $t('Expand all') : $t('Collapse all')"
                    :aria-label="allMainPositionsClosed ? $t('Expand all') : $t('Collapse all')"
                    :icon="allMainPositionsClosed ? 'IconChevronsDown' : 'IconChevronsUp'"
                />

                <div class="flex items-center gap-x-2" v-if="!table.is_template">
                    <SwitchIconTooltip
                        v-model="userExcludeCommentedBudgetItems"
                        :tooltip-text="$t('Excluded items hidden')"
                        size="md"
                        :icon="IconEyeX"
                    />
                    <span class="pl-1 hidden 2xl:inline" :class="[userExcludeCommentedBudgetItems ? 'text-sm/5 font-semibold text-text' : 'text-sm/5 font-bold text-text-subtle', 'text-sm']">
                            {{ $t('Excluded items hidden') }}
                        </span>
                </div>

                <!-- Suche in der Tabelle: Enter springt zum nächsten Treffer, Umschalt+Enter zurück -->
                <div class="flex items-center gap-x-1">
                    <BaseInput
                        id="budget-table-search"
                        v-model="searchTerm"
                        label="Search"
                        :show-label="false"
                        :placeholder="$t('Search')"
                        class="w-36 2xl:w-48"
                        autocomplete="off"
                        @keydown="onSearchKeydown"
                    />
                    <template v-if="searchTerm.trim() !== ''">
                        <span class="w-12 text-center text-xs tabular-nums" :class="searchMatches.length === 0 ? 'text-danger' : 'text-text-muted'">
                            {{ searchMatches.length === 0 ? '0' : (searchIndex + 1) + '/' + searchMatches.length }}
                        </span>
                        <button type="button" class="rounded p-1 text-text-muted hover:bg-surface-sunken disabled:opacity-40"
                                :disabled="searchMatches.length === 0" :title="$t('Previous')" :aria-label="$t('Previous')"
                                @click="goToSearchMatch(-1)">
                            <PropertyIcon name="IconChevronUp" class="h-4 w-4"/>
                        </button>
                        <button type="button" class="rounded p-1 text-text-muted hover:bg-surface-sunken disabled:opacity-40"
                                :disabled="searchMatches.length === 0" :title="$t('Next')" :aria-label="$t('Next')"
                                @click="goToSearchMatch(1)">
                            <PropertyIcon name="IconChevronDown" class="h-4 w-4"/>
                        </button>
                    </template>
                </div>
            </div>
        </div>


        <div class="w-full flex sticky bg-[#CECDD8] z-30" :style="{ top: 'calc(var(--project-header-height, 126px) + 64px)' }">
            <table class="w-full flex ml-8 py-5">
                <thead>
                <draggable
                    v-model="localColumns"
                    item-key="id"
                    tag="tr"
                    class="relative"
                    handle=".column-drag-handle"
                    ghost-class="opacity-40"
                    :disabled="!canEditColumns"
                    :move="canMoveColumn"
                    @end="persistColumnOrder"
                >
                    <template #item="{ element: column, index }">
                    <th v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)"
                        :class="[index === 0 ? 'w-48' : index === 1 ? 'w-48' : index === 2 ? 'w-72 sticky left-0 lg:left-20 z-10 bg-[#CECDD8]' : 'w-48', pendingDeleteKeys['column:' + column.id] ? 'opacity-40 pointer-events-none' : '']">
                        <div class="flex items-center group" :key="column.id"
                             :class="index > 2 ? 'justify-end text-right' : ' justify-between'">

                            <!-- KTO/KST-Überschriften zentriert über der Spalte -->
                            <div :class="index <= 1 ? 'flex-1 text-center' : ''">
                                <div>
                                    <div v-if="column.subName" class="flex items-center justify-end">
                                        <div class="flex items-center mr-2" v-if="column.is_locked">
                                            <div>
                                                <PropertyIcon name="IconLock" class="h-4 w-4 text-text" stroke-width="2"/>
                                            </div>
                                            <div class="-ml-0.5">
                                                <UserPopoverTooltip :user="column.locked_by"
                                                                    :id="column.locked_by?.id ?? 'lockedByTooltipColumn-' + column.id"
                                                                    class="w-6 h-6 border-2 border-white rounded-full"
                                                                    height="5"
                                                                    width="5"/>
                                            </div>
                                        </div>
                                        <div class="text-xs text-white text-right flex items-center gap-x-1">
                                            <ToolTipComponent
                                                :icon="IconFlagUp"
                                                :tooltip-text="$t('Budget-relevant column tooltip')"
                                                icon-size="size-4"
                                                white-icon
                                                stroke="2"
                                                v-if="column.relevant_for_project_groups"
                                            />
                                            {{ column.subName }}
                                        </div>

                                        <span v-if="columnCalculatedNames ? columnCalculatedNames[column.id] : false"
                                              class="ml-1 truncate text-[10px]/[19px] font-semibold uppercase tracking-wider text-text-subtle text-white">
                                            ({{ columnCalculatedNames[column.id] }})
                                        </span>
                                    </div>
                                    <span
                                        v-if="column.showColorMenu === true || column.color !== 'whiteColumn'"
                                        class="-mt-4 relative inline-flex"
                                    >
                                        <Listbox
                                            v-if="$can('edit budget templates') || !table.is_template"
                                            as="div"
                                            v-model="column.color"
                                            class="relative ml-2"
                                        >
                                            <Transition
                                                enter-active-class="transition ease-out duration-100"
                                                enter-from-class="opacity-0 translate-y-1"
                                                enter-to-class="opacity-100 translate-y-0"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="opacity-100 translate-y-0"
                                                leave-to-class="opacity-0 translate-y-1"
                                            >
                                                <ListboxOptions
                                                    :static="column.showColorMenu"
                                                    class="absolute z-30 mt-12 w-56 overflow-hidden rounded-xl border border-border-subtle bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
                                                >
                                                    <!-- Header -->
                                                    <div class="px-3 py-2 border-b border-border-subtle">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-medium text-text-subtle">
                                                                {{ $t("Choose color") }}
                                                            </span>

                                                            <!-- kleiner Close-Icon-Click (kein Button-Element) -->
                                                            <span
                                                                class="inline-flex cursor-pointer rounded-md p-1 text-text-subtle hover:bg-surface-sunken"
                                                                @click="column.showColorMenu = false"
                                                                aria-label="Close"
                                                                role="button"
                                                                tabindex="0"
                                                            >
                                                                <PropertyIcon name="IconX" class="h-4 w-4" />
                                                            </span>
                                                        </div>

                                                        <!-- aktuelle Auswahl -->
                                                        <div class="mt-2 flex items-center gap-2">
                                                            <span
                                                                class="h-5 w-5 rounded-full ring-1 ring-border-subtle"
                                                                :class="column.color"
                                                                aria-hidden="true"
                                                            />
                                                            <span class="text-xs text-text-muted">
                                                                {{ $t("Current") }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Swatches -->
                                                    <div class="p-3">
                                                        <div class="grid grid-cols-6 gap-2">
                                                            <ListboxOption
                                                                v-for="color in colors"
                                                                :key="color"
                                                                :value="color"
                                                                v-slot="{ active, selected }"
                                                            >
                                                                <!-- kein Button: nur span -->
                                                                <button
                                                                    class="relative h-7 w-7 min-w-7 min-h-7 cursor-pointer rounded-full ring-1 ring-border-subtle transition focus:outline-none"
                                                                    :class="[ color,
                                                                        active ? 'scale-[1.03] ring-2 ring-surface-inverse/40' : '',
                                                                        selected ? 'ring-2 ring-success' : ''
                                                                    ]"
                                                                    role="button"
                                                                    tabindex="0"
                                                                    @click="
                                                                        changeColumnColor(color, column.id)
                                                                    "
                                                                >
                                                                    <PropertyIcon
                                                                        v-if="selected"
                                                                        name="IconCheck"
                                                                        class="absolute -right-1 -top-1 h-4 w-4 text-success bg-white rounded-full p-[2px] shadow"
                                                                        aria-hidden="true"
                                                                    />
                                                                </button>
                                                            </ListboxOption>
                                                        </div>

                                                        <!-- Reset (ebenfalls kein Button) -->
                                                        <span
                                                            class="mt-3 inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-border-subtle bg-white px-3 py-2 text-xs text-text-muted hover:bg-surface-sunken"
                                                            role="button"
                                                            tabindex="0"
                                                            @click="
                                                                changeColumnColor('whiteColumn', column.id)
                                                            "
                                                        >
                                                            {{ $t("Reset to default") }}
                                                        </span>
                                                    </div>
                                                </ListboxOptions>
                                            </Transition>
                                        </Listbox>
                                    </span>

                                </div>
                                <div v-if="editingColumnId !== column.id"
                                     class="h-5 font-lexend text-xs w-full max-w-max"
                                     :class="[index <= 1 ? 'mx-auto' : '', canEditColumns ? 'cursor-text rounded px-1 hover:bg-white/40' : '']"
                                     :title="canEditColumns ? $t('Rename') : null"
                                     @click="startColumnNameEdit(column)">
                                    {{ column.name || $t('No name') }}
                                </div>
                                <div v-else>
                                    <input
                                        ref="columnNameInput"
                                        :class="index === 2 ? 'w-64' : 'w-40'"
                                        class="text-sm/5 font-semibold text-text h-6 flex rounded-md border border-border px-1 focus:border-accent-600 focus:ring-1 focus:ring-accent-600 focus:outline-none"
                                        :style="index > 2 ? 'text-align: right' : ''"
                                        type="text"
                                        v-model="editedColumnName"
                                        @keydown.enter.prevent="$event.target.blur()"
                                        @keydown.esc.prevent="editingColumnId = null"
                                        @blur="saveColumnName(column)">
                                </div>
                            </div>
                            <span v-if="canEditColumns && isMovableColumn(column, index)"
                                  class="column-drag-handle invisible group-hover:visible ml-1 cursor-grab text-white hover:text-text"
                                  :title="$t('Move column')">
                                <PropertyIcon name="IconGripVertical" class="h-4 w-4" aria-hidden="true"/>
                            </span>
                            <BaseMenu
                                dots-color="text-white"
                                has-no-offset
                                class="invisible group-hover:visible"
                                white-menu-background
                                v-if="hasBudgetAccess() || $can('edit budget templates')"
                            >
                                <!-- Coloring -->
                                <BaseMenuItem
                                    :title="$t('Coloring')"
                                    icon="IconPalette"
                                    white-menu-background
                                    @click="column.showColorMenu = true"
                                />

                                <!-- Lock / Unlock -->
                                <BaseMenuItem
                                    v-show="$can('can add and remove verified states') || hasAdminRole()"
                                    v-if="!column.is_locked"
                                    :title="$t('Lock')"
                                    icon="IconLock"
                                    white-menu-background
                                    @click="lockColumn(column.id)"
                                />

                                <BaseMenuItem
                                    v-show="$can('can add and remove verified states') || hasAdminRole()"
                                    v-if="column.is_locked"
                                    :title="$t('Unlock')"
                                    icon="IconLockOpen"
                                    white-menu-background
                                    @click="unlockColumn(column.id)"
                                />

                                <BaseMenuItem
                                    v-if="column.type !== 'subprojects_column_for_group'"
                                    v-show="index > 2"
                                    :title="$t('Duplicate')"
                                    icon="IconCopy"
                                    white-menu-background
                                    @click="duplicateColumn(column.id)"
                                />

                                <!-- Budgetrelevante Spalte: Flag kann nur auf eine andere
                                     Wertspalte verschoben, nicht entfernt werden -->
                                <BaseMenuItem
                                    v-if="!column.relevant_for_project_groups && column.type === 'empty'"
                                    v-show="index > 2"
                                    :title="$t('Mark as budget-relevant')"
                                    icon="IconFlagUp"
                                    white-menu-background
                                    @click="setRelevantForProjectGroup(column.id)"
                                />

                                <!-- Include / Exclude column -->
                                <BaseMenuItem
                                    v-show="index > 2"
                                    v-if="column.commented === 1"
                                    :title="$t('Include column')"
                                    icon="IconParenthesesOff"
                                    white-menu-background
                                    @click="updateColumnCommented(column.id, false)"
                                />

                                <BaseMenuItem
                                    v-show="index > 2"
                                    v-else
                                    :title="$t('Exclude column')"
                                    icon="IconParentheses"
                                    white-menu-background
                                    @click="updateColumnCommented(column.id, true)"
                                />

                                <!-- Delete / Duplicate -->
                                <BaseMenuItem
                                    v-if="column.type !== 'subprojects_column_for_group'"
                                    v-show="index > 2"
                                    :title="$t('Delete')"
                                    icon="IconTrash"
                                    white-menu-background
                                    @click="openDeleteColumnModal(column)"
                                />
                            </BaseMenu>

                        </div>
                    </th>
                    </template>
                </draggable>
                </thead>
            </table>
        </div>
        <SageNotAssignedData v-if="this.$page.props.sageApiEnabled"
                             :sage-not-assigned="sageNotAssigned"
                             @remove-sage-not-assigned-data="this.showRemoveSageNotAssignedDataConfirmationModal"
        />
        <div class="w-full flex mb-6">
            <div class="flex flex-wrap w-full bg-surface border-2 border-border">
                <div class="w-full flex">
                    <div class="bg-surface ml-5 w-full">
                        <div :class="table.columns?.length > 5 ? 'mr-5' : 'w-[97%]'" class="flex justify-between my-10">
                            <div class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text flex">
                                {{ $t('Expenses') }}
                                <button type="button" class="w-6" :title="costsOpened ? $t('Collapse') : $t('Expand')" :aria-expanded="costsOpened" @click="toggleSection('cost')">
                                    <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="costsOpened"
                                                   class="h-6 w-6 text-text my-auto"/>
                                    <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class="h-6 w-6 text-text my-auto"/>
                                </button>
                            </div>
                            <BaseMenu dots-color="text-text-muted" white-menu-background v-if="hasBudgetAccess()">
                                <BaseMenuItem
                                    v-show="tableIsEmpty && !table.is_template"
                                    :title="$t('Import template')"
                                    icon="IconFileImport"
                                    white-menu-background
                                    @click="openUseTemplateModal()"
                                />

                                <BaseMenuItem
                                    v-show="tableIsEmpty && !table.is_template"
                                    :title="$t('Import from project')"
                                    icon="IconFileImport"
                                    white-menu-background
                                    @click="openUseTemplateFromProjectModal()"
                                />

                                <BaseMenuItem
                                    v-show="!tableIsEmpty && !table.is_template"
                                    :title="$t('Save as template')"
                                    icon="IconFilePlus"
                                    white-menu-background
                                    @click="openAddBudgetTemplateModal()"
                                />

                                <BaseMenuItem
                                    v-show="!tableIsEmpty && !table.is_template"
                                    :title="$t('Reset')"
                                    icon="IconRestore"
                                    white-menu-background
                                    @click="openResetConfirmation"
                                />

                                <BaseMenuItem
                                    v-show="!table.is_template"
                                    :title="$t('Restore deleted columns')"
                                    icon="IconColumnInsertRight"
                                    white-menu-background
                                    @click="showRestoreColumnsModal = true"
                                />

                                <BaseMenuItem
                                    v-show="table.is_template"
                                    :title="$t('Delete')"
                                    icon="IconTrash"
                                    white-menu-background
                                    @click="deleteBudgetTemplate()"
                                />
                            </BaseMenu>
                        </div>
                        <div @click="addMainPosition('BUDGET_TYPE_COST', positionDefault)"
                             v-if="costsOpened && (this.$can('edit budget templates') || !table.is_template)"
                             class="group relative w-[97%]  cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-accent-600 before:absolute before:inset-x-0 before:-inset-y-1.5 before:content-['']">
                            <div class="group-hover:flex hidden items-center gap-x-1 -mt-3 h-6 pl-3 pr-1 rounded-full bg-accent-600 text-white text-xs font-semibold uppercase shadow-md relative z-20 whitespace-nowrap">
                                {{ $t('Main position') }}
                                <PropertyIcon name="IconCirclePlus"
                                    class="h-4 w-4"/>
                            </div>
                        </div>
                        <draggable
                            v-if="costsOpened"
                            v-model="localCostMainPositions"
                            item-key="id"
                            handle=".main-position-drag-handle"
                            ghost-class="opacity-50"
                            :disabled="!canReorderPositions"
                            @end="persistMainPositionOrder('BUDGET_TYPE_COST')"
                        >
                            <template #item="{ element: mainPosition }">
                                <div class="mb-1">
                                    <div class="relative">
                                        <div v-if="canReorderPositions"
                                             class="main-position-drag-handle absolute left-[-20px] top-4 z-10 cursor-grab text-text-subtle hover:text-primaryText">
                                            <PropertyIcon name="IconGripVertical" class="h-5 w-5" aria-hidden="true" />
                                        </div>
                                        <MainPositionComponent @openVerifiedModal="openVerifiedModal"
                                                               @openCellDetailModal="openCellDetailModal"
                                                               @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                               @openMainPositionSumDetailModal="openMainPositionSumDetailModal"
                                                               @openDeleteModal="openDeleteModal"
                                                               @open-error-modal="openErrorModal"
                                                               @budget-updated="handleBudgetUpdated"
                                                               @order-saved="showOrderSavedToast"
                                                               @budget-patched="applyBudgetPatch"
                                                               :table="table"
                                                               :project="project"
                                                               :main-position="mainPosition"
                                                               :project-managers="projectManager"
                                                               :hasBudgetAccess="this.hasBudgetAccess()"
                                                               type="BUDGET_TYPE_COST"
                                        />
                                    </div>
                                </div>
                            </template>
                        </draggable>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr class=" text-sm/5 font-semibold text-text flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 py-2 bg-surface sticky left-0 lg:left-20 z-10">{{ $t('SUM') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in valueColumns"
                                    :key="column.id"
                                    v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1 flex group relative justify-end items-center"
                                         :class="(summaryTotals.costSums[column.id] ?? 0) < 0 ? 'text-danger' : ''">
                                        <img @click="openBudgetSumDetailModal('COST', column, 'comment')"
                                             v-if="table.costSumDetails?.[column.id]?.hasComments && table.costSumDetails?.[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('COST', column, 'comment')"
                                             v-else-if="table.costSumDetails?.[column.id]?.hasComments"
                                             src="/Svgs/IconSvgs/icon_linked_adjustments.svg"
                                             class="h-5 w-5 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('COST', column, 'moneySource')"
                                             v-else-if="table.costSumDetails?.[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_money_source.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <span
                                            v-if="column.type !== 'sage' && column.type !== 'subprojects_column_for_group'">{{
                                                toCurrencyString(summaryTotals.costSums[column.id] ?? 0)
                                            }}</span>
                                        <span v-if="column.type === 'sage'">{{
                                                toCurrencyString(summaryTotals.sageCost)
                                            }}</span>
                                        <span v-if="column.type === 'subprojects_column_for_group'">
                                            {{ toCurrencyString(summaryTotals.groupCost) }}
                                        </span>
                                        <div v-if="this.hasBudgetAccess()"
                                             class="hidden group-hover:block absolute right-0 z-50 -mr-6"
                                             @click="openBudgetSumDetailModal('COST', column)">
                                            <PropertyIcon name="IconCirclePlus"
                                                class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-accent-600 rounded-full "/>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class=" text-sm/5 font-bold text-text-subtle flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 py-2 bg-surface sticky left-0 lg:left-20 z-10">{{ $t('SUM excluded items') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in valueColumns"
                                    :key="column.id"
                                    v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1">
                                        <span
                                            v-if="column.type !== 'sage' && column.type !== 'subprojects_column_for_group'">
                                            {{ toCurrencyString(table.commentedCostSums?.[column.id]) }}
                                        </span>
                                        <span v-if="column.type === 'sage'">
                                            {{ toCurrencyString(summaryTotals.sageCostCommented) }}
                                        </span>
                                        <span v-if="column.type === 'subprojects_column_for_group'">
                                            {{ toCurrencyString(summaryTotals.groupCostCommented) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12"/>
                <div class="w-full flex">
                    <div class="ml-5 w-full ">
                        <div class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text my-10 flex">
                            {{ $t('Revenue') }}
                            <button type="button" class="w-6" :title="earningsOpened ? $t('Collapse') : $t('Expand')" :aria-expanded="earningsOpened" @click="toggleSection('earning')">
                                <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="earningsOpened"
                                               class="h-6 w-6 text-text my-auto"/>
                                <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class="h-6 w-6 text-text my-auto"/>
                            </button>
                        </div>
                        <draggable
                            v-if="earningsOpened"
                            v-model="localEarningMainPositions"
                            item-key="id"
                            handle=".main-position-drag-handle"
                            ghost-class="opacity-50"
                            :disabled="!canReorderPositions"
                            @end="persistMainPositionOrder('BUDGET_TYPE_EARNING')"
                        >
                            <template #item="{ element: mainPosition }">
                                <div class="mb-1">
                                    <div class="relative">
                                        <div v-if="canReorderPositions"
                                             class="main-position-drag-handle absolute left-[-20px] top-4 z-10 cursor-grab text-text-subtle hover:text-primaryText">
                                            <PropertyIcon name="IconGripVertical" class="h-5 w-5" aria-hidden="true" />
                                        </div>
                                        <MainPositionComponent @openVerifiedModal="openVerifiedModal"
                                                               @openCellDetailModal="openCellDetailModal"
                                                               @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                               @openMainPositionSumDetailModal="openMainPositionSumDetailModal"
                                                               @openDeleteModal="openDeleteModal"
                                                               @open-error-modal="openErrorModal"
                                                               @budget-updated="handleBudgetUpdated"
                                                               @order-saved="showOrderSavedToast"
                                                               @budget-patched="applyBudgetPatch"
                                                               :table="table"
                                                               :project="project"
                                                               :main-position="mainPosition"
                                                               :project-managers="projectManager"
                                                               :hasBudgetAccess="this.hasBudgetAccess()"
                                                               type="BUDGET_TYPE_EARNING"
                                        />
                                    </div>
                                </div>
                            </template>
                        </draggable>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr class=" text-sm/5 font-semibold text-text flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 py-2 bg-surface sticky left-0 lg:left-20 z-10">{{ $t('SUM') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in valueColumns"
                                    :key="column.id"
                                    v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1 flex group relative justify-end items-center"
                                         :class="(summaryTotals.earningSums[column.id] ?? 0) < 0 ? 'text-danger' : ''">
                                        <img @click="openBudgetSumDetailModal('EARNING', column, 'comment')"
                                             v-if="table.earningSumDetails?.[column.id]?.hasComments && table.earningSumDetails?.[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('EARNING', column, 'comment')"
                                             v-else-if="table.earningSumDetails?.[column.id]?.hasComments"
                                             src="/Svgs/IconSvgs/icon_linked_adjustments.svg"
                                             class="h-5 w-5 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('EARNING', column, 'moneySource')"
                                             v-else-if="table.earningSumDetails?.[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_money_source.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <span
                                            v-if="column.type !== 'sage' && column.type !== 'subprojects_column_for_group'">{{
                                                toCurrencyString(summaryTotals.earningSums[column.id] ?? 0)
                                            }}</span>
                                        <span v-if="column.type === 'sage'">{{
                                                toCurrencyString(summaryTotals.sageEarning)
                                            }}</span>
                                        <span v-if="column.type === 'subprojects_column_for_group'">
                                            {{ toCurrencyString(summaryTotals.groupEarning) }}
                                        </span>
                                        <div v-if="this.hasBudgetAccess()"
                                             class="hidden group-hover:block absolute right-0 z-50 -mr-6"
                                             @click="openBudgetSumDetailModal('EARNING', column)">
                                            <PropertyIcon name="IconCirclePlus"
                                                class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-accent-600 rounded-full "/>
                                        </div>
                                    </div>
                                </td>

                            </tr>
                            <tr class=" text-sm/5 font-bold text-text-subtle flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 py-2 bg-surface sticky left-0 lg:left-20 z-10">{{ $t('SUM excluded items') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in valueColumns"
                                    :key="column.id"
                                    v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1">
                                         <span
                                             v-if="column.type !== 'sage' && column.type !== 'subprojects_column_for_group'">
                                            {{ toCurrencyString(table.commentedEarningSums?.[column.id]) }}
                                        </span>
                                        <span v-if="column.type === 'sage'">
                                            {{ toCurrencyString(summaryTotals.sageEarningCommented) }}
                                        </span>
                                        <span v-if="column.type === 'subprojects_column_for_group'">
                                            {{ toCurrencyString(summaryTotals.groupEarningCommented) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12"/>
                <table class="w-[97%] mt-4 mb-2 ml-5">
                    <tbody>
                    <tr class=" items-center text-sm/5 font-semibold text-text flex h-10 w-full text-right">
                        <td class="w-48"></td>
                        <td class="w-48"></td>
                        <td class="w-72 py-2 bg-surface sticky left-0 lg:left-20 z-10">{{ $t('Revenue') }} - {{ $t('Expenses') }}</td>
                        <td class="flex items-center w-48"
                            v-for="column in valueColumns"
                            :key="column.id"
                            v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                            <div class="w-48 my-2 p-1 flex justify-end items-center"
                                 :class="differenceForColumn(column) < 0 ? 'text-danger' : ''">
                                <span>{{ toCurrencyString(differenceForColumn(column)) }}</span>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Schwebende Leiste: Auswahl-Summe und "Rückgängig" nach dem Löschen -->
    <div v-if="selectionSummary.count > 0 || pendingDeletes.length > 0"
         class="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex flex-col items-center gap-y-2 px-4">
        <div v-for="pending in pendingDeletes" :key="pending.key"
             class="pointer-events-auto relative flex items-center gap-x-4 overflow-hidden rounded-xl bg-surface-inverse py-2.5 pl-4 pr-2 text-sm text-white shadow-lg">
            <PropertyIcon name="IconTrash" class="h-4 w-4 shrink-0 opacity-70"/>
            <span class="max-w-xs truncate">{{ pending.label }}</span>
            <button type="button"
                    class="rounded-lg bg-white/15 px-3 py-1 text-sm font-semibold hover:bg-white/25"
                    @click="undoPendingDelete(pending.key)">
                {{ $t('Undo') }}
            </button>
            <span class="budget-undo-progress absolute inset-x-0 bottom-0 h-0.5 origin-left bg-white/60"
                  :style="{ animationDuration: undoDelayMs + 'ms' }"/>
        </div>
        <div v-if="selectionSummary.count > 0"
             class="pointer-events-auto flex items-center gap-x-5 rounded-xl bg-surface-inverse py-2.5 pl-4 pr-2 text-sm text-white shadow-lg">
            <span class="text-white/70">{{ $t('{count} cells selected', {count: selectionSummary.count}) }}</span>
            <span>{{ $t('Sum') }} <span class="ml-1 font-semibold tabular-nums">{{ toCurrencyString(selectionSummary.sum) }}</span></span>
            <span>{{ $t('Average') }} <span class="ml-1 font-semibold tabular-nums">{{ toCurrencyString(selectionSummary.average) }}</span></span>
            <button type="button" class="rounded-lg p-1.5 hover:bg-white/15"
                    :title="$t('Clear selection')" :aria-label="$t('Clear selection')"
                    @click="clearSelection">
                <PropertyIcon name="IconX" class="h-4 w-4"/>
            </button>
        </div>
    </div>
    <NotificationToast
        v-model:show="orderSavedToastVisible"
        title="Order saved"
        type="success"
        :duration="2500"
    />
    <NotificationToast
        v-model:show="showSuccessModal"
        :title="successHeading"
        :description="successDescription !== successHeading ? successDescription : ''"
        type="success"
        :duration="2500"
    />
    <VerifiedRequestModal
        :show="showVerifiedModal"
        :verified-texts="verifiedTexts"
        :budget-access="budgetAccess"
        :project-members="projectMembers"
        @close="closeVerifiedModal"
        @submit="handleVerifiedUserSubmit"
    />
    <ArtworkBaseModal @close="closeBudgetAccessModal" v-if="showBudgetAccessModal" title="Grant budget access" modal-size="sm:max-w-lg">
        <p class="text-sm text-text">
            {{ $t('The user you have requested for verification does not yet have budget access to your project. With the verification request, you grant him/her this right. Are you sure you want to give her/him this right?') }}
        </p>
        <template #footer>
            <BaseUIButton label="Cancel" is-cancel-button hide-icon @click="closeBudgetAccessModal"/>
            <BaseUIButton label="Issue requests & budget access" variant="primary" icon="IconLockOpen" @click="submitVerifiedModalWithBudgetAccess"/>
        </template>
    </ArtworkBaseModal>
    <add-column-component
        v-if="showAddColumnModal"
        :project="project"
        :table="table"
        @closed="closeAddColumnModal($event)"
    />
    <restore-trashed-columns-modal
        v-if="showRestoreColumnsModal"
        :table="table"
        @closed="showRestoreColumnsModal = false"
        @restored="$emit('budget-updated')"
    />
    <cell-detail-modal
        v-if="showCellDetailModal && tempCellData"
        :cell="tempCellData"
        :project-id="project.id"
        :initial-tab="cellDetailOpenTab"
        :budget-type="cellDetailBudgetType"
        @closed="closeCellDetailModal()"
        @comment-saved="handleCommentSaved"
        @comment-deleted="handleCommentDeleted"
        @calculations-saved="handleCalculationsSaved"
        @budget-updated="handleBudgetUpdated"
    />
    <sum-detail-component
        :selectedSumDetail="tempSumDetailData"
        v-if="showSumDetailModal && tempSumDetailData"
        :project-id="project.id"
        :openTab="sumDetailOpenTab"
        :budget-type="sumDetailBudgetType"
        @closed="closeSumDetailModal"
        @comment-saved="handleSumCommentSaved"
        @comment-deleted="handleSumCommentDeleted"
        @money-source-updated="handleSumMoneySourceUpdated"
    />
    <use-template-component
        v-if="showUseTemplateModal"
        :projectId="project?.id"
        :templates="templates"
        @closed="closeUseTemplateModal()"
    />
    <use-template-from-project-budget-component
        v-if="showUseTemplateFromProjectModal"
        :projectId="project?.id"
        @closed="closeUseTemplateFromProjectModal()"
    />
    <add-budget-template-component
        v-if="showAddBudgetTemplateModal"
        :table-id="table.id"
        @closed="closeAddBudgetTemplateModal"
    />
    <rename-table-component
        v-if="showRenameTableModal"
        :table="table"
        @closed="closeRenameBudgetTemplateModal()"
    />
    <confirmation-component
        v-if="showDeleteModal"
        :confirm="resetWanted ? $t('Reset') : $t('Delete')"
        :titel="this.confirmationTitle"
        :description="this.confirmationDescription"
        @closed="afterConfirm"
    />
    <error-component
        v-if="showErrorModal"
        :confirm="$t('OK')"
        :titel="this.errorTitle"
        :description="this.errorDescription"
        @closed="afterErrorConfirm"
    />
    <confirmation-component v-if="this.showDeleteSageNotAssignedDataConfirmationModal"
                            @closed="this.showSageNotAssignedDataConfirmationModalHandler"
                            :description="$t('Do you really want to put the data set in the trash?', [this.sageNotAssignedDataToDelete.buchungstext])"
                            :titel="$t('Move to the trash')"


    />

    <error-component v-if="this.$page.props.flash.error"
                     :titel="$t('An error has occurred')"
                     :description="this.$page.props.flash.error"
                     :confirm="$t('Close message')"
                     @closed="this.$page.props.flash.error = null;"
    />

</template>

<script>
import AddColumnComponent from "@/Layouts/Components/AddColumnComponent.vue";
import RestoreTrashedColumnsModal from "@/Layouts/Components/RestoreTrashedColumnsModal.vue";
import CellDetailModal from "@/Layouts/Components/CellDetailModal.vue";
import {
    Listbox,
    ListboxOption,
    ListboxOptions
} from "@headlessui/vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {router, useForm} from "@inertiajs/vue3";
import MainPositionComponent from "@/Layouts/Components/MainPositionComponent.vue";
import UseTemplateComponent from "@/Layouts/Components/UseTemplateComponent.vue";
import UseTemplateFromProjectBudgetComponent from "@/Layouts/Components/UseTemplateFromProjectBudgetComponent.vue";
import AddBudgetTemplateComponent from "@/Layouts/Components/AddBudgetTemplateComponent.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import SumDetailComponent from "@/Layouts/Components/SumDetailComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import SageNotAssignedData from "@/Pages/Projects/Components/SageNotAssignedData.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import RenameTableComponent from "@/Layouts/Components/RenameTableComponent.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconEyeX, IconFlagUp} from "@tabler/icons-vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import axios from "axios";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import VerifiedRequestModal from "@/Layouts/Components/VerifiedRequestModal.vue";
import draggable from 'vuedraggable';
import NotificationToast from "@/Artwork/Feedback/NotificationToast.vue";
import {nextTick} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {findBudgetSearchMatches, summarizeBudgetSelection} from "@/Layouts/Components/Budget/budgetTableTools.js";
import {
    budgetCellSelection,
    budgetPendingDeletes,
    budgetSearchState,
    clearBudgetCellSelection,
    resetBudgetSearchState
} from "@/Layouts/Components/Budget/budgetTableState.js";
import {setBudgetEntryClosed} from "@/Layouts/Components/Budget/budgetCollapseState.js";
import {
    isBudgetEntryClosed,
    setAllBudgetEntriesClosed,
    toggleBudgetEntryClosed
} from "@/Layouts/Components/Budget/budgetCollapseState.js";

export default {
    name: 'BudgetComponent',
    mixins: [Permissions, CurrencyFloatToStringFormatter],
    components: {
        BaseInput,
        draggable,
        NotificationToast,
        BaseMenuItem,
        PropertyIcon,
        ArtworkBaseModal,
        BaseUIButton,
        SwitchIconTooltip,
        ToolTipComponent,
        UserPopoverTooltip,
        UserSearch,
        BaseMenu,
        SageNotAssignedData,
        SumDetailComponent,
        UseTemplateFromProjectBudgetComponent,
        MainPositionComponent,
        ConfirmationComponent,
        CellDetailModal,
        AddColumnComponent,
        RestoreTrashedColumnsModal,
        Listbox,
        ListboxOption,
        ListboxOptions,
        UseTemplateComponent,
        AddBudgetTemplateComponent,
        RenameTableComponent,
        ErrorComponent,
        VerifiedRequestModal
    },
    data() {
        return {
            orderSavedToastVisible: false,
            showSumDetailModal: false,
            showBudgetAccessModal: false,
            hoveredBorder: null,
            showAddColumnModal: false,
            showCellDetailModal: false,
            showUseTemplateModal: false,
            showRenameTableModal: false,
            showUseTemplateFromProjectModal: false,
            showAddBudgetTemplateModal: false,
            resetWanted: false,
            hoveredRow: null,
            showMenu: null,
            showDeleteModal: false,
            showErrorModal: false,
            mainPositionToDelete: null,
            subPositionToDelete: null,
            rowToDelete: null,
            columnToDelete: null,
            showRestoreColumnsModal: false,
            confirmationTitle: '',
            confirmationDescription: '',
            errorTitle: '',
            errorDescription: '',
            showSuccessModal: false,
            successHeading: '',
            successDescription: '',
            positionDefault: {
                position: 0
            },
            tempCellData: null,  // Temporäre Cell-Daten für Modal
            tempSumDetailData: null,  // Temporäre Sum-Detail-Daten für Modal
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
                pinkColumn: 'pinkColumn',
                // +10 neu (heller)
                softSkyColumn: "softSkyColumn",
                softAquaColumn: "softAquaColumn",
                softTealColumn: "softTealColumn",
                softMintColumn: "softMintColumn",
                softLimeColumn: "softLimeColumn",
                softAmberColumn: "softAmberColumn",
                softPeachColumn: "softPeachColumn",
                softRoseColumn: "softRoseColumn",
                softLavenderColumn: "softLavenderColumn",
                softSlateColumn: "softSlateColumn",
            },
            verifiedTexts: {
                title: this.$t('Verification'),
                positionTitle: '',
                description: this.$t('Have all figures been calculated correctly? Is the calculation plausible? Have your main item verified by a user.')
            },
            showVerifiedModal: false,
            submitVerifiedModalData: useForm({
                is_main: false,
                is_sub: false,
                id: null,
                user: '',
                position: [],
                giveBudgetAccess: false,
                project_title: this.project?.name,
                table_id: this.table?.id
            }),
            cellDetailOpenTab: 'calculation',
            cellDetailBudgetType: null,
            sumDetailOpenTab: 'comment',
            sumDetailBudgetType: null,
            userExcludeCommentedBudgetItems: this.$page.props.auth.user.commented_budget_items_setting ?
                this.$page.props.auth.user.commented_budget_items_setting.exclude === 1 :
                false,
            showDeleteSageNotAssignedDataConfirmationModal: false,
            sageNotAssignedDataToDelete: null,
            localCostMainPositions: [],
            localEarningMainPositions: [],
            editingColumnId: null,
            editedColumnName: '',
            localColumns: [],
            searchTerm: '',
            searchIndex: 0,
            // {key, label, url, heading, description, timer}
            pendingDeletes: [],
            undoDelayMs: 6000
        }
    },
    props: [
        'hideProjectHeader',
        'selectedSumDetail',
        'columnCalculatedNames',
        'table',
        'project',
        'moneySources',
        'selectedCell',
        'selectedRow',
        'templates',
        'budgetAccess',
        'projectManager',
        'columns',
        'sageNotAssigned',
        'first_project_budget_tab_id',
        'isInTrash',
        'canEditComponent'
    ],
    emits: ['changeProjectHeaderVisualisation', 'budget-updated', 'sumDetailLoaded'],
    computed: {
        computedSortedColumns: function () {
            // Kopie statt in-place-sort: das computed darf die Prop nicht mutieren
            return [...(this.table.columns ?? [])].sort((a, b) => {
                if (a.type === 'sage') return 1;
                if (b.type === 'sage') return -1;
                return 0;
            });
        },
        valueColumns: function () {
            return this.computedSortedColumns.slice(3);
        },
        /**
         * Alle Summen der Fusszeilen in EINEM Durchlauf ueber den Baum.
         * Ersetzt die frueheren Template-Methodenaufrufe, die pro Spalte und
         * Render mehrfach ueber die komplette Tabelle iteriert haben.
         */
        summaryTotals: function () {
            const costSums = {};
            const earningSums = {};
            let sageCost = 0;
            let sageEarning = 0;
            let sageCostCommented = 0;
            let sageEarningCommented = 0;

            for (const mainPosition of this.table.main_positions ?? []) {
                const isCost = mainPosition.type === 'BUDGET_TYPE_COST';
                const sums = isCost ? costSums : earningSums;

                for (const [columnId, data] of Object.entries(mainPosition.columnSums ?? {})) {
                    sums[columnId] = (sums[columnId] ?? 0) + (parseFloat(data?.sum) || 0);
                }

                for (const subPosition of mainPosition.sub_positions ?? []) {
                    for (const row of subPosition.sub_position_rows ?? []) {
                        for (const cell of row.cells ?? []) {
                            if (cell.column?.type !== 'sage') {
                                continue;
                            }
                            const amount = (cell.sage_assigned_data ?? []).reduce(
                                (acc, data) => acc + (Number(data.buchungsbetrag) || 0),
                                0
                            );
                            if (cell.commented || cell.column.commented) {
                                if (isCost) {
                                    sageCostCommented += amount;
                                } else {
                                    sageEarningCommented += amount;
                                }
                            } else if (isCost) {
                                sageCost += amount;
                            } else {
                                sageEarning += amount;
                            }
                        }
                    }
                }
            }

            const groupData = this.$page.props.loadedProjectInformation?.BudgetTab?.projectGroupRelevantBudgetData;
            const groupSum = (type, commented) => {
                if (!groupData || !Array.isArray(groupData[type])) {
                    return 0;
                }
                return groupData[type]
                    .filter(item => item?.type === type && Boolean(item?.commented) === commented)
                    .reduce((acc, item) => {
                        const value = parseFloat(item.value?.replace(',', '.') || '0');
                        return acc + (isNaN(value) ? 0 : value);
                    }, 0);
            };

            return {
                costSums,
                earningSums,
                sageCost,
                sageEarning,
                sageCostCommented,
                sageEarningCommented,
                groupCost: groupSum('BUDGET_TYPE_COST', false),
                groupEarning: groupSum('BUDGET_TYPE_EARNING', false),
                groupCostCommented: groupSum('BUDGET_TYPE_COST', true),
                groupEarningCommented: groupSum('BUDGET_TYPE_EARNING', true),
            };
        },
        tableIsEmpty: function () {
            return this.table.main_positions.length === 2 &&
                this.table.main_positions[0].sub_positions.length === 1 &&
                this.table.main_positions[0].sub_positions[0].sub_position_rows.length === 1
        },
        /**
         * Ausgaben, Einnahmen und Ergebnis der budgetrelevanten Spalte fuer die Kopfleiste.
         */
        budgetKpi() {
            const column = this.computedSortedColumns.find(c => c.relevant_for_project_groups);
            if (!column) {
                return null;
            }
            const costs = this.summaryTotals.costSums[column.id] ?? 0;
            const earnings = this.summaryTotals.earningSums[column.id] ?? 0;
            return {
                columnName: column.name || column.subName,
                items: [
                    {label: 'Expenses', value: costs},
                    {label: 'Revenue', value: earnings},
                    {label: 'Result', value: earnings - costs},
                ],
            };
        },
        searchMatches() {
            return findBudgetSearchMatches(this.table.main_positions, this.searchTerm);
        },
        selectionSummary() {
            if (Object.keys(budgetCellSelection.ids).length === 0) {
                return {count: 0, sum: 0, average: 0};
            }
            return summarizeBudgetSelection(this.table.main_positions, budgetCellSelection.ids);
        },
        pendingDeleteKeys() {
            return budgetPendingDeletes.keys;
        },
        costsOpened() {
            return !isBudgetEntryClosed('section', this.table?.id, 'cost');
        },
        earningsOpened() {
            return !isBudgetEntryClosed('section', this.table?.id, 'earning');
        },
        allMainPositionsClosed() {
            const mainPositions = this.table.main_positions ?? [];
            return mainPositions.length > 0
                && mainPositions.every(mainPosition => isBudgetEntryClosed('main', this.table.id, mainPosition.id));
        },
        canEditColumns() {
            return this.hasBudgetAccess() || this.$can('edit budget templates');
        },
        canReorderPositions() {
            return this.hasBudgetAccess() || this.$can('edit budget templates');
        },
        projectMembers: function () {
            let projectMemberArray = [];
            this.project?.users?.forEach(member => {
                    projectMemberArray.push(member.id)
                }
            )
            return projectMemberArray;
        }
    },
    watch: {
        // Bewusst NICHT deep: strukturelle Aenderungen kommen als neue Objekte
        // aus dem Refetch; Zell-Patches mutieren in-place und sollen die
        // Draggable-Listen nicht neu aufbauen.
        'table.main_positions': {
            handler(positions) {
                if (!positions) return;
                this.localCostMainPositions = positions.filter(mp => mp.type === 'BUDGET_TYPE_COST');
                this.localEarningMainPositions = positions.filter(mp => mp.type === 'BUDGET_TYPE_EARNING');
            },
            immediate: true,
        },
        // Kopie fuer das Verschieben per Drag & Drop; nicht deep, Aenderungen kommen als neue
        // Objekte aus dem Refetch.
        'table.columns': {
            handler() {
                this.localColumns = [...this.computedSortedColumns];
            },
            immediate: true,
        },
        searchMatches(matches) {
            const keys = {};
            matches.forEach(match => {
                keys[`${match.type}:${match.id}`] = true;
            });
            budgetSearchState.matchedKeys = keys;
            if (this.searchIndex >= matches.length) {
                this.searchIndex = 0;
            }
            const active = matches[this.searchIndex];
            budgetSearchState.activeKey = active ? `${active.type}:${active.id}` : null;
        },
        searchTerm() {
            // Beim Tippen zum ersten Treffer springen, aber nicht bei jedem Tastendruck
            this.searchIndex = 0;
            window.clearTimeout(this._searchJumpTimer);
            this._searchJumpTimer = window.setTimeout(() => this.goToSearchMatch(0), 300);
        },
        // oeffnet das Modal automatisch, wenn das Backend ein selectedSumDetail liefert
        selectedSumDetail: {
            handler(newVal) {
                if (newVal && (newVal.id || Object.keys(newVal).length > 0)) {
                    this.showSumDetailModal = true;
                }
            },
            immediate: true,
        },
        userExcludeCommentedBudgetItems: {
            handler(excludeHiddenItems) {
                if (this.$page.props.auth.user.commented_budget_items_setting === null) {
                    router.post(
                        route(
                            'user.commentedBudgetItemsSettings.store',
                            {
                                user: this.$page.props.auth.user.id
                            }
                        ),
                        {
                            exclude: excludeHiddenItems
                        },
                        {
                            preserveState: true,
                            preserveScroll: true
                        }
                    );
                    return;
                }

                router.patch(
                    route(
                        'user.commentedBudgetItemsSettings.update',
                        {
                            user: this.$page.props.auth.user.id,
                            commentedBudgetItemsSetting: this.$page.props.auth.user.commented_budget_items_setting.id
                        }
                    ),
                    {
                        exclude: excludeHiddenItems
                    },
                    {
                        preserveScroll: true,
                        preserveState: true
                    }
                );
            }
        },
    },
    mounted() {
        this._onBudgetKeydown = (event) => {
            if (event.key === 'Escape' && Object.keys(budgetCellSelection.ids).length > 0) {
                clearBudgetCellSelection();
            }
        };
        // Tab wird geschlossen: ausstehende Loeschungen noch absenden
        this._onBudgetPageHide = () => this.flushPendingDeletes();
        window.addEventListener('keydown', this._onBudgetKeydown);
        window.addEventListener('pagehide', this._onBudgetPageHide);
    },
    beforeUnmount() {
        window.removeEventListener('keydown', this._onBudgetKeydown);
        window.removeEventListener('pagehide', this._onBudgetPageHide);
        window.clearTimeout(this._searchJumpTimer);
        this.flushPendingDeletes();
        clearBudgetCellSelection();
        resetBudgetSearchState();
    },
    methods: {
        clearSelection() {
            clearBudgetCellSelection();
        },
        onSearchKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                window.clearTimeout(this._searchJumpTimer);
                this.goToSearchMatch(event.shiftKey ? -1 : 1);
            } else if (event.key === 'Escape') {
                this.searchTerm = '';
            }
        },
        /**
         * Springt zum naechsten/vorherigen Treffer (delta 0 = aktueller) und klappt die
         * Abschnitte und Positionen auf, in denen er liegt.
         */
        goToSearchMatch(delta) {
            const matches = this.searchMatches;
            if (matches.length === 0) {
                return;
            }
            this.searchIndex = (this.searchIndex + delta + matches.length) % matches.length;
            const match = matches[this.searchIndex];
            budgetSearchState.activeKey = `${match.type}:${match.id}`;

            setBudgetEntryClosed('section', this.table.id, match.section, false);
            if (match.type !== 'main') {
                setBudgetEntryClosed('main', this.table.id, match.mainPositionId, false);
            }
            if (match.type === 'row') {
                setBudgetEntryClosed('sub', this.table.id, match.subPositionId, false);
            }

            nextTick(() => {
                const element = document.getElementById(`budget-${match.type}-${match.id}`);
                if (!element) {
                    return;
                }
                // Nur senkrecht scrollen: scrollIntoView wuerde die breite Tabelle auch quer verschieben
                const top = element.getBoundingClientRect().top + window.scrollY - window.innerHeight / 2;
                window.scrollTo({top: Math.max(0, top), behavior: 'smooth'});
            });
        },
        isMovableColumn(column, index) {
            // ohne Spalte (Ziehen auf den Rand der Liste) gibt es kein gültiges Ziel
            return !!column && index > 2 && column.type !== 'sage' && column.type !== 'subprojects_column_for_group';
        },
        canMoveColumn(event) {
            return this.isMovableColumn(event.draggedContext.element, event.draggedContext.index)
                && this.isMovableColumn(event.relatedContext.element, event.relatedContext.index);
        },
        persistColumnOrder(event) {
            if (event.oldIndex === event.newIndex) {
                return;
            }
            router.patch(
                route('project.budget.column.reorder'),
                {
                    table_id: this.table.id,
                    column_ids: this.localColumns
                        .filter((column, index) => this.isMovableColumn(column, index))
                        .map(column => column.id),
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.handleBudgetUpdated();
                        this.showOrderSavedToast();
                    },
                    onError: () => {
                        this.localColumns = [...this.computedSortedColumns];
                    },
                }
            );
        },
        /**
         * Loeschen mit Bedenkzeit: der Eintrag wird ausgegraut und erst nach Ablauf wirklich
         * geloescht. Bis dahin laesst sich das Loeschen in der Leiste unten zuruecknehmen.
         */
        scheduleDelete(key, label, url, heading, description) {
            if (this.pendingDeletes.some(pending => pending.key === key)) {
                return;
            }
            budgetPendingDeletes.keys[key] = true;
            this.pendingDeletes.push({
                key,
                label,
                url,
                heading,
                description,
                timer: window.setTimeout(() => this.executePendingDelete(key), this.undoDelayMs),
            });
        },
        takePendingDelete(key) {
            const index = this.pendingDeletes.findIndex(pending => pending.key === key);
            if (index === -1) {
                return null;
            }
            const [pending] = this.pendingDeletes.splice(index, 1);
            window.clearTimeout(pending.timer);
            return pending;
        },
        undoPendingDelete(key) {
            if (this.takePendingDelete(key)) {
                delete budgetPendingDeletes.keys[key];
            }
        },
        executePendingDelete(key) {
            const pending = this.takePendingDelete(key);
            if (!pending) {
                return;
            }
            axios.delete(pending.url, {skipErrorToast: true}).then(({data}) => {
                this.$emit('budget-updated');
                this.successHeading = pending.heading;
                this.successDescription = data?.message ?? pending.description;
                this.showSuccessModal = true;
            }).catch(() => {
                this.openErrorModal(
                    this.$t('An error has occurred'),
                    this.$t('The entry could not be deleted. Please try again.')
                );
            }).finally(() => {
                // Erst nach dem Neuladen wieder einblenden, sonst blitzt der geloeschte Eintrag kurz auf
                window.setTimeout(() => {
                    delete budgetPendingDeletes.keys[key];
                }, 1500);
            });
        },
        flushPendingDeletes() {
            [...this.pendingDeletes].forEach((pending) => {
                this.takePendingDelete(pending.key);
                delete budgetPendingDeletes.keys[pending.key];
                axios.delete(pending.url, {skipErrorToast: true}).catch(() => {});
            });
        },
        persistMainPositionOrder(type) {
            const list = type === 'BUDGET_TYPE_COST' ? this.localCostMainPositions : this.localEarningMainPositions;
            if (!list || list.length === 0) return;
            this.$inertia.patch(
                route('project.budget.main-position.reorder'),
                {
                    table_id: this.table.id,
                    type: type,
                    main_position_ids: list.map(mp => mp.id),
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    // Wie Unter-/Zeilen-Reorder: Budget-Daten neu laden + kurzes Feedback.
                    onSuccess: () => {
                        this.handleBudgetUpdated();
                        this.showOrderSavedToast();
                    },
                }
            );
        },
        toggleSection(section) {
            toggleBudgetEntryClosed('section', this.table?.id, section);
        },
        setAllPositionsClosed(closed) {
            const mainPositions = this.table.main_positions ?? [];
            setAllBudgetEntriesClosed('main', this.table.id, mainPositions.map(mp => mp.id), closed);
            if (!closed) {
                // "Alle aufklappen" oeffnet auch die Unterpositionen, sonst bleibt die Tabelle halb zu
                setAllBudgetEntriesClosed(
                    'sub',
                    this.table.id,
                    mainPositions.flatMap(mp => (mp.sub_positions ?? []).map(sp => sp.id)),
                    false
                );
            }
        },
        startColumnNameEdit(column) {
            if (!this.canEditColumns) {
                return;
            }
            this.editedColumnName = column.name ?? '';
            this.editingColumnId = column.id;
            nextTick(() => {
                const input = this.$refs.columnNameInput;
                (Array.isArray(input) ? input[0] : input)?.select();
            });
        },
        saveColumnName(column) {
            if (this.editingColumnId !== column.id) {
                return;
            }
            this.editingColumnId = null;
            const name = this.editedColumnName.trim();
            // Leerer oder unveraenderter Name: nichts speichern (spart Request + Neuladen)
            if (name === '' || name === column.name) {
                return;
            }
            column.name = name;
            this.updateColumnName(column);
        },
        showOrderSavedToast() {
            // Toast erneut auslösen, auch wenn er gerade noch sichtbar ist.
            this.orderSavedToastVisible = false;
            this.$nextTick(() => { this.orderSavedToastVisible = true; });
        },
        IconEyeX,
        IconFlagUp,
        differenceForColumn(column) {
            const totals = this.summaryTotals;
            if (column.type === 'sage') {
                return totals.sageEarning - totals.sageCost;
            }
            if (column.type === 'subprojects_column_for_group') {
                return totals.groupEarning - totals.groupCost;
            }
            return (totals.earningSums[column.id] ?? 0) - (totals.costSums[column.id] ?? 0);
        },
        /**
         * Wendet den schlanken Zell-Update-Patch des Backends an, statt den
         * kompletten Budget-Payload neu zu laden: Haupt-/Tabellensummen werden
         * in-place aktualisiert, alles andere ergibt sich reaktiv.
         */
        applyBudgetPatch(patch) {
            if (!patch) {
                return;
            }

            for (const [mainPositionId, sums] of Object.entries(patch.mainPositionSums ?? {})) {
                const mainPosition = (this.table.main_positions ?? [])
                    .find(mp => mp.id === Number(mainPositionId));
                if (mainPosition) {
                    mainPosition.columnSums = sums.columnSums;
                    mainPosition.columnVerifiedChanges = sums.columnVerifiedChanges;
                }
            }

            if (patch.tableSums) {
                this.table.costSums = patch.tableSums.costSums;
                this.table.earningSums = patch.tableSums.earningSums;
                this.table.commentedCostSums = patch.tableSums.commentedCostSums;
                this.table.commentedEarningSums = patch.tableSums.commentedEarningSums;
                this.table.costSumDetails = patch.tableSums.costSumDetails;
                this.table.earningSumDetails = patch.tableSums.earningSumDetails;
            }
        },
        hasBudgetAccess() {
            return this.hasAdminRole() ||
                this.budgetAccess?.filter(
                    (user) => user.id === this.$page.props.auth.user.id
                ).length > 0 ||
                this.$canAny(
                    [
                        'can manage global project budgets',
                    ]
                );
        },
        setRelevantForProjectGroup(columnId) {
            router.patch(
                route(
                    'project.budget.column.update.relevant',
                    {
                        column: columnId
                    }
                ),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => this.$emit('budget-updated')
                }
            );
        },
        updateColumnCommented(columnId, bool) {
            router.patch(
                route(
                    'project.budget.column.update.commented',
                    {
                        column: columnId
                    }
                ),
                {
                    commented: bool
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => this.$emit('budget-updated')
                }
            );
        },
        duplicateColumn(columnId) {
            router.post(route('project.budget.column.duplicate', columnId), {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        handleVerifiedUserSubmit(user) {
            this.submitVerifiedModalData.user = user.id;
            this.usersToAdd = user;
            this.submitVerifiedModal();
        },
        changeColumnColor(color, columnId) {
            router.patch(route('project.budget.column-color.change'), {
                color: color,
                columnId: columnId
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => this.$emit('budget-updated')
            })
        },
        openDeleteColumnModal(column) {
            // Löschen ist folgenreich (Spalte samt Zellen/Kommentaren) - wie bei
            // Positionen/Zeilen erst bestätigen lassen.
            this.confirmationTitle = this.$t('Delete column');
            this.confirmationDescription = this.$t(
                'Are you sure you want to delete the column? The last remaining value column is emptied instead.',
                [column.name]
            );
            this.columnToDelete = column;
            this.showDeleteModal = true;
        },
        openAddColumnModal() {
            this.showAddColumnModal = true;
        },
        closeAddColumnModal(columnAdded = false) {
            this.showAddColumnModal = false;
            if (columnAdded) {
                this.$emit('budget-updated');
            }
        },
        openUseTemplateModal() {
            router.reload({
                data: {
                    useTemplates: true
                },
                only: ['selectedSumDetail', 'table', 'selectedCell', 'selectedRow', 'templates', 'budgetAccess'],
                onSuccess: () => {
                    this.showUseTemplateModal = true;
                }
            })
        },
        openRenameTableModal() {
            this.showRenameTableModal = true;
        },
        closeUseTemplateModal() {
            this.showUseTemplateModal = false;
        },
        openUseTemplateFromProjectModal() {
            this.showUseTemplateFromProjectModal = true;
        },
        closeUseTemplateFromProjectModal() {
            this.showUseTemplateFromProjectModal = false;
        },
        openAddBudgetTemplateModal() {
            this.showAddBudgetTemplateModal = true;
        },
        closeAddBudgetTemplateModal(bool) {
            this.showAddBudgetTemplateModal = false;

            if (bool) {
                this.successHeading = this.$t('Template saved');
                this.successDescription = this.$t('Your template has been saved successfully.');
                this.showSuccessModal = true;
            }
        },
        closeRenameBudgetTemplateModal() {
            this.showRenameTableModal = false;
        },
        addMainPosition(type, mainPosition) {
            router.post(route('project.budget.main-position.add'), {
                table_id: this.table.id,
                type: type,
                positionBefore: mainPosition.position
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        updateColumnName(column) {
            router.patch(route('project.budget.column.update-name'), {
                column_id: column.id,
                columnName: column.name
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => this.$emit('budget-updated')
            });
        },
        async openCellDetailModal(cell, type) {
            this.cellDetailOpenTab = type;

            const result = this.findCellInTable(cell.id);

            if (!result) {
                this.openErrorModal(
                    this.$t('An error has occurred'),
                    this.$t('Cell not found. Please refresh the page.')
                );
                return;
            }

            // 2. Öffne Modal SOFORT mit vorhandenen Daten
            this.tempCellData = result.cell;
            this.cellDetailBudgetType = result.budgetType;
            this.showCellDetailModal = true;

            // KEIN automatisches Laden von Kalkulationen mehr!
            // User soll selbst auf "Add calculation item" klicken
        },

        updateCellInTable(cellId, updatesOrCallback) {
            // Finde und aktualisiere die Cell in der lokalen Tabellenstruktur
            for (const mainPosition of this.table.main_positions || []) {
                for (const subPosition of mainPosition.sub_positions || []) {
                    for (const row of subPosition.sub_position_rows || []) {
                        const cellIndex = row.cells?.findIndex(c => c.id === cellId);
                        if (cellIndex !== -1) {
                            const cell = row.cells[cellIndex];

                            if (typeof updatesOrCallback === 'function') {
                                updatesOrCallback(cell);
                            } else {
                                row.cells[cellIndex] = {
                                    ...cell,
                                    ...updatesOrCallback
                                };
                            }

                            return true;
                        }
                    }
                }
            }
            return false;
        },

        findCellInTable(cellId) {
            // Durchsuche alle MainPositions > SubPositions > Rows > Cells
            for (const mainPosition of this.table.main_positions || []) {
                for (const subPosition of mainPosition.sub_positions || []) {
                    for (const row of subPosition.sub_position_rows || []) {
                        const cell = row.cells?.find(c => c.id === cellId);
                        if (cell) {
                            return { cell, budgetType: mainPosition.type };
                        }
                    }
                }
            }
            return null;
        },
        async openBudgetSumDetailModal(type, column, tab = 'comment') {
            this.sumDetailOpenTab = tab;
            this.sumDetailBudgetType = type === 'COST' ? 'BUDGET_TYPE_COST' : 'BUDGET_TYPE_EARNING';

            try {
                const { data } = await axios.get(route('project.budget.sum-details.show'), {
                    params: {
                        type: 'budget',
                        position_id: type, // COST or EARNING
                        column_id: column.id
                    }
                });

                if (data.sumDetail) {
                    this.tempSumDetailData = data.sumDetail;
                    this.showSumDetailModal = true;
                }
            } catch (error) {
                this.openSumDetailLoadError();
            }
        },
        async openSubPositionSumDetailModal(subPosition, column, type) {
            const mainPosition = this.table.main_positions?.find(mp =>
                mp.sub_positions?.some(sp => sp.id === subPosition.id)
            );
            this.sumDetailOpenTab = type;
            this.sumDetailBudgetType = mainPosition?.type || null;

            try {
                const { data } = await axios.get(route('project.budget.sum-details.show'), {
                    params: {
                        type: 'subPosition',
                        position_id: subPosition.id,
                        column_id: column.id
                    }
                });

                if (data.sumDetail) {
                    this.tempSumDetailData = data.sumDetail;
                    this.showSumDetailModal = true;
                }
            } catch (error) {
                this.openSumDetailLoadError();
            }
        },
        async openMainPositionSumDetailModal(mainPosition, column, type) {
            this.sumDetailOpenTab = type;
            this.sumDetailBudgetType = mainPosition.type;

            try {
                const { data } = await axios.get(route('project.budget.sum-details.show'), {
                    params: {
                        type: 'mainPosition',
                        position_id: mainPosition.id,
                        column_id: column.id
                    }
                });

                if (data.sumDetail) {
                    this.tempSumDetailData = data.sumDetail;
                    this.showSumDetailModal = true;
                }
            } catch (error) {
                this.openSumDetailLoadError();
            }
        },
        openSumDetailLoadError() {
            this.openErrorModal(
                this.$t('An error has occurred'),
                this.$t('The details could not be loaded. Please try again.')
            );
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
            this.tempCellData = null;  // Clear temporäre Daten
        },

        closeSumDetailModal() {
            this.showSumDetailModal = false;
            this.tempSumDetailData = null;
        },

        handleSumCommentSaved(data) {
            if (this.tempSumDetailData && this.tempSumDetailData.id === data.sumDetailId) {
                if (!this.tempSumDetailData.comments) {
                    this.tempSumDetailData.comments = [];
                }
                const exists = this.tempSumDetailData.comments.some(c => c.id === data.comment.id);
                if (!exists) {
                    this.tempSumDetailData.comments.unshift(data.comment);
                }
            }
            // Trigger budget update to refresh sums display
            this.$emit('budget-updated');
        },

        handleSumCommentDeleted(data) {
            if (this.tempSumDetailData && this.tempSumDetailData.id === data.sumDetailId) {
                if (this.tempSumDetailData.comments) {
                    const index = this.tempSumDetailData.comments.findIndex(c => c.id === data.commentId);
                    if (index !== -1) {
                        this.tempSumDetailData.comments.splice(index, 1);
                    }
                }
            }
            this.$emit('budget-updated');
        },

        handleSumMoneySourceUpdated() {
            // Trigger budget update to refresh money source links in sums
            this.$emit('budget-updated');
        },

        handleCommentSaved(data) {
            // Kommentare aendern keine Summen - lokal patchen statt Refetch
            if (this.tempCellData && this.tempCellData.id === data.cellId) {
                if (!this.tempCellData.comments) {
                    this.tempCellData.comments = [];
                }
                if (!this.tempCellData.comments.some(c => c.id === data.comment.id)) {
                    this.tempCellData.comments.unshift(data.comment);
                }
            }

            this.updateCellInTable(data.cellId, (cell) => {
                if (!cell.comments) {
                    cell.comments = [];
                }
                if (!cell.comments.some(c => c.id === data.comment.id)) {
                    cell.comments.unshift(data.comment);
                    cell.comments_count = (cell.comments_count ?? 0) + 1;
                }
            });
        },

        handleCommentDeleted(data) {
            // Kommentare aendern keine Summen - lokal patchen statt Refetch
            if (this.tempCellData && this.tempCellData.id === data.cellId) {
                if (this.tempCellData.comments) {
                    const index = this.tempCellData.comments.findIndex(c => c.id === data.commentId);
                    if (index !== -1) {
                        this.tempCellData.comments.splice(index, 1);
                    }
                }
            }

            this.updateCellInTable(data.cellId, (cell) => {
                if (cell.comments) {
                    const index = cell.comments.findIndex(c => c.id === data.commentId);
                    if (index !== -1) {
                        cell.comments.splice(index, 1);
                        cell.comments_count = Math.max((cell.comments_count ?? 1) - 1, 0);
                    }
                }
            });
        },

        handleCalculationsSaved(data) {
            if (this.tempCellData && this.tempCellData.id === data.cellId) {
                this.tempCellData.calculations = data.calculations;
                this.tempCellData.value = data.cellValue;
            }

            this.updateCellInTable(data.cellId, (cell) => {
                cell.calculations = data.calculations;
                cell.value = data.cellValue;
                cell.calculations_count = (data.calculations ?? []).filter(c => Number(c.value) !== 0).length;
            });

            // Kalkulationen aendern den Zellwert -> Summen muessen neu berechnet
            // werden, daher hier weiterhin ein Refetch.
            this.$emit('budget-updated');
        },

        openDeleteModal(title, description, position, type) {
            this.confirmationTitle = title;
            this.confirmationDescription = description
            if (type === 'main') {
                this.mainPositionToDelete = position;
            } else if (type === 'sub') {
                this.subPositionToDelete = position;
            } else {
                this.rowToDelete = position;

            }
            this.showDeleteModal = true;
        },
        afterConfirm(bool) {
            if (!bool) {
                this.resetWanted = false;
                // Ziele zurücksetzen, sonst löscht die nächste Bestätigung das falsche Objekt
                this.columnToDelete = null;
                this.mainPositionToDelete = null;
                this.subPositionToDelete = null;
                this.rowToDelete = null;
                return this.showDeleteModal = false;
            }
            if (this.resetWanted === true) {
                this.resetBudgetTable();
            } else {
                this.deletePosition();
            }
        },
        afterErrorConfirm(bool) {
            this.showErrorModal = false;
        },
        deletePosition() {
            if (this.columnToDelete !== null) {
                this.scheduleDelete(
                    'column:' + this.columnToDelete.id,
                    this.$t('Column deleted') + (this.columnToDelete.name ? ': ' + this.columnToDelete.name : ''),
                    route('project.budget.column.delete', this.columnToDelete.id),
                    this.$t('Column deleted'),
                    this.$t('Column successfully deleted', [this.columnToDelete.name])
                );
            } else if (this.mainPositionToDelete !== null) {
                this.scheduleDelete(
                    'main:' + this.mainPositionToDelete.id,
                    this.$t('Main position deleted') + (this.mainPositionToDelete.name ? ': ' + this.mainPositionToDelete.name : ''),
                    route('project.budget.main-position.delete', this.mainPositionToDelete.id),
                    this.$t('Main position deleted'),
                    this.$t('Main position successfully deleted', [this.mainPositionToDelete.name])
                );
            } else if (this.subPositionToDelete !== null) {
                this.scheduleDelete(
                    'sub:' + this.subPositionToDelete.id,
                    this.$t('Sub-item deleted') + (this.subPositionToDelete.name ? ': ' + this.subPositionToDelete.name : ''),
                    route('project.budget.sub-position.delete', this.subPositionToDelete.id),
                    this.$t('Sub-item deleted'),
                    this.$t('Sub-item successfully deleted', [this.subPositionToDelete.name])
                );
            } else if (this.rowToDelete !== null) {
                this.scheduleDelete(
                    'row:' + this.rowToDelete.id,
                    this.$t('Row deleted'),
                    route('project.budget.sub-position-row.delete', this.rowToDelete.id),
                    this.$t('Row deleted'),
                    this.$t('Line successfully deleted')
                );
            }
            this.mainPositionToDelete = null;
            this.subPositionToDelete = null;
            this.columnToDelete = null;
            this.rowToDelete = null;
            this.showDeleteModal = false;
        },
        closeVerifiedModal(deleteAll = false) {
            this.showVerifiedModal = false;
            if (deleteAll) {
                this.submitVerifiedModalData.user = '';
                this.submitVerifiedModalData.id = null;
                this.submitVerifiedModalData.is_main = false;
                this.submitVerifiedModalData.is_sub = false;
                this.submitVerifiedModalData.position = [];
            }
        },
        closeBudgetAccessModal() {
            this.showBudgetAccessModal = false;
            this.submitVerifiedModalData.user = '';
            this.submitVerifiedModalData.id = null;
            this.submitVerifiedModalData.is_main = false;
            this.submitVerifiedModalData.is_sub = false;
            this.submitVerifiedModalData.position = [];
        },
        submitVerifiedModalWithBudgetAccess() {
            this.submitVerifiedModalData.giveBudgetAccess = true;
            this.submitVerifiedModal();
        },
        submitVerifiedModal() {
            const budgetAccessIds = this.budgetAccess?.map(u => u.id ?? u) ?? [];
            if (budgetAccessIds.includes(this.submitVerifiedModalData.user) || this.submitVerifiedModalData.giveBudgetAccess) {
                if (this.submitVerifiedModalData.is_main) {
                    this.submitVerifiedModalData.post(route('project.budget.verified.main-position.request'), {
                        preserveState: true,
                        preserveScroll: true
                    });
                } else {
                    this.submitVerifiedModalData.post(route('project.budget.verified.sub-position.request'), {
                        preserveState: true,
                        preserveScroll: true
                    });
                }
                this.closeVerifiedModal(true);
            } else {
                this.showBudgetAccessModal = true;
                this.closeVerifiedModal(false);
            }

            if (this.submitVerifiedModalData.giveBudgetAccess) {
                this.closeBudgetAccessModal()
            }
        },
        openVerifiedModal(is_main, is_sub, id, position) {
            this.verifiedTexts.positionTitle = position.name
            this.submitVerifiedModalData.is_main = is_main
            this.submitVerifiedModalData.is_sub = is_sub
            this.submitVerifiedModalData.id = id
            this.submitVerifiedModalData.position = position
            this.showVerifiedModal = true
        },
        lockColumn(columnId) {
            router.patch(this.route('project.budget.lock.column'), {
                columnId: columnId
            }, {preserveState: true, preserveScroll: true, onSuccess: () => this.$emit('budget-updated')});
        },
        unlockColumn(columnId) {
            router.patch(this.route('project.budget.unlock.column'), {
                columnId: columnId
            }, {preserveState: true, preserveScroll: true, onSuccess: () => this.$emit('budget-updated')});
        },
        openResetConfirmation() {
            this.confirmationTitle = this.$t('Reset budget tables');
            this.confirmationDescription = this.$t('Are you sure you want to reset these tables? All links etc. will also be deleted.');
            this.resetWanted = true;
            this.showDeleteModal = true;
        },
        resetBudgetTable() {
            router.patch(this.route('project.budget.reset.table', this.project.id), {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => this.$emit('budget-updated')
            })
            this.resetWanted = false;
            this.showDeleteModal = false;
        },
        deleteBudgetTemplate() {
            router.delete(this.route('project.budget.table.soft.delete', this.table.id), {
                preserveState: true,
                preserveScroll: true
            })
        },
        deleteForceBudgetTemplate() {
            router.delete(this.route('project.budget.table.delete', this.table.id), {
                preserveState: true,
                preserveScroll: true
            })
        },
        restoreBudgetTemplate() {
            router.patch(this.route('project.budget.table.restore', this.table.id), {}, {
                preserveState: true,
                preserveScroll: true
            })
        },
        openErrorModal(title, description) {
            this.errorTitle = title;
            this.errorDescription = description
            this.showErrorModal = true;
        },
        downloadBudgetExport(projectId) {
            window.open(
                route(
                    'projects.export.budget',
                    {
                        project: projectId
                    }
                ),
                '_blank',
                'noopener'
            );
        },
        showRemoveSageNotAssignedDataConfirmationModal(sageNotAssignedData) {
            this.sageNotAssignedDataToDelete = sageNotAssignedData;
            this.showDeleteSageNotAssignedDataConfirmationModal = true;
        },
        showSageNotAssignedDataConfirmationModalHandler(closedToDelete) {
            if (closedToDelete) {
                router.delete(
                    route('sageNotAssignedData.destroy', {
                        sageNotAssignedData: this.sageNotAssignedDataToDelete.id
                    }),
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => this.$emit('budget-updated')
                    }
                )
            }

            this.showDeleteSageNotAssignedDataConfirmationModal = false;
            this.sageNotAssignedDataToDelete = null;
        },
        handleBudgetUpdated() {
            // Emit event to parent (BudgetTab) to reload budget data
            this.$emit('budget-updated');
        }
    },
}
</script>

<style scoped>
.budget-undo-progress {
    animation-name: budget-undo-progress;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
}

@keyframes budget-undo-progress {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

.whiteColumn {
    background-color: #FCFCFBFF;
}

.darkBlueColumn {
    background-color: #21485C;
}

.darkGreenColumn {
    background-color: #4D908E;
}

.darkLightBlueColumn {
    background-color: #168FC3;
}

.lightBlueNew {
    background-color: #3DC3CB;
}

.greenColumn {
    background-color: #2EAA63;
}

.lightGreenColumn {
    background-color: #86C554;
}

.yellowColumn {
    background-color: #F1B640;
}

.orangeColumn {
    background-color: #EB7A3D;
}

.redColumn {
    background-color: #DA3F87;
}

.pinkColumn {
    background-color: #641A54;
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



.stickyHeader {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    display: block;
    top: 0;
    z-index: 21;
    background-color: #CECDD8;
}
</style>
