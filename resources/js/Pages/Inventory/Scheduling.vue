<template>
    <InventoryHeader :title="t('Inventory Scheduling')">
        <div class="mb-3">
            <InventoryFunctionBar :date-value="dateValue" @scroll-to-next="scrollToNext" @scroll-to-previous="scrollToPrevious" />
        </div>

        <div class="-ml-5">
            <div class="z-40" :style="{ '--dynamic-height': windowHeight + 'px' }">
                <div
                    @scroll="syncScrollShiftPlan"
                    ref="shiftPlan"
                    class="bg-white flex-grow"
                    :class="[
                        isFullscreen ? 'overflow-y-auto' : '',
                        showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',
                        ' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll'
                    ]"
                >
                    <Table>
                        <template #head>
                            <div class="stickyHeader">
                                <TableHead id="stickyTableHead" ref="stickyTableHead">
                                    <th class="z-100 relative" style="width:192px;"></th>
                                    <th
                                        v-for="day in days"
                                        :style="{ width: '200px' }"
                                        :id="day.full_day"
                                        class="z-20 h-14 py-3 border-r-4 border-secondaryHover truncate"
                                    >
                                        <div class="flex calendarRoomHeader font-semibold ml-4 mt-2">
                                            {{ day.day_string }} {{ day.full_day }}
                                            <span v-if="day.is_monday" class="text-[10px] font-normal ml-2">(KW{{ day.week_number }})</span>
                                        </div>
                                    </th>
                                </TableHead>
                            </div>
                        </template>
                        <template #body>
                            <TableBody>
                                <tr
                                    v-for="(room, index) in calendar"
                                    class="w-full h-full flex border-b border-dashed"
                                    :id="'roomHeight' + index"
                                >
                                    <th
                                        class="xsDark flex items-center w-48"
                                        :style="{ height: roomHeights[index] + 'px' }"
                                        :class="[
                                            index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover',
                                            isFullscreen || showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft'
                                        ]"
                                    >
                                        <Link href="#" class="flex font-semibold items-center ml-4">
                                            {{ room[days[0].full_day].roomName }}
                                        </Link>
                                    </th>
                                    <td
                                        v-for="day in days"
                                        :style="{ width: day.week_separator ? '40px' : '200px' }"
                                        class="overflow-y-auto cell border-r-2 border-dotted"
                                        :class="[day.is_weekend ? 'bg-backgroundGray' : 'bg-white']"
                                    >
                                        <div
                                            v-for="(events, index) in groupEventsInDayByProject(room[day.full_day]?.events.data)"
                                            class="mb-1"
                                        >
                                            <div class="bg-gray-300 py-1.5 px-2 rounded-t-lg text-sm mb-1">
                                                <span>
                                                    {{ index === 'null' ? t('No Project') : index }}
                                                </span>
                                            </div>
                                            <SingleEventInInventoryScheduling
                                                @update:selected-event-ids="removeEventFromSelectedIds"
                                                :selected-event-ids="selectedEvents"
                                                :multi-edit="multiEditMode"
                                                :day="day.full_day"
                                                v-for="event in events"
                                                :event="event"
                                                :is-last-event="checkIfLastEventInEventData(event, events)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </TableBody>
                        </template>
                    </Table>
                </div>
            </div>
            <div id="userOverview" class="w-full fixed bottom-0 z-40">
                <div class="flex justify-center overflow-y-scroll">
                    <div
                        @click="showCloseUserOverview"
                        :class="showUserOverview ? '' : 'fixed bottom-0'"
                        class="flex h-5 w-8 justify-center items-center cursor-pointer bg-artwork-navigation-background"
                    >
                        <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                            <IconChevronsDown class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                    <div
                        v-if="showUserOverview"
                        @mousedown="startResize"
                        :class="showUserOverview ? '' : 'fixed bottom-0 '"
                        class="flex h-5 w-8 justify-center items-center cursor-ns-resize bg-artwork-navigation-background"
                        :title="t('Hold and drag to change the size')"
                    >
                        <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                            <IconCaretUpDown class="h-3 w-6 text-gray-400" />
                        </div>
                    </div>
                </div>
                <div
                    v-show="showUserOverview"
                    @scroll="syncScrollUserOverview"
                    ref="userOverview"
                    class="relative w-full bg-artwork-navigation-background overflow-x-scroll z-30 overflow-y-scroll"
                    :style="showUserOverview ? { height: userOverviewHeight + 'px' } : { height: '20px' }"
                >
                    <div
                        class="flex items-center justify-between w-full fixed py-5 z-50 bg-artwork-navigation-background px-3"
                        :style="{ top: calculateTopPositionOfUserOverView }"
                    >
                        <Switch
                            @click="toggleMultiEditMode"
                            v-model="multiEditMode"
                            :class="[
                                multiEditMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200',
                                'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none'
                            ]"
                        >
                            <span class="sr-only">Use setting</span>
                            <span
                                :class="[
                                    multiEditMode ? 'translate-x-7' : 'translate-x-0',
                                    'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                ]"
                            >
                                <span
                                    :class="[
                                        multiEditMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in',
                                        'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity'
                                    ]"
                                    aria-hidden="true"
                                >
                                    <IconPencil stroke-width="1.5" class="w-5 h-5" />
                                </span>
                                <span
                                    :class="[
                                        multiEditMode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out',
                                        'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity'
                                    ]"
                                    aria-hidden="true"
                                >
                                    <IconPencil stroke-width="1.5" class="w-5 h-5" />
                                </span>
                            </span>
                        </Switch>
                    </div>
                    <div class="pt-16">
                        <table class="w-full text-white overflow-y-scroll">
                            <div class="w-full">
                                <tbody class="w-full pt-3" v-for="craft in crafts">
                                <SingleCraftInUserOverview :multi-edit="multiEditMode" :days="days" :craft="craft" />
                                </tbody>
                            </div>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="multiEditMode" class="pointer-events-none">
            <div class="fixed w-full h-24 z-50 bottom-0 -ml-9">
                <div class="flex items-center justify-center h-full gap-x-2">
                    <div>
                        <AddButtonSmall type="cancel" no-icon @click="toggleMultiEditMode" text="Abbrechen" class="text-xs pointer-events-auto" />
                    </div>
                    <div>
                        <AddButtonSmall
                            @click="openMultiEditModal"
                            :disabled="!itemIsSelectedForMultiEdit"
                            :text="$t('{0} Element(s) Book', [checkedItems.length])"
                            class="text-xs pointer-events-auto"
                        />
                    </div>
                </div>
            </div>
        </div>
        <MultiEditInventoryModal :selected-items="checkedItems" :events="selectedEventsForMultiEdit" v-if="showMultiEditModal" @closed="closeMultiEditModal" />

        <SideNotification v-if="errorMessagesMultiEdit" :text="errorMessagesMultiEdit" @close="errorMessagesMultiEdit = ''" />
    </InventoryHeader>
