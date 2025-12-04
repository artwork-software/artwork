<template>
    <Popover v-slot="{ open }" class="!ring-0 flex items-center justify-center">
        <PopoverButton :class="open ? '' : 'text-opacity-90'" class="group inline-flex !ring-0 outline-0" @click="calculatePopoverPosition">
            <template v-if="useSlotInsteadOfIcon">
                <slot/>
            </template>
            <template v-else>
                <img v-if="user" :src="user.profile_photo_url" alt="" class="shrink-0 flex object-cover rounded-full !ring-0 focus:ring-0 " :class="['h-' + this.height, 'w-' + this.width, 'min-h-' + this.height, 'min-w-' + this.width, classes]">
                <PropertyIcon name="IconUserExclamation" v-else stroke-width="2" class="p-1 text-black shrink-0 flex object-cover rounded-full !ring-0 focus:ring-0 bg-gray-300" :class="['h-' + this.height, 'w-' + this.width, 'min-h-' + this.height, 'min-w-' + this.width, classes]"/>
            </template>
        </PopoverButton>
        <Teleport to="body">
            <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from" enter-to-class="transition-enter-to" leave-active-class="transition-leave-active" leave-from-class="transition-leave-from" leave-to-class="transition-leave-to">
                <PopoverPanel :class="[!dontTranslatePopoverPosition ? '-translate-x-1/2' : '', isWhite ? 'bg-white border border-gray-200' : 'bg-artwork-navigation-background']" class="absolute left-1/2 z-[10000] transform   rounded-lg shadow-xl px-4 py-4" :style="popoverStyle">
                    <div v-if="user" class="">
                        <div class="flex items-center gap-4">
                            <img class="min-h-14 min-w-14 h-14 w-14 object-cover rounded-full" :src="user.profile_photo_url" alt=""/>
                            <div class="">
                                <div class="font-black font-lexend  text-lg flex items-start gap-x-4 mb-2 border-b border-dashed border-gray-600" :class="isWhite ? 'text-gray-900' : 'text-white'">
                                    {{ user.first_name }} {{ user.last_name }}
                                    <div class="text-gray-300 text-xs my-1">
                                        {{ user.pronouns }}
                                    </div>
                                </div>

                                <div class="text-sm font-bold flex items-center gap-x-2" v-if="user.position" :class="isWhite ? 'text-gray-500' : 'text-gray-300'">
                                    <PropertyIcon name="IconMapPin" class="h-4 w-4" v-if="user.position"/>
                                    {{ user.position }}
                                </div>
                                <div class="text-sm font-bold flex items-center gap-x-2" :class="isWhite ? 'text-gray-500' : 'text-gray-300'" v-if="user.email && !user.email_private || $can('can view private user info') || hasAdminRole()">
                                    <PropertyIcon name="IconMail" class="h-4 w-4" v-if="user.email"/>
                                    {{ user.email }}
                                </div>
                                <div class="text-sm font-bold flex items-center gap-x-2" :class="isWhite ? 'text-gray-500' : 'text-gray-300'" v-if="user.phone_number && !user.phone_private || $can('can view private user info') || hasAdminRole()">
                                    <PropertyIcon name="IconDeviceMobile" class="h-4 w-4" v-if="user.phone_number"/>
                                    {{ user.phone_number }}
                                </div>
                                <div class="col-span-4 mt-2 break-all text-xs italic" :class="isWhite ? 'text-gray-500' : 'text-gray-300'" v-if="user.description">
                                    &bdquo;{{ user.description }}&rdquo;
                                </div>
                                <div class="col-span-4 mt-2 text-red-600 break-all text-xs italic " v-if="user.rejection_reason">
                                    &bdquo;{{ user.rejection_reason }}&rdquo;
                                </div>
                            </div>

                        </div>
                    </div>
                    <div v-else class="flex flex-row items-center ring-1 ring-black ring-opacity-5 text-white shadow-lg gap-x-3 py-3 px-5">
                        <PropertyIcon name="IconUserExclamation" class="h-14 w-14 rounded-full border-2 border-white"/>
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
import Permissions from "@/Mixins/Permissions.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "UserPopoverTooltip",
    mixins: [IconLib, Permissions],
    components: {
        PropertyIcon,
        Popover,
        PopoverButton,
        PopoverPanel
    },
    props: {
        user: {
            type: [Object, Array],
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
        },
        isWhite: {
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
