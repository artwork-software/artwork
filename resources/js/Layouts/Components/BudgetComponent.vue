<template>
    <div :class="[table.is_template ? '' : 'bg-lightBackgroundGray', hideProjectHeader ? '' : 'pt-6']" class="mx-1 pr-10 relative">
        <div class="flex justify-between ">
            <div v-if="table.is_template" class="flex justify-start mb-6 headline2">
                {{ table.name }}
                <Menu as="div" class="ml-4" v-if="this.$can('edit budget templates')">
                    <div class="flex">
                        <MenuButton
                            class="flex bg-tagBg p-0.5 rounded-full">
                            <IconDotsVertical
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
                            class="z-50 absolute w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <a @click="openRenameTableModal()"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        {{ $t('Rename') }}
                                    </a>
                                </MenuItem>
                                <MenuItem v-if="table.is_template" v-slot="{ active }">
                                    <a @click="deleteBudgetTemplate()"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <IconTrash class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                        {{ $t('Delete') }}
                                    </a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>
        <div class="w-full flex flex-row-reverse mb-4 items-center">
            <button v-if="!table.is_template"
                    @click="downloadBudgetExport(project.id)"
                    type="button"
                    class="flex p-2 px-3 mt-1 items-center border border-transparent rounded-full shadow-sm text-white focus:outline-none bg-artwork-buttons-create hover:bg-artwork-buttons-hover">
                <IconFileAnalytics stroke-width="2" class="h-4 w-4 mr-2"/>
                <p class="text-sm">{{ $t('Excel-Export') }}</p>
            </button>
            <div v-if="!table.is_template">
                <IconArrowsDiagonal v-if="!hideProjectHeader" @click="$emit('changeProjectHeaderVisualisation',true)" class="h-6 w-6 mx-2 cursor-pointer"/>
                <IconZoomOut v-else
                             @click="$emit('changeProjectHeaderVisualisation',false)"
                             class="h-7 w-7 mx-2 cursor-pointer"
                />
            </div>
            <SwitchGroup as="div" v-if="!table.is_template">
                <Switch v-model="userExcludeCommentedBudgetItems"
                        :class="[userExcludeCommentedBudgetItems ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-artwork-buttons-hover focus:ring-offset-2']">
                        <span aria-hidden="true"
                              :class="[userExcludeCommentedBudgetItems ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                </Switch>
                <SwitchLabel as="span">
                    <span class="pl-1" :class="[userExcludeCommentedBudgetItems ? 'xsDark' : 'xsLight', 'text-sm']">
                        {{ $t('Excluded items hidden') }}
                    </span>
                </SwitchLabel>
            </SwitchGroup>
        </div>
        <div class="w-full flex stickyHeader">
            <table class="w-full flex ml-6 py-5">
                <thead>
                <tr class="">
                    <th v-for="(column,index) in table.columns"
                        v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)"
                        :class="index <= 1 ? 'pl-2 w-28 text-left' : index === 2 ? 'w-64 text-left pl-2' : index === 3 ? 'w-52 text-right' : 'w-48 px-1 text-right'">
                        <div class="flex items-center " @mouseover="showMenu = column.id" :key="column.id"
                             @mouseout="showMenu = null">
                            <div>
                                <div :class="index <= 2 ? '' : 'justify-end'" class="flex items-center  pr-2">
                                    <div v-if="column.subName" class="flex items-center">
                                        <div class="flex items-center mr-2" v-if="column.is_locked">
                                            <div>
                                                <svg  xmlns="http://www.w3.org/2000/svg" width="11.975" height="13.686" class="" viewBox="0 0 11.975 13.686">
                                                <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                                                      d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                                                      fill="#27233C"/>
                                                 </svg>
                                            </div>
                                            <div class="-ml-0.5 ">
                                                <img :src="column?.locked_by?.profile_photo_url" alt="" class="object-cover w-6 h-6 border-2 border-white rounded-full">
                                            </div>
                                        </div>
                                        <div class="columnSubName text-white ">
                                            {{ column.subName }}
                                        </div>

                                        <span v-if="columnCalculatedNames ? columnCalculatedNames[column.id] : false" class="ml-1 truncate columnSubName text-white">
                                            ({{columnCalculatedNames[column.id]}})
                                        </span>
                                    </div>
                                    <span  class="-mt-4" v-if="column.showColorMenu === true || column.color !== 'whiteColumn'">
                                        <Listbox as="div" class="flex ml-2" v-model="column.color" v-if="this.$can('edit budget templates') || !table.is_template">
                                            <transition leave-active-class="transition ease-in duration-100"
                                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                <ListboxOptions :static="column.showColorMenu"
                                                    class="absolute w-24 z-10 mt-12 bg-primary shadow-lg max-h-64 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                                    <ListboxOption as="template" class=""
                                                                   v-for="color in colors"
                                                                   :key="color"
                                                                   :value="color" v-slot="{ active, selected }">
                                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 text-sm subpixel-antialiased']"
                                                            @click="changeColumnColor(color, column.id)">
                                                            <div class="flex">
                                                                <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'block truncate']">
                                                                    <span
                                                                        class="block truncate items-center ml-3 flex rounded-full h-10 w-10"
                                                                        :class="color">
                                                                    </span>
                                                                </span>
                                                            </div>
                                                            <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                                <CheckIcon v-if="selected"
                                                                           class="h-5 w-5 flex text-success"
                                                                           aria-hidden="true"/>
                                                            </span>
                                                        </li>
                                                    </ListboxOption>
                                                </ListboxOptions>
                                            </transition>
                                        </Listbox>
                                    </span>
                                </div>
                                <div @click="column.clicked = !column.clicked"
                                     :class="index <= 1 ? 'w-16 justify-start' : index === 2 ? 'w-64 justify-start' : index === 3 ? 'w-48 justify-end' : 'w-40 px-3 justify-end'" class="h-5 pr-1 mr-1 xsDark flex "
                                     v-if="!column.clicked">
                                    {{ column.name }}
                                </div>
                                <div v-else>
                                    <input
                                        :class="index <= 1 ? 'w-16 text-left' : index === 2 ? 'w-64 text-left' : index === 3 ? 'w-48 text-right' : 'w-40 text-right'"
                                        class="xsDark h-5  pr-1 mr-1 flex " type="text"
                                        v-model="column.name"
                                        @focusout="updateColumnName(column); column.clicked = !column.clicked">
                                </div>
                            </div>
                            <Menu as="div" v-show="showMenu === column.id" v-if="this.$can('edit budget templates') || !table.is_template">
                                <div class="flex">
                                    <MenuButton
                                        class="flex bg-tagBg p-0.5 rounded-full">
                                        <IconDotsVertical
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
                                        class="absolute w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-slot="{ active }">
                                                <a @click="column.showColorMenu = true"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                                                    </svg>
                                                    {{ $t('Coloring') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }" v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-if="!column.is_locked">
                                                <a @click="lockColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                    </svg>
                                                    {{ $t('Lock') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }" v-show="this.$can('can add and remove verified states') || this.hasAdminRole()" v-if="column.is_locked">
                                                <a @click="unlockColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconLockOpen stroke-width="1.5" stroke="currentColor" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    {{ $t('Unlock') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a v-show="index > 2" @click="deleteColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconTrash class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    {{ $t('Delete') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a v-show="index > 2" @click="duplicateColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconCopy class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    {{ $t('Duplicate') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-show="index > 2" v-slot="{ active }" v-if="column.commented === 1">
                                                <a @click="updateColumnCommented(column.id, false)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconLockOpen stroke-width="1.5" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true"/>
                                                    {{ $t('Include column') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-show="index > 2" v-slot="{ active }" v-else>
                                                <a @click="updateColumnCommented(column.id, true)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconLock stroke-width="1.5" class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true"/>
                                                    {{ $t('Exclude column') }}
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div v-if="showMenu !== column.id" class="w-5"></div>
                        </div>
                    </th>
                    <th>
                        <div class="flex items-center">
                    <div class="text-white hidden xl:block ml-3 mt-3">
                        {{ $t('New column') }}
                    </div>
                        <button v-if="this.$can('edit budget templates') || !table.is_template"
                                class="font-bold mr-2 ml-2 text-xl hover:bg-buttonHover p-1 mt-3 bg-secondary border-white border-2 hover:border-buttonBlue rounded-full items-center uppercase shadow-sm text-secondaryHover"
                                @click="openAddColumnModal()">
                            <IconPlus stroke-width="1.5" class="h-4 w-4"/>
                        </button>
                        </div>
                    </th>
                </tr>
                </thead>
            </table>
        </div>
        <SageNotAssignedData v-if="!this.isBudgetTemplateManagement && this.$page.props.sageApiEnabled"
                             :sage-not-assigned="sageNotAssigned"
                             @remove-sage-not-assigned-data="this.showRemoveSageNotAssignedDataConfirmationModal"
        />
        <div class="w-full flex mb-6">
            <div class="flex flex-wrap w-full bg-secondaryHover border-2 border-gray-300">
                <div class="w-full flex">
                    <div class="bg-secondaryHover ml-5 w-full" v-if="costsOpened">
                        <div :class="table.columns?.length > 5 ? 'mr-5' : 'w-[97%]'" class="flex justify-between my-10">
                        <div class="headline4  flex">
                            {{ $t('Expenses') }}
                            <button class="w-6" @click="costsOpened = !costsOpened">
                                <IconChevronUp stroke-width="1.5" v-if="costsOpened" class="h-6 w-6 text-primary my-auto"/>
                                <IconChevronDown stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                            </button>
                        </div>
                        <Menu v-if="!table.is_template" as="div" class="">
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
                                    class="absolute w-56 -translate-x-full shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="tableIsEmpty && !table.is_template" @click="openUseTemplateModal()"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconFileImport class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                {{ $t('Import template') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="tableIsEmpty && !table.is_template" @click="openUseTemplateFromProjectModal()"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconFileImport class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                {{ $t('Import from project') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="!tableIsEmpty && !table.is_template && this.$can('edit budget templates')" @click="openAddBudgetTemplateModal()"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconFilePlus class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                {{ $t('Save as template') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="!tableIsEmpty && !table.is_template" @click="resetBudgetTable"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconRestore
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                {{ $t('Reset') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="table.is_template" @click="deleteBudgetTemplate()"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <IconTrash class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                {{ $t('Delete') }}
                                            </a>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                        </div>
                        <div @click="addMainPosition('BUDGET_TYPE_COST', positionDefault)"
                             v-if="this.$can('edit budget templates') || !table.is_template"
                             class="group w-[97%] bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                            <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                                {{ $t('Main position') }}
                                <IconCirclePlus
                                    class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></IconCirclePlus>
                            </div>
                        </div>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr v-if="tablesToShow[0]?.length > 0" v-for="(mainPosition,mainIndex) in tablesToShow[0]">
                                <MainPositionComponent @openVerifiedModal="openVerifiedModal"
                                                       @openCellDetailModal="openCellDetailModal"
                                                       @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                       @openMainPositionSumDetailModal="openMainPositionSumDetailModal"
                                                       @openDeleteModal="openDeleteModal"
                                                       @open-error-modal="openErrorModal"
                                                       :table="table"
                                                       :project="project"
                                                       :main-position="mainPosition"
                                                       :project-managers="projectManager"
                                                       type="BUDGET_TYPE_COST"
                                />
                            </tr>
                            <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right te">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">SUM</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns?.slice(3)"
                                    v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1 flex group relative justify-end items-center" :class="this.getSumOfTable(0,column.id) < 0 ? 'text-red-500' : ''">
                                        <img @click="openBudgetSumDetailModal('COST', column, 'comment')" v-if="table.costSumDetails[column.id]?.hasComments && table.costSumDetails[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('COST', column, 'comment')" v-else-if="table.costSumDetails[column.id]?.hasComments"
                                             src="/Svgs/IconSvgs/icon_linked_adjustments.svg"
                                             class="h-5 w-5 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('COST', column, 'moneySource')" v-else-if="table.costSumDetails[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_money_source.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <span v-if="column.type !== 'sage'">{{ this.toCurrencyString(this.getSumOfTable(0, column.id)) }}</span>
                                        <span v-else>{{ this.toCurrencyString(this.calculateSageColumnWithCellSageDataValue(0)) }}</span>
                                        <div class="hidden group-hover:block absolute right-0 z-50 -mr-6"
                                             @click="openBudgetSumDetailModal('COST', column)">
                                            <IconCirclePlus class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full " />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">{{ $t('SUM excluded items') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns.slice(3)"
                                    v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1">
                                        <span v-if="column.type !== 'sage'">
                                            {{ this.toCurrencyString(table.commentedCostSums[column.id]) }}
                                        </span>
                                        <span v-else>
                                                {{ this.toCurrencyString(this.calculateSageColumnWithCellSageDataCommented(0)) }}
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
                                <IconChevronUp stroke-width="1.5" v-if="costsOpened" class="h-6 w-6 text-primary my-auto"/>
                                <IconChevronDown stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12" />
                    <div class="w-full flex">
                    <div class="ml-5 w-full bg-secondaryHover" v-if="earningsOpened">
                        <div class="headline4 my-10 flex">
                            {{ $t('Revenue') }}
                            <button class="w-6" @click="earningsOpened = !earningsOpened">
                                <IconChevronUp stroke-width="1.5" v-if="earningsOpened" class="h-6 w-6 text-primary my-auto"/>
                                <IconChevronDown stroke-width="1.5" v-else class="h-6 w-6 text-primary my-auto"/>
                            </button>
                        </div>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr v-if="tablesToShow[1]?.length > 0" v-for="(mainPosition) in tablesToShow[1]">
                                <MainPositionComponent @openVerifiedModal="openVerifiedModal"
                                                       @openCellDetailModal="openCellDetailModal"
                                                       @openSubPositionSumDetailModal="openSubPositionSumDetailModal"
                                                       @openMainPositionSumDetailModal="openMainPositionSumDetailModal"
                                                       @openDeleteModal="openDeleteModal"
                                                       @open-error-modal="openErrorModal"
                                                       :table="table"
                                                       :project="project"
                                                       :main-position="mainPosition"
                                                        :project-managers="projectManager"
                                                       type="BUDGET_TYPE_EARNING"
                                />
                            </tr>
                            <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">SUM</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns.slice(3)"
                                    v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1 flex group relative justify-end items-center"
                                         :class="this.getSumOfTable(1,column.id) < 0 ? 'text-red-500' : ''">
                                        <img @click="openBudgetSumDetailModal('EARNING', column, 'comment')" v-if="table.earningSumDetails[column.id]?.hasComments && table.earningSumDetails[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_and_adjustments.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('EARNING', column, 'comment')" v-else-if="table.earningSumDetails[column.id]?.hasComments"
                                             src="/Svgs/IconSvgs/icon_linked_adjustments.svg"
                                             class="h-5 w-5 mr-1 cursor-pointer"/>
                                        <img @click="openBudgetSumDetailModal('EARNING', column, 'moneySource')" v-else-if="table.earningSumDetails[column.id]?.hasMoneySource"
                                             src="/Svgs/IconSvgs/icon_linked_money_source.svg"
                                             class="h-6 w-6 mr-1 cursor-pointer"/>
                                        <span v-if="column.type !== 'sage'">{{ this.toCurrencyString(this.getSumOfTable(1, column.id)) }}</span>
                                        <span v-else>{{ this.toCurrencyString(this.calculateSageColumnWithCellSageDataValue(1)) }}</span>
                                        <div class="hidden group-hover:block absolute right-0 z-50 -mr-6"
                                             @click="openBudgetSumDetailModal('EARNING', column)">
                                            <PlusCircleIcon class="h-6 w-6 flex-shrink-0 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full " />
                                        </div>
                                    </div>
                                </td>

                            </tr>
                            <tr class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">{{ $t('SUM excluded items') }}</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns.slice(3)"
                                    v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                                    <div class="w-48 my-2 p-1">
                                         <span v-if="column.type !== 'sage'">
                                            {{ this.toCurrencyString(table.commentedEarningSums[column.id]) }}
                                         </span>
                                        <span v-else>
                                            {{ this.toCurrencyString(calculateSageColumnWithCellSageDataCommented(1)) }}
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
                                <IconChevronUp stroke-width="1.5" v-if="earningsOpened"
                                               class="h-6 w-6 text-primary my-auto"></IconChevronUp>
                                <IconChevronDown stroke-width="1.5" v-else
                                                 class="h-6 w-6 text-primary my-auto"></IconChevronDown>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12" />
                <tr class="bg-secondaryHover items-center xsDark flex h-10 mt-4 mb-2 w-full text-right">
                    <td class="w-44 xsDark uppercase flex ml-6">
                        {{ $t('Revenue') }} - {{ $t('Expenses') }}
                    </td>
                    <td class="w-10 mr-1"></td>
                    <td class="w-72 my-2">SUM</td>
                    <td class="flex items-center w-48"
                        v-for="column in table.columns.slice(3)"
                        v-show="!(column.commented && this.$page.props.user.commented_budget_items_setting?.exclude === 1)">
                        <div class="w-48 my-2 p-1" :class="[this.getSumOfTable(1, column.id) - this.getSumOfTable(0, column.id) < 0 ? 'text-red-500' : '', this.calculateSageColumnWithCellSageDataValue(1) - this.calculateSageColumnWithCellSageDataValue(0) < 0 ? 'text-red-500' : '']">
                            <span v-if="column.type !== 'sage'">
                                {{ this.toCurrencyString((this.getSumOfTable(1, column.id) - this.getSumOfTable(0, column.id))) }}
                            </span>
                            <span v-else>
                                {{ this.toCurrencyString((this.calculateSageColumnWithCellSageDataValue(1) - this.calculateSageColumnWithCellSageDataValue(0))) }}
                            </span>
                        </div>
                    </td>
                </tr>
            </div>
        </div>
    </div>
    <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ successHeading }}
                </div>
                <IconX stroke-width="1.5" @click="closeSuccessModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="successText">
                    {{ successDescription }}
                </div>
                <div class="mt-6">
                    <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-full"
                            @click="closeSuccessModal">
                        <IconCheck stroke-width="1.5" class="h-6 w-6 text-secondaryHover"/>
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <jet-dialog-modal :show="showVerifiedModal" @close="closeVerifiedModal">
        <template #content>
            <img :alt="$t('New column')" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ verifiedTexts.title }} <span class="xsDark">{{ verifiedTexts.positionTitle }}</span>
                </div>
                <IconX stroke-width="1.5" @click="closeVerifiedModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="mb-3 xsLight" v-html="verifiedTexts.description"></div>
                <div class="mb-2">
                    <div class="relative w-full">
                        <div class="w-full" v-if="showUserAdd">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   :placeholder="$t('Who should verify your calculation?')"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index" class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4" v-if="budgetAccess.includes(user.id)">
                                            <p @click="addUserToVerifiedUserArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                        <!-- Project Members -->
                                        <div class="flex-1 text-sm py-4" v-if="projectMembers.includes(user.id) && !budgetAccess.includes(user.id)">
                                            <p @click="addUserToVerifiedUserArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div v-if="submitVerifiedModalData.user !== ''" class="mt-2 mb-4 flex items-center">
                        <span class="flex mr-5 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full object-cover"
                                     :src="usersToAdd.profile_photo_url" alt=""/>
                                <span class="flex ml-4 sDark">
                                    {{ usersToAdd.first_name }} {{ usersToAdd.last_name }}
                                </span>
                                <button type="button" @click="deleteUserFromVerifiedUserArray">
                                    <span class="sr-only">{{ $t('Remove user from money source') }}</span>
                                    <IconX stroke-width="1.5"
                                        class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                </button>
                            </div>
                        </span>
                    </div>
                </div>
                <div class="mt-6 flex justify-center">
                    <button class="focus:outline-none my-auto inline-flex items-center px-10 py-3 border border-transparent
                            text-xs font-bold uppercase shadow-sm text-secondaryHover rounded-full bg-buttonBlue"
                            @click="submitVerifiedModal">
                        {{ $t('Request verification') }}
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <jet-dialog-modal :show="showBudgetAccessModal" @close="closeBudgetAccessModal">
        <template #content>
            <img :alt="$t('New column')" src="/Svgs/Overlays/illu_budget_access.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Grant budget access') }}
                </div>
                <p>
                    {{ $t('The user you have requested for verification does not yet have budget access to your project. With the verification request, you grant him/her this right. Are you sure you want to give her/him this right?') }}
                </p>
                <IconX stroke-width="1.5" @click="closeBudgetAccessModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-6">
                    <button class="focus:outline-none my-auto inline-flex items-center px-10 py-3 border border-transparent
                            text-xs font-bold uppercase shadow-sm text-secondaryHover rounded-full bg-buttonBlue"
                            @click="submitVerifiedModalWithBudgetAccess">
                        {{ $t('Issue requests & budget access') }}
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <add-column-component
        v-if="showAddColumnModal"
        :project="project"
        :table="table"
        @closed="closeAddColumnModal()"
    />
    <cell-detail-component
        v-if="showCellDetailModal"
        :cell="selectedCell"
        :moneySources="moneySources"
        :project-id="project.id"
        :openTab="cellDetailOpenTab"
        @closed="closeCellDetailModal()"
    />
    <sum-detail-component
        :selectedSumDetail="selectedSumDetail"
        v-if="showSumDetailModal"
        :project-id="project.id"
        :openTab="sumDetailOpenTab"
        @closed="showSumDetailModal = false"
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
import {
    PencilAltIcon,
    PlusCircleIcon,
    TrashIcon,
    XCircleIcon,
    XIcon,
    ZoomInIcon,
    ZoomOutIcon,
    DocumentReportIcon
} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon,PlusIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import AddColumnComponent from "@/Layouts/Components/AddColumnComponent.vue";
import CellDetailComponent from "@/Layouts/Components/CellDetailComponent.vue";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import {useForm} from "@inertiajs/inertia-vue3";
import {Inertia} from "@inertiajs/inertia";
import MainPositionComponent from "@/Layouts/Components/MainPositionComponent.vue";
import RowDetailComponent from "@/Layouts/Components/RowDetailComponent.vue";
import UseTemplateComponent from "@/Layouts/Components/UseTemplateComponent.vue";
import UseTemplateFromProjectBudgetComponent from "@/Layouts/Components/UseTemplateFromProjectBudgetComponent.vue";
import AddBudgetTemplateComponent from "@/Layouts/Components/AddBudgetTemplateComponent.vue";
import Button from "@/Jetstream/Button.vue";
import RenameTableComponent from "@/Layouts/Components/RenameTableComponent.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import SumDetailComponent from "@/Layouts/Components/SumDetailComponent.vue";
import Permissions from "@/mixins/Permissions.vue";
import SageNotAssignedData from "@/Pages/Projects/Components/SageNotAssignedData.vue";
import IconLib from "@/mixins/IconLib.vue";
import CurrencyFloatToStringFormatter from "@/mixins/CurrencyFloatToStringFormatter.vue";

export default {
    name: 'BudgetComponent',
    mixins: [Permissions, IconLib, CurrencyFloatToStringFormatter],
    components: {
        SageNotAssignedData,
        ZoomInIcon, ZoomOutIcon,
        SwitchGroup,
        SwitchLabel,
        Switch,
        SumDetailComponent,
        Button,
        UseTemplateFromProjectBudgetComponent,
        MainPositionComponent,
        ConfirmationComponent,
        CellDetailComponent,
        AddColumnComponent,
        ChevronDownIcon,
        ChevronUpIcon,
        PlusCircleIcon,
        XCircleIcon,
        DotsVerticalIcon,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        JetDialogModal,
        CheckIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        RowDetailComponent,
        UseTemplateComponent,
        AddBudgetTemplateComponent,
        PlusIcon,
        RenameTableComponent,
        ErrorComponent,
        DocumentReportIcon,
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
            resetWanted:false,
            hoveredRow: null,
            showMenu: null,
            showDeleteModal: false,
            showErrorModal: false,
            mainPositionToDelete: null,
            subPositionToDelete: null,
            rowToDelete: null,
            confirmationTitle: '',
            confirmationDescription: '',
            errorTitle:'',
            errorDescription:'',
            showSuccessModal: false,
            successHeading: '',
            successDescription: '',
            positionDefault: {
                position: 0
            },
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
            verifiedTexts: {
                title: this.$t('Verification'),
                positionTitle: '',
                description: this.$t('Have all figures been calculated correctly? Is the calculation plausible? Have your main item verified by a user.')
            },
            showVerifiedModal: false,
            user_search_results: [],
            user_query: '',
            usersToAdd: '',
            showUserAdd: true,
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
            sumDetailOpenTab: 'comment',
            userExcludeCommentedBudgetItems: this.$page.props.user.commented_budget_items_setting ?
                this.$page.props.user.commented_budget_items_setting.exclude === 1 :
                false,
            showDeleteSageNotAssignedDataConfirmationModal: false,
            sageNotAssignedDataToDelete: null
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
        'isBudgetTemplateManagement',
        'sageNotAssigned',
        'first_project_budget_tab_id'
    ],
    emits: ['changeProjectHeaderVisualisation'],
    computed: {

        tablesToShow: function () {
            let costTableArray = [];
            let earningTableArray = [];
            this.table.main_positions.forEach((mainPosition) => {
                if (mainPosition.type === 'BUDGET_TYPE_COST') {
                    costTableArray.push(mainPosition);
                } else {
                    earningTableArray.push(mainPosition);
                }
            })
            return [costTableArray, earningTableArray]
        },
        tableIsEmpty: function () {
            return this.table.main_positions.length === 2 &&
                this.table.main_positions[0].sub_positions.length === 1 &&
                this.table.main_positions[0].sub_positions[0].sub_position_rows.length === 1 &&
                this.table.columns?.length === 4;
        },
        projectMembers: function () {
            let projectMemberArray = [];
            this.project.users.forEach(member => {
                    projectMemberArray.push(member.id)
                }
            )
            return projectMemberArray;
        },
        sortedColumns() {
            // Zuerst filtern wir die Spalten, die nicht vom Typ 'sage' sind
            const nonSageColumns = this.table.columns.filter(column => column.type !== 'sage');
            // Dann filtern wir die Spalten, die vom Typ 'sage' sind
            const sageColumns = this.table.columns.filter(column => column.type === 'sage');
            // Kombiniere die beiden Arrays, wobei die 'sage' Spalten am Ende stehen
            return [...nonSageColumns, ...sageColumns];
        }
    },
    created() {
        this.sortColumns(); // Sortiere die Spalten, wenn die Komponente initialisiert wird
    },
    watch: {
        userExcludeCommentedBudgetItems: {
            handler(excludeHiddenItems) {
                if (this.$page.props.user.commented_budget_items_setting === null) {
                    Inertia.post(
                        route(
                            'user.commentedBudgetItemsSettings.store',
                            {
                                user: this.$page.props.user.id
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

                Inertia.patch(
                    route(
                        'user.commentedBudgetItemsSettings.update',
                        {
                            user: this.$page.props.user.id,
                            commentedBudgetItemsSetting: this.$page.props.user.commented_budget_items_setting.id
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
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },
    methods: {
        updateColumnCommented(columnId, bool) {
            Inertia.patch(
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
                    preserveState: true
                }
            );
        },
        sortColumns() {
            this.table.columns.sort((a, b) => {
                if (a.type === 'sage') return 1; // Verschiebe 'sage' ans Ende
                if (b.type === 'sage') return -1; // Behalte 'sage' am Ende
                return 0; // Ändere die Reihenfolge von a und b nicht
            });
        },
        duplicateColumn(columnId){
            Inertia.post(route('project.budget.column.duplicate', columnId), {}, {
                preserveState: true,
                preserveScroll: true
            });
        },
        checkCellColor(cell, mainPosition, subPosition) {
            let cssString = '';
            if (cell.column.color === 'whiteColumn') {
                if (cell.value !== cell.verified_value) {
                    cssString += ' xsWhiteBold ';
                } else {
                    cssString += ' xsDark ';
                }
            } else {
                cssString += ' xsWhiteBold ';
                if (cell.value !== cell.verified_value) {
                    cssString += ' bg-red-300 '
                } else {
                    cssString += cell.column.color;
                }
            }

            if (cell.value !== cell.verified_value) {
                if (mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED') {
                    cssString += ' bg-red-300 '
                    if (cell.column.color !== 'whiteColumn') {
                        cssString += ' xsWhiteBold '
                    }
                } else {
                    cssString += cell.column.color;
                }
            }

            return cssString
        },
        getSumOfTable(tableType, columnId) {
            let sum = 0;
            this.tablesToShow[tableType].forEach((mainPosition) => {
                sum += mainPosition.columnSums[columnId]?.sum;
            })
            if(isNaN(sum)){
                return 0;
            }else{
                return sum;
            }

        },
        calculateSageColumnWithCellSageDataCommented(tableType){
            if (tableType === 0) {
                // calculate the sum of all buchungsbetrag in the sage_assigned_data array for the cost table (main_position.type === 'BUDGET_TYPE_COST')
                return this.table.main_positions.filter(mainPosition => mainPosition.type === 'BUDGET_TYPE_COST').reduce((accumulator, mainPosition) => {
                    return accumulator + mainPosition.sub_positions.reduce((accumulator, subPosition) => {
                        return accumulator + subPosition.sub_position_rows.reduce((accumulator, subPositionRow) => {
                            return accumulator + subPositionRow.cells.filter(cell => cell.column.type === 'sage' && cell.commented || cell.column.commented).reduce((accumulator, cell) => {
                                return accumulator + cell.sage_assigned_data.reduce((accumulator, sageAssignedData) => {
                                    return accumulator + sageAssignedData.buchungsbetrag;
                                }, 0);
                            }, 0);
                        }, 0);
                    }, 0);
                }, 0);
            } else {
                // calculate the sum of all buchungsbetrag in the sage_assigned_data array for the earning table (main_position.type === 'BUDGET_TYPE_EARNING')
                return this.table.main_positions.filter(mainPosition => mainPosition.type === 'BUDGET_TYPE_EARNING').reduce((accumulator, mainPosition) => {
                    return accumulator + mainPosition.sub_positions.reduce((accumulator, subPosition) => {
                        return accumulator + subPosition.sub_position_rows.reduce((accumulator, subPositionRow) => {
                            return accumulator + subPositionRow.cells.filter(cell => cell.column.type === 'sage' && cell.commented || cell.column.commented).reduce((accumulator, cell) => {
                                return accumulator + cell.sage_assigned_data.reduce((accumulator, sageAssignedData) => {
                                    return accumulator + sageAssignedData.buchungsbetrag;
                                }, 0);
                            }, 0);
                        }, 0);
                    }, 0);
                }, 0);
            }
        },
        calculateSageColumnWithCellSageDataValue(tableType) {
            if (tableType === 0) {
                // calculate the sum of all buchungsbetrag in the sage_assigned_data array for the cost table (main_position.type === 'BUDGET_TYPE_COST')
                return this.table.main_positions.filter(mainPosition => mainPosition.type === 'BUDGET_TYPE_COST').reduce((accumulator, mainPosition) => {
                    return accumulator + mainPosition.sub_positions.reduce((accumulator, subPosition) => {
                        return accumulator + subPosition.sub_position_rows.reduce((accumulator, subPositionRow) => {
                            return accumulator + subPositionRow.cells.filter(cell => cell.column.type === 'sage' && !cell.commented && !cell.column.commented).reduce((accumulator, cell) => {
                                return accumulator + cell.sage_assigned_data.reduce((accumulator, sageAssignedData) => {
                                    return accumulator + sageAssignedData.buchungsbetrag;
                                }, 0);
                            }, 0);
                        }, 0);
                    }, 0);
                }, 0);
            } else {
                // calculate the sum of all buchungsbetrag in the sage_assigned_data array for the earning table (main_position.type === 'BUDGET_TYPE_EARNING')
                return this.table.main_positions.filter(mainPosition => mainPosition.type === 'BUDGET_TYPE_EARNING').reduce((accumulator, mainPosition) => {
                    return accumulator + mainPosition.sub_positions.reduce((accumulator, subPosition) => {
                        return accumulator + subPosition.sub_position_rows.reduce((accumulator, subPositionRow) => {
                            return accumulator + subPositionRow.cells.filter(cell => cell.column.type === 'sage' && !cell.commented && !cell.column.commented).reduce((accumulator, cell) => {
                                return accumulator + cell.sage_assigned_data.reduce((accumulator, sageAssignedData) => {
                                    return accumulator + sageAssignedData.buchungsbetrag;
                                }, 0);
                            }, 0);
                        }, 0);
                    }, 0);
                }, 0);
            }
        },
        addUserToVerifiedUserArray(user) {
            this.submitVerifiedModalData.user = user.id;
            this.usersToAdd = user
            this.user_query = '';
            this.showUserAdd = false;
        },
        deleteUserFromVerifiedUserArray() {
            this.submitVerifiedModalData.user = '';
            this.usersToAdd = ''
            this.showUserAdd = true
        },
        changeColumnColor(color, columnId) {
            this.$inertia.patch(route('project.budget.column-color.change'), {
                color: color,
                columnId: columnId
            })
        },
        deleteColumn(column) {
            this.$inertia.delete(route('project.budget.column.delete', column))
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
        openAddColumnModal() {
            this.showAddColumnModal = true;
        },
        closeAddColumnModal() {
            this.showAddColumnModal = false;
        },
        openUseTemplateModal() {
            Inertia.reload({
                data: {
                    useTemplates: true
                },
                only: ['selectedSumDetail','table','selectedCell','selectedRow','templates', 'budgetAccess'],
                onSuccess: () => {
                    this.showUseTemplateModal = true;
                }
            })
        },
        openRenameTableModal(){
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
        closeRenameBudgetTemplateModal(){
            this.showRenameTableModal = false;
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
        addMainPosition(type, mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                table_id: this.table.id,
                type: type,
                positionBefore: mainPosition.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        updateColumnName(column) {
            this.$inertia.patch(route('project.budget.column.update-name'), {
                column_id: column.id,
                columnName: column.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
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
        updateSubPositionName(subPosition) {
            this.$inertia.patch(route('project.budget.sub-position.update-name'), {
                subPosition_id: subPosition.id,
                subPositionName: subPosition.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        openCellDetailModal(cell, type) {
            Inertia.get(
                route('projects.tab', {project: this.project.id, projectTab: this.first_project_budget_tab_id}),
                {
                    selectedCell: cell.id,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.cellDetailOpenTab = type;
                        this.showCellDetailModal = true;
                    }
                }
            );
        },
        openBudgetSumDetailModal(type, column, tab = 'comment') {
            Inertia.get(route('projects.tab', {project: this.project.id, projectTab: this.first_project_budget_tab_id}), {
                selectedBudgetType: type,
                selectedColumn: column.id,
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.sumDetailOpenTab = tab;
                    this.showSumDetailModal = true;
                }
            })
        },
        openSubPositionSumDetailModal(subPosition, column, type) {
            Inertia.get(route('projects.tab', {project: this.project.id, projectTab: this.first_project_budget_tab_id}), {
                selectedSubPosition: subPosition.id,
                selectedColumn: column.id,
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.sumDetailOpenTab = type;
                    this.showSumDetailModal = true;

                }
            })
        },
        openMainPositionSumDetailModal(mainPosition, column, type) {
            Inertia.get(route('projects.tab', {project: this.project.id, projectTab: this.first_project_budget_tab_id}),
                {
                    selectedMainPosition: mainPosition.id,
                    selectedColumn: column.id,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.sumDetailOpenTab = type;
                        this.showSumDetailModal = true;
                    }
                }
            )
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        openDeleteRowModal(row) {
            this.confirmationTitle = this.$t('Delete row');
            this.confirmationDescription = this.$t('Are you sure you want to delete this line? All links etc. will also be deleted.');
            this.rowToDelete = row;
            this.showDeleteModal = true;
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
        openDeleteSubPositionModal(subPosition) {
            this.confirmationTitle = this.$t('Delete sub-item');
            this.confirmationDescription = this.$t('Are you sure you want to delete the sub-item', [subPosition.name]);
            this.subPositionToDelete = subPosition;
            this.showDeleteModal = true;
        },
        afterConfirm(bool) {
            if (!bool){
                this.resetWanted = false;
                return this.showDeleteModal = false;
            }
            if(this.resetWanted === true)
            {
                this.resetBudgetTable();
            }else{
                this.deletePosition();
            }
        },
        afterErrorConfirm(bool){
            this.showErrorModal = false;
        },
        deletePosition() {
            if (this.mainPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.main-position.delete', this.mainPositionToDelete.id),{preserveState: true, preserveScroll: true})
                this.successHeading = this.$t('Main position deleted');
                this.successDescription = this.$t('Main position successfully deleted', [this.mainPositionToDelete.name]);
            } else if (this.subPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.sub-position.delete', this.subPositionToDelete.id),{preserveState: true, preserveScroll: true})
                this.successHeading = this.$t('Sub-item deleted');
                this.successDescription = this.$t('Sub-item successfully deleted', [this.subPositionToDelete.name]);
            } else {
                this.$inertia.delete(`/project/budget/sub-position-row/${this.rowToDelete.id}`, {
                    preserveScroll: true,
                    preserveState: true
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
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        closeVerifiedModal(deleteAll = false) {
            this.showVerifiedModal = false;
            if(deleteAll){
                this.user_query = '';
                this.showUserAdd = true;
                this.submitVerifiedModalData.user = '';
                this.submitVerifiedModalData.id = null;
                this.submitVerifiedModalData.is_main = false;
                this.submitVerifiedModalData.is_sub = false;
                this.submitVerifiedModalData.position = [];
            }
        },
        closeBudgetAccessModal(){
            this.showBudgetAccessModal = false;
            this.user_query = '';
            this.showUserAdd = true;
            this.submitVerifiedModalData.user = '';
            this.submitVerifiedModalData.id = null;
            this.submitVerifiedModalData.is_main = false;
            this.submitVerifiedModalData.is_sub = false;
            this.submitVerifiedModalData.position = [];
        },
        submitVerifiedModalWithBudgetAccess(){
            this.submitVerifiedModalData.giveBudgetAccess = true;
            this.submitVerifiedModal();
        },
        submitVerifiedModal() {
            if(this.budgetAccess.includes(this.submitVerifiedModalData.user) || this.submitVerifiedModalData.giveBudgetAccess){
                if (this.submitVerifiedModalData.is_main) {
                    this.submitVerifiedModalData.post(route('project.budget.verified.main-position.request'),{preserveState: true, preserveScroll: true});
                } else {
                    this.submitVerifiedModalData.post(route('project.budget.verified.sub-position.request'),{preserveState: true, preserveScroll: true});
                }
                this.closeVerifiedModal(true);
            } else {
                this.showBudgetAccessModal = true;
                this.closeVerifiedModal(false);
            }

            if(this.submitVerifiedModalData.giveBudgetAccess){
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
        openVerifiedModalSub(subPosition) {
            this.verifiedTexts.positionTitle = subPosition.name
            this.submitVerifiedModalData.is_sub = true
            this.submitVerifiedModalData.id = subPosition.id
            this.submitVerifiedModalData.position = subPosition
            this.showVerifiedModal = true
        },
        verifiedMainPosition(mainPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.main-position'), {
                mainPositionId: mainPositionId,
                table_id: this.table.id,
            }, {preserveState: true, preserveScroll: true})
        },
        verifiedSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.sub-position'), {
                subPositionId: subPositionId,
                table_id: this.table.id,
            }, {preserveState: true, preserveScroll: true})
        },
        requestRemove(position, type) {
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            }, {preserveState: true, preserveScroll: true})
        },
        removeVerification(position, type) {
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            }, {preserveState: true, preserveScroll: true})
        },
        lockColumn(columnId) {
            this.$inertia.patch(this.route('project.budget.lock.column'), {
                columnId: columnId
            }, {preserveState: true, preserveScroll: true});
        },
        unlockColumn(columnId) {
            this.$inertia.patch(this.route('project.budget.unlock.column'), {
                columnId: columnId
            }, {preserveState: true, preserveScroll: true});
        },
        openResetConfirmation(){
            this.confirmationTitle = this.$t('Reset budget tables');
            this.confirmationDescription = this.$t('Are you sure you want to reset these tables? All links etc. will also be deleted.');
            this.resetWanted = true;
            this.showDeleteModal = true;
        },
        resetBudgetTable(){
            this.$inertia.patch(this.route('project.budget.reset.table', this.project.id),{}, {preserveState: true, preserveScroll: true})
            this.resetWanted= false;
            this.showDeleteModal = false;
        },
        deleteBudgetTemplate(){
          this.$inertia.delete(this.route('project.budget.table.delete', this.table.id), {preserveState: true, preserveScroll: true})
        },
        openErrorModal(title, description) {
            this.errorTitle = title;
            this.errorDescription = description
            this.showErrorModal = true;
        },
        downloadBudgetExport(projectId) {
            window.open(route(
                'projects.export.budget',
                {
                    project: projectId
                }
            ));
        },
        showRemoveSageNotAssignedDataConfirmationModal(sageNotAssignedData) {
            this.sageNotAssignedDataToDelete = sageNotAssignedData;
            this.showDeleteSageNotAssignedDataConfirmationModal = true;
        },
        showSageNotAssignedDataConfirmationModalHandler(closedToDelete) {
            if (closedToDelete) {
                Inertia.delete(
                    route('sageNotAssignedData.destroy',
                        {
                            sageNotAssignedData: this.sageNotAssignedDataToDelete.id
                        },
                        {
                            preserveState: true,
                            preserveScroll: true
                        }
                    )
                )
            }

            this.showDeleteSageNotAssignedDataConfirmationModal = false;
            this.sageNotAssignedDataToDelete = null;
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

.stickyHeader {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    display: block;
    top: 4rem;
    z-index: 21;
    background-color: #CECDD8;
}
</style>
