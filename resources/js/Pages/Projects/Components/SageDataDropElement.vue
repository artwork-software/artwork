<script>
import {router} from "@inertiajs/vue3";
import SageDropMultipleDataSelectModal from "@/Pages/Projects/Components/SageDropMultipleDataSelectModal.vue";

export default {
    name: "SageDataDropElement",
    components: {SageDropMultipleDataSelectModal},
    props: ['row', 'tableId', 'subPositionId'],
    data(){
        return {
            isDragOver: false,
            showMultipleDataSelectModal: false,
            DataSelect: null,
            cell: null
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

            if(data.type === 'globaleMove'){
                router.post(this.route('project.budget.drop.sage'), {
                    table_id: this.tableId,
                    sub_position_id: this.subPositionId,
                    positionBefore: this.row ? this.row.position : -1,
                    sage_data_id: data.id,
                }, {
                    preserveScroll: true,
                    preserveState: true,
                });
            }

            if(data.type === 'rowMove'){
                if(data.sage_assigned_data.length > 1){
                    this.DataSelect = data;
                    this.DataSelect.table_id = this.tableId;
                    this.DataSelect.sub_position_id = this.subPositionId;
                    this.DataSelect.positionBefore = this.row ? this.row.position : -1;
                    this.showMultipleDataSelectModal = true;
                } else {
                    router.post(this.route('project.budget.move.sage.to.row', {
                        table_id: this.tableId,
                        sub_position_id: this.subPositionId,
                        positionBefore: this.row ? this.row.position : -1,
                        columnCell: data.id,
                    }), {
                        multiple: false
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    });
                }
            }
        },
    }
}
</script>

<template>
    <div class="w-full h-1" @dragover="onDragOver" @drop="onDrop" @dragleave.prevent="onDragLeave"></div>
    <SageDropMultipleDataSelectModal v-if="showMultipleDataSelectModal" type="dropOnRow" :cellData="DataSelect" :cell="cell" @close="showMultipleDataSelectModal = false"  />
</template>
