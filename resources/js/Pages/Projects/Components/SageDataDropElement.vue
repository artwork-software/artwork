<script>
import {Inertia} from "@inertiajs/inertia";

export default {
    name: "SageDataDropElement",
    props: ['row', 'tableId', 'subPositionId'],
    data(){
        return {
            isDragOver: false,
        }
    },
    methods: {
        onDragOver(event) {
            event.preventDefault();
            this.isDragOver = true;
        },
        onDragLeave() {
            this.isDragOver = false;
        },
        onDrop(event) {
            this.isDragOver = false;
            event.preventDefault();
            const data = JSON.parse(event.dataTransfer.getData('text/plain'));

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
    <div class="w-full h-1" @dragover="onDragOver" @drop="onDrop" @dragleave.prevent="onDragLeave"></div>
</template>
