<template>
    <RoomSettingsHeader
        :title="$t('Sort rooms')"
        :description="$t('Drag and drop rooms to change their order in the system.')"
    >
        <SettingsGuideBanner
            class="mb-6"
            storage-key="settings-guide.rooms.sort"
            title="How does this area work?"
            :paragraphs="[
                'The order here determines the order of the room rows in the calendar, the planning calendar and the shift plan.',
            ]"
        />
        <!-- Reihenfolge der Areale -->
        <template v-if="sortedAreas.length > 1">
            <h2 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text">{{ $t('Areas ') }}</h2>
            <p class="mt-1 mb-3 text-sm text-text-subtle">
                {{ $t('This order applies to the page "Rooms & areas" and to area lists in filters. The calendar follows the room order below.') }}
            </p>
            <draggable ghost-class="opacity-50" item-key="id" :list="sortedAreas" class="flex flex-wrap gap-2"
                       @start="dragging=true" @end="dragging=false" @change="updateAreaOrder">
                <template #item="{element}">
                    <div class="group flex items-center gap-x-2 rounded-lg bg-surface-sunken px-3 py-2" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'">
                        <PropertyIcon name="IconGripVertical" class="h-4 w-4 shrink-0 text-text-subtle group-hover:text-text" stroke-width="1.5"/>
                        <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full border border-border" :style="{ backgroundColor: element.color ?? '#000000' }"></span>
                        <span class="text-sm/5 font-semibold text-text">{{ element.name }}</span>
                    </div>
                </template>
            </draggable>
        </template>

        <!-- Reihenfolge der Räume -->
        <div class="mt-8 mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text">{{ $t('Rooms') }}</h2>
            <BaseUIButton
                v-if="sortedAreas.length > 1 && sortedRooms.length > 1"
                variant="secondary"
                icon="IconArrowsSort"
                label="Group by area"
                :disabled="roomsAreGrouped"
                :title="roomsAreGrouped ? $t('The rooms already follow the order of the areas.') : $t('Orders the rooms by area. The order within each area stays as it is.')"
                @click="groupRoomsByArea"
            />
        </div>
        <div v-if="!sortedRooms.some((room) => !room.temporary)"
             class="rounded-xl border border-dashed border-border-subtle bg-surface-sunken/40 p-4 text-sm text-text-subtle">
            {{ $t('No rooms created yet') }}
        </div>
        <draggable ghost-class="opacity-50" item-key="id" :list="sortedRooms" @start="dragging=true" @end="dragging=false" @change="updateRoomOrder">
            <template #item="{element}">
                <div v-show="!element.temporary" class="flex group">
                    <div class="flex items-center gap-x-3 bg-surface-sunken py-4 px-4 my-1 rounded-lg w-full" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                        <PropertyIcon name="IconGripVertical" class="h-5 w-5 shrink-0 text-text-subtle group-hover:text-text" stroke-width="1.5"/>
                        <Link :href="route('rooms.show',{room: element.id})" class="min-w-0 truncate text-sm/5 font-semibold text-text hover:underline">
                            {{ element.name }}
                        </Link>
                        <span v-if="element.area?.name" class="ml-auto flex shrink-0 items-center gap-1.5 text-xs text-text-subtle">
                            <span class="inline-block h-2 w-2 rounded-full border border-border" :style="{ backgroundColor: element.area.color ?? '#000000' }"></span>
                            {{ element.area.name }}
                        </span>
                    </div>
                </div>
            </template>
        </draggable>
    </RoomSettingsHeader>
</template>


<script>
import RoomSettingsHeader from "@/Pages/Areas/Components/RoomSettingsHeader.vue";
import draggable from "vuedraggable";
import {Link} from "@inertiajs/vue3";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {orderRoomsByArea, roomsFollowAreaOrder} from "@/Pages/Areas/Composables/areaListTools.js";

export default {
    name: "RoomReorderManagement",
    components: {
        BaseUIButton,
        SettingsGuideBanner,
        PropertyIcon,
        RoomSettingsHeader, Link, draggable},
    props: {
        rooms: {type: Array, default: () => []},
        areas: {type: Array, default: () => []},
    },
    data() {
        return {
            dragging: false,
            // eigene Listen: Ziehen ändert die Reihenfolge sofort, gespeichert wird im Hintergrund
            sortedRooms: [...this.rooms],
            sortedAreas: [...this.areas],
        }
    },
    computed: {
        roomsAreGrouped() {
            return roomsFollowAreaOrder(this.sortedRooms, this.sortedAreas);
        },
    },
    methods: {
        updateRoomOrder() {
            // nur ID und Position schicken; die Seite bleibt beim Ablegen an Ort und Stelle
            this.$inertia.post(route('rooms.order.new'), {
                rooms: this.sortedRooms.map((room, index) => ({id: room.id, position: index + 1}))
            }, {preserveScroll: true, preserveState: true});
        },
        updateAreaOrder() {
            this.$inertia.post(route('areas.order'), {
                areas: this.sortedAreas.map((area) => area.id)
            }, {preserveScroll: true, preserveState: true});
        },
        groupRoomsByArea() {
            this.sortedRooms = orderRoomsByArea(this.sortedRooms, this.sortedAreas);
            this.updateRoomOrder();
        },
    }
}
</script>


<style scoped>

</style>
