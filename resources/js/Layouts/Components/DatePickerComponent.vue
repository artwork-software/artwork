<template>
    <div>
        <vue-tailwind-datepicker :shortcuts="customShortcuts" :placeholder="new Date(dateValue[0])" separator=" - " :formatter="formatter" :options="this.datePickerOptions" i18n="de" v-model="dateValue" />
    </div>
</template>

<script>

import VueTailwindDatepicker from 'vue-tailwind-datepicker'


import {ref} from "vue";

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
    date: 'DD.MM.YY',
    month: 'MMM'
})

const customShortcuts = () => {
    return [
        {
            label: 'Last 15 Days',
            atClick: () => {
                const date = new Date();
                return [
                    new Date(date.setDate(date.getDate() - 15)),
                    new Date()
                ];
            }
        },
        {
            label: 'Last Years',
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
}
</script>

<style scoped>

</style>
