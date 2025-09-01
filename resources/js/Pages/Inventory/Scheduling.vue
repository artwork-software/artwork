<template>
    <InventoryHeader :title="$t('Inventory Scheduling')">
        <div class="mb-3">
            <InventoryFunctionBar :date-value="dateValue" @scroll-to-next="scrollToNext" @scroll-to-previous="scrollToPrevious" @nextTimeRange="nextTimeRange" @previousTimeRange="previousTimeRange" />
        </div>

        <div class="-ml-5">
            <div class="z-40" :style="{ '--dynamic-height': windowHeight + 'px' }">
                <div @scroll="syncScrollShiftPlan"
                    ref="shiftPlan"
                    class="bg-white flex-grow"
                    :class="[
                        isFullscreen ? 'overflow-y-auto' : '',
                        showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',
                        ' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll'
                    ]">
                    <Table>
                        <template #head>
                            <div class="stickyHeader">
                                <TableHead id="stickyTableHead" ref="stickyTableHead">
                                    <th class="z-100 relative" style="width:192px;"></th>
                                    <th v-for="day in days" :key="day.full_day"
                                        :style="{ width: '200px' }"
                                        :id="day.full_day"
                                        class="z-20 h-14 py-3 border-r-4 border-secondaryHover truncate">
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
                                <tr v-for="(room, index) in calendar"
                                    class="w-full h-full flex border-b border-dashed"
                                    :id="'roomHeight' + index" :key="'roomHeight' + index">
                                    <th class="xsDark flex items-center w-48"
                                        :style="{ height: roomHeights[index] + 'px' }"
                                        :class="[
                                            index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover',
                                            isFullscreen || showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft'
                                        ]">
                                        <Link href="#" class="flex font-semibold items-center ml-4">
                                            {{ foundedRoomName(room) }}
                                        </Link>
                                    </th>
                                    <td v-for="day in days"
                                        :style="{ width: day.week_separator ? '40px' : '200px' }"
                                        class="overflow-y-auto cell border-r-2 border-dotted"
                                        :class="[day.is_weekend ? 'bg-backgroundGray' : 'bg-white']" :key="day.full_day">
                                        <div v-for="(events, index) in groupEventsInDayByProject(room[day.full_day]?.events)" class="mb-1" :key="index">
                                            <div class="bg-gray-300 py-1.5 px-2 rounded-t-lg text-sm mb-1">
                                                <span>
                                                    {{ index === 'null' ? $t('No Project') : index }}
                                                </span>
                                            </div>
                                            <SingleEventInInventoryScheduling
                                                @update:selected-event-ids="removeEventFromSelectedIds"
                                                :selected-event-ids="selectedEvents"
                                                :multi-edit="multiEditMode"
                                                :day="day.full_day"
                                                v-for="event in events"
                                                :key="event.id"
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
                    <div @click="showCloseUserOverview"
                         :class="showUserOverview ? 'rounded-tl-lg' : 'fixed bottom-0 rounded-t-lg'"
                        class="flex h-5 w-8 justify-center items-center cursor-pointer bg-artwork-navigation-background">
                        <div :class="showUserOverview ? '' : 'fixed bottom-0 rotate-180 mb-0.5'">
                            <IconChevronsDown class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                    <div v-if="showUserOverview"
                        @mousedown="startResize"
                        :class="showUserOverview ? '' : 'fixed bottom-0 '"
                        class="flex h-5 w-8 justify-center items-center cursor-ns-resize bg-artwork-navigation-background rounded-tr-lg"
                        :title="$t('Hold and drag to change the size')">
                        <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                            <SelectorIcon class="h-3 w-6 text-gray-400"/>
                        </div>
                    </div>
                </div>
                <div v-show="showUserOverview"
                    @scroll="syncScrollUserOverview"
                    ref="userOverview"
                    class="relative w-full bg-artwork-navigation-background overflow-x-scroll z-30 overflow-y-scroll"
                    :style="showUserOverview ? { height: userOverviewHeight + 'px' } : { height: '20px' }">
                    <div class="flex items-center justify-between w-full fixed py-5 z-50 bg-artwork-navigation-background px-3"
                        :style="{ top: calculateTopPositionOfUserOverView }">
                        <Switch @click="toggleMultiEditMode" v-model="multiEditMode" :class="[multiEditMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                            <span :class="[multiEditMode ? 'translate-x-5' : 'translate-x-0', 'inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                <span :class="[multiEditMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-20', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent
                                        icon="IconPencil"
                                        icon-size="h-4 w-4"
                                        :tooltip-text="$t('Edit')"
                                        direction="right"
                                    />
                                </span>
                                <span :class="[multiEditMode ? 'opacity-100 duration-200 ease-in z-20' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent
                                        icon="IconPencil"
                                        icon-size="h-4 w-4"
                                        :tooltip-text="$t('Edit')"
                                        direction="right"
                                    />
                                </span>
                            </span>
                        </Switch>
                        <div v-if="multiEditMode" class="pointer-events-none">
                            <div class="w-full -mt-2.5">
                                <div class="flex items-center justify-center h-full gap-x-2">
                                    <div>
                                        <AddButtonSmall type="cancel" no-icon @click="toggleMultiEditMode" :text="$t('Cancel')" class="text-xs pointer-events-auto" />
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
                        <div class="mr-20 flex items-center gap-1">
                            <BaseFilter :only-icon="true" :gray-icon="true">
                                <div class="flex flex-col w-full gap-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-xs flex justify-between">{{ $t('Filter') }}</span>
                                        <span class="xxsLight cursor-pointer text-right w-full" @click="resetFilters()">
                                        {{ $t('Reset') }}
                                    </span>
                                    </div>
                                    <div class="text-xs border-b">{{ $t('Crafts') }}</div>
                                    <div class="flex flex-col gap-0">
                                        <BaseFilterCheckboxList :list="getCraftFilters()"
                                                                filter-name="inventory-scheduling-crafts-filter"
                                                                @change-filter-items="updateCraftFilters"/>
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs !border-0"
                                     @click="showAmountFilter = !showAmountFilter">
                                    <div>{{ $t('Quantity') }}</div>
                                    <IconChevronDown v-if="!showAmountFilter"
                                                     stroke-width="1.5" class="h-5 w-5 cursor-pointer"
                                                     aria-hidden="true"/>
                                    <IconChevronUp v-if="showAmountFilter"
                                                   stroke-width="1.5"
                                                   class="h-5 w-5 cursor-pointer"
                                                   aria-hidden="true"/>
                                </div>
                                <div>
                                <input v-if="showAmountFilter"
                                       v-model="amountFilterValue"
                                       type="number"
                                       class="input mt-1 h-7 text-xs text-black placeholder:text-gray-500"
                                       :placeholder="$t('Minimum available quantity...')"/>
                                </div>
                            </BaseFilter>
                            <input v-if="searchOpened"
                                   class="w-60 h-10 bg-artwork-navigation-background border text-white border-gray-500 rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary"
                                   type="text"
                                   aria-label="ajax search text input"
                                   :placeholder="$t('Search')"
                                   v-model="searchValue"
                            />
                            <ToolTipComponent
                                v-if="!searchOpened"
                                icon="IconSearch"
                                icon-size="h-5 w-5"
                                classes="text-white"
                                :tooltip-text="$t('Search')"
                                direction="left"
                                @click="toggleSearch"
                            />
                            <IconX v-else
                                   class="h-5 w-5 cursor-pointer hover:text-blue-500 text-white"
                                   @click="toggleSearch(true)"/>
                        </div>
                    </div>
                    <div class="pt-20">
                        <table class="w-full text-white overflow-y-scroll">
                            <div class="w-full">
                                <tbody class="w-full pt-3" v-for="craft in filteredCrafts" :key="craft.id">
                                    <SingleCraftInUserOverview :multi-edit="multiEditMode" :days="days" :craft="craft" />
                                </tbody>
                            </div>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <MultiEditInventoryModal :selected-items="checkedItems" :events="selectedEventsForMultiEdit" v-if="showMultiEditModal" @closed="closeMultiEditModal" />

        <SideNotification v-if="errorMessagesMultiEdit" :text="errorMessagesMultiEdit" @close="errorMessagesMultiEdit = ''" />
    </InventoryHeader>
</template>

<script setup>
import {onMounted, onUpdated, ref, watch} from "vue";
import {Link, router} from "@inertiajs/vue3";
import {
    IconChevronDown,
    IconChevronsDown,
    IconChevronUp,
    IconX
} from "@tabler/icons-vue";
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
import {useTranslation} from "@/Composeables/Translation.js";
import useCraftFilterAndSearch from "@/Pages/Inventory/Composeables/useCraftFilterAndSearch.js";
import {SelectorIcon} from "@heroicons/vue/solid";
import {usePermission} from "@/Composeables/Permission.js";
import {usePage} from "@inertiajs/vue3";
import debounce from "lodash.debounce";
import dayjs from "dayjs";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseFilterCheckboxList from "@/Layouts/Components/BaseFilterCheckboxList.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import Input from "@/Jetstream/Input.vue";
const { can, canAny, hasAdminRole } = usePermission(usePage().props);
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
        },
        craftFilters: {
            type: Array,
            required: true
        }
    });

