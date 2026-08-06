<template>
    <div class="flex w-full mt-1">
        <div class="flex w-full">

            <Link :href="route('rooms.show',{room: declinedRoomId})" class="flex items-center text-danger  text-xs cursor-pointer"
                 v-if="declinedRoomId">
                <p class="line-through">
                {{ this.rooms.find(room => room.id === declinedRoomId).name }}
                </p>
                <p class="mx-1 text-xs/[18px] text-text-subtle">|</p>
            </Link>
            <Link v-else
                :href="route('rooms.show',{room: this.event.room_id})"
                class="text-accent-600 text-xs cursor-pointer flex items-center"
                v-if="this.event.room_id">
                {{
                    this.rooms.find(room => room.id === this.event.room_id).name
                }} <p class="mx-1 text-xs/[18px] text-text-subtle">|</p>
            </Link>
            <div v-if="this.event.event_type_id" class="flex text-xs/[18px] text-text-subtle items-center">
                {{
                    this.eventTypes.find(eventType => eventType.id === this.event.event_type_id).name
                }}
                <div v-if="this.event.eventName">
                    , {{ this.event.eventName }}
                </div>
            </div>
            <Link v-if="this.event.project_id"
                :href="route('projects.tab',{project: this.event.project_id, projectTab: this.first_project_calendar_tab_id})"
                class="text-accent-600 text-xs cursor-pointer flex items-center">
                <p class="mx-1 text-xs/[18px] text-text-subtle">|</p>
                {{ this.projects.find(project => project.id === this.event.project_id)?.name }}
            </Link>
            <div v-if="event.start_time && event.end_time" class="text-xs/[18px] text-text-subtle flex items-center">
                <p class="mx-1">|</p>
                {{ this.event.start_time }} -
                {{ this.event.end_time }}
            </div>
        </div>
    </div>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import {Link} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "NotificationEventInfoRow",
    components: {Button, IconX, Link},
    props: ['event', 'rooms', 'eventTypes','projects','declinedRoomId', 'first_project_calendar_tab_id'],
    methods: {

    },
}
</script>

<style scoped>

</style>
