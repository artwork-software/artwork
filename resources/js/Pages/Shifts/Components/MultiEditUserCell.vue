<template>
    <div class="flex items-center gap-2">
        <div class="flex items-center ml-2">
            <input :checked="item.checkedForMultiEdit" @change="changeUserForMultiEdit" id="comments" aria-describedby="comments-description" name="comments" type="checkbox" class="border-gray-300 text-green-600 focus:ring-green-600" :class="[$page.props.user.compact_mode ? 'h-3 w-3 ' : 'h-6 w-6 ']" />
        </div>
        <div class="drag-item w-full p-2 text-white text-xs rounded-lg flex items-center gap-2" :style="{backgroundColor: backgroundColorWithOpacity(color), color: TextColorWithDarken(color, 10)}">
            <div class="w-5" v-if="!$page.props.user.compact_mode">
                <img :src="item.profile_photo_url" alt="" class="h-5 w-5 rounded-full object-cover">
            </div>
            <div class="text-left cursor-pointer" :class="[$page.props.user.compact_mode ? 'h-4' : 'h-8']">
                <div v-if="type === 0" class="text-ellipsis w-32">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1">(i)</div>
                    </div>
                    <div class="text-xs w-full flex" v-if="!$page.props.user.compact_mode"> {{plannedHours.toFixed(1)}}  {{expectedHours ? ' | ' + expectedHours.toFixed(1) : ''}}</div>
                </div>
                <div v-else-if="type === 1" class="text-ellipsis w-32">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1"> (e) </div>
                    </div>
                    <div class="text-xs w-full" v-if="!$page.props.user.compact_mode">{{plannedHours.toFixed(1)}}</div>
                </div>
                <div v-else class="text-ellipsis w-32">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.provider_name }}</div>
                        <div class="ml-1"> (DL) </div>
                    </div>
                    <div class="text-xs w-full" v-if="!$page.props.user.compact_mode">{{plannedHours.toFixed(1)}}</div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default defineComponent({
    name: "MultiEditUserCell",
    mixins: [ColorHelper],
    props: [
        'item',
        'type',
        'plannedHours',
        'expectedHours',
        'userForMultiEdit',
        'multiEditMode',
        'color'
    ],
    watch: {
        userForMultiEdit: {
            handler() {
                this.item.checkedForMultiEdit = this.userForMultiEdit?.id === this.item?.id && this.userForMultiEdit?.type === this.type;
            },
            deep: true
        },
        multiEditMode: {
            handler() {
                if (!this.multiEditMode) {
                    this.item.checkedForMultiEdit = false
                }
            },
            deep: true
        }
    },
    emits: ['addUserToMultiEdit'],
    methods: {
        changeUserForMultiEdit() {
            if (this.item.checkedForMultiEdit) {
                this.item.checkedForMultiEdit = false
                this.$emit('addUserToMultiEdit', null)
                return
            }

            this.$emit(
                'addUserToMultiEdit',
                {
                    id: this.item.id,
                    type: this.type,
                    display_name: this.item.first_name && this.item.last_name ?
                        this.item.first_name + ' ' + this.item.last_name :
                        this.item.provider_name,
                    profile_photo_url: this.item.profile_photo_url,
                    assigned_craft_ids: this.item.assigned_craft_ids,
                    shift_ids: this.item.shift_ids,
                    shift_qualifications: this.item.shift_qualifications
                }
            );
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
