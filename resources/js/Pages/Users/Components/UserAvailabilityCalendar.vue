<template>

    <div class="w-full">
        <div class="w-full items-center flex justify-between">
            <div class="sDark my-2">
                <h2>{{ dateToShow[0] }}</h2>
            </div>
            <div>
                <button class="ml-2 text-black" @click="previousMonth">
                    <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                </button>
                <button class="ml-2 text-black" @click="nextMonth">
                    <ChevronRightIcon class="h-5 w-5 text-primary"/>
                </button>
            </div>
        </div>
        <table class="w-full border-separate">
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
                <td class="col-span-1 " v-for="day in week.days" :key="day" :class="day.onVacation ? 'bg-backgroundGray' : ''">
                    <div :class="day.notInMonth ? 'text-secondary' : ''" class="p-6 flex justify-center">
                        {{ day.day }}
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
import dayjs from "dayjs";

export default defineComponent({
    name: "UserAvailabilityCalendar",
    components: {ChevronRightIcon, Button, ChevronLeftIcon},
    props: ['calendarData', 'dateToShow'],
    methods: {
        previousMonth() {
            const currentMonth = new Date(this.dateToShow[1].date);

            Inertia.reload({
                data: {
                    month: this.subtractOneMonth(currentMonth),
                }
            })
        },
        nextMonth() {
            const currentMonth = new Date(this.dateToShow[1].date);

            Inertia.reload({
                data: {
                    month: this.addOneMonth(currentMonth),
                }
            })
        },
        addOneMonth(dateObj) {
            const day = dayjs(dateObj)
            return day.add(+1, 'month').format('YYYY-MM-DD');
        },
        subtractOneMonth(dateObj) {
            return dayjs(dateObj).subtract(1, 'month').format('YYYY-MM-DD');
        }
    }
})
</script>


<style scoped>


</style>
