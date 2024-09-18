<template>
    <Popover v-slot="{ open }" class="!ring-0 -mb-1 -pb-1">
        <PopoverButton :class="open ? '' : 'text-opacity-90'"
                       class="group inline-flex !ring-0 outline-0 -mb-1"
                       @click="calculatePopoverPosition">
            <template v-if="useSlotInsteadOfIcon">
                <slot/>
            </template>
            <template v-else>
                <img v-if="user" :src="user.profile_photo_url" alt=""
                     class="shrink-0 flex object-cover rounded-full !ring-0 focus:ring-0 "
                     :class="['h-' + this.height, 'w-' + this.width, classes]">
                <IconUserExclamation v-else
                                     stroke-width="2"
                                     class="p-1 text-black shrink-0 flex object-cover rounded-full !ring-0 focus:ring-0 bg-gray-300"
                                     :class="['h-' + this.height, 'w-' + this.width, classes]"/>
            </template>
        </PopoverButton>
        <Teleport to="body">
            <transition enter-active-class="transition-enter-active"
                        enter-from-class="transition-enter-from"
                        enter-to-class="transition-enter-to"
                        leave-active-class="transition-leave-active"
                        leave-from-class="transition-leave-from"
                        leave-to-class="transition-leave-to">
                <PopoverPanel
                    :class="[!dontTranslatePopoverPosition ? '-translate-x-1/2' : '']"
                    class="absolute left-1/2 z-50 transform sm:px-0 bg-artwork-navigation-background ring-0 py-3 px-5"
                    :style="popoverStyle">
                    <div v-if="user" class="shadow-lg ring-1 ring-black ring-opacity-5">
                        <div class="grid grid-cols-4 w-96">
                            <div class="col-span-1 shrink-0 ml-3 flex items-center justify-center">
                                <img class="mx-auto shrink-0 flex h-14 w-14 mt-2 object-cover rounded-full"
                                     :src="user.profile_photo_url" alt=""/>
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
                    <div v-else class="flex flex-row items-center ring-1 ring-black ring-opacity-5 text-white shadow-lg gap-x-3 py-3 px-5">
                        <IconUserExclamation class="h-14 w-14 rounded-full border-2 border-white"/>
                        <div class="font-black font-lexend text-white text-lg">
                            {{ $t('Deleted user') }}
                        </div>
                    </div>
                </PopoverPanel>
            </transition>
        </Teleport>
    </Popover>
</template>

<script>
import {Popover, PopoverButton, PopoverPanel} from '@headlessui/vue'
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "UserPopoverTooltip",
    mixins: [IconLib],
    components: {
        Popover,
        PopoverButton,
        PopoverPanel
    },
    props: {
        user: {
            type: Object,
            required: false,
            default: null
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
        },
        useSlotInsteadOfIcon: {
            type: Boolean,
            default: false
        },
        dontTranslatePopoverPosition: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            popoverStyle: {
                top: '0px',
                left: '0px',
            },
        }
    },
    methods: {
        calculatePopoverPosition(event) {
            const {top, left, height, width} = event.target.getBoundingClientRect();

            this.popoverStyle.top = `${top + window.scrollY + height}px`;
            this.popoverStyle.left = `${left + width / 2}px`;
        },
    },
}
</script>
