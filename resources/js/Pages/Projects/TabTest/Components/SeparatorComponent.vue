<script>

export default {
    name: "SeparatorComponent",
    props: ['data', 'projectId', 'inSidebar'],
    computed: {
        calculateHeight() {
            // format to number
            return parseInt(this.data.data.height) / 2;
        }
    }
}
</script>

<template>
    <div :style="{ 'paddingTop' : calculateHeight + 'px',  'paddingBottom' : calculateHeight + 'px' }" class="w-full flex items-center justify-center ">
        <div class="w-full flex items-center justify-center h-0.5 bg-gray-300" v-if="data.data.showLine"></div>
    </div>
</template>

<style scoped>

</style>

