<template>
    <pre>
        {{ calendarData }}
    </pre>
    <pre>
        {{ rooms }}
    </pre>
    <div class="w-full flex flex-wrap">
        <table class="w-full flex flex-wrap">
            <thead class="w-full ml-24">
            <tr class=" w-full">
                <th v-for="(room,index) in rooms" class=" w-full bg-gray-300">
                    <div class="flex items-center w-64 ">
                        <div>
                            {{ room.name }}
                        </div>
                    </div>
                </th>
            </tr>
            </thead>
            <tbody class="flex w-full flex-wrap">
            <tr class="w-full h-52  flex" v-for="day in days">
                <th class="w-24">
                    {{ day }}
                </th>
                <td class="w-64 h-52 overflow-y-auto" v-for="room in calendarData">
                    <div v-for="event in room[day]">
                        {{event}}
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon} from '@heroicons/vue/outline'


export default {
    name: "AddButton",
    components: {Button, PlusCircleIcon},
    props: ['calendarData', 'rooms', 'days'],
}
</script>

<style scoped>
/* Custom Event Type Colors */

.occupancy_option {
    background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuX0tudFciIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHdpZHRoPSIxNyIgaGVpZ2h0PSIxNyIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PGxpbmUgeDE9IjAiIHk9IjAiIHgyPSIwIiB5Mj0iMTciIHN0cm9rZT0iI0YzRjRGNiIgc3Ryb2tlLXdpZHRoPSI2Ii8+PC9wYXR0ZXJuPjwvZGVmcz4gPHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuX0tudFcpIiBvcGFjaXR5PSIxIi8+PC9zdmc+')
}

.eventType0 {
    border-left: solid #A7A6B1;
    border-width: 1px 1px 1px 3px;
}

.eventType1 {
    border-left: solid #641a54;
    border-width: 1px 1px 1px 3px;
}

.eventType2 {
    border-left: solid #da3f87;
    border-width: 1px 1px 1px 3px;
}

.eventType3 {
    border-left: solid #eb7a3d;
    border-width: 1px 1px 1px 3px;
}

.eventType4 {
    border-left: solid #f1b640;
    border-width: 1px 1px 1px 3px;
}

.eventType5 {
    border-left: solid #86c554;
    border-width: 1px 1px 1px 3px;
}

.eventType6 {
    border-left: solid #2eaa63;
    border-width: 1px 1px 1px 3px;
}

.eventType7 {
    border-left: solid #3dc3cb;
    border-width: 1px 1px 1px 3px;
}

.eventType8 {
    border-left: solid #168fc3;
    border-width: 1px 1px 1px 3px;
}

.eventType9 {
    border-left: solid #4d908e;
    border-width: 1px 1px 1px 3px;
}

.eventType10 {
    border-left: solid #21485c;
    border-width: 1px 1px 1px 3px;
}
</style>
