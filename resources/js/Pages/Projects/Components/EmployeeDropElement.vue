<script>
import {defineComponent} from 'vue'
import ChooseUserSeriesShift from "@/Pages/Projects/Components/ChooseUserSeriesShift.vue";

export default defineComponent({
    name: "EmployeeDropElement",
    components: {ChooseUserSeriesShift},
    props: ['shiftId' , 'craftId', 'master', 'users', 'maxCount', 'currentCount', 'freeEmployeeCount', 'freeMasterCount', 'userIds', 'is_series'],
    data(){
        return {
            showChooseUserSeriesShiftModal: false,
            buffer: {
                onlyThisDay: false,
                start: null,
                end: null,
                dayOfWeek: null
            },
            selectedUser: null,
            dropFeedback: null,
        }
    },
    emits: ['dropFeedback'],
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            this.selectedUser = event.dataTransfer.getData('application/json');
            if(this.is_series){
                this.showChooseUserSeriesShiftModal = true
            } else {
                this.saveUser();
            }
        },
        changeBuffer(buffer){
            this.buffer = buffer
            this.showChooseUserSeriesShiftModal = false
            this.saveUser();
        },
        saveUser(){
            let dropElement = this.selectedUser;
            dropElement = JSON.parse(dropElement)[0];


            if(dropElement.craft_ids && !dropElement.craft_ids.includes(this.craftId)){
                this.dropFeedback = 'Nutzer*in hat nicht das richtige Gewerk';
                this.$emit('dropFeedback', this.dropFeedback);
                return;
            }

            if(this.maxCount === this.currentCount){
                return;
            }

            if(!dropElement.master && this.freeEmployeeCount === 0){
                return;
            }

            if(dropElement.type === 0){
                if(this.userIds.userIds.includes(dropElement.id)){
                    return;
                }
            }

            if(dropElement.type === 1){
                if(this.userIds.freelancerIds.includes(dropElement.id)){
                    return;
                }
            }

            if(dropElement.type === 2){
                if(this.userIds.providerIds.includes(dropElement.id)){
                    return;
                }
            }

            if (dropElement.type === 0) {
                this.$inertia.post(route('add.shift.user', {shift: this.shiftId, user: dropElement.id}), {
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }

            if(dropElement.type === 1){
                this.$inertia.post(route('add.shift.freelancer', {shift: this.shiftId, freelancer: dropElement.id}), {
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }
            if (dropElement.type === 2) {
                this.$inertia.post(route('add.shift.provider', {shift: this.shiftId, serviceProvider: dropElement.id}), {
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }
        }
    }

})
</script>

<template>
    <div class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer" @dragover="onDragOver" @drop="onDrop">
        <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
        <span class="text-xs">Unbesetzt</span>
    </div>

    <ChooseUserSeriesShift v-if="showChooseUserSeriesShiftModal" @close-modal="showChooseUserSeriesShiftModal = false" @returnBuffer="changeBuffer" />


</template>

<style scoped>

</style>