</template>

<script setup>
import {onMounted, ref, watch} from "vue";
import {Link, usePage} from "@inertiajs/vue3";
import {IconCaretUpDown, IconChevronsDown, IconPencil} from "@tabler/icons-vue";
import {Switch} from "@headlessui/vue";

import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";
import InventoryFunctionBar from "@/Components/FunctionBars/InventoryFunctionBar.vue";
import Table from "@/Components/Table/Table.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import SingleEventInInventoryScheduling from "@/Pages/Inventory/Components/SingleEventInInventoryScheduling.vue";
import SingleCraftInUserOverview from "@/Pages/Inventory/Components/SingleCraftInUserOverview.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import MultiEditInventoryModal from "@/Pages/Inventory/Components/MultiEditInventoryModal.vue";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import {useTranslation} from "@/Pages/Composeables/Translation.js";

const $t = useTranslation(),
    props = defineProps({
        dateValue: {
            type: Array,
            required: true
        },
        calendar: {
            type: Object,
            required: true
        },
        days: {
            type: Object,
            required: true
        },
        crafts: {
            type: Object,
            required: true
        }
    });

const isFullscreen = ref(false);
const showUserOverview = ref(true);
const windowHeight = ref(window.innerHeight);
const userOverviewHeight = ref((window.innerHeight / 2) + 50);
const userOverview = ref(null);
const shiftPlan = ref(null);
const currentDayOnView = ref(props.days ? props.days[0] : null);
const startY = ref(0);
const startHeight = ref(0);
const roomHeights = ref([]);
const multiEditMode = ref(false);
const itemIsSelectedForMultiEdit = ref(false);
const checkedItems = ref([]);
const selectedEvents = ref([]);
const showMultiEditModal = ref(false);
const selectedEventsForMultiEdit = ref([]);
const errorMessagesMultiEdit = ref('');

