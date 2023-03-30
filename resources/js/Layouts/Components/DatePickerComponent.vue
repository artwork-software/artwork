<template>
    <div>
        <div class="flex items-center">
            <CalendarIcon class="w-5 h-5 mr-2" @click="this.showDateRangePicker = !this.showDateRangePicker"/>
            <input v-model="dateValue[0]"
                   id="startDate"
                   type="date"
                   placeholder="Start"
                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
            <input v-model="dateValue[1]"
                   id="endDate"
                   type="date"
                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
        </div>
        <vue-tailwind-datepicker class="absolute z-50" v-if="this.showDateRangePicker" no-input :shortcuts="customShortcuts"
                                 :placeholder="dateValue[0] - dateValue[1]" separator=" - " :formatter="formatter"
                                 :options="this.datePickerOptions" i18n="de" v-model="dateValue"/>
    </div>
</template>

<script>

import VueTailwindDatepicker from 'vue-tailwind-datepicker'


import {ref} from "vue";
import {Inertia} from "@inertiajs/inertia";
import {CalendarIcon} from "@heroicons/vue/outline";

const datePickerOptions = ref({
    shortcuts: {
        today: 'Heute',
        yesterday: 'Gestern',
        past: period => 'Letzte ' + period + ' Tage',
        currentMonth: 'Aktueller Monat',
        pastMonth: 'Letzter Monat'
    },
    footer: {
        apply: 'Anwenden',
        cancel: 'Abbrechen'
    }
})
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MMM'
})

const customShortcuts = () => {
    return [
        {
            label: 'Heute',
            atClick: () => {
                const date = new Date();
                return [
                    new Date(),
                    new Date()
                ];
            }
        },
        {
            label: 'Aktuelle Woche',
            atClick: () => {
                const date = new Date();
                return [
                    new Date(date.setFullYear(date.getFullYear() - 1)),
                    new Date()
                ];
            }
        }
    ]
}

export default {
    name: "DatePickerComponent",
    components: {VueTailwindDatepicker, CalendarIcon},
    props: ['dateValueArray'],
    data() {
        return {
            dateValue: this.dateValueArray ? this.dateValueArray : [],
            datePickerOptions: datePickerOptions,
            formatter: formatter,
            showDateRangePicker: false,
            refreshKey: 1,
            customShortcuts: customShortcuts,
        }

    },
    watch: {
        dateValue: {
            handler() {
                this.showDateRangePicker = false;
                    Inertia.reload({
                        data: {
                            startDate: this.dateValue[0],
                            endDate: this.dateValue[1],
                        }
                    })
            },
            deep: true
        }
    },
}
</script>

<style scoped>

</style>