const isFullscreen = ref(false);
const showUserOverview = ref(true);
const windowHeight = ref(window.innerHeight);
const userOverviewHeight = ref(usePage().props.auth.user.drawer_height);
const userOverview = ref(null);
const dateValueCopy = ref(props.dateValue);
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
const { searchValue, crafts, craftFilters, filteredCrafts, amountFilterValue } = useCraftFilterAndSearch();
const searchOpened = ref(false);
const showAmountFilter = ref(false );

const  setSearchData = () => {
    crafts.value = props.crafts;
    craftFilters.value = props.craftFilters;
};

const getCraftFilters = () => {
    return props.crafts.map((craft) => {
        return {
            id: craft.id,
            name: craft.name,
            checked: props.craftFilters.includes(craft.id)
        };
    });
};

const toggleSearch = (close = false) => {
    if (close) {
        searchValue.value = '';
    }
    searchOpened.value = !searchOpened.value;
}

onMounted(() => {
    window.addEventListener('resize', updateHeight);
    updateHeight();
    setSearchData();
    calculateAllRoomHeights();
});

onUpdated(() => {
    setSearchData();
});

const closeMultiEditModal = () => {
    toggleMultiEditMode();
    showMultiEditModal.value = false;
};

const toggleMultiEditMode = () => {
    multiEditMode.value = !multiEditMode.value;

    if (!multiEditMode.value) {
        filteredCrafts.value.forEach((craft) => {
            craft.value.filtered_inventory_categories.forEach((category) => {
                category.groups.forEach((group) => {
                    group.folders.forEach((folder) => {
                        folder.items.forEach((item) => {
                            item.checked = false;
                        });
                    });
                    group.items.forEach((item) => {
                        item.checked = false;
                    });
                });
            });
        });

        props.days.forEach((day) => {
            props.calendar.forEach((room) => {
                room[day.full_day].events.forEach((event) => {
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

const foundedRoomName = (room) => {
    const roomArray = Object.values(room);
    const foundRoom = roomArray.find((room) => room.roomName);
    return foundRoom ? foundRoom.roomName : null;
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
            room[day.full_day].events.forEach((event) => {
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

const nextTimeRange = () => {
    const dateDifference = calculateDateDifference();
    dateValueCopy.value[0] = dayjs(dateValueCopy.value[0]).add(dateDifference + 1, 'day').format('YYYY-MM-DD');
    dateValueCopy.value[1] = dayjs(dateValueCopy.value[1]).add(dateDifference + 1, 'day').format('YYYY-MM-DD');
    updateTimes();
}

const previousTimeRange = () => {
    const dateDifference = calculateDateDifference();
    dateValueCopy.value[0] = dayjs(dateValueCopy.value[0]).subtract(dateDifference + 1, 'day').format('YYYY-MM-DD');
    dateValueCopy.value[1] = dayjs(dateValueCopy.value[1]).subtract(dateDifference + 1, 'day').format('YYYY-MM-DD');
    updateTimes();
}

const calculateDateDifference = () => {
    const date1 = new Date(dateValueCopy.value[0]);
    const date2 = new Date(dateValueCopy.value[1]);
    const timeDifference = date2.getTime() - date1.getTime();
    return timeDifference / (1000 * 3600 * 24);
}

const updateTimes = () => {
    console.log('updateTimes', dateValueCopy.value[0], dateValueCopy.value[1]);
    router.patch(route('update.user.calendar.filter.dates', usePage().props.auth.user.id), {
        start_date: dateValueCopy.value[0],
        end_date: dateValueCopy.value[1],
    }, {
        preserveScroll: true,
        preserveState: true
    });
}


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

    if ((window.innerHeight - 110) - (startHeight.value + diff) < 110) {
        userOverviewHeight.value = (window.innerHeight - 110) - 250;
        updateHeight();
        return;
    }

    userOverviewHeight.value = startHeight.value + diff;
    updateHeight();
};

const stopResize = (event) => {
    event.preventDefault();
    debounceApplyUserOverviewHeight();
    document.removeEventListener('mousemove', resizing);
    document.removeEventListener('mouseup', stopResize);
};

const updateHeight = () => {
    if (!showUserOverview.value) {
        windowHeight.value = window.innerHeight - 250;
    } else {
        windowHeight.value = window.innerHeight - 220 - userOverviewHeight.value;
    }

    if (window.innerHeight - 110 < 400) {
        userOverviewHeight.value = window.innerHeight - 300;
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
    const mode = usePage().props.auth.user.goto_mode;
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

const applyUserOverviewHeight = () => {
    router.patch(route('user.update.userOverviewHeight', {user: usePage().props.auth.user.id}), {
        drawer_height: userOverviewHeight.value
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debounceApplyUserOverviewHeight = debounce(applyUserOverviewHeight, 500);

const updateCraftFilters = (args = {list: []}) => {
    router.patch(
        route('inventory-management.inventory.filter.update'),
        {
            filter: args.list
                .filter((arg) => arg.checked === true)
                .map((arg) => {
                    return {craftId: arg.id}
                })
        },
        {
            preserveScroll: true
        }
    );
};

const resetFilters = () => {
    searchValue.value = '';
    craftFilters.value = [];
    router.patch(
        route('inventory-management.inventory.filter.update'),
        {
            filter: []
        },
        {
            preserveScroll: true
        }
    );
    amountFilterValue.value = null;
};

watch(
    () => filteredCrafts,
    (newCrafts) => {
        newCrafts.value.forEach((craft) => {
            craft.value.filtered_inventory_categories.forEach((category) => {
                category.groups.forEach((group) => {
                    group.folders.forEach((folder) => {
                        folder.items.forEach((item) => {
                            if (!checkedItems.value.find((checkedItem) => checkedItem.id === item.id)) {
                                if (item.checked) {
                                    checkedItems.value.push({
                                        id: item.id,
                                        name: item.name,
                                        craft: craft.value.name,
                                        category: category.name,
                                        group: group.name
                                    });
                                    itemIsSelectedForMultiEdit.value = true;
                                }
                            } else {
                                if (!item.checked) {
                                    checkedItems.value = checkedItems.value.filter((checkedItem) => checkedItem.id !== item.id);
                                }
                                if (checkedItems.value.length === 0) {
                                    itemIsSelectedForMultiEdit.value = false;
                                }
                            }
                        })
                    });
                    group.items.forEach((item) => {
                        if (!checkedItems.value.find((checkedItem) => checkedItem.id === item.id)) {
                            if (item.checked) {
                                checkedItems.value.push({
                                    id: item.id,
                                    name: item.name,
                                    craft: craft.value.name,
                                    category: category.name,
                                    group: group.name
                                });
                                itemIsSelectedForMultiEdit.value = true;
                            }
                        } else {
                            if (!item.checked) {
                                checkedItems.value = checkedItems.value.filter((checkedItem) => checkedItem.id !== item.id);
                            }
                            if (checkedItems.value.length === 0) {
                                itemIsSelectedForMultiEdit.value = false;
                            }
                        }
                    });
                });
            });
        });
    },
    {deep: true}
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
