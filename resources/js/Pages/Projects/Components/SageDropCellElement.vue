<script>
import {Inertia} from "@inertiajs/inertia";
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: "SageDropCellElement",
    mixins: [IconLib],
    props: ['value', 'cellId'],
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

            //console.log(data, this.cellId);

            if(data.type === 'globaleMove') {
                Inertia.post(route('project.budget.move.sage', {
                    sageNotAssignedData: data.id,
                    columnCell: this.cellId
                }), {
                    sageData: data,
                    cellId: this.cellId
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        console.log('success');
                    },
                    onError: () => {
                        console.log('error');
                    }
                });
            }

            if (data.type === 'rowMove'){
                Inertia.post(route('project.budget.move.sage.row', {
                    columnCell: this.cellId,
                    movedColumn: data.id
                }), {
                    rowId: data.id,
                    cellId: this.cellId
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        console.log('success');
                    },
                    onError: () => {
                        console.log('error');
                    }
                });
            }
        },
    }
}
</script>

<template>
    <div  @dragover="onDragOver" @drop="onDrop" @dragleave.prevent="onDragLeave">
        {{ value}}
    </div>
</template>

<style scoped>

</style>
