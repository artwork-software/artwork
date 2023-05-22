<template>
    <div v-if="!project">
        <div class="flex items-center">
            <CalendarIcon class="w-5 h-5 mr-2" @click="this.showDateRangePicker = !this.showDateRangePicker"/>
            <input v-model="dateValueArray[0]"
                   @change="this.updateTimes"
                   id="startDate"
                   type="date"
                   :disabled="!!project"
                   placeholder="Start"
                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
            <input v-model="dateValueArray[1]"
                   @change="this.updateTimes"
                   :disabled="!!project"
                   id="endDate"
                   type="date"
                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
        </div>
        <vue-tailwind-datepicker class="absolute z-50" v-if="this.showDateRangePicker && dateValuePicker" no-input
                                 :shortcuts="customShortcuts"
                                 :placeholder="dateValuePicker[0] - dateValuePicker[1]" separator=" - " :formatter="formatter"
                                 :options="this.datePickerOptions" @update:modelValue="dateValueArray = $event" i18n="de"
                                 v-model="dateValuePicker"/>
    </div>
    <div class="font-medium text-gray-900" v-else>
        Projektzeitraum: {{new Date(dateValueArray[0]).format("DD.MM.YYYY")}} - {{new Date(dateValueArray[1]).format("DD.MM.YYYY")}}
    </div>
</template>

<script>

import VueTailwindDatepicker from 'vue-tailwind-datepicker'


import {ref} from "vue";
import {Inertia} from "@inertiajs/inertia";
import {CalendarIcon} from "@heroicons/vue/outline";
import Permissions from "@/mixins/Permissions.vue";


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
                const today = new Date();
                const firstDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)));
                const lastDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1) + 6));

                return [firstDayOfWeek, lastDayOfWeek];
            }
        },
        {
            label: 'Aktueller Monat',
            atClick: () => {
                const today = new Date();
                const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                return [firstDayOfMonth, lastDayOfMonth];
            }
        },
        {
            label: 'Aktuelles Jahr',
            atClick: () => {
                const today = new Date();
                const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
                const lastDayOfYear = new Date(today.getFullYear(), 11, 31);

                return [firstDayOfYear, lastDayOfYear];
            }
        },
        {
            label: 'Nächste 30 Tage',
            atClick: () => {
                const today = new Date();
                const next30DaysStart = new Date(today.setDate(today.getDate() + 1));
                const next30DaysEnd = new Date(today.setDate(today.getDate() + 29));

                return [next30DaysStart, next30DaysEnd];
            }
        },
        {
            label: 'Nächste 90 Tage',
            atClick: () => {
                const today = new Date();
                const next90DaysStart = new Date(today.setDate(today.getDate() + 1));
                const next90DaysEnd = new Date(today.setDate(today.getDate() + 89));

                return [next90DaysStart, next90DaysEnd];
            }
        }
    ]
}

export default {
    mixins: [Permissions],
    name: "DatePickerComponent",
    components: {VueTailwindDatepicker, CalendarIcon},
    props: ['dateValueArray','project'],
    data() {
        return {
            dateValue: this.dateValueArray ? this.dateValueArray : [],
            datePickerOptions: datePickerOptions,
            dateValuePicker: this.dateValueArray ? this.dateValueArray: [],
            formatter: formatter,
            showDateRangePicker: false,
            refreshPage: false,
            customShortcuts: customShortcuts,
        }
    },
    watch: {
        dateValuePicker: {
            handler() {
                this.showDateRangePicker = false;
                Inertia.reload({
                    data: {
                        startDate: this.dateValueArray[0],
                        endDate: this.dateValueArray[1],
                    }
                })
            }
        }
    },
    methods: {
        updateTimes() {
            Inertia.reload({
                data: {
                    startDate: this.dateValueArray[0],
                    endDate: this.dateValueArray[1],
                }
            })
        },
    }
}
</script>

<style scoped>

</style>
