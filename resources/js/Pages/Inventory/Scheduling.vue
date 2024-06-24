<template>
    <InventoryHeader :title="$t('Inventory Scheduling')">

        <div class="mb-3">
            <InventoryFunctionBar :date-value="dateValue" />
        </div>

       <div class="-ml-5">
           <div class="z-40" :style="{ '--dynamic-height': windowHeight + 'px' }">
               <div ref="calendarPlan" id="shiftPlan" class="bg-white flex-grow" :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll']">
                   <Table>
                       <template #head>
                           <div class="stickyHeader">
                               <TableHead id="stickyTableHead" ref="stickyTableHead">
                                   <th class="z-100 relative" style="width:192px;"></th>
                                   <th  v-for="day in days" :style="{width: day.week_separator ? '40px' : '200px'}" :id="day.full_day" class="z-20 h-14 py-3 border-r-4 border-secondaryHover truncate">
                                       <div class="flex calendarRoomHeader font-semibold ml-4 mt-2">
                                           {{ day.day_string }} {{ day.full_day }} <span v-if="day.is_monday" class="text-[10px] font-normal ml-2">(KW{{ day.week_number }})</span>
                                       </div>
                                   </th>
                               </TableHead>
                           </div>
                       </template>
                       <template #body>
                           <TableBody>
                               <tr v-for="(room,index) in calendar" class="w-full flex">
                                   <th class="xsDark flex items-center h-28 w-48"
                                       :class="[index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover', isFullscreen || showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                       <Link class="flex font-semibold items-center ml-4">
                                           {{ room[days[0].full_day].roomName }}
                                       </Link>
                                   </th>
                                   <td v-for="day in days" :style="{width: day.week_separator ? '40px' : '200px'}" class="overflow-y-auto cell border-r-2 border-dotted" :class="[day.is_weekend ? 'bg-backgroundGray' : 'bg-white']">
                                       <div v-for="(events, index) in groupEventsInDayByProject(room[day.full_day]?.events.data)" class="mb-1">
                                           <div class="bg-gray-200 py-1 px-2 rounded-t-lg text-sm mb-1">
                                               {{ index === 'null' ? 'No Project' : index }}
                                           </div>
                                          <SingleEventInInventoryScheduling v-for="event in events" :event="event" :is-last-event="checkIfLastEventInEventData(event, room[day.full_day]?.events.data)" />
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
                   <div  @click="showCloseUserOverview" :class="showUserOverview ? '' : 'fixed bottom-0'"
                         class="flex h-5 w-8 justify-center items-center cursor-pointer bg-artwork-navigation-background">
                       <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                           <svg xmlns="http://www.w3.org/2000/svg" width="14.123" height="6.519"
                                viewBox="0 0 14.123 6.519">
                               <g id="Gruppe_1608" data-name="Gruppe 1608"
                                  transform="translate(-275.125 870.166) rotate(-90)">
                                   <path id="Pfad_1313" data-name="Pfad 1313" d="M0,0,6.814,3.882,13.628,0"
                                         transform="translate(865.708 289) rotate(-90)" fill="none" stroke="#a7a6b1"
                                         stroke-width="1"/>
                                   <path id="Pfad_1314" data-name="Pfad 1314" d="M0,0,4.4,2.509,8.809,0"
                                         transform="translate(864.081 286.591) rotate(-90)" fill="none"
                                         stroke="#a7a6b1" stroke-width="1"/>
                               </g>
                           </svg>
                       </div>
                   </div>
                   <div v-if="showUserOverview" @mousedown="startResize" :class="showUserOverview ? '' : 'fixed bottom-0 '"
                        class="flex h-5 w-8 justify-center items-center cursor-ns-resize bg-artwork-navigation-background"
                        :title="$t('Hold and drag to change the size')">
                       <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                           <SelectorIcon class="h-3 w-6 text-gray-400" />
                       </div>
                   </div>
               </div>
               <div v-show="showUserOverview" ref="userOverview" class="relative w-full bg-artwork-navigation-background overflow-x-scroll z-30 overflow-y-scroll" :style="showUserOverview ? { height: userOverviewHeight + 'px'} : {height: 20 + 'px'}">
                   <div class="flex items-center justify-between w-full fixed py-5 z-50 bg-artwork-navigation-background px-3" :style="{top: calculateTopPositionOfUserOverView}">

                   </div>
               </div>
           </div>
       </div>


        <pre>
            {{ calendar }}
        </pre>
    </InventoryHeader>

