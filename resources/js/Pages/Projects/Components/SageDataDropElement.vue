<script>
import {Inertia} from "@inertiajs/inertia";

export default {
    name: "SageDataDropElement",
    props: ['row', 'tableId', 'subPositionId'],
    data(){
        return {
            isDragOver: false, // Zustand, ob das Element gerade über das Drop-Ziel gezogen wird
        }
    },
    methods: {
        onDragOver(event) {
            event.preventDefault();
            this.isDragOver = true; // Aktualisiere den Zustand beim Drag-Over
        },
        onDragLeave() {
            this.isDragOver = false; // Setze den Zustand zurück, wenn das Element das Drop-Ziel verlässt
        },
        onDrop(event) {
            // Behandle das Drop-Ereignis
            this.isDragOver = false; // Setze den Zustand zurück, nachdem das Element abgelegt wurde
            // Füge hier deine Logik hinzu, um das abgelegte Element zu verarbeiten
            event.preventDefault();
            const data = JSON.parse(event.dataTransfer.getData('text/plain'));
            console.log('drop', data, this.row);

            // Hier kannst du das abgelegte Element verarbeiten
            Inertia.post(this.route('project.budget.drop.sage'), {
                table_id: this.tableId,
                sub_position_id: this.subPositionId,
                positionBefore: this.row ? this.row.position : -1,
                sage_data_id: data.id,
            }, {
                preserveScroll: true,
                preserveState: true,
            });
        },
    }
}
</script>

<template>
    <!-- add class if drop has element -->
    <div class="w-full h-1" @dragover="onDragOver" @drop="onDrop"  @dragleave.prevent="onDragLeave">
    </div>
</template>

<style scoped>

</style>
