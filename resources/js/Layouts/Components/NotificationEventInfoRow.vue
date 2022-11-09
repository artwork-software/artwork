<template>
    <div class="flex w-full mt-1">
        <div class="flex w-full">
            <Link
                :href="route('rooms.show',{room: this.event.roomId})"
                class="text-buttonBlue text-xs cursor-pointer flex items-center"
                v-if="this.event.roomId">
                {{
                    this.rooms.filter(room => room.id === this.event.roomId)
                }} <p class="mx-1 xxsLight">|</p>
            </Link>
            <div class="flex xxsLight items-center">
                {{
                    this.eventTypes.filter(eventType => eventType.id === this.event.event_type_id)[0].name
                }}
                <div v-if="this.event.eventName">
                    , {{ this.event.eventName }}
                </div>
            </div>
            <Link
                :href="route('projects.show',{project: this.event.project.id, openTab: 'calendar'})"
                class="text-buttonBlue text-xs cursor-pointer flex items-center">
                <p class="mx-1 xxsLight">|</p>
                {{ this.event.project?.name }}
            </Link>
            <div class="xxsLight flex items-center">
                <p class="mx-1">|</p>
                {{ this.formatDate(this.event.start_time) }} -
                {{ this.formatDate(this.event.end_time) }}
            </div>
        </div>
    </div>
</template>

<script>
import Button from "@/Jetstream/Button";
import {XIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/inertia-vue3";

export default {
    name: "TagComponent",
    components: {Button, XIcon, Link},
    props: ['event', 'rooms', 'eventTypes'],
    methods: {
        formatDate(isoDate) {
            return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
        }
    },
}
</script>

<style scoped>

</style>
