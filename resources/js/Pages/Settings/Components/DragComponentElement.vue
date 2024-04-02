<script>
export default {
    name: "DragComponentElement",
    props: ['component'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData(
                'application/json',
                JSON.stringify( this.component )
            );
        }
    }
}
</script>

<template>
    <div class="p-3 rounded-lg border mb-3 hover:cursor-grab" draggable="true" @dragstart="onDragStart">
        <div class="flex items-center justify-center mb-2">
            <img :src="component.preview" class="h-24 w-auto object-cover rounded-md" alt="">
        </div>
        <div class="text-center text-sm font-bold">
            {{ component.name }}
        </div>
    </div>
</template>

<style scoped>

</style>
