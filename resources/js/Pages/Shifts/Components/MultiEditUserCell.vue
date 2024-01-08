
<template>
    <div class="flex items-center gap-2">
        <div class="flex items-center ml-2">
            <input :checked="item.checkedForMultiEdit" @change="changeUserForMultiEdit" id="comments" aria-describedby="comments-description" name="comments" type="checkbox" class="h-6 w-6 border-gray-300 text-green-600 focus:ring-green-600" />
        </div>
        <div class="drag-item w-full p-2 bg-gray-50/10 text-white text-xs rounded-lg flex items-center gap-2 my-2" >
            <div class="w-5">
                <img :src="item.profile_photo_url" alt="" class="h-5 w-5 rounded-full object-cover">
            </div>
            <div class="text-left h-8 cursor-pointer">
                <div v-if="type === 0" class="text-ellipsis w-32">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1">(i)</div>
                    </div>
                    <div class="text-xs w-full flex"> {{plannedHours.toFixed(1)}}  {{expectedHours ? ' | ' + expectedHours.toFixed(1) : ''}}</div>
                </div>
                <div v-else-if="type === 1" class="text-ellipsis w-32">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1"> (e) </div>
                    </div>
                    <div class="text-xs w-full">{{plannedHours.toFixed(1)}}</div>
                </div>
                <div v-else class="text-ellipsis w-32">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.provider_name }}</div>
                        <div class="ml-1"> (DL) </div>
                    </div>
                    <div class="text-xs w-full">{{plannedHours.toFixed(1)}}</div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "MultiEditUserCell",
    props: ['item', 'type','plannedHours','expectedHours', 'userForMultiEdit', 'multiEditMode'],
    data() {
        return {

        }
    },
    watch: {
        userForMultiEdit: {
            handler(){
                this.item.checkedForMultiEdit = this.userForMultiEdit?.id === this.item?.id && this.userForMultiEdit?.type === this.type;
            },
            deep: true
        },
        multiEditMode: {
            handler(){
                if(!this.multiEditMode) {
                    this.item.checkedForMultiEdit = false
                }
            },
            deep: true
        }
    },
    emits: ['addUserToMultiEdit'],
    methods: {
        changeUserForMultiEdit() {
            if(this.item.checkedForMultiEdit){
                this.item.checkedForMultiEdit = false
                this.$emit('addUserToMultiEdit', null)
                return
            }
            const returnValue = {
                id: this.item.id,
                type: this.type,
                assigned_craft_ids: this.item.assigned_craft_ids,
                shift_ids_array: this.item.shift_ids_array
            }
            this.$emit('addUserToMultiEdit', returnValue)
        }
    }
})
</script>
<style scoped>
.truncate {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
</style>
