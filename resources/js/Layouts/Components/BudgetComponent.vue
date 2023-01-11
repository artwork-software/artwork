<template>
    <div class="mx-4">
        <div class="flex justify-end mb-10">
            <AddButton @click="openAddColumnModal()" text="Neue Spalte" mode="page"></AddButton>
        </div>
        <div class="w-full flex">
            <table class="w-full flex ml-16">
                <thead>
                <tr>
                    <th v-for="(column,index) in budget.columns"
                        :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'" class="text-left">
                        <div class="flex items-center" @mouseover="showMenu = column.id" :key="column.id"
                             @mouseout="showMenu = null">
                            <div>
                                <p class="columnSubName"> {{ column.subName }} <span v-if="column.calculateName" class="ml-1">({{ column.calculateName }})</span></p>
                                <div @click="column.clicked = !column.clicked"
                                     :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'" class="h-5"
                                     v-if="!column.clicked">
                                    {{ column.name }}
                                </div>
                                <div v-else>
                                    <input
                                        :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'"
                                        class="my-2 xsDark" type="text"
                                        v-model="column.name"
                                        @focusout="updateColumnName(column); column.clicked = !column.clicked">
                                </div>
                            </div>
                            <Menu as="div" v-show="showMenu === column.id">
                                <div class="flex">
                                    <MenuButton
                                        class="flex ">
                                        <DotsVerticalIcon
                                            class="flex-shrink-0 h-6 w-6 text-gray-600"
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
                                                <a @click="openEditTaskModal(element)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Bearbeiten
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a @click="deleteColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <TrashIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Löschen
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </th>
                </tr>
                </thead>
            </table>

        </div>

        <div class="flex my-8 ">
            <div class="flex w-full bg-secondaryHover border border-2 border-gray-300">
                <button class="bg-buttonBlue w-6"
                        @click="costsOpened = !costsOpened">
                    <ChevronUpIcon v-if="costsOpened"
                                   class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                </button>

                <div class="bg-secondaryHover ml-8 w-full" v-if="costsOpened">
                    <div class="headline4 my-10">Ausgaben</div>
                    <div @click="addMainPosition('BUDGET_TYPE_COST', positionDefault)"
                         class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                        <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                            Hauptposition
                            <PlusCircleIcon class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                        </div>
                    </div>
                    <table class="w-11/12 mb-6">
                        <tbody class="">
                        <tr class="" v-for="(mainPosition,mainIndex) in tablesToShow[0]">
                            <th class="bg-primary text-left p-0">
                                <div class="flex" @mouseover="showMenu = 'MainPosition' + mainPosition.id"
                                     @mouseout="showMenu = null">
                                <div class="pl-2 xsWhiteBold flex w-full items-center h-10"
                                    v-if="!mainPosition.clicked">
                                    <div @click="mainPosition.clicked = !mainPosition.clicked">
                                        {{ mainPosition.name }}
                                    </div>
                                    <button class="my-auto w-6 ml-3"
                                            @click="mainPosition.closed = !mainPosition.closed">
                                        <ChevronUpIcon v-if="!mainPosition.closed"
                                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                </div>
                                <div v-else class="flex items-center">
                                    <input
                                        class="my-2 ml-1 xsDark" type="text"
                                        v-model="mainPosition.name"
                                        @focusout="updateMainPositionName(mainPosition); mainPosition.clicked = !mainPosition.clicked">
                                    <button class="my-auto w-6 ml-3"
                                            @click="mainPosition.closed = !mainPosition.closed">
                                        <ChevronUpIcon v-if="!mainPosition.closed"
                                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                </div>
                                    <div class="flex items-center justify-end">
                                        <div class="flex flex-wrap w-full">
                                            <div class="flex w-full">
                                                <Menu as="div" class="my-auto relative"
                                                      v-show="showMenu === 'MainPosition' + mainPosition.id">
                                                    <div class="flex">
                                                        <MenuButton
                                                            class="flex ml-6">
                                                            <DotsVerticalIcon
                                                                class="mr-3 flex-shrink-0 h-6 w-6 text-secondaryHover my-auto"
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
                                                            class="origin-top-right absolute right-0 w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                            <div class="py-1">
                                                                <MenuItem v-slot="{ active }">
                                                                                <span
                                                                                    @click="openDeleteMainPositionModal(mainPosition)"
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

                                <!-- HIER ADD UNTERPOSITION Funktion -->
                                <div @click="addSubPosition(mainPosition.id)"
                                    class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                                    <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                                        Unterposition
                                        <PlusCircleIcon class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                    </div>
                                </div>
                                <table v-if="!mainPosition.closed" class="w-full ">


                                    <thead class="">
                                    <tr class="" v-for="(subPosition,subIndex) in mainPosition.sub_positions">
                                        <th class="bg-silverGray xxsDark">
                                            <div
                                                class="pl-2 xxsDark flex items-center h-10"
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
                                            <div v-else class="flex">
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
                                            <table class="w-full" v-if="!subPosition.closed">
                                                <tbody class="bg-secondaryHover w-full">
                                                <tr :class="[rowIndex !== 0 && hoveredRow !== row.id ? 'border-t-2 border-silverGray': '', hoveredRow === row.id ? 'border-buttonBlue ' : '']"
                                                    @mouseover="hoveredRow = row.id" @mouseout="hoveredRow = null"
                                                    class="bg-secondaryHover flex justify-between items-center border-2"
                                                    v-for="(row,rowIndex) in subPosition.sub_position_rows">
                                                    <div class="flex items-center">

                                                        <PlusCircleIcon @click="addRowToSubPosition(subPosition, row)"
                                                                        :class="hoveredRow === row.id ? '' : 'hidden'"
                                                                        class="h-6 w-6 absolute -ml-3 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                        <td v-for="(cell,index) in row.cells"  :class="[index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48', cell.column.color !== 'bg-secondaryHover' ? 'xsWhiteBold' : 'xsDark', cell.column.color]">
                                                            <div
                                                                :class="[row.commented ? 'xsLight' : '', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48',hoveredRow === row.id ? '' : 'ml-2.5', cell.value < 0 ? 'text-red-500' : '']"
                                                                class="my-4 h-6 flex items-center"
                                                                @click="cell.clicked = !cell.clicked"
                                                                v-if="!cell.clicked">
                                                                <img v-if="cell.linked_money_source_id !== null"
                                                                     src="/Svgs/IconSvgs/icon_linked_moneySource.svg"
                                                                     class="h-6 w-6"/>
                                                                {{ cell.value }}
                                                            </div>
                                                            <div class="flex items-centert"
                                                                 :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'"
                                                                 v-else-if="cell.clicked && cell.column.type === 'empty'">
                                                                <input
                                                                    :class="index <= 1 ? 'w-20' : index === 2 ? 'w-64' : 'w-44'"
                                                                    class="my-2 xsDark" type="text"
                                                                    v-model="cell.value"
                                                                    @focusout="updateCellValue(cell)">
                                                                <PlusCircleIcon v-if="index > 2"
                                                                                @click="openCellDetailModal(cell)"
                                                                                class="h-6 w-6 -ml-3 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                            </div>
                                                            <div
                                                                :class="[row.commented ? 'xsLight' : 'xsDark', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48',hoveredRow === row.id ? '' : 'ml-2.5', cell.value < 0 ? 'text-red-500' : '']"
                                                                class="my-4 h-6 flex items-center"
                                                                @click="cell.clicked = !cell.clicked"
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
                                                    <XCircleIcon @click="deleteRowFromSubPosition(row)"
                                                                 :class="hoveredRow === row.id ? '' : 'hidden'"
                                                                 class="h-6 w-6 -mr-3 cursor-pointer justify-end text-secondaryHover bg-error rounded-full"></XCircleIcon>
                                                </tr>
                                                <tr class="bg-silverGray xsDark flex h-10 w-full">
                                                    <td class="w-24"></td>
                                                    <td class="w-24"></td>
                                                    <td class="w-72 ml-2 my-2">SUM</td>
                                                    <div class="flex items-center"
                                                         v-for="cell in subPosition.sub_position_rows[0].cells">
                                                        <td v-if="cell.column_id > 3" class="w-48 ml-0.5 my-4"
                                                            :class="getSumsOfSubPosition(subPosition)[cell.column_id] < 0 ? 'text-red-500' : ''">
                                                            {{
                                                                getSumsOfSubPosition(subPosition)[cell.column_id]
                                                            }}
                                                        </td>
                                                    </div>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div @click="addSubPosition(mainPosition.id, subPosition)"
                                                 class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                                                <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                                                    Unterposition
                                                    <PlusCircleIcon class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                </div>
                                            </div>
                                        </th>
                                    </tr>

                                    <tr class="bg-primary xsWhiteBold flex h-10 w-full">
                                        <td class="w-24"></td>
                                        <td class="w-24"></td>
                                        <td class="w-72 ml-2 my-2">SUM</td>
                                        <td class="w-48 my-2">3002</td>
                                    </tr>
                                    </thead>
                                    <!-- HIER ADD HAUPTPOSITION -->
                                    <div @click="addMainPosition('BUDGET_TYPE_COST', mainPosition)"
                                         class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                                        <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                                            <PlusCircleIcon class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                            Hauptposition
                                        </div>
                                    </div>

                                </table>

                            </th>
                        </tr>
                        <tr class="bg-secondaryHover xsDark flex h-10 w-full">
                            <td class="w-24"></td>
                            <td class="w-24"></td>
                            <td class="w-72 ml-2 my-2">SUM</td>
                            <td class="w-48 my-2">3000</td>
                        </tr>
                        <!-- TODO: Hier noch einfügen if(commented === true) -->
                        <tr v-if="true" class="bg-secondaryHover xsLight flex h-10 w-full">
                            <td class="w-24"></td>
                            <td class="w-24"></td>
                            <td class="w-72 ml-2 my-2">SUM ausgeklammerte Posten</td>
                            <td class="w-48 my-2">3000</td>
                        </tr>
                        </tbody>
                    </table>

                </div>
                <!-- View if not opened Event -->
                <div class="ml-2 w-full bg-secondaryHover" v-else>
                    <div class="headline4 my-10">Ausgaben</div>
                </div>
            </div>
        </div>

        <div class="flex my-8 ">
            <div class="flex w-full border border-2 border-gray-300">
                <button class="bg-buttonBlue w-6"
                        @click="earningsOpened = !earningsOpened">
                    <ChevronUpIcon v-if="earningsOpened"
                                   class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                </button>

                <div class="ml-2 w-11/12" v-if="earningsOpened">
                    <h2>Einahmen</h2>
                    <table class="w-full">
                        <tbody>
                        <tr v-for="mainPosition in tablesToShow[1]">
                            <th class="bg-primary text-white text-left">
                                <div class="pl-2 flex items-center h-10">
                                    {{ mainPosition.name }}
                                    <button class="my-auto w-6 ml-3"
                                            @click="mainPosition.closed = !mainPosition.closed">
                                        <ChevronUpIcon v-if="!mainPosition.closed"
                                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                </div>
                                <table v-if="!mainPosition.closed" class="w-full">
                                    <thead>
                                    <tr v-for="subPosition in mainPosition.sub_positions">
                                        <th class="bg-lightBackgroundGray xsDark">
                                            <div class="pl-2 flex items-center h-10">
                                                {{ subPosition.name }}
                                                <button class="my-auto w-6 ml-3"
                                                        @click="subPosition.closed = !subPosition.closed">
                                                    <ChevronUpIcon v-if="!subPosition.closed"
                                                                   class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                                    <ChevronDownIcon v-else
                                                                     class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                                                </button>
                                            </div>
                                            <table class="w-full" v-if="!subPosition.closed">
                                                <tbody>
                                                <tr v-for="row in subPosition.sub_position_rows">
                                                    <td v-for="cell in row.cells" class="w-40">
                                                        <div @click="cell.clicked = !cell.clicked"
                                                             v-if="!cell.clicked">{{ cell.value }}
                                                        </div>
                                                        <div v-else>
                                                            <input type="text" v-model="cell.value"
                                                                   @focusout="cell.clicked = !cell.clicked">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="bg-lightBackgroundGray xsDark h-10"
                                                    style="background-color: #ccc !important">
                                                    <td></td>
                                                    <td></td>
                                                    <td>SUM</td>
                                                    <td>3000</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </th>
                                    </tr>
                                    </thead>
                                </table>
                            </th>
                        </tr>
                        </tbody>
                    </table>

                </div>
                <!-- View if not opened Event -->
                <div class="ml-2 w-11/12" v-else>
                    <h2>Einahmen</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-redColumn">

    </div>

    <pre v-for="cell in this.budget.table[0].sub_positions[0].sub_position_rows[0].cells">
                                                        {{ cell.column_id }}
                                                    </pre>

    <pre>
        {{ this.budget.table[0] }}
        </pre>


    <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">

                <div class="headline1 my-2">
                    {{ successHeading }}
                </div>
                <XIcon @click="closeSuccessModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="successText">
                    {{ successDescription }}
                </div>
                <div class="mt-6">
                    <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-full"
                            @click="closeSuccessModal">
                        <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                    </button>
                </div>
            </div>

        </template>
    </jet-dialog-modal>
    <!-- Termin erstellen Modal-->
    <add-column-component
        v-if="showAddColumnModal"
        :columns="budget.columns"
        :project="project"
        @closed="closeAddColumnModal()"
    />
    <!-- Cell Detail Modal-->
    <cell-detail-component
        v-if="showCellDetailModal"
        :cell="cellToShow"
        :moneySources="moneySources"
        @closed="closeCellDetailModal()"
    />
    <confirmation-component
        v-if="showDeleteMainPositionModal"
        confirm="Löschen"
        titel="Hauptposition löschen"
        :description="'Bist du sicher, dass du die Hauptposition ' + this.mainPositionToDelete.name + ' löschen möchtest?'"
        @closed="afterConfirm"/>

</template>

<script>


import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
import AddColumnComponent from "@/Layouts/Components/AddColumnComponent.vue";
import CellDetailComponent from "@/Layouts/Components/CellDetailComponent.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal";

export default {
    name: 'BudgetComponent',

    components: {
        ConfirmationComponent,
        CellDetailComponent,
        AddColumnComponent,
        AddButton,
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
        CheckIcon
    },

    data() {
        return {
            costsOpened: true,
            earningsOpened: true,
            hoveredBorder: null,
            showAddColumnModal: false,
            showCellDetailModal: false,
            cellToShow: null,
            hoveredRow: null,
            showMenu: null,
            showDeleteMainPositionModal: false,
            mainPositionToDelete: null,
            showSuccessModal: false,
            successHeading: '',
            successDescription: '',
            positionDefault: {
                position: 1
            }
        }
    },

    props: ['budget', 'project', 'moneySources'],

    computed: {
        tablesToShow: function () {
            let costTableArray = [];
            let earningTableArray = [];
            this.budget.table.forEach((mainPosition) => {
                if (mainPosition.type === 'BUDGET_TYPE_COST') {
                    costTableArray.push(mainPosition);
                } else {
                    earningTableArray.push(mainPosition);
                }
            })
            return [costTableArray, earningTableArray]
        },
        sumsToShow: function () {
            let sums = [];
            this.budget.table.forEach((mainPosition) => {
                mainPosition.sub_positions?.forEach((subPosition) => {
                    subPosition.sub_position_rows?.forEach((row) => {
                        row.cells.forEach((cell) => {
                            if (cell.column_id > 3) {
                                if (!isNaN(cell.value) && cell.value !== '') {
                                    if (sums[subPosition.id + '' + cell.column_id] === undefined) {
                                        sums[subPosition.id + '' + cell.column_id] = 0
                                        sums[subPosition.id + '' + cell.column_id] += parseInt(cell.value);
                                        //console.log(sums);
                                    } else {
                                        sums[subPosition.id + '' + cell.column_id] += parseInt(cell.value);
                                    }
                                }
                            }
                        })
                    })
                })
            })
            return sums;
        }
    },

    methods: {

        deleteColumn(column){
            this.$inertia.delete(route('project.budget.column.delete', column))
        },
        addRowToSubPosition(subPosition, row) {

            this.$inertia.post(route('project.budget.sub-position-row.add'), {
                project_id: this.project.id,
                sub_position_id: subPosition.id,
                positionBefore: row.position
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },
        getSumsOfSubPosition(subPosition) {
            let sums = [];
            subPosition.sub_position_rows?.forEach((row) => {
                row.cells.forEach((cell) => {
                    if (cell.column_id > 3) {
                        if (!isNaN(cell.value) && cell.value !== '') {
                            if (sums[cell.column_id] === undefined) {
                                sums[cell.column_id] = 0
                                sums[cell.column_id] += parseInt(cell.value);
                            } else {
                                sums[cell.column_id] += parseInt(cell.value);
                            }
                        }
                    }
                })
            })
            return sums;
        },
        getSumsOfMainPosition(mainPosition) {
            let sums = [];
            mainPosition.sub_positions?.forEach((sub_position) => {
                sub_position.cells.forEach((cell) => {
                    if (cell.column_id > 3) {
                        if (!isNaN(cell.value) && cell.value !== '') {
                            if (sums[cell.column_id] === undefined) {
                                sums[cell.column_id] = 0
                                sums[cell.column_id] += parseInt(cell.value);
                            } else {
                                sums[cell.column_id] += parseInt(cell.value);
                            }
                        }
                    }
                })
            })
            return sums;
        },
        currencyFormat(number) {
            const formatter = new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR',
            });
            return formatter.format(number);
        },
        openAddColumnModal() {
            this.showAddColumnModal = true;
        },
        closeAddColumnModal() {
            this.showAddColumnModal = false;
        },
        updateCellValue(cell) {
            cell.clicked = !cell.clicked;
            if (cell.value === null || cell.value === '') {
                cell.value = 0;
            }
            this.$inertia.patch(route('project.budget.cell.update'), {
                column_id: cell.column.id,
                value: cell.value,
                sub_position_row_id: cell.sub_position_row_id
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
                project_id: this.project.id,
                main_position_id: mainPositionId,
                positionBefore: subPositionBefore.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        addMainPosition(type, mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                project_id: this.project.id,
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
            });
        },
        updateMainPositionName(mainPosition) {
            this.$inertia.patch(route('project.budget.main-position.update-name'), {
                mainPosition_id: mainPosition.id,
                mainPositionName: mainPosition.name
            });
        },
        updateSubPositionName(subPosition) {
            this.$inertia.patch(route('project.budget.sub-position.update-name'), {
                subPosition_id: subPosition.id,
                subPositionName: subPosition.name
            });
        },
        openCellDetailModal(column) {
            this.cellToShow = column;
            this.showCellDetailModal = true;
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        deleteRowFromSubPosition(row) {
            this.$inertia.delete(`/project/budget/sub-position-row/${row.id}`);
        },
        openDeleteMainPositionModal(mainPosition){
            this.mainPositionToDelete = mainPosition;
            this.showDeleteMainPositionModal = true;
        },
        afterConfirm(bool) {
            if (!bool) return this.showDeleteMainPositionModal = false;

            this.deleteMainPosition();

        },
        deleteMainPosition(){
            this.showDeleteMainPositionModal = false;
            this.$inertia.delete(route('project.budget.main-position.delete', this.mainPositionToDelete.id))
            //this.$inertia.delete(`/project/budget/main-position/${this.mainPositionToDelete.id}`); Bitte wie oben
            this.successHeading = "Hauptposition gelöscht"
            this.successDescription = "Hauptposition " + this.mainPositionToDelete.name + " erfolgreich gelöscht"
            this.showSuccessModal = true;

            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.mainPositionToDelete = null;
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
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
