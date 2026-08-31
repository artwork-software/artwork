<template>
    <TransitionRoot as="template" :show="show">
        <TransitionChild as="template" enter="transform transition ease-in-out duration-500 sm:duration-700" enter-from="translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-500 sm:duration-700" leave-from="translate-x-0" leave-to="translate-x-full">
            <div class="fixed right-0 top-0 z-100 h-screen bg-surface-inverse w-[26rem]">
                <div class="h-full max-h-screen overflow-y-scroll overflow-x-clip">
                    <div>
                        <div class="mt-5 px-3 text-text-inverse">
                            <slot></slot>
                        </div>
                    </div>
                </div>
            </div>
        </TransitionChild>
    </TransitionRoot>

    <div class="fixed top-44 right-0 cursor-pointer z-100 transition-all duration-700" :class="{'right-[25.7rem]': show}" @click="updateShow">
        <div class="bg-surface-inverse px-2 py-1.5 flex items-center rounded-l-lg">
            <PropertyIcon name="IconChevronsLeft" class="w-5 h-5 text-sm/5 font-bold text-text-subtle" v-if="!show"/>
            <PropertyIcon name="IconChevronsRight" class="w-5 h-5 text-sm/5 font-bold text-text-subtle" v-else/>
        </div>
    </div>

</template>

<script>

import axios from "axios";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
export default {
    name: "BaseSidenav",
    mixins: [Permissions, IconLib],
    components: {
        PropertyIcon,
        Dialog,
        DialogPanel,
        DialogTitle,
        TransitionChild,
        TransitionRoot
    },
    data() {
        return {
            show: this.$page.props.auth.user ? this.$page.props.auth.user.is_sidebar_opened : false
        }
    },
    emits: ['toggle'],
    methods: {
        updateShow() {
            this.show = !this.show
            // Still per axios persistieren statt per Inertia-Visit: der Visit zeigte den
            // NProgress-Ladebalken und lud alle Seiten-Props neu (gefühlter Refresh) —
            // Abnahme-Befund RG-05. Die UI läuft komplett über den lokalen State.
            this.$page.props.auth.user.is_sidebar_opened = this.show
            axios.patch(route('user.sidebar.update', {user: this.$page.props.auth.user.id}), {
                is_sidebar_opened: this.show
            }).catch(() => {
                // Anzeige bleibt lokal korrekt; beim nächsten Toggle wird erneut gespeichert.
            })
        }
    }
}
</script>
