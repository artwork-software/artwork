<script>
export default {
    name: "TableHead",
}
</script>

<template>
    <thead>
        <tr class="flex w-full bg-secondaryHover stickyHeader">
            <slot />
        </tr>
    </thead>
</template>

<style scoped>
.stickyHeader {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    display: block;
    top: 0px;
}

.stickyYAxis {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 60px;
    z-index: 22;
}

.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}
</style>
