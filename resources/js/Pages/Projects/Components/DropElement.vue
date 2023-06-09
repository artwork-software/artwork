<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "DropElement",
    props: ['shiftId', 'master'],
    methods: {
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            let dropElement = event.dataTransfer.getData('application/json');
            dropElement = JSON.parse(dropElement)[0];
            if(dropElement.master && this.master){
                this.$inertia.post(route('add.shift.master', this.shiftId), {
                        user_id: dropElement.id
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )

            } else {
                this.$inertia.post(route('add.shift.user', this.shiftId), {
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
    <div class="flex items-center gap-2 p-1 hover:bg-gray-50/40 rounded cursor-pointer" @dragover="onDragOver" @drop="onDrop">
        <span class="h-4 w-4 rounded-full block bg-gray-500"></span>
        <span class="text-xs">Unbesetzt</span>
        <span v-if="master">
            <svg xmlns="http://www.w3.org/2000/svg" width="13.2" height="10.8" viewBox="0 0 13.2 10.8">
                <path id="Icon_awesome-crown" data-name="Icon awesome-crown" d="M9.9,8.4H2.1a.3.3,0,0,0-.3.3v.6a.3.3,0,0,0,.3.3H9.9a.3.3,0,0,0,.3-.3V8.7A.3.3,0,0,0,9.9,8.4Zm1.2-6a.9.9,0,0,0-.9.9.882.882,0,0,0,.083.371l-1.358.814A.6.6,0,0,1,8.1,4.268L6.568,1.594a.9.9,0,1,0-1.136,0L3.9,4.267a.6.6,0,0,1-.829.218L1.719,3.671A.9.9,0,1,0,.9,4.2a.919.919,0,0,0,.144-.015L2.4,7.8H9.6l1.356-3.615A.919.919,0,0,0,11.1,4.2a.9.9,0,0,0,0-1.8Z" transform="translate(0.6 0.6)" fill="none" stroke="#82818a" stroke-width="1.2"/>
            </svg>
        </span>
    </div>
</template>

<style scoped>

</style>
