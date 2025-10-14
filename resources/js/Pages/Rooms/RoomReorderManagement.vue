<template>
    <RoomSettingsHeader
        :title="$t('Sort rooms')"
        :description="$t('Drag and drop rooms to change their order in the system.')"
    >
        <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="rooms" @start="dragging=true" @end="dragging=false" @change="updateRoomOrder(rooms)">
            <template #item="{element}" :key="element.id">
                <div v-show="!element.temporary" class="flex group" @mouseover="showMenu = element.id" :key="element.id" @mouseout="showMenu = null">
                    <div class="flex bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                        <div class="flex w-full">
                            <div class="flex">
                                <IconDragDrop class="my-auto xsDark h-5 w-5 hidden group-hover:block"/>
                                <Link :href="route('rooms.show',{room: element.id})" class="ml-4 my-auto xsDark">
                                    {{ element.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>
    </RoomSettingsHeader>
</template>


<script>
import RoomSettingsHeader from "@/Pages/Areas/Components/RoomSettingsHeader.vue";
import draggable from "vuedraggable";
import {IconCopy, IconDotsVertical, IconEdit, IconTrash} from "@tabler/icons-vue";
import {Link} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "RoomReorderManagement",
    mixins: [IconLib],
    components: {RoomSettingsHeader, UserPopoverTooltip, IconEdit, Link, IconDotsVertical, IconCopy, IconTrash, draggable},
    props: [
        'rooms'
    ],
    data() {
        return {
            dragging: false,
            showMenu: null,
        }
    },
    methods: {
        updateRoomOrder(rooms) {
            rooms.map((room, index) => {
                room.position = index + 1
            })

            this.$inertia.post(route('rooms.order.new'), {
                rooms: rooms
            });
        }
    }
}
</script>


<style scoped>

</style>
