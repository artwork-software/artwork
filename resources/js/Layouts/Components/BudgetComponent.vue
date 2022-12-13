<template>
    <div class="mx-4">
        <div v-for="column in budget.columnNames">
            {{column.name}}
        </div>
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
                            {{budget}}
                        </div>
                        <!-- View if not opened Event -->
                        <div class="ml-2 w-11/12" v-else>

                        </div>
                    </div>
                </div>
            </div>

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
        }
    },

    props: ['budget'],

    methods: {

    },
}
</script>

<style scoped></style>
