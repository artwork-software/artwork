<template>

    <div>
        <pre>
        {{ rooms }}
        </pre>
    </div>

</template>

<script>
export default {
    name: "DayManagement",
    props: ['hours_of_day','rooms'],
}
</script>

<style scoped>

</style>
