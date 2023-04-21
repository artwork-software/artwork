<template>
    <div :class="[event.class, textStyle]" :style="{ width: width + 'px', height: totalHeight * zoomFactor + 'px' }" class="px-1 py-0.5 rounded-lg relative group">
        <div class="eventHeader  flex justify-between">
            <div class="flex items-center">
                <CalendarIcon v-if="new Date(event.start).toDateString() !== new Date(event.end).toDateString()" class="h-4 w-4 mr-1" :class="event.class"></CalendarIcon>
                {{ event.eventTypeName }}
            </div>
            <!-- Icons -->
            <div v-if="event.audience"
                 class="flex">
                <svg :class="event.class" xmlns="http://www.w3.org/2000/svg" width="22.37" height="11.23" viewBox="0 0 19.182 10.124">
                    <g id="Gruppe_555" data-name="Gruppe 555" transform="translate(0.128 0.128)">
                        <g id="Gruppe_549" data-name="Gruppe 549" transform="translate(0.372 0.372)">
                            <path id="Pfad_825" data-name="Pfad 825" d="M39.116,8.027c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z" transform="translate(-28.015 -4.977)" fill="none" stroke-miterlimit="10" stroke-width="1"/>
                            <path id="Pfad_826" data-name="Pfad 826" d="M29.852,27.618a3.323,3.323,0,0,1-1.5-.525,2.717,2.717,0,0,0-1.891,2.492v.62a.634.634,0,0,0,.671.593h6.265a.636.636,0,0,0,.673-.593v-.62a2.717,2.717,0,0,0-1.891-2.492,3.336,3.336,0,0,1-1.488.523Z" transform="translate(-21.17 -21.674)" fill="none" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1"/>
                            <path id="Pfad_827" data-name="Pfad 827" d="M64.568,3.008c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z" transform="translate(-48.181 -1)" fill="none"  stroke-miterlimit="10" stroke-width="1"/>
                            <path id="Pfad_828" data-name="Pfad 828" d="M56.324,25.779h4.6a.636.636,0,0,0,.673-.593v-.62a2.716,2.716,0,0,0-1.891-2.492,3.336,3.336,0,0,1-1.488.523l-.836,0a3.322,3.322,0,0,1-1.5-.525,3.021,3.021,0,0,0-1.345.955" transform="translate(-43.416 -17.697)" fill="none" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1"/>
                            <path id="Pfad_829" data-name="Pfad 829" d="M13.659,3.008c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z" transform="translate(-7.846 -1)" fill="none" stroke-miterlimit="10" stroke-width="1"/>
                            <path id="Pfad_830" data-name="Pfad 830" d="M8.137,23.127a3,3,0,0,0-1.419-1.053,3.337,3.337,0,0,1-1.487.523l-.836,0a3.323,3.323,0,0,1-1.5-.525A2.716,2.716,0,0,0,1,24.566v.62a.634.634,0,0,0,.671.593H6.189" transform="translate(-1 -17.697)" fill="none" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1"/>
                        </g>
                    </g>
                </svg>
            </div>
        </div>
        <!-- Time -->
        <div class="flex">
            <span v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                  class="items-center eventTime">{{ new Date(event.start).format("DD.MM.YYYY")}}, {{ new Date(event.start).formatTime("HH:mm") }} - {{
                    new Date(event.end).formatTime("HH:mm")
                }}
            </span>
            <span class="flex w-full" v-else>
                <span class="items-center eventTime">
                    <span class="text-error">
                        {{ new Date(event.start).toDateString() !== new Date(event.end).toDateString() ? '!' : ''}}
                        </span>
                    {{ new Date(event.start).format("DD.MM., HH:mm") }} - {{
                        new Date(event.end).format("DD.MM. HH:mm")
                    }}
                </span>
            </span>
        </div>
        <!-- User-Icons -->
        <div class="-ml-3 mb-0.5">
            <div v-if="event.projectLeaders && !project"
                 class="mt-1 ml-5 flex flex-wrap">
                <div class="flex flex-wrap flex-row -ml-1.5"
                     v-for="user in event.projectLeaders?.slice(0,3)">
                    <img :data-tooltip-target="user.id"
                         :class="'h-5 w-5'"
                         class="rounded-full object-cover"
                         :src="user.profile_photo_url"
                         alt=""/>
                    <UserTooltip :user="user"/>
                </div>
                <div v-if="event.projectLeaders.length >= 4" class="my-auto">
                    <Menu as="div" class="relative">
                            <MenuButton class="flex rounded-full focus:outline-none">
                                <div
                                    :class="'h-5 w-5'"
                                    class="-ml-1.5 flex-shrink-0 flex items-center my-auto font-semibold rounded-full shadow-sm text-white bg-black">
                                    <p class="">
                                        +{{ event.projectLeaders.length - 3 }}
                                    </p>
                                </div>
                            </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <MenuItem v-for="user in event.projectLeaders" v-slot="{ active }">
                                    <Link href="#"
                                          :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <img :class="'h-5 w-5'"
                                             class="rounded-full"
                                             :src="user.profile_photo_url"
                                             alt=""/>
                                        <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                        </span>
                                    </Link>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
            <div v-else-if="event.created_by"
                 class="mt-1 ml-3 flex flex-wrap w-full">
                <div class="-mr-3 flex flex-wrap flex-row">
                    <img :data-tooltip-target="event.created_by.id"
                         :class="'h-5 w-5'"
                         class="rounded-full object-cover"
                         :src="event.created_by.profile_photo_url"
                         alt=""/>
                    <UserTooltip :user="event.created_by"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon, CalendarIcon} from '@heroicons/vue/outline'
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";


export default {
    name: "SingleCalendarEvent",
    components: {Menu, MenuItem, MenuItems, MenuButton, UserTooltip, Button, PlusCircleIcon, CalendarIcon},
    props: ['event']
}
</script>

<style scoped>

.occupancy_option {
    background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuX0tudFciIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHdpZHRoPSIxNyIgaGVpZ2h0PSIxNyIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PGxpbmUgeDE9IjAiIHk9IjAiIHgyPSIwIiB5Mj0iMTciIHN0cm9rZT0iI0YzRjRGNiIgc3Ryb2tlLXdpZHRoPSI2Ii8+PC9wYXR0ZXJuPjwvZGVmcz4gPHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuX0tudFcpIiBvcGFjaXR5PSIxIi8+PC9zdmc+')
}

.eventType0 {
    background-color: #A7A6B115;
    stroke: #7F7E88;
    color: #7F7E88;
}

.eventType1 {
    background: #641a5415;
    stroke: #631D53;
    color: #631D53
}

.eventType2 {
    background: #da3f8715;
    stroke: #D84387;
    color: #D84387
}

.eventType3 {
    background: #eb7a3d15;
    stroke: #E97A45;
    color: #E97A45
}

.eventType4 {
    background: #f1b64015;
    stroke: #CB8913;
    color: #CB8913
}

.eventType5 {
    background: #86c55415;
    stroke: #648928;
    color: #648928
}

.eventType6 {
    background: #2eaa6315;
    stroke: #35A965;
    color: #35A965
}

.eventType7 {
    background: #3dc3cb15;
    stroke: #35ACB2;
    color: #35ACB2
}

.eventType8 {
    background: #168fc315;
    stroke: #2290C1;
    color: #2290C1
}

.eventType9 {
    background: #4d908e15;
    stroke: #50908E;
    color: #50908E
}
.eventType10 {
    background: #21485C15;
    stroke: #23485B;
    color: #23485B
}
</style>
