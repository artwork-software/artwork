<template>

    <div :class="[table.is_template ? '' : 'bg-light-background-gray', hideProjectHeader ? '' : 'pt-6']"
         class="mx-1 pr-10 relative">
        <div class="flex justify-between ">
            <div v-if="table.is_template" class="flex justify-start mb-6 headline2">
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
        <div class="w-full sticky z-40 flex flex-row-reverse gap-x-4 py-4 items-center bg-light-background-gray" :style="{ top: 'calc(var(--project-header-height, 126px))' }">
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
                    use-translation
                    icon="IconArrowsDiagonal"
                />
                <BaseUIButton
                    v-else
                    @click="$emit('changeProjectHeaderVisualisation',false)"
                    label=""
                    use-translation
                    icon="IconZoomOut"
                />
            </div>

            <div class="flex items-center gap-x-2" v-if="!table.is_template">
                <SwitchIconTooltip
                    v-model="userExcludeCommentedBudgetItems"
                    :tooltip-text="$t('Excluded items hidden')"
                    size="md"
                    :icon="IconEyeX"
                />
                <span class="pl-1" :class="[userExcludeCommentedBudgetItems ? 'xsDark' : 'xsLight', 'text-sm']">
                        {{ $t('Excluded items hidden') }}
                    </span>
            </div>

            <div class="flex items-center gap-x-2" v-if="!table.is_template && this.$page.props.budgetAccountManagementGlobal">
                <SwitchDualLabel
                    v-model="userShowAccountName"
                    :left-label="$t('Show number')"
                    :right-label="$t('Show name')"
                />
                <ToolTipComponent
                    icon="IconInfoCircle"
                    icon-size="w-5 h-5"
                    :tooltip-text="$t('Use the slider to determine whether you want to see the name or the number in the first two columns when account linking is active.')"
                />
            </div>
        </div>


        <div class="w-full flex sticky bg-[#CECDD8] z-30" :style="{ top: 'calc(var(--project-header-height, 126px) + 64px)' }">
            <table class="w-full flex ml-8 py-5">
                <thead>
                <tr class="relative">
                    <th v-for="(column,index) in computedSortedColumns"
                        :key="column.id"
                        v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)"
                        :class="index === 0 ? 'w-48' : index === 1 ? 'w-48' : index === 2 ? 'w-72' : 'w-48'">
                        <div class="flex items-center group" :key="column.id"
                             :class="index > 2 ? 'justify-end text-right' : ' justify-between'">
                            <div>
                                <div>
                                    <div v-if="column.subName" class="flex items-center justify-end">
                                        <div class="flex items-center mr-2" v-if="column.is_locked">
                                            <div>
                                                <PropertyIcon name="IconLock" class="h-4 w-4 text-primary" stroke-width="2"/>
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
                                              class="ml-1 truncate columnSubName text-white">
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
                                                    class="absolute z-30 mt-12 w-56 overflow-hidden rounded-xl border border-gray-200
                                                           bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
                                                >
                                                    <!-- Header -->
                                                    <div class="px-3 py-2 border-b border-gray-100">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-medium text-gray-500">
                                                                {{ $t("Choose color") }}
                                                            </span>

                                                            <!-- kleiner Close-Icon-Click (kein Button-Element) -->
                                                            <span
                                                                class="inline-flex cursor-pointer rounded-md p-1 text-gray-500 hover:bg-gray-100"
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
                                                                class="h-5 w-5 rounded-full ring-1 ring-gray-200"
                                                                :class="column.color"
                                                                aria-hidden="true"
                                                            />
                                                            <span class="text-xs text-gray-600">
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
                                                                    class="relative h-7 w-7 min-w-7 min-h-7 cursor-pointer rounded-full ring-1 ring-gray-200 transition
                                                                           focus:outline-none"
                                                                    :class="[
                                                                        color,
                                                                        active ? 'scale-[1.03] ring-2 ring-primary/40' : '',
                                                                        selected ? 'ring-2 ring-emerald-500/50' : ''
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
                                                                        class="absolute -right-1 -top-1 h-4 w-4 text-emerald-600 bg-white rounded-full p-[2px] shadow"
                                                                        aria-hidden="true"
                                                                    />
                                                                </button>
                                                            </ListboxOption>
                                                        </div>

                                                        <!-- Reset (ebenfalls kein Button) -->
                                                        <span
                                                            class="mt-3 inline-flex w-full cursor-pointer items-center justify-center rounded-lg border border-gray-200
                                                                   bg-white px-3 py-2 text-xs text-gray-700 hover:bg-gray-50"
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
                                <div @click="column.clicked = !column.clicked" class="h-5 font-lexend text-xs w-full max-w-max"
                                     v-if="!column.clicked">
                                    {{ column.name ?? 'no name' }}
                                </div>
                                <div v-else>
                                    <input
                                        :class="index === 0 ? 'w-48' : index === 1 ? 'w-48' : index === 2 ? 'w-72' : 'w-48'"
                                        class="xsDark h-5 pr-1 mr-1 flex rounded-md border border-gray-300 px-1 focus:border-artwork-buttons-create focus:ring-1 focus:ring-artwork-buttons-create focus:outline-none"
                                        type="text"
                                        v-model="column.name"
                                        @keyup.enter="$event.target.blur()"
                                        @keyup.esc="column.name = editedColumnNameOriginalValue ?? column.name; column.clicked = false"
                                        @focus="editedColumnNameOriginalValue = column.name"
                                        @focusout="updateColumnName(column); column.clicked = !column.clicked">
                                </div>
                            </div>
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
                                    icon="IconLockOpen"
                                    white-menu-background
                                    @click="updateColumnCommented(column.id, false)"
                                />

                                <BaseMenuItem
                                    v-show="index > 2"
                                    v-else
                                    :title="$t('Exclude column')"
                                    icon="IconLock"
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

                </tr>
                </thead>
            </table>
        </div>
        <SageNotAssignedData v-if="this.$page.props.sageApiEnabled"
                             :sage-not-assigned="sageNotAssigned"
                             @remove-sage-not-assigned-data="this.showRemoveSageNotAssignedDataConfirmationModal"
        />
        <div class="w-full flex mb-6">
            <div class="flex flex-wrap w-full bg-secondary-hover border-2 border-gray-300">
                <div class="w-full flex">
                    <div class="bg-secondary-hover ml-5 w-full" v-if="costsOpened">
                        <div :class="table.columns?.length > 5 ? 'mr-5' : 'w-[97%]'" class="flex justify-between my-10">
                            <div class="headline4  flex">
                                {{ $t('Expenses') }}
                                <button class="w-6" @click="costsOpened = !costsOpened">
                                    <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="costsOpened"
                                                   class="h-6 w-6 text-primary my-auto"/>
                                    <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                                </button>
                            </div>
                            <BaseMenu dots-color="text-artwork-context-dark" white-menu-background v-if="hasBudgetAccess()">
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
                                    @click="resetBudgetTable"
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
                             v-if="this.$can('edit budget templates') || !table.is_template"
                             class="group w-[97%] bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-artwork-buttons-create">
                            <div class="group-hover:block hidden uppercase text-artwork-buttons-create text-sm -mt-8">
                                {{ $t('Main position') }}
                                <PropertyIcon name="IconCirclePlus"
                                    class="h-6 w-6 ml-12 text-secondaryHover bg-artwork-buttons-create rounded-full" />
                            </div>
                        </div>
                        <draggable
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
                                             class="main-position-drag-handle absolute left-[-20px] top-4 z-10 cursor-grab text-secondary hover:text-primaryText">
                                            <PropertyIcon name="IconGripVertical" class="h-5 w-5" aria-hidden="true" />
                                        </div>
                                        <MainPositionComponent @openVerifiedModal="openVerifiedModal"
                                                               @openCellDetailModal="openCellDetailModal"
                                                               @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                               @openMainPositionSumDetailModal="openMainPositionSumDetailModal"
                                                               @openDeleteModal="openDeleteModal"
                                                               @open-error-modal="openErrorModal"
                                                               @budget-updated="handleBudgetUpdated"
                                                               @budget-patched="applyBudgetPatch"
                                                               :table="table"
                                                               :project="project"
                                                               :main-position="mainPosition"
                                                               :project-managers="projectManager"
                                                               :hasBudgetAccess="this.hasBudgetAccess()"
                                                               :user-show-account-name="userShowAccountName"
                                                               type="BUDGET_TYPE_COST"
                                        />
                                    </div>
                                </div>
                            </template>
                        </draggable>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 my-2">{{ $t('SUM') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in valueColumns"
                                    :key="column.id"
                                    v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1 flex group relative justify-end items-center"
                                         :class="(summaryTotals.costSums[column.id] ?? 0) < 0 ? 'text-red-500' : ''">
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
                                                class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-artwork-buttons-create rounded-full "/>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 my-2">{{ $t('SUM excluded items') }}</td>
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
                    <div class="ml-5 w-full bg-secondaryHover" v-else>
                        <div class="headline4 my-10 flex">
                            {{ $t('Expenses') }}
                            <button class="w-6" @click="costsOpened = !costsOpened">
                                <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="costsOpened"
                                                   class="h-6 w-6 text-primary my-auto"/>
                                <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12"/>
                <div class="w-full flex">
                    <div class="ml-5 w-full bg-secondaryHover" v-if="earningsOpened">
                        <div class="headline4 my-10 flex">
                            {{ $t('Revenue') }}
                            <button class="w-6" @click="earningsOpened = !earningsOpened">
                                <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="earningsOpened"
                                               class="h-6 w-6 text-primary my-auto"/>
                                <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                            </button>
                        </div>
        <draggable
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
                                             class="main-position-drag-handle absolute left-[-20px] top-4 z-10 cursor-grab text-secondary hover:text-primaryText">
                                            <PropertyIcon name="IconGripVertical" class="h-5 w-5" aria-hidden="true" />
                                        </div>
                                        <MainPositionComponent @openVerifiedModal="openVerifiedModal"
                                                               @openCellDetailModal="openCellDetailModal"
                                                               @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                               @openMainPositionSumDetailModal="openMainPositionSumDetailModal"
                                                               @openDeleteModal="openDeleteModal"
                                                               @open-error-modal="openErrorModal"
                                                               @budget-updated="handleBudgetUpdated"
                                                               @budget-patched="applyBudgetPatch"
                                                               :table="table"
                                                               :project="project"
                                                               :main-position="mainPosition"
                                                               :project-managers="projectManager"
                                                               :hasBudgetAccess="this.hasBudgetAccess()"
                                                               :user-show-account-name="userShowAccountName"
                                                               type="BUDGET_TYPE_EARNING"
                                        />
                                    </div>
                                </div>
                            </template>
                        </draggable>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 my-2">{{ $t('SUM') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in valueColumns"
                                    :key="column.id"
                                    v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1 flex group relative justify-end items-center"
                                         :class="(summaryTotals.earningSums[column.id] ?? 0) < 0 ? 'text-red-500' : ''">
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
                                                class="h-6 w-6 flex-shrink-0 cursor-pointer text-white bg-artwork-buttons-create rounded-full "/>
                                        </div>
                                    </div>
                                </td>

                            </tr>
                            <tr class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                                <td class="w-48"></td>
                                <td class="w-48"></td>
                                <td class="w-72 my-2">{{ $t('SUM excluded items') }}</td>
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
                    <!-- View if not opened Event -->
                    <div class="ml-5 w-full bg-secondaryHover" v-else>
                        <div class="headline4 my-10 flex">
                            {{ $t('Revenue') }}
                            <button class="w-6"
                                    @click="earningsOpened = !earningsOpened">
                                <PropertyIcon name="IconChevronUp" stroke-width="1.5" v-if="earningsOpened"
                                               class="h-6 w-6 text-primary my-auto"/>
                                <PropertyIcon name="IconChevronDown" stroke-width="1.5" v-else
                                                 class="h-6 w-6 text-primary my-auto"/>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12"/>
                <table class="w-[97%] mt-4 mb-2 ml-5">
                    <tbody>
                    <tr class="bg-secondaryHover items-center xsDark flex h-10 w-full text-right">
                        <td class="w-48"></td>
                        <td class="w-48"></td>
                        <td class="w-72 my-2">{{ $t('Revenue') }} - {{ $t('Expenses') }}</td>
                        <td class="flex items-center w-48"
                            v-for="column in valueColumns"
                            :key="column.id"
                            v-show="!(column.commented && this.$page.props.auth.user.commented_budget_items_setting?.exclude === 1)">
                            <div class="w-48 my-2 p-1 flex justify-end items-center"
                                 :class="differenceForColumn(column) < 0 ? 'text-red-500' : ''">
                                <span>{{ toCurrencyString(differenceForColumn(column)) }}</span>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <ArtworkBaseModal @close="closeSuccessModal" v-if="showSuccessModal" :title="successHeading" :description="successDescription">
        <div class="mx-4">
            <div class="headline1 my-2">
                {{ successHeading }}
            </div>
            <div class="successText">
                {{ successDescription }}
            </div>
            <div class="mt-6 flex justify-center">
                <ArtworkBaseModalButton variant="success" @click="closeSuccessModal">
                    <PropertyIcon name="IconCheck" stroke-width="1.5" class="h-5 w-5"/>
                </ArtworkBaseModalButton>
            </div>
        </div>
    </ArtworkBaseModal>
    <VerifiedRequestModal
        :show="showVerifiedModal"
        :verified-texts="verifiedTexts"
        :budget-access="budgetAccess"
        :project-members="projectMembers"
        @close="closeVerifiedModal"
        @submit="handleVerifiedUserSubmit"
    />
    <ArtworkBaseModal @close="closeBudgetAccessModal" v-if="showBudgetAccessModal" :title="$t('Grant budget access')" :description="$t('The user you have requested for verification does not yet have budget access to your project. With the verification request, you grant him/her this right. Are you sure you want to give her/him this right?')">
        <div class="mx-4">
            <div class="mt-6 flex justify-center">
                <ArtworkBaseModalButton variant="primary" @click="submitVerifiedModalWithBudgetAccess">
                    {{ $t('Issue requests & budget access') }}
                </ArtworkBaseModalButton>
            </div>
        </div>
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
        :confirm="$t('Delete')"
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
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import axios from "axios";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import VerifiedRequestModal from "@/Layouts/Components/VerifiedRequestModal.vue";
import draggable from 'vuedraggable';

