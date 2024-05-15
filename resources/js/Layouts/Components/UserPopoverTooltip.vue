<template>
    <Popover v-slot="{ open }" class="relative !ring-0">
        <PopoverButton :class="open ? '' : 'text-opacity-90'" class="group inline-flex !ring-0 outline-0" @click="calculatePopoverPosition">
            <img :src="user.profile_photo_url" alt="" class="mx-auto shrink-0 flex object-cover rounded-full !ring-0 focus:ring-0" :class="['h-' + this.height, 'w-' + this.width, classes]">
        </PopoverButton>
        <Teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-1 opacity-0"
                        enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 opacity-100"
                        leave-to-class="translate-y-1 opacity-0">

                <PopoverPanel class="absolute left-1/2 z-50 mt-2 -translate-x-1/2 transform sm:px-0 bg-artwork-navigation-background ring-0 py-3 px-5" :style="popoverStyle">
                    <div class="shadow-lg ring-1 ring-black ring-opacity-5">
                        <div class="grid grid-cols-4 w-96">
                            <div class="col-span-1 shrink-0 ml-3 flex items-center justify-center">
                                <img class="mx-auto shrink-0 flex h-14 w-14 mt-2 object-cover rounded-full" :src="user.profile_photo_url" alt=""/>
                            </div>
                            <div class="col-span-3">
                                <div class="font-black font-lexend text-white text-lg">
                                    {{ user.first_name }} {{ user.last_name }}
                                </div>
                                <div class="-mt-1 text-white">
                                    <span v-if="user.business">
                                        {{ user.business }},
                                    </span>
                                    <span v-if="user.position">
                                        {{ user.position }}
                                    </span>
                                </div>
                                <div class="mt-2 text-white" v-if="user.email">
                                    {{ user.email }}
                                </div>
                                <div class="text-white" v-if="user.phone_number">
                                    {{ user.phone_number }}
                                </div>
                            </div>
                            <div class="col-span-4 mt-2 text-white max-w-96 break-all">
                                {{ user.description }}
                            </div>
                        </div>
                    </div>
                </PopoverPanel>
            </transition>
        </Teleport>
    </Popover>
</template>

<script>
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'

export default {
    name: "UserPopoverTooltip",
    components: {
        Popover,
        PopoverButton,
        PopoverPanel
    },
    props: {
        user: {
            type: Object,
            required: true
        },
        height: {
            type: String,
            default: '12'
        },
        width: {
            type: String,
            default: '12'
        },
        classes: {
            type: String,
            default: ''
        }
    },
    data(){
        return {
            popoverStyle: {
                top: '0px',
                left: '0px',
            },
        }
    },
    methods: {
        calculatePopoverPosition(event) {
            const { top, left, height, width } = event.target.getBoundingClientRect();
            this.popoverStyle.top = `${top + height}px`;
            this.popoverStyle.left = `${left + width / 2}px`;
        },
    },
}
</script>



<style scoped>

</style>
