<script>
import {defineComponent} from 'vue'
import {CheckIcon} from "@heroicons/vue/outline";

export default defineComponent({
    name: "ShiftDropElement",
    components: {CheckIcon},
    props: ['shift'],
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            let dropElement = event.dataTransfer.getData('application/json');
            dropElement = JSON.parse(dropElement)[0];
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
    <div class="flex" @dragover="onDragOver" @drop="onDrop">
        <div class="flex">
            {{ shift.craft.abbreviation }} {{ shift.start }} - {{ shift.end }}
            ({{ shift.employee_count }}/{{ shift.number_employees }}
            <span v-if="shift.number_masters > 0">| {{ shift.masters.length }}/{{ shift.number_masters }}</span>
            )
        </div>
        <div v-if="shift.empty_employee_count === 0">
            <CheckIcon class="h-5 w-5 flex text-success" aria-hidden="true"/>
        </div>
    </div>

</template>

<style scoped>

</style>
