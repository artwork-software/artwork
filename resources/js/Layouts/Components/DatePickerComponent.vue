<template>
    <div v-if="!project">
        <div class="flex items-center gap-x-2" id="datePicker">
            <VueDatePicker
                v-model="dateValuePicker"
                model-auto
                range
                multi-calendars
                :preset-dates="customShortcuts"
                :enable-time-picker="false"
                :teleport="true"
                auto-position="bottom"
                :format="format"
                :preview-format="'dd.MM.yyyy'"
                :cancelText="$t('Cancel')" :selectText="$t('Apply')"
                :locale="usePage().props.auth.user.language"
            >
                <template #trigger>
                    <ToolTipComponent
                        direction="right"
                        :tooltip-text="$t('Select time')"
                        icon="IconCalendar"
                        icon-size="h-5 w-5 mr-3"
                        class="cursor-pointer"
                    />
                </template>
                <template #preset-date-range-button="{ label, value }">
                  <span
                      role="button"
                      :tabindex="0"
                      @click="() => handleShortcut(value)"
                      @keyup.enter.prevent="() => handleShortcut(value)"
                      @keyup.space.prevent="() => handleShortcut(value)"
                  >
                    {{ label }}
                  </span>
                </template>
            </VueDatePicker>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 pointer-events-none left-1 xsDark flex items-center pl-3 bg-white z-40 h-8 top-[3px]">
                    {{ startDateString }},
                </div>
                <input v-if="is_user_shift_plan === true"
                       v-model="dateValue[0]"
                       @change="updateTimes"
                       ref="startDate"
                       id="startDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Start"
                       class="border-gray-300 pl-10 py-2 xsDark bg-white border shadow-sm disabled:border-none flex-grow rounded-lg min-w-40" />
                <input v-else
                       v-model="dateValue[0]"
                       @change="updateTimes"
                       ref="startDate"
                       id="startDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Start"
                       class="border-gray-300 pl-10 py-2 xsDark bg-white border shadow-sm disabled:border-none flex-grow rounded-lg min-w-40" />
                <div class="absolute inset-y-0 right-1 flex items-center pl-4 bg-white z-40 h-8 top-1">
                    <IconCalendar class="h-5 w-5 text-artwork-buttons-context hidden" aria-hidden="true" />
                </div>
            </div>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-1 pointer-events-none xsDark flex items-center pl-3 bg-white z-40 h-8 top-[3px]">
                     {{ endDateString }},
                </div>
                <input v-if="is_user_shift_plan === true"
                       v-model="dateValue[1]"
                       @change="updateTimes"
                       ref="endDate"
                       id="endDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Ende"
                       class="border-gray-300 pl-10 py-2 xsDark bg-white border shadow-sm disabled:border-none flex-grow rounded-lg min-w-40" />
                <input v-else
                       v-model="dateValue[1]"
                       @change="updateTimes"
                       ref="endDate"
                       id="endDate"
                       type="date"
                       :disabled="!!project"
                       placeholder="Ende"
                       class="border-gray-300 pl-10 py-2 xsDark bg-white border shadow-sm disabled:border-none flex-grow rounded-lg min-w-40" />
                <div class="absolute inset-y-0 right-1 flex items-center pl-4 bg-white z-40 h-8 top-1">
                    <IconCalendar class="h-5 w-5 text-artwork-buttons-context hidden" aria-hidden="true" />
                </div>
            </div>
        </div>
    </div>
    <div class="font-medium text-gray-900" v-else>
        {{ $t('Project period') }}: {{ new Date(dateValue[0]).format("DD.MM.YYYY") }} - {{ new Date(dateValue[1]).format("DD.MM.YYYY") }}
    </div>
    <transition name="fade" appear>
        <div class="pointer-events-none fixed z-100 inset-x-0 top-5 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8" v-show="hasError">
            <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                <component :is="IconAlertSquareRounded" class="size-5 text-yellow-400" aria-hidden="true" />
                <p class="text-sm/6 text-white"> {{ errorMessage }} </p>
                <button type="button" class="-m-1.5 flex-none p-1.5">
                    <span class="sr-only">Dismiss</span>
                    <component is="IconX" class="size-5 text-white" aria-hidden="true" @click="hasError = false" />
                </button>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import {useTranslation} from "@/Composeables/Translation.js";
