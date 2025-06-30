<template>
    <div class="relative">
        <div class="my-auto w-full relative">
            <BaseInput
                id="room_search_input"
                v-model="room_search_query"
                :label="label"
                class="w-full"
                @focus="room_search_query = ''"
                @focusout="rooms = []"
            />

        </div>
        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="rooms.length > 0" class="absolute rounded-lg z-10 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200">
                    <div v-for="(room, index) in rooms" :key="index" class="flex items-center cursor-pointer">
                        <div>
                            <div class="flex-1 text-sm py-4" @click="selectRoom(room)">
                                <p class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                    <span class="ml-2 truncate">{{ room.name }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    name: "RoomSearch",
    mixins: [IconLib],
    components: {BaseInput, AlertComponent, TextInputComponent, TeamIconCollection},
    data() {
        return {
            room_search_query: '',
            rooms: []
        }
    },
    props: {
        label: {
            type: String,
            default: 'Search for Rooms'
        }
    },
    emits: ['room-selected'],
    methods: {
        selectRoom(selectedRoom) {
            this.$emit('room-selected', selectedRoom);
            this.room_search_query = '';
        },
    },
    watch: {
        room_search_query: {
            handler() {
                axios.post(route('room.search'),{
                    search: this.room_search_query,
                    wantsJson: true,
                }).then(response => {
                    this.rooms = response.data;
                });
            },
            deep: true
        }
    }
}
</script>
