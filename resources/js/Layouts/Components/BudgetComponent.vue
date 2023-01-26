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
                        :class="index <= 1 ? 'w-20' : index === 2 ? 'w-64' : 'w-44'" class="text-right">
                        <div class="flex items-center " @mouseover="showMenu = column.id" :key="column.id"
                             @mouseout="showMenu = null">
                            <div>
                                <div class="flex items-center justify-end pr-2">
                                    <p class="columnSubName xsLight">
                                        {{ column.subName }}
                                        <span v-if="column.calculateName" class="ml-1">
                                            ({{ column.calculateName }})
                                        </span>
                                    </p>
                                    <span class="ml-1"
                                          v-if="index > 2 && column.showColorMenu === true || column.color !== 'whiteColumn'">
                                        <Listbox as="div" class="flex mr-2" v-model="column.color">
                                                <ListboxButton>
                                                   <button class="w-4 h-4 flex justify-center items-center rounded-full"
                                                           :class="column.color === 'whiteColumn' ? 'whiteColumn border border-1' : column.color"
                                                           @click="column.openColor = !column.openColor">
                                                        <ChevronUpIcon v-if="column.openColor"
                                                                       class="h-3 w-3 my-auto"
                                                                       :class="column.color === 'whiteColumn' ? 'text-black' : 'text-white'"></ChevronUpIcon>
                                                        <ChevronDownIcon v-else
                                                                         class="h-3 w-3 text-white my-auto"
                                                                         :class="column.color === 'whiteColumn' ? 'text-black' : 'text-white'"></ChevronDownIcon>
                                                    </button>
                                                </ListboxButton>

                                                <transition leave-active-class="transition ease-in duration-100"
                                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <ListboxOptions
                                                        class="absolute w-24 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                                        <ListboxOption as="template" class="max-h-32"
                                                                       v-for="color in colors"
                                                                       :key="color"
                                                                       :value="color" v-slot="{ active, selected }">
                                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 text-sm subpixel-antialiased']"
                                                                @click="changeColumnColor(color, column.id)">
                                                                <div class="flex">
                                                                    <span
                                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'block truncate']">
                                                                        <span
                                                                            class="block truncate items-center ml-3 flex rounded-full h-10 w-10"
                                                                            :class="color">
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                                <span
                                                                    :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
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
                                     :class="index <= 1 ? 'w-20' : index === 2 ? 'w-64' : 'w-44'" class="h-5 pr-2"
                                     v-if="!column.clicked">
                                    {{ column.name }}
                                </div>
                                <div v-else>
                                    <input
                                        :class="index <= 1 ? 'w-20' : index === 2 ? 'w-64' : 'w-44'"
                                        class="my-2 xsDark pr-2 text-right" type="text"
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
                                                <a @click="column.showColorMenu = true"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Einfärben
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }" v-if="!column.is_locked">
                                                <a @click="lockColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Sperren
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }" v-if="column.is_locked">
                                                <a @click="unlockColumn(column.id)"
                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Entsperren
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
                            <div v-if="showMenu !== column.id" class="w-6">

                            </div>
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
                         class="group w-11/12 bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                        <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                            Hauptposition
                            <PlusCircleIcon
                                class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                        </div>
                    </div>
                    <table class="w-11/12 mb-6">
                        <tbody class="">
                        <tr v-if="tablesToShow[0]?.length > 0" v-for="(mainPosition,mainIndex) in tablesToShow[0]">
                            <MainPositionComponent @openRowDetailModal="openRowDetailModal" @openVerifiedModal="openVerifiedModal" @openCellDetailModal="openCellDetailModal" @openDeleteModal="openDeleteModal" :budget="budget" :project="project" :main-position="mainPosition"></MainPositionComponent>
                        </tr>
                        <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right">
                            <td class="w-28"></td>
                            <td class="w-28"></td>
                            <td class="w-72 my-2">SUM</td>
                            <td class="flex items-center w-48"
                                v-for="column in budget.columns.slice(3)">
                                <div class="w-48 my-2 p-1"
                                     :class="this.getSumOfTable(0,column.id) < 0 ? 'text-red-500' : ''">
                                    {{ this.getSumOfTable(0, column.id) }}
                                </div>
                            </td>

                        </tr>
                        <!-- TODO: Hier noch einfügen if(commented === true) -->
                        <tr v-if="true" class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                            <td class="w-28"></td>
                            <td class="w-28"></td>
                            <td class="w-72 my-2">SUM ausgeklammerte Posten</td>
                            <td class="w-48 my-2 p-1">3000</td>
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
                            <th class="bg-primary text-white">
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


    <jet-dialog-modal :show="showVerifiedModal" @close="closeVerifiedModal">
        <template #content>
            <img alt="Neue Spalte" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ verifiedTexts.title }} <span class="xsDark">{{ verifiedTexts.positionTitle }}</span>
                </div>
                <XIcon @click="closeVerifiedModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="mb-2 xsLight">
                    {{ verifiedTexts.description }}
                </div>
                <p class="xsLight flex mb-2">
                    <!-- TODO: SVG ersetzen mit IMG TAG -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" class="mr-2">
                        <g id="warning" transform="translate(-453 -292)">
                            <rect id="Rechteck_468" data-name="Rechteck 468" width="20" height="20"
                                  transform="translate(453 292)" fill="#e0e000"/>
                            <path id="Icon_metro-warning" data-name="Icon metro-warning"
                                  d="M8.4,2.984l4.884,9.734H3.514L8.4,2.984Zm0-1.056a.842.842,0,0,0-.693.508L2.731,12.351c-.381.678-.057,1.232.72,1.232h9.894c.777,0,1.1-.554.72-1.232h0L9.091,2.436A.842.842,0,0,0,8.4,1.928ZM9.126,11.4a.728.728,0,1,1-.728-.728A.728.728,0,0,1,9.126,11.4ZM8.4,9.941a.728.728,0,0,1-.728-.728V7.027a.728.728,0,1,1,1.457,0V9.212A.728.728,0,0,1,8.4,9.941Z"
                                  transform="translate(454.602 294.245)" fill="#fcfcfb" stroke="#fcfcfb"
                                  stroke-width="0.2"/>
                        </g>
                    </svg>
                    Achtung: Du gibst der/dem Nutzer*in dadurch Budgetzugriff!
                </p>
                <div class="mb-2">
                    <div class="relative w-full">
                        <div class="w-full" v-if="showUserAdd">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   placeholder="Wer soll deine Kalkulation verifizieren?*"
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
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
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
                                    <span class="sr-only">User aus Finanzierungsquelle entfernen</span>
                                    <XIcon
                                        class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                </button>
                            </div>
                        </span>
                    </div>
                </div>
                <div class="mt-6">
                    <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-full"
                            @click="submitVerifiedModal">
                        <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                    </button>
                </div>
            </div>

        </template>
    </jet-dialog-modal>
    <!-- Termin erstellen Modal-->
    <add-column-component
        v-if="showAddColumnModal"
        :project="project"
        :table="budget.table"
        @closed="closeAddColumnModal()"
    />
    <!-- Cell Detail Modal-->
    <cell-detail-component
        v-if="showCellDetailModal"
        :cell="budget.selectedCell"
        :moneySources="moneySources"
        @closed="closeCellDetailModal()"
    />
    <!-- Row Detail Modal-->
    <row-detail-component
        v-if="showRowDetailModal"
        :row="budget.selectedRow"
        :moneySources="moneySources"
        @closed="closeRowDetailModal()"
    />
    <confirmation-component
        v-if="showDeleteModal"
        confirm="Löschen"
        :titel="this.confirmationTitle"
        :description="this.confirmationDescription"
        @closed="afterConfirm"/>


    <pre>
        {{ budget.table }}
    </pre>
