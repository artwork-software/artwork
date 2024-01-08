<template>
    <div class="fixed bg-primary h-screen w-[26rem] top-0 z-40 transition-all duration-200 "
         :class="show ? 'right-0 overflow-y-scroll' : '-right-[25.7rem]'">
            <div @click="$emit('toggle')" :class="show ? 'fixed' : 'bg-primary absolute'" class="cursor-pointer pr-2 py-2 h-auto right-[26rem] bg-primary top-44 text-secondary">
                <img src="/Svgs/IconSvgs/sidebar_icon.svg"
                     alt="sidenav_icon"
                     class="w-5 h-5"
                     :class="show ? 'fixed bg-primary pr-1 pb-1' : 'rotate-180 bg-primary absolute'"
                     />
                <div v-show="!show" class="xsLight ml-5">
                    Details
                </div>

            </div>
            <div class="mt-32 ml-8 mr-8 text-secondary">
                <slot></slot>
            </div>
    </div>
</template>

<script>

import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "BaseSidenav",
    mixins: [Permissions],
    props: {
        show: Boolean
    },
}
</script>

<style scoped>

</style>
