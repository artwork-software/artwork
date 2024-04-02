<script>
export default {
    name: "Title",
    props: ['data'],
}
</script>

<template>
    <div class="my-2">
        <h1 class="text-2xl font-bold text-gray-900">{{ data.name }}</h1>
    </div>
</template>

<style scoped>

</style>
