<template>
    <div>
        <input v-model="dateValue[0]"
               id="startDate"
               type="date"
               class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
        <input v-model="dateValue[1]"
               id="endDate"
               type="date"
               class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
        <vue-tailwind-datepicker :shortcuts="customShortcuts" :placeholder="dateValue[0] - dateValue[1]" separator=" - " :formatter="formatter" :options="this.datePickerOptions" i18n="de" v-model="dateValue" />
    </div>
</template>

<script>

import VueTailwindDatepicker from 'vue-tailwind-datepicker'


import {ref} from "vue";
import {Inertia} from "@inertiajs/inertia";

const datePickerOptions = ref({
    shortcuts: {
        today: 'Heute',
        yesterday: 'Gestern',
        past: period =>'Letzte ' + period + ' Tage',
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
    components: {VueTailwindDatepicker},
    props: {
    },
    data(){
        return{
            dateValue:[],
            datePickerOptions: datePickerOptions,
            formatter:formatter,
            customShortcuts: customShortcuts,
        }

    },
    watch: {
        dateValue: {
            handler() {
                // TODO: HIER WERDEN BEIDE DATES IN EINEM ARRAY [ startdate, enddate ] ANS BACKEND WEITERGEGEGBEN im Format DD.MM.YY BITTE IM BACKEND umsetzen.
                Inertia.reload({
                    data: {
                        dateRangeArray: this.dateValue,
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
