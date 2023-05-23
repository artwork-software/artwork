<template>
    <h3 class="headline2 mb-6">Verfügbarkeit</h3>
    <div class="w-full">
        <div class="w-full items-center flex justify-between">
            <div class="sDark my-2">
            <h2>{{ dateToShow[0] }}</h2>
            </div>
            <div>
            <button  class="ml-2 text-black" @click="previousMonth">
                <ChevronLeftIcon class="h-5 w-5 text-primary"/>
            </button>
            <button class="ml-2 text-black" @click="nextMonth">
                <ChevronRightIcon class="h-5 w-5 text-primary"/>
            </button>
            </div>
        </div>
        <table class="w-full">
            <tr class="bg-backgroundGray sDark">
                <th class="p-6"></th>
                <th class="p-6">MO</th>
                <th class="p-6">DI</th>
                <th class="p-6">MI</th>
                <th class="p-6">DO</th>
                <th class="p-6">FR</th>
                <th class="p-6">SA</th>
                <th class="p-6">SO</th>
            </tr>
            <tr class="sDark grid-cols-8" v-for="week in calendarData" :key="week.weekNumber">
                <td class="col-span-1">
                    <div class="p-6 flex justify-center">
                        KW {{ week.weekNumber }}
                    </div>
                </td>
                <td class="col-span-1" v-for="day in week.days" :key="day">
                    <div class="p-6 flex justify-center">
                        {{ day }}
                    </div>

                </td>
            </tr>
        </table>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import {Inertia} from "@inertiajs/inertia";
import {ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import Button from "@/Jetstream/Button.vue";

export default defineComponent({
    name: "UserAvailabilityCalendar",
    components: {ChevronRightIcon, Button, ChevronLeftIcon},
    props: ['calendarData', 'dateToShow'],
    data() {
        return {
            currentMonth: '',
        };
    },
    methods: {
        previousMonth(dateToShow) {
            const currentMonth = new Date(dateToShow[1]);
            const previousMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);

            // Formatting the previous month as 'YYYY-MM'
            const year = previousMonth.getFullYear();
            const month = String(previousMonth.getMonth() + 1).padStart(2, '0');

            const previousMonthString = `${year}-${month}`;

            Inertia.reload({
                data: {
                    month: previousMonthString,
                }
            })
        },
        nextMonth(dateToShow) {
            const currentMonth = new Date(dateToShow[1]);
            const nextMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);

            // Formatting the next month as 'YYYY-MM'
            const year = nextMonth.getFullYear();
            const month = String(nextMonth.getMonth() + 1).padStart(2, '0');

            const nextMonthString = `${year}-${month}`;

            Inertia.reload({
                data: {
                    month: nextMonthString,
                }
            })
        }
    }
})
</script>


<style scoped>


</style>