import {IconAlertSquareRounded, IconCalendar} from '@tabler/icons-vue';
const $t = useTranslation()
// Props
const props = defineProps({
    dateValueArray: Array,
    project: [Object, Boolean],
    is_shift_plan: Boolean,
    is_user_shift_plan: Boolean,
    is_inventory_article_planning: Boolean,
    is_planning: {
        type: Boolean,
        default: false
    }
});

// Refs & State
const dateValue = ref(props.dateValueArray ? [...props.dateValueArray] : []);
const dateValuePicker = ref(props.dateValueArray ? [...props.dateValueArray] : []);
const showDateRangePicker = ref(false);
const errorMessage = ref('');
const hasError = ref(false);
const startDateString = ref('');
const endDateString = ref('');
const startDate = ref(null);
const endDate = ref(null);

// Formatter
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MMM'
});

// Shortcuts
const customShortcuts = [
    {
        label: $t('Today'),
        value: () => {
            const today = new Date();
            today.setHours(12, 0, 0, 0);
            return [new Date(today), new Date(today)];
        }
    },
    {
        label: $t('Current week'),
        value: () => {
            const today = new Date();
            const first = new Date(today);
            const last = new Date(today);
            const day = today.getDay();
            const offset = day === 0 ? -6 : 1;
            first.setDate(today.getDate() - day + offset);
            last.setDate(first.getDate() + 6);
            first.setHours(12, 0, 0, 0);
            last.setHours(12, 0, 0, 0);
            return [first, last];
        }
    },
    {
        label: $t('Current month'),
        value: () => {
            const today = new Date();
            const first = new Date(today.getFullYear(), today.getMonth(), 1, 12, 0, 0, 0);
            const last = new Date(today.getFullYear(), today.getMonth() + 1, 0, 12, 0, 0, 0);
            return [first, last];
        }
    },
    {
        label: $t('Current year'),
        value: () => {
            const today = new Date();
            const first = new Date(today.getFullYear(), 0, 1, 12, 0, 0, 0);
            const last = new Date(today.getFullYear(), 11, 31, 12, 0, 0, 0);
            return [first, last];
        }
    },
    {
        label: $t('Next 7 days'),
        value: () => {
            const start = new Date();
            const end = new Date();
            start.setDate(start.getDate() + 1);
            end.setDate(end.getDate() + 7);
            start.setHours(12, 0, 0, 0);
            end.setHours(12, 0, 0, 0);
            return [start, end];
        }
    },
    {
        label: $t('Next 14 days'),
        value: () => {
            const start = new Date();
            const end = new Date();
            start.setDate(start.getDate() + 1);
            end.setDate(end.getDate() + 14);
            start.setHours(12, 0, 0, 0);
            end.setHours(12, 0, 0, 0);
            return [start, end];
        }
    },
    {
        label: $t('Next 30 days'),
        value: () => {
            const start = new Date();
            const end = new Date();
            start.setDate(start.getDate() + 1);
            end.setDate(end.getDate() + 30);
            start.setHours(12, 0, 0, 0);
            end.setHours(12, 0, 0, 0);
            return [start, end];
        }
    },
    {
        label: $t('Next 90 days'),
        value: () => {
            const start = new Date();
            const end = new Date();
            start.setDate(start.getDate() + 1);
            end.setDate(end.getDate() + 90);
            start.setHours(12, 0, 0, 0);
            end.setHours(12, 0, 0, 0);
            return [start, end];
        }
    },
    {
        label: $t('Next 12 months'),
        value: () => {
            const start = new Date();
            const end = new Date();
            start.setDate(start.getDate() + 1);
            end.setFullYear(end.getFullYear() + 1);
            end.setDate(end.getDate() - 1);
            start.setHours(12, 0, 0, 0);
            end.setHours(12, 0, 0, 0);
            return [start, end];
        }
    },
];

