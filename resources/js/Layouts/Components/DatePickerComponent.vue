<template>
    <div v-if="!project">
        <div class="flex items-center">
            <IconCalendar class="w-5 h-5 mr-2" @click="this.showDateRangePicker = !this.showDateRangePicker"/>
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
        {{$t('Project period')}}: {{new Date(dateValueArray[0]).format("DD.MM.YYYY")}} - {{new Date(dateValueArray[1]).format("DD.MM.YYYY")}}
    </div>
    <div v-if="hasError" class="text-error mt-1 mx-2">{{ errorMessage }}</div>

</template>

<script>

import VueTailwindDatepicker from 'vue-tailwind-datepicker'


import {ref} from "vue";
import {Inertia} from "@inertiajs/inertia";
import {CalendarIcon} from "@heroicons/vue/outline";
import Permissions from "@/mixins/Permissions.vue";
import IconLib from "@/mixins/IconLib.vue";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MMM'
})


export default {
    mixins: [Permissions, IconLib],
    name: "DatePickerComponent",
    components: {VueTailwindDatepicker, CalendarIcon},
    props: ['dateValueArray','project', 'is_shift_plan'],
    data() {
        return {
            dateValue: this.dateValueArray ? this.dateValueArray : [],
            datePickerOptions: {
                shortcuts: {
                    today: this.$t('Today'),
                    yesterday: this.$t('Yesterday'),
                    past: period => this.$t('Last {0} days', [period]),
                    currentMonth: this.$t('Current month'),
                    pastMonth: this.$t('Past month')
                },
                footer: {
                    apply: this.$t('Apply'),
                    cancel: this.$t('Cancel')
                }
            },
            dateValuePicker: this.dateValueArray ? this.dateValueArray: [],
            formatter: formatter,
            showDateRangePicker: false,
            refreshPage: false,
            //customShortcuts: customShortcuts,
            customShortcuts: null,
            errorMessage: '',
            hasError: false,

        }
    },
    computed: {

    },
    watch: {
        dateValuePicker: {
            handler() {
                this.showDateRangePicker = false;
                this.updateTimes()
            }
        }
    },
    mounted() {
        this.customShortcuts = () => {
            return [
                {
                    label: this.$t('Today'),
                    atClick: () => {
                        const date = new Date();
                        return [
                            new Date(),
                            new Date()
                        ];
                    }
                },
                {
                    label: this.$t('Current week'),
                    atClick: () => {
                        const today = new Date();
                        const firstDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)));
                        const lastDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1) + 6));

                        return [firstDayOfWeek, lastDayOfWeek];
                    }
                },
                {
                    label: this.$t('Current month'),
                    atClick: () => {
                        const today = new Date();
                        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                        return [firstDayOfMonth, lastDayOfMonth];
                    }
                },
                {
                    label: this.$t('Current year'),
                    atClick: () => {
                        const today = new Date();
                        const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
                        const lastDayOfYear = new Date(today.getFullYear(), 11, 31);

                        return [firstDayOfYear, lastDayOfYear];
                    }
                },
                {
                    label: this.$t('Next 30 days'),
                    atClick: () => {
                        const today = new Date();
                        const next30DaysStart = new Date(today.setDate(today.getDate() + 1));
                        const next30DaysEnd = new Date(today.setDate(today.getDate() + 29));

                        return [next30DaysStart, next30DaysEnd];
                    }
                },
                {
                    label: this.$t('Next 90 days'),
                    atClick: () => {
                        const today = new Date();
                        const next90DaysStart = new Date(today.setDate(today.getDate() + 1));
                        const next90DaysEnd = new Date(today.setDate(today.getDate() + 89));

                        return [next90DaysStart, next90DaysEnd];
                    }
                }
            ]}
    },
    methods: {
        updateTimes() {
            const startDate = new Date(this.dateValueArray[0]);
            const endDate = new Date(this.dateValueArray[1]);

            if (endDate < startDate) {
                this.errorMessage = this.$t('The end date must be after the start date.');
                this.hasError = true;
            } else {
                this.errorMessage = '';
                this.hasError = false;

                if(this.is_shift_plan){
                    Inertia.patch(route('update.user.shift.calendar.filter.dates', this.$page.props.user.id), {
                        start_date: startDate,
                        end_date: endDate,
                    }, {
                        preserveState: false,
                        preserveScroll: true,
                    })
                } else {
                    Inertia.patch(route('update.user.calendar.filter.dates', this.$page.props.user.id), {
                        start_date: startDate,
                        end_date: endDate,
                    }, {
                        preserveState: false,
                        preserveScroll: true,
                    })
                }


                // Perform the reload or other actions here
                /*Inertia.reload({
                    data: {
                        startDate: this.dateValueArray[0],
                        endDate: this.dateValueArray[1],
                    }
                });*/

            }
        },
    },
}
</script>

<style scoped>

</style>
