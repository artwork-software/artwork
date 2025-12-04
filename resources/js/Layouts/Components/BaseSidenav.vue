<template>
    <TransitionRoot as="template" :show="show">
        <TransitionChild as="template" enter="transform transition ease-in-out duration-500 sm:duration-700" enter-from="translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-500 sm:duration-700" leave-from="translate-x-0" leave-to="translate-x-full">
            <div class="fixed right-0 top-0 z-100 h-screen bg-artwork-navigation-background w-[26rem]">
                <div class="h-full max-h-screen overflow-y-scroll overflow-x-clip">
                    <div>
                        <div class="mt-5 px-3 text-artwork-navigation-color">
                            <slot></slot>
                        </div>
                    </div>
                </div>
            </div>
        </TransitionChild>
    </TransitionRoot>

    <div class="fixed top-44 right-0 cursor-pointer z-100 transition-all duration-700" :class="{'right-[25.7rem]': show}" @click="updateShow">
        <div class="bg-artwork-navigation-background px-2 py-1.5 flex items-center rounded-l-lg">
            <PropertyIcon name="IconChevronsLeft" class="w-5 h-5 xsLight" v-if="!show"/>
            <PropertyIcon name="IconChevronsRight" class="w-5 h-5 xsLight" v-else/>
        </div>
    </div>

</template>

<script>

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
            this.$inertia.patch(route('user.sidebar.update', {user: this.$page.props.auth.user.id}), {
                is_sidebar_opened: this.show
            }, {
                preserveScroll: true,
                preserveState: false
            })
        }
    }
}
</script>