// Computed
const formattedStartDate = computed({
    get() {
        return dateValue.value.length > 0 && dateValue.value[0] instanceof Date
            ? dateValue.value[0].toISOString().slice(0, 10)
            : '';
    },
    set(value) {
        const date = value ? new Date(value + 'T12:00:00') : null;
        if (dateValue.value.length > 0) {
            dateValue.value[0] = date;
        } else {
            dateValue.value[0] = date;
            dateValue.value.push(null);
        }
    }
});
const formattedEndDate = computed({
    get() {
        return dateValue.value.length > 1 && dateValue.value[1] instanceof Date
            ? dateValue.value[1].toISOString().slice(0, 10)
            : '';
    },
    set(value) {
        const date = value ? new Date(value + 'T12:00:00') : null;
        if (dateValue.value.length > 1) {
            dateValue.value[1] = date;
        } else {
            if (dateValue.value.length === 0) dateValue.value.push(null);
            dateValue.value[1] = date;
        }
    }
});

// Methoden
function getDayOfWeek(date) {
    const days = ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'];
    return days[date.getDay()];
}

function removeDateIcons() {
    if (startDate.value) {
        startDate.value.style.webkitAppearance = 'none';
        startDate.value.style.mozAppearance = 'textfield';
        startDate.value.style.webkitCalendarPickerIndicator = 'none';
        startDate.value.style.webkitClearButton = 'none';
        startDate.value.style.webkitInnerSpinButton = 'none';
        startDate.value.style.webkitOuterSpinButton = 'none';
        startDate.value.style.mozFocusInner = 'none';
        startDate.value.style.mozFocusOuter = 'none';
        startDate.value.style.msClear = 'none';
    }
    if (endDate.value) {
        endDate.value.style.webkitAppearance = 'none';
        endDate.value.style.mozAppearance = 'textfield';
        endDate.value.style.webkitCalendarPickerIndicator = 'none';
        endDate.value.style.webkitClearButton = 'none';
        endDate.value.style.webkitInnerSpinButton = 'none';
        endDate.value.style.webkitOuterSpinButton = 'none';
        endDate.value.style.mozFocusInner = 'none';
        endDate.value.style.mozFocusOuter = 'none';
        endDate.value.style.msClear = 'none';
    }
}

function handleShortcut(value) {
    dateValue.value = typeof value === 'function' ? value() : value;
    updateTimes();
}

function format(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function updateTimes() {
    const startDateObj = new Date(dateValue.value[0]);
    const endDateObj = new Date(dateValue.value[1]);

    if (startDateObj?.getFullYear() < 1800 || endDateObj?.getFullYear() < 1800) {
        errorMessage.value = $t('Please select a valid date.');
        return;
    }

    if (endDateObj < startDateObj) {
        errorMessage.value = $t('The date only changes when the end date is the same date or later than the start date.');
        hasError.value = true;
    } else {
        errorMessage.value = '';
        hasError.value = false;

        const userId = usePage().props.auth.user.id;
        if (props.is_shift_plan) {
            router.patch(route('update.user.shift.calendar.filter.dates', userId), {
                start_date: startDateObj,
                end_date: endDateObj,
            }, {
                preserveState: false,
                preserveScroll: true,
            });
        } else if (props.is_user_shift_plan) {
            router.patch(route('update.user.worker.shift-plan.filters.update', userId), {
                start_date: startDateObj,
                end_date: endDateObj,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        } else if (props.is_inventory_article_planning) {
            router.patch(route('update.user.inventory.article-plan.filters.update', userId), {
                start_date: startDateObj,
                end_date: endDateObj,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        } else {
            router.patch(route('update.user.calendar.filter.dates', userId), {
                start_date: startDateObj,
                end_date: endDateObj,
                isPlanning: props.is_planning,
            }, {
                preserveState: false,
                preserveScroll: true,
            });
        }
    }
}

// Watcher
watch(dateValuePicker, () => {
    dateValue.value[0] = format(dateValuePicker.value[0]);
    dateValue.value[1] = format(dateValuePicker.value[1]);
    updateTimes();
});

// Lifecycle
onMounted(() => {
    removeDateIcons();
    startDateString.value = getDayOfWeek(new Date(dateValue.value[0])).replace('.', '');
    endDateString.value = getDayOfWeek(new Date(dateValue.value[1])).replace('.', '');
    document.addEventListener('click', (event) => {
        if (!event.target.closest('#datePicker') && showDateRangePicker.value) {
            showDateRangePicker.value = false;
        }
    });
});
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
