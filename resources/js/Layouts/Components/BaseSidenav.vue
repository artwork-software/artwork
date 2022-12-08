<template>
    <div class="fixed bg-primary h-screen w-[26rem] top-0 z-40 transition-all duration-200"
         :class="show ? 'right-0' : '-right-[25.7rem]'">
        <img src="/Svgs/IconSvgs/sidebar_icon.svg"
             class="pl-2 pr-1 py-2 absolute w-5 h-auto right-[26rem] bg-primary top-36" alt="sidenav_icon"
             :class="show ? '' : 'rotate-180'"
             @click="$emit('change')"/>
        <div class="mt-32 ml-8 mr-8 text-secondary">
            <slot></slot>
        </div>
    </div>
</template>

<script>

export default {
    name: "BaseSidenav",
    props: {
        show: Boolean
    },
}
</script>

<style scoped>

</style>
