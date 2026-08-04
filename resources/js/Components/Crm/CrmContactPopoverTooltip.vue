<template>
    <Popover v-slot="{ open }" class="!ring-0 flex items-center justify-center">
        <PopoverButton :class="open ? '' : 'text-opacity-90'" class="group inline-flex !ring-0 outline-0" @click="calculatePopoverPosition">
            <!-- CRM-Avatar: typ-farbiger Ring + Badge kennzeichnen den Kontakt als CRM-Eintrag -->
            <span class="relative inline-flex shrink-0" :class="['h-' + height, 'w-' + width, 'min-h-' + height, 'min-w-' + width]">
                <img
                    :src="contact.profile_photo_url"
                    :alt="contact.display_name"
                    class="h-full w-full object-cover rounded-full ring-2"
                    :class="classes"
                    :style="{ '--tw-ring-color': typeColor }"
                />
                <span
                    class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full ring-2 ring-white"
                    :style="{ backgroundColor: typeColor }"
                    :title="contact.contact_type?.name"
                >
                    <PropertyIcon :name="badgeIcon" class="h-2.5 w-2.5 text-white" stroke-width="2.5"/>
                </span>
            </span>
        </PopoverButton>
        <Teleport to="body">
            <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from" enter-to-class="transition-enter-to" leave-active-class="transition-leave-active" leave-from-class="transition-leave-from" leave-to-class="transition-leave-to" @enter="clampPanelPosition">
                <PopoverPanel :class="[!dontTranslatePopoverPosition ? '-translate-x-1/2' : '', isWhite ? 'bg-white border border-border-subtle' : 'bg-artwork-navigation-background']" class="absolute left-1/2 z-[10000] transform rounded-lg shadow-xl px-4 py-4" :style="popoverStyle">
                    <div class="flex items-center gap-4">
                        <span class="relative inline-flex shrink-0">
                            <img class="min-h-14 min-w-14 h-14 w-14 object-cover rounded-full ring-2" :style="{ '--tw-ring-color': typeColor }" :src="resolvedContact.profile_photo_url" alt=""/>
                            <span class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white" :style="{ backgroundColor: typeColor }">
                                <PropertyIcon :name="badgeIcon" class="h-3 w-3 text-white" stroke-width="2.5"/>
                            </span>
                        </span>
                        <div>
                            <div class="font-black font-lexend text-lg flex items-center gap-x-2 mb-2 border-b border-dashed border-border-strong" :class="isWhite ? 'text-text' : 'text-text-inverse'">
                                <span>{{ resolvedContact.display_name }}</span>
                                <button
                                    v-if="canViewCrm"
                                    type="button"
                                    class="shrink-0 rounded-full p-0.5 transition"
                                    :class="isWhite ? 'text-text-subtle hover:text-text' : 'text-white/70 hover:text-white'"
                                    :title="$t('Open CRM contact')"
                                    @click="goToCrmContact"
                                >
                                    <PropertyIcon name="IconExternalLink" class="h-4 w-4"/>
                                </button>
                            </div>
                            <div v-if="resolvedContact.contact_type" class="mb-1">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                      :style="{ backgroundColor: typeColor + '26', color: isWhite ? typeColor : '#fff' }">
                                    <PropertyIcon v-if="resolvedContact.contact_type.icon" :name="resolvedContact.contact_type.icon" class="mr-1 h-3.5 w-3.5"/>
                                    {{ $t(resolvedContact.contact_type.name) }}
                                </span>
                            </div>
                            <div class="text-sm font-bold flex items-center gap-x-2" v-if="resolvedContact.email" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                <PropertyIcon name="IconMail" class="h-4 w-4"/>
                                {{ resolvedContact.email }}
                            </div>
                            <div class="text-sm font-bold flex items-center gap-x-2" v-if="resolvedContact.phone_number" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                <PropertyIcon name="IconDeviceMobile" class="h-4 w-4"/>
                                {{ resolvedContact.phone_number }}
                            </div>
                            <div class="text-sm font-bold flex items-center gap-x-2" v-if="resolvedContact.city" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                <PropertyIcon name="IconMapPin" class="h-4 w-4"/>
                                {{ resolvedContact.city }}
                            </div>
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

// Modulweiter Cache analog UserPopoverTooltip: Tooltip-Details werden pro
// Kontakt nur einmal geladen, egal wie viele Popover-Instanzen ihn zeigen.
const lazyContactDetailsCache = reactive(new Map());
const lazyContactDetailsPending = new Set();

export default {
    name: "CrmContactPopoverTooltip",
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
        contact: {
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
        canViewCrm() {
            return this.hasAdminRole() || this.can('can view crm');
        },
        typeColor() {
            return this.contact.contact_type?.color || '#6b7280';
        },
        badgeIcon() {
            return this.contact.contact_type?.icon || 'IconAddressBook';
        },
        resolvedContact() {
            const details = lazyContactDetailsCache.get(this.contact.id);
            return details ? {...this.contact, ...details} : this.contact;
        }
    },
    methods: {
        fetchLazyContactDetails() {
            const contactId = this.contact?.id;
            if (!contactId || lazyContactDetailsCache.has(contactId) || lazyContactDetailsPending.has(contactId)) {
                return;
            }

            lazyContactDetailsPending.add(contactId);
            axios.get(route('crm.contacts.tooltip', {crmContact: contactId}))
                .then((response) => lazyContactDetailsCache.set(contactId, response.data))
                .catch(() => {})
                .finally(() => lazyContactDetailsPending.delete(contactId));
        },
        calculatePopoverPosition(event) {
            this.fetchLazyContactDetails();
            const {top, left, height, width} = event.target.getBoundingClientRect();

            this.anchorTop = top + window.scrollY;
            this.anchorBottom = this.anchorTop + height;
            this.popoverStyle.top = `${this.anchorBottom}px`;
            this.popoverStyle.left = `${left + window.scrollX + width / 2}px`;
        },
        clampPanelPosition(el) {
            // Panel darf nicht über den Viewport-Rand hinausragen (analog UserPopoverTooltip)
            const margin = 8;
            const panelWidth = el.offsetWidth;
            const currentLeft = parseFloat(this.popoverStyle.left) || 0;

            const offset = this.dontTranslatePopoverPosition ? 0 : panelWidth / 2;
            const minLeft = window.scrollX + margin + offset;
            const maxLeft = window.scrollX + window.innerWidth - margin - panelWidth + offset;

            const clampedLeft = Math.min(Math.max(currentLeft, minLeft), Math.max(minLeft, maxLeft));
            if (clampedLeft !== currentLeft) {
                this.popoverStyle.left = `${clampedLeft}px`;
            }

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
        goToCrmContact() {
            if (this.canViewCrm && this.contact?.id) {
                router.visit(route('crm.contacts.show', {crmContact: this.contact.id}));
            }
        },
    },
}
</script>
