<template>
    <div  draggable="true" @dragstart="onDragStart"  class="bg-gray-100 rounded-lg px-4 py-3 h-32 flex items-center justify-center w-full">
        <div>
            <div class="xsDark">
                {{ $t(component.name) ?? component.name }}
            </div>
            <div class="xsLight">
                {{ $t(component.type) ?? component.type }}
            </div>
        </div>
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