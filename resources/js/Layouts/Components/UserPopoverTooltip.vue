<template>
    <Popover v-slot="{ open }" class="!ring-0 flex items-center justify-center">
        <PopoverButton :class="open ? '' : 'text-opacity-90'" class="group inline-flex !ring-0 outline-0" @click="calculatePopoverPosition">
            <template v-if="useSlotInsteadOfIcon">
                <slot/>
            </template>
            <template v-else>
                <img v-if="user" :src="user.profile_photo_url" alt="" class="shrink-0 flex object-cover rounded-full !ring-0 " :class="['h-' + this.height, 'w-' + this.width, 'min-h-' + this.height, 'min-w-' + this.width, classes]">
                <PropertyIcon name="IconUserExclamation" v-else stroke-width="2" class="p-1 text-black shrink-0 flex object-cover rounded-full !ring-0 bg-border" :class="['h-' + this.height, 'w-' + this.width, 'min-h-' + this.height, 'min-w-' + this.width, classes]"/>
            </template>
        </PopoverButton>
        <Teleport to="body">
            <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from" enter-to-class="transition-enter-to" leave-active-class="transition-leave-active" leave-from-class="transition-leave-from" leave-to-class="transition-leave-to" @enter="clampPanelPosition">
                <PopoverPanel :class="[!dontTranslatePopoverPosition ? '-translate-x-1/2' : '', isWhite ? 'bg-white border border-border-subtle' : 'bg-surface-inverse']" class="absolute left-1/2 z-[10000] transform   rounded-lg shadow-xl px-4 py-4" :style="popoverStyle">
                    <div v-if="resolvedUser" class="">
                        <div class="flex items-center gap-4">
                            <img class="min-h-14 min-w-14 h-14 w-14 object-cover rounded-full" :src="resolvedUser.profile_photo_url" alt=""/>
                            <div class="">
                                <div class="font-black font-lexend  text-lg flex items-start gap-x-4 mb-2 border-b border-dashed" :class="isWhite ? 'text-text border-border' : 'text-text-inverse border-white/10'">
                                    <span :class="{'underline cursor-pointer': canViewUserInfo}" @click="goToUserInfo">{{ resolvedUser.first_name }} {{ resolvedUser.last_name }}</span>
                                    <div class="text-white/70 text-xs my-1">
                                        {{ resolvedUser.pronouns }}
                                    </div>
                                </div>

                                <div class="text-sm font-bold flex items-center gap-x-2" v-if="resolvedUser.position" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                    <PropertyIcon name="IconMapPin" class="h-4 w-4" v-if="resolvedUser.position"/>
                                    {{ resolvedUser.position }}
                                </div>
                                <div class="text-sm font-bold flex items-center gap-x-2" :class="isWhite ? 'text-text-subtle' : 'text-white/70'" v-if="resolvedUser.email && !resolvedUser.email_private || $can('can view private user info') || hasAdminRole()">
                                    <PropertyIcon name="IconMail" class="h-4 w-4" v-if="resolvedUser.email"/>
                                    {{ resolvedUser.email }}
                                </div>
                                <div class="text-sm font-bold flex items-center gap-x-2" :class="isWhite ? 'text-text-subtle' : 'text-white/70'" v-if="resolvedUser.phone_number && !resolvedUser.phone_private || $can('can view private user info') || hasAdminRole()">
                                    <PropertyIcon name="IconDeviceMobile" class="h-4 w-4" v-if="resolvedUser.phone_number"/>
                                    {{ resolvedUser.phone_number }}
                                </div>
                                <div class="col-span-4 mt-2 break-all text-xs italic" :class="isWhite ? 'text-text-subtle' : 'text-white/70'" v-if="resolvedUser.description">
                                    &bdquo;{{ resolvedUser.description }}&rdquo;
                                </div>
                                <div class="col-span-4 mt-2 text-danger break-all text-xs italic " v-if="resolvedUser.rejection_reason">
                                    &bdquo;{{ resolvedUser.rejection_reason }}&rdquo;
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
import {router, usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";
import {reactive} from "vue";
import axios from "axios";

// Modulweiter Cache: Tooltip-Details werden pro User nur einmal geladen,
// egal wie viele Popover-Instanzen (z.B. Kalender-Kacheln) denselben User zeigen.
const lazyUserDetailsCache = reactive(new Map());
const lazyUserDetailsPending = new Set();

export default {
    name: "UserPopoverTooltip",
    mixins: [IconLib, Permissions],
    setup() {
        const { can, hasAdminRole } = usePermission(usePage().props);
        return { can, hasAdminRole };
    },
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
        },
        autoFlipVertical: {
            type: Boolean,
            default: false
        },
        // Opt-in: user-Prop enthält nur Basisdaten (id, Name, Foto) — Kontaktdaten
        // werden erst beim Öffnen des Popovers nachgeladen und pro User gecacht.
        lazyLoad: {
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
            anchorTop: 0,
            anchorBottom: 0,
        }
    },
    computed: {
        canViewUserInfo() {
            return this.hasAdminRole() ||
                this.can('can manage workers') ||
                this.can('can view private user info');
        },
        resolvedUser() {
            if (!this.lazyLoad || !this.user?.id) {
                return this.user;
            }

            const details = lazyUserDetailsCache.get(this.user.id);
            if (details) {
                return {...this.user, ...details};
            }

            // Bis die Details da sind, keine Kontaktfelder aus dem Bulk-Payload zeigen —
            // dort fehlen die email_private/phone_private-Flags.
            const {email, phone_number, ...basicUser} = this.user;
            return basicUser;
        }
    },
    methods: {
        fetchLazyUserDetails() {
            if (!this.lazyLoad || !this.user?.id) {
                return;
            }

            const userId = this.user.id;
            if (lazyUserDetailsCache.has(userId) || lazyUserDetailsPending.has(userId)) {
                return;
            }

            lazyUserDetailsPending.add(userId);
            axios.get(route('user.tooltip.info', {user: userId}))
                .then((response) => lazyUserDetailsCache.set(userId, response.data))
                .catch(() => {})
                .finally(() => lazyUserDetailsPending.delete(userId));
        },
        calculatePopoverPosition(event) {
            this.fetchLazyUserDetails();
            const {top, left, height, width} = event.target.getBoundingClientRect();

            this.anchorTop = top + window.scrollY;
            this.anchorBottom = this.anchorTop + height;
            this.popoverStyle.top = `${this.anchorBottom}px`;
            this.popoverStyle.left = `${left + window.scrollX + width / 2}px`;
        },
        clampPanelPosition(el) {
            // Panel darf nicht über den Viewport-Rand hinausragen
            const margin = 8;
            const panelWidth = el.offsetWidth; // unabhängig von der scale-Transition
            const currentLeft = parseFloat(this.popoverStyle.left) || 0;

            // popoverStyle.left ist in Seiten-Koordinaten; ohne Translation = linke Kante,
            // mit -translate-x-1/2 = Panel-Mitte
            const offset = this.dontTranslatePopoverPosition ? 0 : panelWidth / 2;
            const minLeft = window.scrollX + margin + offset;
            const maxLeft = window.scrollX + window.innerWidth - margin - panelWidth + offset;

            const clampedLeft = Math.min(Math.max(currentLeft, minLeft), Math.max(minLeft, maxLeft));
            if (clampedLeft !== currentLeft) {
                this.popoverStyle.left = `${clampedLeft}px`;
            }

            // Opt-in: nach oben öffnen, wenn das Panel unten aus dem Viewport ragen würde
            if (this.autoFlipVertical) {
                const panelHeight = el.offsetHeight;
                const viewportBottom = window.scrollY + window.innerHeight - margin;

                if (this.anchorBottom + panelHeight > viewportBottom) {
                    const topAbove = this.anchorTop - panelHeight;
                    if (topAbove >= window.scrollY + margin) {
                        this.popoverStyle.top = `${topAbove}px`;
                    }
                }
            }
        },
        goToUserInfo() {
            if (this.canViewUserInfo && this.user?.id) {
                router.visit(route('user.edit.info', {user: this.user.id}));
            }
        },
    },
}
</script>
