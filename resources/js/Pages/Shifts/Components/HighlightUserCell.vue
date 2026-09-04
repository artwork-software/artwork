
<template>

    <div class="w-full">
        <div @click="$emit('highlightShiftsOfUser', item.id, type)" :class="[$page.props.auth.user.compact_mode ? 'h-8 flex items-center justify-between' : 'h-12']" class="drag-item w-full p-2 text-white text-xs flex items-center gap-2 relative !rounded-lg border" :style="{backgroundColor: backgroundColorWithOpacity(color), borderColor : color+'80'}">
            <div class="text-white" v-if="!$page.props.auth.user.compact_mode">
                <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover min-w-6 min-h-6">
            </div>
            <div class="text-left cursor-pointer flex items-center gap-2 w-full">
                <div>
                    <div v-if="type === 0" class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-32' : 'w-24'">
                        <div class="flex">
                            <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">
                                {{ item.first_name }} {{ item.last_name }}
                            </div>
                        </div>
                    </div>
                    <div v-else-if="type === 1" class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-32' : 'w-24'">
                        <div class="flex">
                            <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">
                                {{ item.first_name }} {{ item.last_name }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-32' : 'w-24'">
                        <div class="flex">
                            <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">{{ item.provider_name }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-center w-26">
                        <div class="text-[9px] w-full " :class="workTimeBalanceClass" :title="workTimeBalanceTooltip" v-if="!$page.props.auth.user.compact_mode && type === 0 && workTimeBalance">{{ workTimeBalance }}</div>
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
                        classes="text-white"
                    />
                </div>
                <button
                    v-if="type === 0 && enableInfoModal && (can('can view shift user kpis') || is('artwork admin'))"
                    type="button"
                    class="hover:opacity-70 transition-opacity"
                    :title="$t('Key figures')"
                    @click.stop="$emit('openUserInfoModal', item.id)"
                >
                    <PropertyIcon name="IconInfoCircle" class="w-4 h-4" />
                </button>
                <a v-if="type === 0" :href="route('user.edit.shiftplan', item.id)" class="">
                    <PropertyIcon name="IconCalendarShare" class="w-4 h-4" />
                </a>
            </div>

        </div>

    </div>
</template>
<script>
import {defineComponent} from 'vue'
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import {can, is} from "laravel-permission-to-vuejs";
import {useWorkTimeBalanceBadge} from "@/Composeables/useWorkTimeBalanceBadge.js";

export default defineComponent({
    name: "HighlightUserCell",
    components: {PropertyIcon, ToolTipComponent},
    props: [
        'item',
        'type',
        //'plannedHours',
        //'expectedHours',
        'highlightedUser',
        'color',
        'isManagingCraft',
        'workTimeBalance',
        'workTimeBalanceMinutes',
        'enableInfoModal'
    ],
    emits: ['highlightShiftsOfUser', 'openUserInfoModal'],
    setup(props) {
        const {backgroundColorWithOpacityOld: backgroundColorWithOpacity} = useColorHelper();
        const {balanceClass, balanceTooltip} = useWorkTimeBalanceBadge(props);
        return {backgroundColorWithOpacity, can, is, workTimeBalanceClass: balanceClass, workTimeBalanceTooltip: balanceTooltip};
    },

    computed: {
        divStyle() {
            return {
                opacity: this.highlightedUser ? '1' : '0.3'
            };
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
