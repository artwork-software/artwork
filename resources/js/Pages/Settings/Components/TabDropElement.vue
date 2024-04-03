<script>
export default {
    name: "TabDropElement",
    props: ['order'],
    data() {
        return {
            dropOver: false
        }
    },
    methods: {
        onDragOver(event) {
            this.dropOver = true;
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            const data = JSON.parse(event.dataTransfer.getData('application/json'));

            if(data.drop_type === 'component') {
                this.dropOver = false;
                return;
            }

            this.$inertia.post(route('tab.reorder', {projectTab: data.id}), {
                order: this.order
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.dropOver = false;
                }
            });
        }
    }
}
</script>

<template>
    <div class="flex items-center h-4 min-h-4 hover:bg-gray-50/40 rounded cursor-pointer" @dragleave="dropOver = false" @dragover="onDragOver" @drop="onDrop">
         <span v-if="dropOver" class="text-xs text-gray-300 w-full flex items-center justify-center pointer-events-none">
            Zum neu Anordnen hier loslassen
        </span>
    </div>
</template>

<style scoped>

</style>