export default {
    name: 'BudgetComponent',
    mixins: [Permissions, CurrencyFloatToStringFormatter],
    components: {
        draggable,
        BaseMenuItem,
        PropertyIcon,
        ArtworkBaseModal,
        ArtworkBaseModalButton,
        BaseUIButton,
        SwitchIconTooltip,
        SwitchDualLabel,
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
            showSumDetailModal: false,
            showBudgetAccessModal: false,
            costsOpened: true,
            earningsOpened: true,
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
            userShowAccountName: this.$page.props.auth.user.budget_account_display_setting ?
                !this.$page.props.auth.user.budget_account_display_setting.show_number :
                false,
            showDeleteSageNotAssignedDataConfirmationModal: false,
            sageNotAssignedDataToDelete: null,
            localCostMainPositions: [],
            localEarningMainPositions: [],
            editedColumnNameOriginalValue: null
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
        userShowAccountNumber() {
            return !this.userShowAccountName;
        },
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
        userShowAccountName: {
            handler(showName) {
                const showNumber = !showName;
                if (!this.$page.props.auth.user.budget_account_display_setting) {
                    router.post(
                        route(
                            'user.budgetAccountDisplaySettings.store',
                            {
                                user: this.$page.props.auth.user.id
                            }
                        ),
                        {
                            show_number: showNumber
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
                        'user.budgetAccountDisplaySettings.update',
                        {
                            user: this.$page.props.auth.user.id,
                            budgetAccountDisplaySetting: this.$page.props.auth.user.budget_account_display_setting.id
                        }
                    ),
                    {
                        show_number: showNumber
                    },
                    {
                        preserveScroll: true,
                        preserveState: true
                    }
                );
            }
        },
    },
    methods: {
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
                }
            );
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
                        'can manage all project budgets without docs'
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
                console.error('Error loading budget sum details:', error);
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
                console.error('Error loading sub position sum details:', error);
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
                console.error('Error loading main position sum details:', error);
            }
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
                router.delete(route('project.budget.column.delete', this.columnToDelete.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => this.$emit('budget-updated')
                });
                this.successHeading = this.$t('Column deleted');
                this.successDescription = this.$t('Column successfully deleted', [this.columnToDelete.name]);
            } else if (this.mainPositionToDelete !== null) {
                router.delete(route('project.budget.main-position.delete', this.mainPositionToDelete.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => this.$emit('budget-updated')
                })
                this.successHeading = this.$t('Main position deleted');
                this.successDescription = this.$t('Main position successfully deleted', [this.mainPositionToDelete.name]);
            } else if (this.subPositionToDelete !== null) {
                router.delete(route('project.budget.sub-position.delete', this.subPositionToDelete.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => this.$emit('budget-updated')
                })
                this.successHeading = this.$t('Sub-item deleted');
                this.successDescription = this.$t('Sub-item successfully deleted', [this.subPositionToDelete.name]);
            } else {
                router.delete(`/project/budget/sub-position-row/${this.rowToDelete.id}`, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => this.$emit('budget-updated')
                });
                this.successHeading = this.$t('Row deleted');
                this.successDescription = this.$t('Line successfully deleted');
            }
            this.showDeleteModal = false;
            this.showSuccessModal = true;

            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.mainPositionToDelete = null;
            this.subPositionToDelete = null;
            this.columnToDelete = null;
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
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
            router.patch(this.route('project.budget.table.restore', this.table.id), {
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
