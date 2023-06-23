<script>
import {defineComponent} from 'vue'
import {CheckIcon} from "@heroicons/vue/outline";

export default defineComponent({
    name: "ShiftDropElement",
    components: {CheckIcon},
    props: ['shift', 'users','showRoom','event','room', 'maxCount', 'currentCount', 'freeEmployeeCount', 'freeMasterCount', 'userIds'],
    computed: {
        userIds(){
            return this.users.map(user => user.id)
        },
        shiftUserIds(){
            const ids = {
                userIds: [],
                freelancerIds: [],
                providerIds: []
            }
            this.shift.users.forEach(user => {
                ids.userIds.push(user.id)
            })

            this.shift.freelancer?.forEach((freelancer) => {
                ids.freelancerIds.push(freelancer.id)
            })

            this.shift.service_provider?.forEach((provider) => {
                ids.providerIds.push(provider.id)
            })

            return ids;
        }
    },
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            let dropElement = event.dataTransfer.getData('application/json');
            dropElement = JSON.parse(dropElement)[0];

            if(this.maxCount === this.currentCount){
                return;
            }

            if(dropElement.master && this.freeMasterCount === 0){
                return;
            }

            if(!dropElement.master && this.freeEmployeeCount === 0){
                return;
            }

            if(dropElement.type === 0){
                if(this.shiftUserIds.userIds.includes(dropElement.id)){
                    return;
                }
            }

            if(dropElement.type === 1){
                if(this.shiftUserIds.freelancerIds.includes(dropElement.id)){
                    return;
                }
            }

            if(dropElement.type === 2){
                if(this.shiftUserIds.providerIds.includes(dropElement.id)){
                    return;
                }
            }

            console.log(dropElement);
            if(dropElement.master && dropElement.type === 0){
                this.$inertia.post(route('add.shift.master', this.shift.id), {
                        user_id: dropElement.id,
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )

            } else if (dropElement.type === 0 && !dropElement.master) {
                this.$inertia.post(route('add.shift.user', {shift: this.shift.id, user: dropElement.id}), {}, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }

            if(dropElement.type === 1 && !dropElement.master){
                this.$inertia.post(route('add.shift.freelancer', this.shift.id), {
                        freelancer_id: dropElement.id
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            } else if (dropElement.type === 1 && dropElement.master) {
                this.$inertia.post(route('add.shift.freelancer.master', this.shift.id), {
                        freelancer_id: dropElement.id
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }

            if(dropElement.type === 2 && dropElement.master){
                this.$inertia.post(route('add.shift.provider.master', this.shift.id), {
                        service_provider_id: dropElement.id
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            } else if (dropElement.type === 2 && !dropElement.master) {
                this.$inertia.post(route('add.shift.provider', this.shift.id), {
                        service_provider_id: dropElement.id
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
    <div class="flex xsLight text-shiftText subpixel-antialiased" @dragover="onDragOver" @drop="onDrop">
        <div>
            {{ shift.craft.abbreviation }} {{ shift.start }} - {{ shift.end }}
        </div>
        <div v-if="!showRoom" class="ml-0.5">
             ({{ shift.user_count ? shift.user_count : 0 }}/{{ shift.number_employees }}
            <span v-if="shift.number_masters > 0">| {{ shift.master_count }}/{{ shift.number_masters }}</span>
            )
        </div>
        <div v-else-if="room" class="truncate">
            , {{room?.name}}
        </div>
        <div v-if="shift.empty_employee_count === 0 && shift.empty_master_count === 0">
            <CheckIcon class="h-5 w-5 flex text-success" aria-hidden="true"/>
        </div>
    </div>

</template>

<style scoped>

</style>
