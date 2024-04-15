<script>
export default {
    name: "Title",
    props: ['data', 'inSidebar'],
}
</script>

<template>
    <div class="my-2">
        <h1 class="font-bold" :style="{fontSize: data.data.title_size + 'px'}" :class="inSidebar ? 'text-white' : ' text-gray-900' ">{{ data.name }}</h1>
    </div>
</template>

<style scoped>

</style>
