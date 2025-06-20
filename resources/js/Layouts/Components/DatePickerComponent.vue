<template>
    <div v-if="!project">
        <div class="flex items-center gap-x-2" id="datePicker">
            <ToolTipComponent direction="right" :tooltip-text="$t('Select time')" icon="IconCalendar" icon-size="h-5 w-5 mr-3" class="cursor-pointer" @click="this.showDateRangePicker = !this.showDateRangePicker"/>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 pointer-events-none left-1 xsDark flex items-center pl-3 bg-white z-40 h-8 top-1">
                    {{ startDateString }},
                </div>
                <!-- necessary to bind dateValueArray as v-model, otherwise dates are not updated correctly in user shift plans -->
                <input v-if="is_user_shift_plan === true"
                       v-model="dateValueArray[0]"
                       @change="this.updateTimes"
                       ref="startDate"
                       id="startDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Start"
                       class="border-gray-300 pl-10 inputMain xsDark placeholder-secondary shadow-sm disabled:border-none flex-grow rounded-lg min-w-40" />
                <input v-else
                       v-model="dateValue[0]"
                       @change="this.updateTimes"
                       ref="startDate"
                       id="startDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Start"
                       class="border-gray-300 pl-10 inputMain xsDark placeholder-secondary shadow-sm disabled:border-none flex-grow rounded-lg min-w-40" />
                <div class="absolute inset-y-0 right-1.5 flex items-center pl-3 bg-white z-40 h-8 top-1">
                    <IconCalendar class="h-5 w-5 text-artwork-buttons-context hidden" aria-hidden="true" />
                </div>
            </div>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-1 pointer-events-none xsDark flex items-center pl-3 bg-white z-40 h-8 top-1">
                     {{ endDateString}},
                </div>
                <!-- necessary to bind dateValueArray as v-model, otherwise dates are not updated correctly in user shift plans -->
                <input v-if="is_user_shift_plan === true"
                       v-model="dateValueArray[1]"
                       @change="this.updateTimes"
                       ref="endDate"
                       id="endDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Ende"
                       class="border-gray-300 pl-10 inputMain xsDark placeholder-secondary disabled:border-none flex-grow rounded-lg min-w-40" />
                <input v-else
                       v-model="dateValue[1]"
                       @change="this.updateTimes"
                       ref="endDate"
                       id="endDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Ende"
                       class="border-gray-300 pl-10 inputMain xsDark placeholder-secondary disabled:border-none flex-grow rounded-lg min-w-40" />
                    <div class="absolute inset-y-0 right-1.5 flex items-center pl-3 bg-white z-40 h-8 top-1">
                        <IconCalendar class="h-5 w-5 text-artwork-buttons-context hidden" aria-hidden="true" />
                    </div>
            </div>
        </div>
        <VueTailwindDatepicker class="absolute z-50" v-if="showDateRangePicker"
                               no-input
                               :shortcuts="customShortcuts"
                               separator=" - " :formatter="formatter"
                               :options="datePickerOptions" @update:modelValue="dateValue = $event" i18n="de"
                               v-model="dateValuePicker" id="datePicker">
        </VueTailwindDatepicker>
    </div>
    <div class="font-medium text-gray-900" v-else>
        {{ $t('Project period') }}: {{ new Date(dateValue[0]).format("DD.MM.YYYY") }} - {{ new Date(dateValue[1]).format("DD.MM.YYYY") }}
    </div>
    <div v-if="hasError" class="text-error mt-1 mx-2" :class="errorMessage.length > 0 ? 'mt-10' : ''" >{{ errorMessage }}</div>
</template>

<script>
import VueTailwindDatepicker from 'vue-tailwind-datepicker'
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MMM'
})

