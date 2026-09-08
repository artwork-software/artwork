<template>
    <div class="w-full">
        <div :class="[page.props.auth.user.compact_mode ? 'h-8 flex items-center justify-between' : 'h-12', { 'border-dashed': item.is_freelancer || type === 1}]" draggable="true" @dragstart="onDragStart"
            class="drag-item w-full p-2 text-white text-xs flex items-center gap-2 relative !rounded-lg border" :style="{backgroundColor: backgroundColorWithOpacityOld(color), borderColor : color+'80'}">

            <div class="text-white" v-if="!page.props.auth.user.compact_mode">
                <UserPopoverTooltip v-if="type === 0 || type === 1" :user="item" :use-slot-instead-of-icon="true" :auto-flip-vertical="true">
                    <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover min-w-6 min-h-6 cursor-pointer"/>
                </UserPopoverTooltip>
                <ServiceProviderPopoverTooltip v-else :provider="item" :use-slot-instead-of-icon="true" :auto-flip-vertical="true">
                    <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover min-w-6 min-h-6 cursor-pointer"/>
                </ServiceProviderPopoverTooltip>
            </div>

            <div class="text-left cursor-pointer flex items-center gap-2 w-full">
                <div>
                    <!-- Typ 0/1: Intern/Extern -->
                    <div v-if="type === 0 || type === 1"
                        class="text-ellipsis"
                        :class="page.props.auth.user.compact_mode ? 'w-32' : 'w-24'">
                        <div class="flex">
                            <div :class="isManagingCraft ? 'underline truncate' : 'truncate'">
                                {{ item.first_name }} {{ item.last_name }}
                            </div>
                        </div>
                    </div>

                    <!-- Typ 2: Provider -->
                    <div v-else
                        class="text-ellipsis"
                        :class="page.props.auth.user.compact_mode ? 'w-32' : 'w-24'">
                        <div class="flex">
                            <div :class="isManagingCraft ? 'underline truncate' : 'truncate'">
                                {{ item.provider_name }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center w-26">
                        <div v-if="!page.props.auth.user.compact_mode && type === 0 && workTimeBalance"
                            class="text-[9px] w-full"
                            :class="workTimeBalanceClass"
                            :title="workTimeBalanceTooltip">
                            {{ workTimeBalance }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end w-fit gap-2 absolute right-2 top-2">
                <div v-if="(type === 0 && item.is_freelancer) || type === 1">
                    <ToolTipComponent
                        icon="IconId"
                        icon-size="w-4 h-4"
                        tooltip-text="Freelancer*in"
                        direction="top"
                        stroke="2"
                        icon-color="text-white"
                    />
                </div>

                <button
                    v-if="type === 0 && enableInfoModal && (can('can view shift user kpis') || is('artwork admin'))"
                    type="button"
                    class="hover:opacity-70 transition-opacity"
                    :title="$t('Key figures')"
                    @click.stop="emit('openUserInfoModal', item.id)"
                >
                    <PropertyIcon name="IconInfoCircle" class="w-4 h-4" />
                </button>

                <a v-if="type === 0" :href="route('user.edit.shiftplan', item.id)">
                    <PropertyIcon name="IconCalendarShare" class="w-4 h-4" />
                </a>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useWorkTimeBalanceBadge } from '@/Composeables/useWorkTimeBalanceBadge.js';
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue';
import ServiceProviderPopoverTooltip from '@/Layouts/Components/ServiceProviderPopoverTooltip.vue';
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import { can, is } from 'laravel-permission-to-vuejs';
const {
    backgroundColorWithOpacityOld
} = useColorHelper();
/**
 * Types (locker, aber hilfreich). Passe bei Bedarf an deine echten DTOs an.
 */
type UserAuth = {
    compact_mode: boolean
}

type PageProps = {
    auth: { user: UserAuth }
}

type Craft = {
    universally_applicable?: boolean
    abbreviation?: string
}

type ItemBase = {
    id: number | string
    profile_photo_url?: string
    assigned_craft_ids?: number[] | string[]
    shift_qualifications?: unknown
    is_freelancer?: boolean
}

type PersonItem = ItemBase & {
    first_name?: string
    last_name?: string
}

type ProviderItem = ItemBase & {
    provider_name?: string
}

type Item = PersonItem & ProviderItem

const props = defineProps<{
    item: Item
    type: 0 | 1 | 2
    plannedHours?: number | string
    color?: string
    craft?: Craft | null
    isManagingCraft?: boolean
    workTimeBalance?: string | null
    workTimeBalanceMinutes?: number | null
    enableInfoModal?: boolean
}>()

const emit = defineEmits<{
    (e: 'openUserInfoModal', id: number | string): void
}>()

/**
 * AZK-Badge: Farbe aus den Rohminuten (Fallback Textformat), Tooltip
 * "Stand: Nachtbuchung bis gestern" – geteilt mit HighlightUserCell/MultiEditUserCell.
 */
const { balanceClass: workTimeBalanceClass, balanceTooltip: workTimeBalanceTooltip } = useWorkTimeBalanceBadge(props)

/**
 * onDragStart – identisch zur Options API Version
 */
function onDragStart(event: DragEvent) {
    if (!event.dataTransfer) return
    event.dataTransfer.setData(
        'application/json',
        JSON.stringify({
            id: props.item.id,
            type: props.type,
            craft_ids: props.item.assigned_craft_ids,
            shift_qualifications: props.item.shift_qualifications,
            craft_universally_applicable: props.craft?.universally_applicable ?? false,
            craft_abbreviation: props.craft?.abbreviation ?? '',
        }),
    )
}

/**
 * $page Äquivalent in Composition API
 */
const page = usePage<PageProps>()
</script>

<style scoped>
.truncate {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
</style>
