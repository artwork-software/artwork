<template>
    <div class="flex items-center">
        <div class="flex items-center pr-2">
            <input :checked="computedCheckedForMultiEdit" @change="changeUserForMultiEdit" :disabled="Object.keys(multiEditCellByDayAndUser).length !== 0" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist-dark" :class="[$page.props.auth.user.compact_mode ? 'h-3 w-3 ' : 'h-5 w-5 ', Object.keys(multiEditCellByDayAndUser).length !== 0 ? 'cursor-not-allowed' : '']" />
        </div>

        <div class="card glassy !rounded-lg">
            <div :class="[$page.props.auth.user.compact_mode ? 'h-8 flex items-center justify-between' : 'h-12']" class="drag-item w-40 p-2 text-white text-xs !rounded-lg flex items-center gap-2" :style="{backgroundColor: backgroundColorWithOpacity(color)}">
                <div class="text-white" v-if="!$page.props.auth.user.compact_mode">
                    <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover min-w-6 min-h-6">
                </div>
                <div class="text-left cursor-pointer flex items-center gap-2 w-full">
                    <div>
                        <div v-if="type === 0" class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-32' : 'w-20'">
                            <div class="flex">
                                <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">
                                    {{ item.first_name }} {{ item.last_name }}
                                </div>
                            </div>

                        </div>
                        <div v-else-if="type === 1" class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-32' : 'w-20'">
                            <div class="flex">
                                <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">
                                    {{ item.first_name }} {{ item.last_name }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-36' : 'w-24'">
                            <div class="flex">
                                <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">{{ item.provider_name }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-center w-26">
                            <div class="text-[9px] w-full " v-if="!$page.props.auth.user.compact_mode && type === 0"> {{plannedHours}}  {{expectedHours ? ' | ' + expectedHours : ''}}</div>
                            <div class="text-[9px] w-full" v-if="!$page.props.auth.user.compact_mode && type !== 0">{{ plannedHours }}</div>
                        </div>
                    </div>

                </div>
                <div class="flex items-center justify-end w-fit gap-2 absolute right-2 top-2">
                    <div v-if="type === 0 && item.is_freelancer || type === 1">
                        <ToolTipComponent
                            icon="IconId"
                            icon-size="w-4 h-4"
                            tooltip-text="Freelancer*in"
                            direction="top"
                            classes="text-gray-300"
                        />
                    </div>
                    <a v-if="type === 0" :href="route('user.edit.shiftplan', item.id)" class="">
                        <IconCalendarShare class="w-4 h-4" />
                    </a>
                </div>

            </div>
        </div>
    </div>


</template>
<script>
import {defineComponent} from 'vue'
import ColorHelper from "@/Mixins/ColorHelper.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconCalendarShare} from "@tabler/icons-vue";

export default defineComponent({
    name: "MultiEditUserCell",
    components: {IconCalendarShare, ToolTipComponent},
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
        'craft',
        'multiEditCellByDayAndUser',
        'isManagingCraft'
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
                    craft_abbreviation: this.craft?.abbreviation,
                    multiEditType: 'MultiEditUserCell'
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
