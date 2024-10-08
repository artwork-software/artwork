<template>
    <div class="flex items-center gap-2">
        <div class="flex items-center">
            <input :checked="computedCheckedForMultiEdit" @change="changeUserForMultiEdit" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist" :class="[$page.props.user.compact_mode ? 'h-3 w-3 ' : 'h-5 w-5 ']" />
        </div>
        <div class="drag-item w-full p-2 text-white text-xs rounded-lg flex items-center gap-2" :style="{backgroundColor: backgroundColorWithOpacity(color), color: TextColorWithDarken(color, 10)}">
            <div class="w-5" v-if="!$page.props.user.compact_mode">
                <img :src="item.profile_photo_url" alt="" class="h-5 w-5 rounded-full object-cover">
            </div>
            <div class="text-left cursor-pointer" :class="[$page.props.user.compact_mode ? 'h-4' : 'h-8']">
                <div v-if="type === 0" class="text-ellipsis" :class="$page.props.user.compact_mode ? 'w-36' : 'w-28'">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1">(i)</div>
                    </div>
                    <div class="text-xs w-full flex" v-if="!$page.props.user.compact_mode"> {{plannedHours.toFixed(1)}}  {{expectedHours ? ' | ' + expectedHours.toFixed(1) : ''}}</div>
                </div>
                <div v-else-if="type === 1" class="text-ellipsis" :class="$page.props.user.compact_mode ? 'w-36' : 'w-28'">
                    <div class="flex">
                        <div class="truncate">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1"> (e) </div>
                    </div>
                    <div class="text-xs w-full" v-if="!$page.props.user.compact_mode">{{plannedHours.toFixed(1)}}</div>
                </div>
                <div v-else class="text-ellipsis" :class="$page.props.user.compact_mode ? 'w-36' : 'w-28'">
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
        'color',
        'craftId',
        'craft'
    ],
    watch: {
        multiEditMode: {
            handler() {
                if (!this.multiEditMode) {
                    this.item = null;
                }
            }
        }
    },
    emits: ['addUserToMultiEdit'],
    computed: {
        computedCheckedForMultiEdit() {
            return this.userForMultiEdit?.id === this.item?.id &&
                this.userForMultiEdit?.type === this.type &&
                this.userForMultiEdit?.craftId === this.craftId ? 'checked' : null;
        }
    },
    methods: {
        changeUserForMultiEdit(event) {
            if (!event.target.checked) {
                this.$emit('addUserToMultiEdit', null);
                return;
            }

            this.$emit(
                'addUserToMultiEdit',
                {
                    id: this.item.id,
                    type: this.type,
                    craftId: this.craftId,
                    display_name: this.item.first_name && this.item.last_name ?
                        this.item.first_name + ' ' + this.item.last_name :
                        this.item.provider_name,
                    profile_photo_url: this.item.profile_photo_url,
                    assigned_craft_ids: this.item.assigned_craft_ids,
                    shift_ids: this.item.shift_ids,
                    shift_qualifications: this.item.shift_qualifications,
                    craft_are_universally_applicable: this.craft?.universally_applicable ?? false,
                    craft_abbreviation: this.craft?.abbreviation
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
