<template>
    <div class="mx-4">

        <table class="w-full ml-10">
            <thead>
                <tr>
                    <th v-for="column in budget.columnNames" class="text-left w-40" >{{ column.name }}</th>
                </tr>
            </thead>
        </table>

        <div class="flex my-8 ">
            <div class="flex w-full border border-2 border-gray-300">
                <button class="bg-buttonBlue w-6"
                        @click="costsOpened = !costsOpened">
                    <ChevronUpIcon v-if="costsOpened"
                                   class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                </button>

                <div class="ml-2 w-11/12" v-if="costsOpened">
                    <h2>Ausgaben</h2>
                    <table class="w-full">
                        <tbody>
                            <tr v-for="mainPosition in tablesToShow[0]">
                                <th class="bg-primary text-white text-left">
                                    <div class="pl-2 flex items-center h-10">
                                        {{ mainPosition.name }}
                                        <button class="my-auto w-6 ml-3" @click="mainPosition.closed = !mainPosition.closed">
                                            <ChevronUpIcon v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                            <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                        </button>
                                    </div>
                                    <table v-if="!mainPosition.closed" class="w-full">
                                        <thead>
                                            <tr v-for="subPosition in mainPosition.sub_positions">
                                                <th class="bg-lightBackgroundGray xsDark" >
                                                    <div class="pl-2 flex items-center h-10">
                                                        {{ subPosition.name }}
                                                        <button class="my-auto w-6 ml-3" @click="subPosition.closed = !subPosition.closed">
                                                            <ChevronUpIcon v-if="!subPosition.closed" class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                                            <ChevronDownIcon v-else class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                                                        </button>
                                                    </div>
                                                    <table class="w-full" v-if="!subPosition.closed">
                                                        <tbody>
                                                            <tr v-for="row in subPosition.sub_position_rows">
                                                                <td v-for="column in row.columns" class="w-40">
                                                                    <div @click="column.pivot.clicked = !column.pivot.clicked"  v-if="!column.pivot.clicked">{{ column.pivot.value }}</div>
                                                                    <div v-else>
                                                                        <input type="text" v-model="column.pivot.value" @focusout="column.pivot.clicked = !column.pivot.clicked">
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr class="bg-lightBackgroundGray xsDark h-10" style="background-color: #ccc !important">
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
                    <h2>Ausgaben</h2>
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
                                    <button class="my-auto w-6 ml-3" @click="mainPosition.closed = !mainPosition.closed">
                                        <ChevronUpIcon v-if="!mainPosition.closed" class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                </div>
                                <table v-if="!mainPosition.closed" class="w-full">
                                    <thead>
                                    <tr v-for="subPosition in mainPosition.sub_positions">
                                        <th class="bg-lightBackgroundGray xsDark">
                                            <div class="pl-2 flex items-center h-10">
                                                {{ subPosition.name }}
                                                <button class="my-auto w-6 ml-3" @click="subPosition.closed = !subPosition.closed">
                                                    <ChevronUpIcon v-if="!subPosition.closed" class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                                    <ChevronDownIcon v-else class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                                                </button>
                                            </div>
                                            <table class="w-full" v-if="!subPosition.closed">
                                                <tbody>
                                                <tr v-for="row in subPosition.sub_position_rows">
                                                    <td v-for="column in row.columns" class="w-40">
                                                        <div @click="column.pivot.clicked = !column.pivot.clicked"  v-if="!column.pivot.clicked">{{ column.pivot.value }}</div>
                                                        <div v-else>
                                                            <input type="text" v-model="column.pivot.value" @focusout="column.pivot.clicked = !column.pivot.clicked">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="bg-lightBackgroundGray xsDark h-10" style="background-color: #ccc !important">
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
            {{tablesToShow[0]}}

        </pre>

</template>

<script>


import {ChevronDownIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon} from "@heroicons/vue/solid";

export default {
    name: 'BudgetComponent',

    components: {
        ChevronDownIcon,
        ChevronUpIcon,
    },

    data() {
        return {
            costsOpened: true,
            earningsOpened: true
        }
    },

    props: ['budget'],

    computed: {
        tablesToShow: function() {
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

    },
}
</script>

<style scoped></style>
