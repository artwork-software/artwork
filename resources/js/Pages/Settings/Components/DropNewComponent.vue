<script>
export default {
    name: "DropNewComponent",
    props: ['tab', 'order'],
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            const data = JSON.parse(event.dataTransfer.getData('application/json'));
            console.log(data);

            this.$inertia.post(route('tab.add.component', {projectTab: this.tab.id}), {
                component_id: data.id,
                order: this.order
            });
        }
    }
}
</script>

<template>
    <div class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer" @dragover="onDragOver" @drop="onDrop">
    </div>
</template>

<style scoped>

</style>