</template>

<script>


import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
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
    MenuItems
} from "@headlessui/vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import {useForm} from "@inertiajs/inertia-vue3";
import {Inertia} from "@inertiajs/inertia";
import MainPositionComponent from "@/Layouts/Components/MainPositionComponent.vue";
import RowDetailComponent from "@/Layouts/Components/RowDetailComponent.vue";

export default {
    name: 'BudgetComponent',

    components: {
        MainPositionComponent,
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
        CheckIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        RowDetailComponent
    },

    data() {
        return {
            costsOpened: true,
            earningsOpened: true,
            hoveredBorder: null,
            showAddColumnModal: false,
            showCellDetailModal: false,
            showRowDetailModal: false,
            hoveredRow: null,
            showMenu: null,
            showDeleteModal: false,
            mainPositionToDelete: null,
            subPositionToDelete: null,
            rowToDelete: null,
            confirmationTitle:'',
            confirmationDescription: '',
            showSuccessModal: false,
            successHeading: '',
            successDescription: '',
            positionDefault: {
                position: 0
            },
            colors: {
                whiteColumn: 'whiteColumn',
                greenColumn: 'greenColumn',
                yellowColumn: 'yellowColumn',
                redColumn: 'redColumn',
                lightGreenColumn: 'lightGreenColumn'
            },
            verifiedTexts: {
                title: 'Verifizierung',
                positionTitle: '',
                description: 'Sind alle Zahlen richtig kalkuliert? Ist die Kalkulation plausibel? Lasse deine Hauptposition durch eine Nutzer*in verifizieren. '
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
                project_title: this.project.name,
                table_id: this.budget.table.id
            }),
        }
    },

    props: ['budget', 'project', 'moneySources'],

    computed: {
        tablesToShow: function () {
            let costTableArray = [];
            let earningTableArray = [];
            this.budget.table.main_positions.forEach((mainPosition) => {
                if (mainPosition.type === 'BUDGET_TYPE_COST') {
                    costTableArray.push(mainPosition);
                } else {
                    earningTableArray.push(mainPosition);
                }
            })
            return [costTableArray, earningTableArray]
        },
    },
    watch: {
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
                if(cell.value !== cell.verified_value){
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
                sum += mainPosition.columnSums[columnId];
            })
            return sum;
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
                table_id: this.budget.table.id,
                sub_position_id: subPosition.id,
                positionBefore: row ? row.position : -1
            }, {
                preserveState: true,
                preserveScroll: true
            });
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
                table_id: this.budget.table.id,
                main_position_id: mainPositionId,
                positionBefore: subPositionBefore.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        addMainPosition(type, mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                table_id: this.budget.table.id,
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
        openCellDetailModal(column) {
            Inertia.reload({
                data: {
                    selectedCell: column.id,
                },
                onSuccess: () => {
                    this.showCellDetailModal = true;
                }
            })
        },
        openRowDetailModal(row) {
            Inertia.reload({
                data: {
                    selectedRow: row.id,
                },
                onSuccess: () => {
                    this.showRowDetailModal = true;
                }
            })
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        closeRowDetailModal() {
            this.showRowDetailModal = false;
        },
        openDeleteRowModal(row){
          this.confirmationTitle = 'Zeile löschen';
          this.confirmationDescription = 'Bist du sicher, dass du diese Zeile löschen möchtest? Sämtliche Verlinkungen etc. werden ebenfalls gelöscht.';
          this.rowToDelete = row;
          this.showDeleteModal = true;
        },
        openDeleteModal(title, description, position, type) {
            this.confirmationTitle = title;
            this.confirmationDescription = description
            if(type === 'main'){
                this.mainPositionToDelete = position;
            }else if(type === 'sub'){
                this.subPositionToDelete = position;
            }else{
                this.rowToDelete = position;
            }
            this.showDeleteModal = true;
        },
        openDeleteSubPositionModal(subPosition) {
            this.confirmationTitle = 'Unterposition löschen';
            this.confirmationDescription = 'Bist du sicher, dass du die Unterposition ' + subPosition.name + ' löschen möchtest?'
            this.subPositionToDelete = subPosition;
            this.showDeleteModal = true;
        },
        afterConfirm(bool) {
            if (!bool) return this.showDeleteModal = false;

            this.deletePosition();

        },
        deletePosition() {
            if (this.mainPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.main-position.delete', this.mainPositionToDelete.id))
                this.successHeading = "Hauptposition gelöscht"
                this.successDescription = "Hauptposition " + this.mainPositionToDelete.name + " erfolgreich gelöscht"
            } else if (this.subPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.sub-position.delete', this.subPositionToDelete.id))
                this.successHeading = "Unterposition gelöscht"
                this.successDescription = "Unterposition " + this.subPositionToDelete.name + " erfolgreich gelöscht"
            }else{
                this.$inertia.delete(`/project/budget/sub-position-row/${this.rowToDelete.id}`, {
                    preserveScroll: true,
                    preserveState: true
                });
                this.successHeading = "Zeile gelöscht"
                this.successDescription = "Zeile erfolgreich gelöscht"
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
        closeVerifiedModal() {
            this.showVerifiedModal = false;
            this.user_query = '';
            this.showUserAdd = true;
            this.submitVerifiedModalData.user = '';
            this.submitVerifiedModalData.id = null;
            this.submitVerifiedModalData.is_main = false;
            this.submitVerifiedModalData.is_sub = false;
            this.submitVerifiedModalData.position = [];
        },
        submitVerifiedModal() {
            if (this.submitVerifiedModalData.is_main) {
                this.submitVerifiedModalData.post(route('project.budget.verified.main-position.request'));
            } else {
                this.submitVerifiedModalData.post(route('project.budget.verified.sub-position.request'));
            }

            this.closeVerifiedModal();
        },
        openVerifiedModal(is_main,is_sub,id,position) {
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
                table_id: this.budget.table.id,
            })
        },
        verifiedSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.sub-position'), {
                subPositionId: subPositionId,
                table_id: this.budget.table.id,
            })
        },
        requestRemove(position, type){
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            })
        },
        removeVerification(position, type){
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            })
        },
        lockColumn(columnId){
            this.$inertia.patch(this.route('project.budget.lock.column'), {
                columnId: columnId
            });
        },
        unlockColumn(columnId){
            this.$inertia.patch(this.route('project.budget.unlock.column'), {
                columnId: columnId
            });
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
