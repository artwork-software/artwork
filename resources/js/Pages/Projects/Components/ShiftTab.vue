<template>
    <div class="flex justify-between items-center">
        <div>
            <SwitchGroup as="div" class="flex items-center">
                <Switch v-model="enabled" :class="[enabled ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                    <span aria-hidden="true" :class="[enabled ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="font-medium text-gray-900">Festgeschrieben</span>
                </SwitchLabel>
            </SwitchGroup>
        </div>
        <div>
            <div @click="userWindow = !userWindow">
                <svg xmlns="http://www.w3.org/2000/svg" width="24.162" height="17.536" viewBox="0 0 24.162 17.536">
                    <g id="public" transform="translate(-6.127 0.378)">
                        <g id="Gruppe_549" data-name="Gruppe 549" transform="translate(6.877 0.372)">
                            <path id="Pfad_825" data-name="Pfad 825" d="M42.16,9.549c0,.076,0,.149-.006.224a3.531,3.531,0,0,1-7.044.06c-.009-.095-.013-.189-.013-.284a3.531,3.531,0,0,1,7.063,0Z" transform="translate(-31.943 -4.187)" fill="none" stroke="#27233c" stroke-miterlimit="10" stroke-width="1.5"/>
                            <path id="Pfad_826" data-name="Pfad 826" d="M32.424,28.016a5.84,5.84,0,0,1-2.644-.923,4.775,4.775,0,0,0-3.323,4.38v1.09A1.114,1.114,0,0,0,27.636,33.6h11.01a1.118,1.118,0,0,0,1.183-1.042v-1.09a4.775,4.775,0,0,0-3.323-4.38,5.863,5.863,0,0,1-2.615.92Z" transform="translate(-26.457 -17.569)" fill="none" stroke="#27233c" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1.5"/>
                            <path id="Pfad_827" data-name="Pfad 827" d="M67.612,4.53c0,.076,0,.149-.006.224a3.531,3.531,0,0,1-7.044.06c-.009-.095-.013-.189-.013-.284a3.531,3.531,0,0,1,7.063,0Z" transform="translate(-48.105 -1)" fill="none" stroke="#27233c" stroke-miterlimit="10" stroke-width="1.5"/>
                            <path id="Pfad_828" data-name="Pfad 828" d="M57.679,28.586h8.087a1.118,1.118,0,0,0,1.183-1.042v-1.09a4.774,4.774,0,0,0-3.323-4.379,5.863,5.863,0,0,1-2.615.92l-1.468,0a5.838,5.838,0,0,1-2.644-.923,5.309,5.309,0,0,0-2.364,1.678" transform="translate(-44.286 -14.382)" fill="none" stroke="#27233c" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1.5"/>
                        </g>
                    </g>
                </svg>
            </div>

            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95">
                <div class="z-40 origin-top-right absolute right-10 px-4 py-2 w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none" v-show="userWindow"  ref="containerRef">

                    <div class="flex items-center justify-between">
                        <div>

                        </div>
                        <div>
                            <XIcon class="h-6 w-6 text-white" @click="userWindow = !userWindow" />
                        </div>
                    </div>

                    <div  @mousedown="preventContainerDrag">
                        <div class="" v-for="user in users" >
                            <DragElement :item="user" />
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
    <div class="mt-5">
        <SingleShiftEvent v-for="event in eventsWithRelevant" :crafts="crafts" :event="event"/>
    </div>
</template>
<script>
import {defineComponent} from 'vue'
import {Menu, MenuButton, MenuItem, MenuItems, Switch, SwitchGroup, SwitchLabel} from '@headlessui/vue'
import {DuplicateIcon, PencilAltIcon} from "@heroicons/vue/outline";
import {DotsVerticalIcon, TrashIcon, XIcon} from "@heroicons/vue/solid";
import SingleShiftEvent from "@/Pages/Projects/Components/SingleShiftEvent.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
export default defineComponent({
    name: "ShiftTab",
    props: ['eventsWithRelevant', 'crafts', 'users'],
    components: {
        DragElement,
        SingleShiftEvent,
        PencilAltIcon, TrashIcon, DuplicateIcon, DotsVerticalIcon,
        Switch, SwitchGroup, SwitchLabel, Menu, MenuItems, MenuItem, MenuButton,
        XIcon
    },
    data(){
        return {
            enabled: false,
            userWindow: false,
            top: 0,
            left: 0,
        }
    },
    mounted() {
        this.makeContainerDraggable();
    },
    methods: {
        makeContainerDraggable() {
            const container = this.$refs.containerRef;
            let isDragging = false;
            let offsetX = 0;
            let offsetY = 0;

            container.addEventListener('mousedown', (event) => {
                isDragging = true;
                offsetX = event.clientX - container.offsetLeft;
                offsetY = event.clientY - container.offsetTop;
            });

            document.addEventListener('mousemove', (event) => {
                if (isDragging) {
                    container.style.left = `${event.clientX - offsetX}px`;
                    container.style.top = `${event.clientY - offsetY}px`;
                }
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
            });
        },
        preventContainerDrag(event) {
            event.stopPropagation();
        },
    },
})
</script>


<style scoped>

</style>
