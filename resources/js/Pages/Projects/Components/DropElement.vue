<script>
import {defineComponent} from 'vue'
import ChooseUserSeriesShift from "@/Pages/Projects/Components/ChooseUserSeriesShift.vue";

export default defineComponent({
    name: "DropElement",
    components: {ChooseUserSeriesShift},
    props: ['shiftId', 'craftId', 'master', 'users', 'maxCount', 'currentCount', 'freeEmployeeCount', 'freeMasterCount', 'userIds', 'is_series'],
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

            if(this.maxCount === this.currentCount){
                return;
            }

            if(dropElement.craft_ids && !dropElement.craft_ids.includes(this.craftId)){
                this.dropFeedback = 'Nutzer*in kann nicht zu Schichten von diesem Gewerk zugewiesen werden.';
                this.$emit('dropFeedback', this.dropFeedback);
                return;
            }

            if(dropElement.master && this.freeMasterCount === 0 && this.freeEmployeeCount === 0){
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

            if(dropElement.master && dropElement.type === 0 && this.freeMasterCount > 0){
                this.$inertia.post(route('shift.assignUserByType', {shift: this.shiftId}), {
                        userId: dropElement.id,
                        userType: dropElement.type,
                        shiftQualificationId: null,
                        craft_abbreviation: '',
                        seriesShiftData: this.buffer,
                        isShiftTab: false
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )

            } else if (dropElement.type === 0 && !dropElement.master || this.freeMasterCount === 0 && dropElement.master ) {
                this.$inertia.post(route('shift.assignUserByType', {shift: this.shiftId}), {
                        userId: dropElement.id,
                        userType: dropElement.type,
                        shiftQualificationId: null,
                        craft_abbreviation: '',
                        seriesShiftData: this.buffer,
                        isShiftTab: false
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }

            if(dropElement.type === 1 && !dropElement.master){
                this.$inertia.post(route('shift.assignUserByType', {shift: this.shiftId}), {
                        userId: dropElement.id,
                        userType: dropElement.type,
                        shiftQualificationId: null,
                        craft_abbreviation: '',
                        seriesShiftData: this.buffer,
                        isShiftTab: false
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            } else if (dropElement.type === 1 && dropElement.master) {
                this.$inertia.post(route('shift.assignUserByType', {shift: this.shiftId}), {
                        userId: dropElement.id,
                        userType: dropElement.type,
                        shiftQualificationId: null,
                        craft_abbreviation: '',
                        seriesShiftData: this.buffer,
                        isShiftTab: false
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }

            if(dropElement.type === 2 && dropElement.master){
                this.$inertia.post(route('shift.assignUserByType', {shift: this.shiftId}), {
                        userId: dropElement.id,
                        userType: dropElement.type,
                        shiftQualificationId: null,
                        craft_abbreviation: '',
                        seriesShiftData: this.buffer,
                        isShiftTab: false
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            } else if (dropElement.type === 2 && !dropElement.master) {
                this.$inertia.post(route('shift.assignUserByType', {shift: this.shiftId}), {
                        userId: dropElement.id,
                        userType: dropElement.type,
                        shiftQualificationId: null,
                        craft_abbreviation: '',
                        seriesShiftData: this.buffer,
                        isShiftTab: false
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
        <span v-if="master">
            <svg xmlns="http://www.w3.org/2000/svg" width="13.2" height="10.8" viewBox="0 0 13.2 10.8">
                <path id="Icon_awesome-crown" data-name="Icon awesome-crown" d="M9.9,8.4H2.1a.3.3,0,0,0-.3.3v.6a.3.3,0,0,0,.3.3H9.9a.3.3,0,0,0,.3-.3V8.7A.3.3,0,0,0,9.9,8.4Zm1.2-6a.9.9,0,0,0-.9.9.882.882,0,0,0,.083.371l-1.358.814A.6.6,0,0,1,8.1,4.268L6.568,1.594a.9.9,0,1,0-1.136,0L3.9,4.267a.6.6,0,0,1-.829.218L1.719,3.671A.9.9,0,1,0,.9,4.2a.919.919,0,0,0,.144-.015L2.4,7.8H9.6l1.356-3.615A.919.919,0,0,0,11.1,4.2a.9.9,0,0,0,0-1.8Z" transform="translate(0.6 0.6)" fill="none" stroke="#82818a" stroke-width="1.2"/>
            </svg>
        </span>
    </div>

    <ChooseUserSeriesShift v-if="showChooseUserSeriesShiftModal" @close-modal="showChooseUserSeriesShiftModal = false" @returnBuffer="changeBuffer" />
</template>

<style scoped>

</style>