</template>

<script setup>

import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";
import InventoryFunctionBar from "@/Components/FunctionBars/InventoryFunctionBar.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import Table from "@/Components/Table/Table.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import {Link} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import {SelectorIcon} from "@heroicons/vue/solid";
import SingleEventInInventoryScheduling from "@/Pages/Inventory/Components/SingleEventInInventoryScheduling.vue";

const props = defineProps({
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
})

const isFullscreen = ref(false);
const showUserOverview = ref(true);
const windowHeight = ref(window.innerHeight);
const userOverviewHeight = ref(515);
const userOverview = ref(null);
const shiftPlan = ref(null);
const currentDayOnView = ref([]);
const startY = ref(0);
const startHeight = ref(0);

onMounted(() => {
    window.addEventListener('resize', updateHeight);
    window.addEventListener('scroll', syncScrollShiftPlan);
    window.addEventListener('scroll', syncScrollUserOverview);
    updateHeight();
});

const checkIfLastEventInEventData = (event, eventData) => {
    return eventData.indexOf(event) === eventData.length - 1;
}

const groupEventsInDayByProject = (events) => {
    return events.reduce((acc, event) => {
        if (!acc[event.projectName]) {
            acc[event.projectName] = [];
        }
        acc[event.projectName].push(event);
        return acc;
    }, {});
}

const startResize = (event) => {
    event.preventDefault();
    startY.value = event.clientY;
    startHeight.value = userOverviewHeight.value;

    document.addEventListener('mousemove', resizing);
    document.addEventListener('mouseup', stopResize);
}
const resizing = (event) => {
    const currentY = event.clientY;
    const diff = startY.value - currentY;
    if (startHeight.value + diff < 100) {
        userOverviewHeight.value = 100;
        updateHeight()
        return;
    }

    if ((window.innerHeight - 160) - (startHeight.value + diff) < 160) {
        userOverviewHeight.value = (window.innerHeight - 160) - 200;
        updateHeight()
        return;
    }

    userOverviewHeight.value = startHeight.value + diff;
    updateHeight()
}
const stopResize = (event) => {
    event.preventDefault();
    document.removeEventListener('mousemove', resizing);
    document.removeEventListener('mouseup', stopResize);
}

const updateHeight = () => {
    if(!showUserOverview){
        windowHeight.value = (window.innerHeight - 250);
    } else {
        windowHeight.value = (window.innerHeight - 160) - userOverviewHeight.value;
    }

    if (window.innerHeight - 160 < 400) {
        userOverviewHeight.value = window.innerHeight - 300;
    }

    // check if userOverviewHeight is not smaller than 100
    if (userOverviewHeight.value < 100) {
        userOverviewHeight.value = 100;
    }
}

const showCloseUserOverview = () => {
    showUserOverview.value = !showUserOverview.value;
    updateHeight();
}

const syncScrollShiftPlan = (event) => {
    if (userOverview) {
        // Synchronize horizontal scrolling from shiftPlan to userOverview
        userOverview.scrollLeft = event.target.scrollLeft;

        // update the current day on view with the day that is currently visible check if day.week_separator is false
        // because we don't want to update the currentDayOnView with the week separator
        const firstDay = document.getElementById(props.days[0].full_day)
        const scrollableContainer = shiftPlan; // Use the shiftPlan reference as the scrollable container
        const firstDayPosition = scrollableContainer.scrollLeft;
        const scrollPosition = scrollableContainer.scrollLeft;
        const dayIndex = Math.floor(scrollPosition / firstDay.offsetWidth);
        if (props.days[dayIndex].week_separator) {
            currentDayOnView.value = props.days[dayIndex];
        } else {
            currentDayOnView.vaule = props.days[dayIndex + 1];
        }
    }
}

const syncScrollUserOverview = (event) => {
    if (shiftPlan) {
        // Synchronize horizontal scrolling from userOverview to shiftPlan
        shiftPlan.scrollLeft = event.target.scrollLeft;
    }
}

const calculateTopPositionOfUserOverView = () => {
    return showUserOverview.value ? userOverviewHeight.value + 'px' : '0';
}

</script>

<style scoped>

/* this only works in some browsers but is wanted by the client */
.cell {
    overflow: overlay;
}


.stickyHeader {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    display: block;
    top: 0px;
}

.stickyYAxis {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 60px;
    z-index: 22;
}

.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}
</style>