onMounted(() => {
    window.addEventListener('resize', updateHeight);
    updateHeight();
    calculateAllRoomHeights();
});

const closeMultiEditModal = () => {
    toggleMultiEditMode();
    showMultiEditModal.value = false;
};

const toggleMultiEditMode = () => {
    multiEditMode.value = !multiEditMode.value;

    if (!multiEditMode.value) {
        props.crafts.forEach((craft) => {
            craft.inventory_categories.forEach((category) => {
                category.groups.forEach((group) => {
                    group.items.forEach((item) => {
                        item.checked = false;
                    });
                });
            });
        });

        props.days.forEach((day) => {
            props.calendar.forEach((room) => {
                room[day.full_day].events.data.forEach((event) => {
                    event.checked = false;
                });
            });
        });

        checkedItems.value = [];
        selectedEvents.value = [];
        selectedEventsForMultiEdit.value = [];
        errorMessagesMultiEdit.value = '';
    }
};

const openMultiEditModal = () => {
    if (checkedItems.value.length === 0) {
        errorMessagesMultiEdit.value = $t('Please select at least one inventory item.')
        return;
    }

    if (selectedEvents.value.length === 0) {
        errorMessagesMultiEdit.value = $t('Please select at least one event.')
        return;
    }



    props.days.forEach((day) => {
        props.calendar.forEach((room) => {
            room[day.full_day].events.data.forEach((event) => {
                if (event.checked && selectedEvents.value.includes(event.id)) {
                    if (!selectedEventsForMultiEdit.value.find((selectedEvent) => selectedEvent.id === event.id)) {
                        selectedEventsForMultiEdit.value.push(event);
                    }
                }
            });
        });
    });
    errorMessagesMultiEdit.value = '';
    showMultiEditModal.value = true;
};

const checkIfLastEventInEventData = (event, eventData) => {
    return eventData.indexOf(event) === eventData.length - 1;
};

const groupEventsInDayByProject = (events) => {
    return events.reduce((acc, event) => {
        if (!acc[event.projectName]) {
            acc[event.projectName] = [];
        }
        acc[event.projectName].push(event);
        return acc;
    }, {});
};

const startResize = (event) => {
    event.preventDefault();
    startY.value = event.clientY;
    startHeight.value = userOverviewHeight.value;

    document.addEventListener('mousemove', resizing);
    document.addEventListener('mouseup', stopResize);
};

const resizing = (event) => {
    const currentY = event.clientY;
    const diff = startY.value - currentY;
    if (startHeight.value + diff < 100) {
        userOverviewHeight.value = 100;
        updateHeight();
        return;
    }

    if ((window.innerHeight - 160) - (startHeight.value + diff) < 160) {
        userOverviewHeight.value = (window.innerHeight - 160) - 250;
        updateHeight();
        return;
    }

    userOverviewHeight.value = startHeight.value + diff;
    updateHeight();
};

const stopResize = (event) => {
    event.preventDefault();
    document.removeEventListener('mousemove', resizing);
    document.removeEventListener('mouseup', stopResize);
};

const updateHeight = () => {
    if (!showUserOverview.value) {
        windowHeight.value = window.innerHeight - 250;
    } else {
        windowHeight.value = window.innerHeight - 270 - userOverviewHeight.value;
    }

    if (window.innerHeight - 160 < 400) {
        userOverviewHeight.value = window.innerHeight - 350;
    }

    if (userOverviewHeight.value < 100) {
        userOverviewHeight.value = 100;
    }
};

const showCloseUserOverview = () => {
    showUserOverview.value = !showUserOverview.value;
    updateHeight();
};

const calculateRoomHeightByIndex = (index) => {
    const roomHeight = document.getElementById('roomHeight' + index);
    return roomHeight ? roomHeight.offsetHeight : 0;
};

