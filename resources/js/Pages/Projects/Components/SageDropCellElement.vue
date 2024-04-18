<script>
import {Inertia} from "@inertiajs/inertia";
import IconLib from "@/mixins/IconLib.vue";
import SageDropMultipleDataSelectModal from "@/Pages/Projects/Components/SageDropMultipleDataSelectModal.vue";

export default {
    name: "SageDropCellElement",
    components: {SageDropMultipleDataSelectModal},
    mixins: [IconLib],
    props: ['value', 'cell'],
    data(){
        return {
            isDragOver: false,
            showMultipleDataSelectModal: false,
            DataSelect: null
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

            if(data.type === 'globaleMove') {
                Inertia.post(route('project.budget.move.sage', {
                    sageNotAssignedData: data.id,
                    columnCell: this.cell.id
                }),{
                    multiple: false
                }, {
                    preserveState: true,
                    preserveScroll: true
                });
            }

            if (data.type === 'rowMove'){
                if (data.sage_assigned_data.length > 1){
                    this.DataSelect = data;
                    this.showMultipleDataSelectModal = true;
                } else {
                    Inertia.post(route('project.budget.move.sage.row', {
                        columnCell: this.cell.id,
                        movedColumn: data.id
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
    <div  @dragover="onDragOver" @drop="onDrop" @dragleave.prevent="onDragLeave">
        {{ value }}
    </div>
    <SageDropMultipleDataSelectModal v-if="showMultipleDataSelectModal" @close="showMultipleDataSelectModal = false" :cell-data="DataSelect" :cell="cell" type="dropOnValue"/>
</template>

<style scoped>

</style>
