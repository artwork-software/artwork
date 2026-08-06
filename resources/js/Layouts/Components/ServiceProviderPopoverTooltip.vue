<template>
    <Popover v-slot="{ open }" class="!ring-0 flex items-center justify-center">
        <PopoverButton :class="open ? '' : 'text-opacity-90'" class="group inline-flex !ring-0 outline-0" @click="calculatePopoverPosition">
            <template v-if="useSlotInsteadOfIcon">
                <slot/>
            </template>
            <template v-else>
                <img v-if="provider" :src="provider.profile_photo_url" alt="" class="shrink-0 flex object-cover rounded-full !ring-0" :class="['h-' + height, 'w-' + width, 'min-h-' + height, 'min-w-' + width, classes]">
                <PropertyIcon name="IconBuildingStore" v-else stroke-width="2" class="p-1 text-black shrink-0 flex object-cover rounded-full !ring-0 bg-border" :class="['h-' + height, 'w-' + width, 'min-h-' + height, 'min-w-' + width, classes]"/>
            </template>
        </PopoverButton>
        <Teleport to="body">
            <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from" enter-to-class="transition-enter-to" leave-active-class="transition-leave-active" leave-from-class="transition-leave-from" leave-to-class="transition-leave-to" @enter="clampPanelPosition">
                <PopoverPanel :class="[!dontTranslatePopoverPosition ? '-translate-x-1/2' : '', isWhite ? 'bg-white border border-border-subtle' : 'bg-surface-inverse']" class="absolute left-1/2 z-[10000] transform rounded-lg shadow-xl px-4 py-4" :style="popoverStyle">
                    <div v-if="provider">
                        <div class="flex items-center gap-4">
                            <img class="min-h-14 min-w-14 h-14 w-14 object-cover rounded-full" :src="provider.profile_photo_url" alt=""/>
                            <div>
                                <div class="font-black font-lexend text-lg flex items-start gap-x-4 mb-2 border-b border-dashed" :class="isWhite ? 'text-text border-border-strong' : 'text-text-inverse border-white/20'">
                                    <span :class="{'underline cursor-pointer': canViewProviderInfo}" @click="goToProviderInfo">{{ provider.provider_name }}</span>
                                </div>

                                <div class="text-sm font-bold flex items-center gap-x-2" v-if="provider.email" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                    <PropertyIcon name="IconMail" class="h-4 w-4"/>
                                    {{ provider.email }}
                                </div>
                                <div class="text-sm font-bold flex items-center gap-x-2" v-if="provider.phone_number" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                    <PropertyIcon name="IconDeviceMobile" class="h-4 w-4"/>
                                    {{ provider.phone_number }}
                                </div>
                                <div class="text-sm font-bold flex items-center gap-x-2" v-if="providerAddress" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                    <PropertyIcon name="IconMapPin" class="h-4 w-4"/>
                                    {{ providerAddress }}
                                </div>
                                <div class="text-xs italic mt-2" v-if="!provider.email && !provider.phone_number && !providerAddress" :class="isWhite ? 'text-text-subtle' : 'text-white/70'">
                                    {{ $t('No contact details available') }}
                                </div>
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
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import {router, usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";

export default {
    name: "ServiceProviderPopoverTooltip",
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
        provider: {
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
        canViewProviderInfo() {
            return this.hasAdminRole() || this.can('can manage workers');
        },
        providerAddress() {
            if (!this.provider) return '';
            const cityLine = [this.provider.zip_code, this.provider.location].filter(Boolean).join(' ');
            return [this.provider.street, cityLine].filter(Boolean).join(', ');
        }
    },
    methods: {
        calculatePopoverPosition(event) {
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
        goToProviderInfo() {
            if (this.canViewProviderInfo && this.provider?.id) {
                router.visit(route('service_provider.show', {serviceProvider: this.provider.id}));
            }
        },
    },
}
</script>