export default {
    mixins: [Permissions, IconLib],
    name: "DatePickerComponent",
    components: {ToolTipComponent, VueTailwindDatepicker},
    props: ['dateValueArray', 'project', 'is_shift_plan', 'is_user_shift_plan', 'is_inventory_article_planning'],
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
            dateValuePicker: this.dateValueArray ? this.dateValueArray : [],
            formatter: formatter,
            showDateRangePicker: false,
            refreshPage: false,
            customShortcuts: null,
            errorMessage: '',
            hasError: false,
            startDateString: '',
            endDateString: '',
        }
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
        this.removeDateIcons();
        this.startDateString = this.getDayOfWeek(new Date(this.dateValue[0])).replace('.', '');
        this.endDateString = this.getDayOfWeek(new Date(this.dateValue[1])).replace('.', '');
        document.addEventListener('click', (event) => {
            if (!event.target.closest('#datePicker') && this.showDateRangePicker) {
                this.showDateRangePicker = false;
            }
        });

        this.customShortcuts = () => {
            return [
                {
                    label: this.$t('Today'),
                    atClick: () => {
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
                },
                {
                    label: this.$t('Next 12 months'),
                    atClick: () => {
                        const today = new Date();
                        const next12MonthsStart = new Date(today.setDate(today.getDate() + 1));
                        const next12MonthsEnd = new Date(today.setDate(today.getDate() + 364));

                        return [next12MonthsStart, next12MonthsEnd];
                    }
                }
            ]
        }
    },
    methods: {
        getDayOfWeek(date) {
            const days = ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'];
            return days[date.getDay()];
        },
        removeDateIcons() {
            const startDateInput = this.$refs.startDate;
            const endDateInput = this.$refs.endDate;

            if (startDateInput) {
                startDateInput.style.webkitAppearance = 'none';
                startDateInput.style.mozAppearance = 'textfield';

                startDateInput.style.webkitCalendarPickerIndicator = 'none';
                startDateInput.style.webkitClearButton = 'none';
                startDateInput.style.webkitInnerSpinButton = 'none';
                startDateInput.style.webkitOuterSpinButton = 'none';
                startDateInput.style.mozFocusInner = 'none';
                startDateInput.style.mozFocusOuter = 'none';
                startDateInput.style.msClear = 'none';
            }

            if (endDateInput) {
                endDateInput.style.webkitAppearance = 'none';
                endDateInput.style.mozAppearance = 'textfield';
                endDateInput.style.webkitCalendarPickerIndicator = 'none';
                endDateInput.style.webkitClearButton = 'none';
                endDateInput.style.webkitInnerSpinButton = 'none';
                endDateInput.style.webkitOuterSpinButton = 'none';
                endDateInput.style.mozFocusInner = 'none';
                endDateInput.style.mozFocusOuter = 'none';
                endDateInput.style.msClear = 'none';

            }
        },
        toggleDateRangePicker() {
            this.showDateRangePicker = !this.showDateRangePicker;
        },
        updateTimes() {
            const startDate = new Date(this.dateValue[0]);
            const endDate = new Date(this.dateValue[1]);

            if (startDate?.getFullYear() < 1800 || endDate?.getFullYear() < 1800) {
                this.errorMessage = this.$t('Please select a valid date.');
                return;
            }

            if (endDate < startDate) {
                this.errorMessage = this.$t('The end date must be after the start date.');
                this.hasError = true;
            } else {
                this.errorMessage = '';
                this.hasError = false;

                if (this.is_shift_plan) {
                    router.patch(route('update.user.shift.calendar.filter.dates', this.$page.props.auth.user.id), {
                        start_date: startDate,
                        end_date: endDate,
                    }, {
                        preserveState: false,
                        preserveScroll: true,
                    });
                } else if (this.is_user_shift_plan) {
                    router.patch(route('update.user.worker.shift-plan.filters.update', this.$page.props.auth.user.id), {
                        start_date: startDate,
                        end_date: endDate,
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    });
                } else if (this.is_inventory_article_planning) {
                    router.patch(route('update.user.inventory.article-plan.filters.update', this.$page.props.auth.user.id), {
                        start_date: startDate,
                        end_date: endDate,
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    });
                } else {
                    router.patch(route('update.user.calendar.filter.dates', this.$page.props.auth.user.id), {
                        start_date: startDate,
                        end_date: endDate,
                    }, {
                        preserveState: false,
                        preserveScroll: true,
                    });
                }
            }
        },
    },
}
</script>

<style scoped>
.remove-date-icon::-webkit-calendar-picker-indicator {
    display: none;
}

.remove-date-icon::-webkit-clear-button {
    display: none;
}

.remove-date-icon::-webkit-inner-spin-button {
    display: none;
}

.remove-date-icon::-webkit-outer-spin-button {
    display: none;
}

.remove-date-icon::-moz-focus-inner {
    border: 0;
}

.remove-date-icon::-moz-focus-outer {
    border: 0;
}

.remove-date-icon::-ms-clear {
    display: none;
}

/* Firefox specific rules */
.remove-date-icon {
    -moz-appearance: textfield;
}


input[type="date"]::-webkit-calendar-picker-indicator {
    display: none;
}

input[type="date"]::-webkit-clear-button {
    display: none;
}

input[type="date"]::-webkit-inner-spin-button {
    display: none;
}

input[type="date"]::-webkit-outer-spin-button {
    display: none;
}

input[type="date"]::-moz-focus-inner {
    border: 0;
}

input[type="date"]::-moz-focus-outer {
    border: 0;
}

input[type="date"]::-ms-clear {
    display: none;
}

/* Firefox specific rules */
input[type="date"] {
    -moz-appearance: textfield;
}
</style>
