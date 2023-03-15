<template>
    <div :class="event.class" class="px-1 py-0.5">
        <div class="eventHeader w-full flex justify-between">
            <div>
                {{ event.title }}
            </div>
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
        <div>
            <span v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                  class="items-center eventTime">{{ new Date(event.start).formatTime("HH:mm") }} - {{
                    new Date(event.end).formatTime("HH:mm")
                }}
            </span>
            <span class="flex w-full" v-else>
                <span class="items-center eventTime">
                    {{ new Date(event.start).format("DD.MM. HH:mm") }} - {{
                        new Date(event.end).format("DD.MM. HH:mm")
                    }}
                </span>
            </span>
        </div>
    </div>
</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon} from '@heroicons/vue/outline'


export default {
    name: "AddButton",
    components: {Button, PlusCircleIcon},
    props: ['event']
}
</script>

<style scoped>

.occupancy_option {
    background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuX0tudFciIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHdpZHRoPSIxNyIgaGVpZ2h0PSIxNyIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PGxpbmUgeDE9IjAiIHk9IjAiIHgyPSIwIiB5Mj0iMTciIHN0cm9rZT0iI0YzRjRGNiIgc3Ryb2tlLXdpZHRoPSI2Ii8+PC9wYXR0ZXJuPjwvZGVmcz4gPHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuX0tudFcpIiBvcGFjaXR5PSIxIi8+PC9zdmc+')
}

.eventType0 {
    background: #e3eceb;
    stroke: #86c554;
}

.eventType1 {
    background: #641a54;
    stroke: #86c554;
}

.eventType2 {
    background: #da3f87;
    stroke: #86c554;
}

.eventType3 {
    background: #eb7a3d;
    stroke: #86c554;
}

.eventType4 {
    background: #f1b640;
    stroke: #86c554;
}

.eventType5 {
    background: #86c554;
    stroke: #86c554;
}

.eventType6 {
    background: #2eaa63;
    stroke: #86c554;
}

.eventType7 {
    background: #3dc3cb;
    stroke: #86c554;
}

.eventType8 {
    background: #168fc3;
    stroke: #86c554;
}

.eventType9 {
    background: #4d908e;
    stroke: #86c554;
}
.eventType10 {
    background: #21485c;
    stroke: #86c554;
}
</style>
