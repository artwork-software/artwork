<template>
    <div  draggable="true" @dragstart="onDragStart"  class="bg-gray-100 rounded-lg px-4 py-3 h-24 flex items-center justify-center w-full">
        {{ $t(component.name) ?? component.name }}
    </div>
</template>

<script setup>

const props = defineProps({
    component: {
        type: Object,
        required: true,
    },
})

const onDragStart = (event) => {
    event.dataTransfer.setData('component', JSON.stringify(props.component));
}

</script>

<style scoped>

</style>