const syncScrollShiftPlan = (event) => {
    if (userOverview.value) {
        userOverview.value.scrollLeft = event.target.scrollLeft;

        const firstDay = document.getElementById(props.days[0].full_day)
        const scrollableContainer = shiftPlan.value; // Use the shiftPlan reference as the scrollable container
        const firstDayPosition = scrollableContainer.scrollLeft;
        const scrollPosition = scrollableContainer.scrollLeft;
        const dayIndex = Math.floor(scrollPosition / firstDay.offsetWidth);
        if (!props.days[dayIndex].week_separator) {
            currentDayOnView.value = props.days[dayIndex];
        } else {
            currentDayOnView.value = props.days[dayIndex + 1];
        }
    }
};

const syncScrollUserOverview = (event) => {
    if (shiftPlan.value) {
        shiftPlan.value.scrollLeft = event.target.scrollLeft;
    }
};

const calculateTopPositionOfUserOverView = () => {
    return showUserOverview.value ? userOverviewHeight.value + 'px' : '0';
};

const calculateAllRoomHeights = () => {
    roomHeights.value = props.calendar.map((_, index) => calculateRoomHeightByIndex(index));
};

const removeEventFromSelectedIds = (eventId) => {
    if (selectedEvents.value.includes(eventId)) {
        selectedEvents.value = selectedEvents.value.filter((id) => id !== eventId);
    } else {
        selectedEvents.value.push(eventId);
    }
};

const scrollToNext = () => {
    handleScroll('next');
};

const scrollToPrevious = () => {
    handleScroll('previous');
};

const handleScroll = (type) => {
    const mode = usePage().props.user.goto_mode;
    if (mode === 'day') {
        goToPeriod('day', type);
    } else if (mode === 'week') {
        goToPeriod('week', type);
    } else if (mode === 'month') {
        goToPeriod('month', type);
    }
};

const goToPeriod = (period, type) => {
    let nextIndex, newDay, offsetMultiplier;

    if (period === 'day') {
        const currentIndex = props.days.indexOf(currentDayOnView.value);
        nextIndex = type === 'next' ? currentIndex + 1 : currentIndex - 1;
        if (nextIndex >= 0 && nextIndex < props.days.length) {
            newDay = props.days[nextIndex];
            offsetMultiplier = nextIndex;
        }
    } else if (period === 'week') {
        const weekNumber = currentDayOnView.value.week_number + (type === 'next' ? 1 : -1);
        newDay = props.days.find(day => day.is_monday && day.week_number === weekNumber);
        offsetMultiplier = props.days.indexOf(newDay);
    } else if (period === 'month') {
        const monthNumber = currentDayOnView.value.month_number + (type === 'next' ? 1 : -1);
        newDay = props.days.find(day => day.is_first_day_of_month && day.month_number === monthNumber);
        offsetMultiplier = props.days.indexOf(newDay);
    }

    if (newDay) {
        const firstDay = document.getElementById(currentDayOnView.value.full_day);
        const scrollableContainer = shiftPlan.value;
        scrollableContainer.scrollLeft = firstDay.offsetWidth * offsetMultiplier;
    }
};


watch(
    () => props.crafts,
    (newCrafts) => {
        newCrafts.forEach((craft) => {
            craft.inventory_categories.forEach((category) => {
                category.groups.forEach((group) => {
                    group.items.forEach((item) => {
                        if (item.checked && !checkedItems.value.find((checkedItem) => checkedItem.id === item.id)) {
                            checkedItems.value.push({
                                id: item.id,
                                name: item.name,
                                craft: craft.name,
                                category: category.name,
                                group: group.name
                            });
                            itemIsSelectedForMultiEdit.value = true;
                        } else {
                            checkedItems.value = checkedItems.value.filter((checkedItem) => checkedItem.id !== item.id);
                            if (checkedItems.value.length === 0) {
                                itemIsSelectedForMultiEdit.value = false;
                            }
                        }
                    });
                });
            });
        });
    },
    { deep: true }
);
</script>

<style scoped>
.cell {
    overflow: overlay;
}

.stickyHeader {
    position: sticky;
    align-self: flex-start;
    z-index: 30;
    top: 0;
}

.stickyYAxis {
    position: sticky;
    align-self: flex-start;
    left: 60px;
    z-index: 22;
}

.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    left: 0;
    z-index: 22;
}
</style>
