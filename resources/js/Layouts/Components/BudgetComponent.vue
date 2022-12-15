<template>
    <div class="mx-4">

        <div class="w-full flex">
            <table class="w-full flex ml-16">
                <thead>
                <tr>
                    <th v-for="(column,index) in budget.columns"
                        :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'" class="text-left">
                        <div @click="column.clicked = !column.clicked"
                             v-if="!column.clicked">
                            {{ column.name }}
                        </div>
                        <div v-else>
                                <input
                                    :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'"
                                    class="my-2 xsDark" type="text"
                                    v-model="column.name"
                                    @focusout="column.clicked = !column.clicked">
                        </div>
                    </th>
                </tr>
                </thead>
            </table>
            <AddButton @click="openAddColumnModal()" class="w-44" text="Neue Spalte" mode="page"></AddButton>
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
                    <table class="w-11/12 mb-6">
                        <tbody class="">
                        <tr class="" v-for="(mainPosition,mainIndex) in tablesToShow[0]">
                            <th class="bg-primary text-left p-0">
                                <div class="pl-2 xsWhiteBold flex items-center h-10">
                                    {{ mainPosition.name }}
                                    <button class="my-auto w-6 ml-3"
                                            @click="mainPosition.closed = !mainPosition.closed">
                                        <ChevronUpIcon v-if="!mainPosition.closed"
                                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                </div>
                                <!-- HIER ADD UNTERPOSITION Funktion -->
                                <div
                                    class="bg-secondaryHover h-1 flex justify-end border-dashed hover:border-t-2 hover:border-buttonBlue "
                                    @mouseover="hoveredBorder = mainIndex + 'subBefore'"
                                    @mouseout="hoveredBorder = null">
                                    <div v-if="hoveredBorder === mainIndex + 'subBefore'"
                                         class="uppercase text-secondaryHover text-sm -mt-8">
                                        Unterposition
                                        <PlusCircleIcon v-if="hoveredBorder === mainIndex + 'subBefore'"
                                                        class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                    </div>
                                </div>
                                <table v-if="!mainPosition.closed" class="w-full ">
                                    <thead class="">
                                    <tr class="" v-for="subPosition in mainPosition.sub_positions">
                                        <th class="bg-silverGray xxsDark ">
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
                                                <tbody class="bg-secondaryHover w-full">
                                                <tr :class="rowIndex !== 0 ? 'border-t-2 border-silverGray': ''" class="bg-secondaryHover flex"
                                                    v-for="(row,rowIndex) in subPosition.sub_position_rows">
                                                    <td :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'"
                                                        v-for="(column,index) in row.columns">
                                                        <div :class="row.commented ? 'xsLight' : 'xsDark'" class="ml-2 my-4"
                                                             @click="column.pivot.clicked = !column.pivot.clicked"
                                                             v-if="!column.pivot.clicked">{{ column.pivot.value }}
                                                        </div>
                                                        <div v-else>
                                                            <input
                                                                :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48'"
                                                                class="my-2 xsDark" type="text"
                                                                v-model="column.pivot.value"
                                                                @focusout="column.pivot.clicked = !column.pivot.clicked">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="bg-silverGray xsDark flex h-10 w-full">
                                                    <td class="w-24"></td>
                                                    <td class="w-24"></td>
                                                    <td class="w-72 ml-2 my-2">SUM</td>
                                                    <td class="w-48 my-2">3000</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </th>
                                    </tr>
                                    <!-- HIER ADD UNTERPOSITION -->
                                    <div
                                        class="bg-secondaryHover h-1 flex justify-end border-dashed hover:border-t-2 hover:border-buttonBlue "
                                        @mouseover="hoveredBorder = mainIndex + 'subAfter'"
                                        @mouseout="hoveredBorder = null">
                                        <div v-if="hoveredBorder === mainIndex + 'subAfter'"
                                             class="uppercase text-buttonBlue text-sm -mt-8">
                                            Unterposition
                                            <PlusCircleIcon v-if="hoveredBorder === mainIndex + 'subAfter'"
                                                            class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                        </div>

                                    </div>

                                    <tr class="bg-primary xsWhiteBold flex h-10 w-full">
                                        <td class="w-24"></td>
                                        <td class="w-24"></td>
                                        <td class="w-72 ml-2 my-2">SUM</td>
                                        <td class="w-48 my-2">3002</td>
                                    </tr>
                                    </thead>
                                    <!-- HIER ADD HAUPTPOSITION -->
                                    <div
                                        class="bg-secondaryHover h-1 flex justify-end border-dashed hover:border-t-2 hover:border-buttonBlue "
                                        @mouseover="hoveredBorder = mainIndex + 'main'"
                                        @mouseout="hoveredBorder = null">
                                        <div v-if="hoveredBorder === mainIndex + 'main'"
                                             class="uppercase text-secondaryHover text-sm -mt-8">
                                            Hauptposition
                                            <PlusCircleIcon v-if="hoveredBorder === mainIndex + 'main'"
                                                            class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
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
                                                    <td v-for="column in row.columns" class="w-40">
                                                        <div @click="column.pivot.clicked = !column.pivot.clicked"
                                                             v-if="!column.pivot.clicked">{{ column.pivot.value }}
                                                        </div>
                                                        <div v-else>
                                                            <input type="text" v-model="column.pivot.value"
                                                                   @focusout="column.pivot.clicked = !column.pivot.clicked">
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

    <pre>
            {{ tablesToShow[0] }}

        </pre>
    <!-- Termin erstellen Modal-->
    <add-column-component
        v-if="showAddColumnModal"
        @closed="closeAddColumnModal()"
    />

</template>

<script>


import {ChevronDownIcon, PlusCircleIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
import AddColumnComponent from "@/Layouts/Components/AddColumnComponent.vue";

export default {
    name: 'BudgetComponent',

    components: {
        AddColumnComponent,
        AddButton,
        ChevronDownIcon,
        ChevronUpIcon,
        PlusCircleIcon
    },

    data() {
        return {
            costsOpened: true,
            earningsOpened: true,
            hoveredBorder: null,
            showAddColumnModal: false,
        }
    },

    props: ['budget'],

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
        }
    },

    methods: {
        openAddColumnModal() {
            this.showAddColumnModal = true;
        },
        closeAddColumnModal() {
            this.showAddColumnModal = false;
        }
    },
}
</script>

<style scoped>
</style>
