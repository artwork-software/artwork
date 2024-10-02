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
                <th class="p-6">{{ $t('Mon') }}</th>
                <th class="p-6">{{ $t('Tue') }}</th>
                <th class="p-6">{{ $t('Wed') }}</th>
                <th class="p-6">{{ $t('Thu') }}</th>
                <th class="p-6">{{ $t('Fri') }}</th>
                <th class="p-6">{{ $t('Sat') }}</th>
                <th class="p-6">{{ $t('Sun') }}</th>
            </tr>
            <tr class="grid-cols-8" v-for="week in calendarData" :key="week.weekNumber">
                <td class="col-span-1 sDark">
                    <div class="p-6 flex justify-center">
                        KW {{ week.weekNumber }}
                    </div>
                </td>
                <td class="col-span-1 cursor-pointer" v-for="day in week.days" :key="day" @click="showVacationsAndAvailabilities(day.day_formatted)">
                    <div
                        :class="{
                            'font-extrabold text-gray-800' : day.isToday,
                            'bg-gray-800 rounded-full' : day.day_formatted === showVacationsAndAvailabilitiesDate,
                            'text-gray-400': day.isToday,
                            'text-green-500': day.hasAvailability,
                            'text-red-500':  day.onVacation,
                            'text-white': !day.onVacation && !day.hasAvailability && day.day_formatted === showVacationsAndAvailabilitiesDate
                        }"
                         class="p-6 flex items-center justify-center" >
                        {{ day.day }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</template>

<script>

/*

:class="{
                        'text-gray-400' : day.notInMonth,
                        '!font-bold' : day.day_formatted === showVacationsAndAvailabilitiesDate || day.isToday,
                        'bg-red-500 rounded-full text-white' : day.day_formatted === showVacationsAndAvailabilitiesDate && day.hasConflict,
                        '!text-green-500': day.hasAvailability,
                         '!text-red-500': day.onVacation &&  day.day_formatted !== showVacationsAndAvailabilitiesDate && !day.hasAvailability || day.hasConflict,
                        }"
 */
import {defineComponent} from 'vue'
import {router} from "@inertiajs/vue3";
import {ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import Button from "@/Jetstream/Button.vue";
import dayjs from "dayjs";

export default defineComponent({
    name: "UserAvailabilityCalendar",
    components: {ChevronRightIcon, Button, ChevronLeftIcon},
    props: ['calendarData', 'dateToShow', 'showVacationsAndAvailabilitiesDate'],
    methods: {
        previousMonth() {
            const currentMonth = new Date(this.dateToShow[1].date);

            router.reload({
                data: {
                    month: this.subtractOneMonth(currentMonth),
                }
            })
        },
        nextMonth() {
            const currentMonth = new Date(this.dateToShow[1].date);

            router.reload({
                data: {
                    month: this.addOneMonth(currentMonth),
                }
            })
        },
        showVacationsAndAvailabilities(day) {
            const currentMonth = new Date(this.dateToShow[1].date);
            const rightMonth = dayjs(currentMonth)
            router.reload({
                data: {
                    showVacationsAndAvailabilities: day,
                    vacationMonth: rightMonth.format('YYYY-MM-DD')
                },
                preserveState: true,
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
