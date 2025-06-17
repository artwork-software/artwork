
<template>
    <!--<div class="card glassy !rounded-lg">
        <div @click="$emit('highlightShiftsOfUser', item.id, type)" class="w-full p-2 text-white text-xs !rounded-lg flex items-center gap-2 "  :style="{backgroundColor: backgroundColorWithOpacity(color), divStyle}">
            <div v-if="!$page.props.auth.user.compact_mode">
                <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover">
            </div>
            <div class="text-left cursor-pointer" :class="[$page.props.auth.user.compact_mode ? 'h-4' : 'h-8']">
                <div v-if="type === 0" class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-36' : 'w-28'">
                    <div class="flex">
                        <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1">(i)</div>
                    </div>
                    <div class="text-xs w-full flex" v-if="!$page.props.auth.user.compact_mode"> {{ plannedHours }}  {{ expectedHours ? ' | ' + expectedHours : '' }}</div>
                </div>
                <div v-else-if="type === 1" class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-36' : 'w-28'">
                    <div class="flex">
                        <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">
                            {{ item.first_name }} {{ item.last_name }}
                        </div>
                        <div class="ml-1"> (e) </div>
                    </div>
                    <div class="text-xs w-full" v-if="!$page.props.auth.user.compact_mode">{{ plannedHours }}</div>
                </div>
                <div v-else class="text-ellipsis" :class="$page.props.auth.user.compact_mode ? 'w-36' : 'w-28'">
                    <div class="flex">
                        <div :class="this.isManagingCraft ? 'underline truncate' : 'truncate'">
                            {{ item.provider_name }}
                        </div>
                        <div class="ml-1"> (DL) </div>
                    </div>
                    <div class="text-xs w-full" v-if="!$page.props.auth.user.compact_mode">{{ plannedHours }}</div>
                </div>
            </div>
        </div>
    </div>-->
    <div class="card glassy !rounded-lg">
        <div @click="$emit('highlightShiftsOfUser', item.id, type)" :class="[$page.props.auth.user.compact_mode ? 'h-8 flex items-center justify-between' : 'h-12']" class="drag-item w-full p-2 text-white text-xs flex items-center gap-2 relative !rounded-lg" :style="{backgroundColor: backgroundColorWithOpacity(color) + '!important'}">
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
                        classes="text-white"
                    />
                </div>
                <a v-if="type === 0" :href="route('user.edit.shiftplan', item.id)" class="">
                    <IconCalendarShare class="w-4 h-4" />
                </a>
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
    name: "HighlightUserCell",
    components: {IconCalendarShare, ToolTipComponent},
    props: [
        'item',
        'type',
        'plannedHours',
        'expectedHours',
        'highlightedUser',
        'color',
        'isManagingCraft'
    ],
    emits: ['highlightShiftsOfUser'],
    mixins: [ColorHelper],
    methods: {

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
