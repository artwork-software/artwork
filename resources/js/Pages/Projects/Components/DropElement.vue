<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "DropElement",
    props: ['shiftId'],
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            const id = event.dataTransfer.getData('text/plain');
            this.$inertia.post(route('add.shift.user', this.shiftId), {
                    user_id: id
                }, {
                    preserveState: true,
                    preserveScroll: true,
                }
            )
        }
    }

})
</script>

<template>
    <div class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer" @dragover="onDragOver" @drop="onDrop">
        <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
        <span class="text-xs">Unbesetzt</span>
    </div>
</template>

<style scoped>

</style>
