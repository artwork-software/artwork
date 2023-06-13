<script>
import {defineComponent} from 'vue'
import {CheckIcon} from "@heroicons/vue/outline";

export default defineComponent({
    name: "ShiftDropElement",
    components: {CheckIcon},
    props: ['shift', 'users','showRoom','event','room'],
    computed: {
        userIds(){
            return this.users.map(user => user.id)
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

            // prevent adding the same user twice
            if (this.userIds.includes(dropElement.id)) {
                return;
            }

            if(dropElement.master){
                this.$inertia.post(route('add.shift.master', this.shift.id), {
                        user_id: dropElement.id
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )

            } else {
                this.$inertia.post(route('add.shift.user', this.shift.id), {
                        user_id: dropElement.id
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
