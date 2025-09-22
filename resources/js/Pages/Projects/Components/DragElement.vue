<template>
    <div class="card glassy-shiftplan !rounded-lg">
        <div
            class="drag-item w-full p-2 text-white text-xs flex items-center gap-2 relative !rounded-lg"
            :class="page.props.auth.user.compact_mode ? 'h-8 flex items-center justify-between' : 'h-12'"
            draggable="true"
            @dragstart="onDragStart"
            :style="{ backgroundColor: backgroundColorWithOpacity(color, 25) + '!important' }"
        >
            <div class="text-white" v-if="!page.props.auth.user.compact_mode">
                <img
                    :src="item.profile_photo_url"
                    alt=""
                    class="h-6 w-6 rounded-full object-cover min-w-6 min-h-6"
                />
            </div>

            <div class="text-left cursor-pointer flex items-center gap-2 w-full">
                <div>
                    <!-- Typ 0/1: Intern/Extern -->
                    <div
                        v-if="type === 0 || type === 1"
                        class="text-ellipsis"
                        :class="page.props.auth.user.compact_mode ? 'w-32' : 'w-24'"
                    >
                        <div class="flex">
                            <div :class="isManagingCraft ? 'underline truncate' : 'truncate'">
                                {{ item.first_name }} {{ item.last_name }}
                            </div>
                        </div>
                    </div>

                    <!-- Typ 2: Provider -->
                    <div
                        v-else
                        class="text-ellipsis"
                        :class="page.props.auth.user.compact_mode ? 'w-32' : 'w-24'"
                    >
                        <div class="flex">
                            <div :class="isManagingCraft ? 'underline truncate' : 'truncate'">
                                {{ item.provider_name }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center w-26">
                        <div
                            v-if="!page.props.auth.user.compact_mode && type === 0"
                            class="text-[9px] w-full"
                            :class="workTimeBalanceClass"
                        >
                            {{ workTimeBalance }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end w-fit gap-2 absolute right-2 top-2">
                <div v-if="(type === 0 && item.is_freelancer) || type === 1">
                    <ToolTipComponent
                        :icon="IconId"
                        icon-size="w-4 h-4"
                        tooltip-text="Freelancer*in"
                        direction="top"
                        classes="text-white"
                    />
                </div>

                <a v-if="type === 0" :href="route('user.edit.shiftplan', item.id)">
                    <IconCalendarShare class="w-4 h-4" />
                </a>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';
import { IconId, IconCalendarShare } from '@tabler/icons-vue';
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
const {
    backgroundColorWithOpacity
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
}>()

/**
 * workTimeBalanceClass wie vorher als computed
 * Erwartetes Format: "1h 30m" / "-2h 0m" etc.
 */
const workTimeBalanceClass = computed(() => {
    const val = props.workTimeBalance
    if (!val) return 'text-white'

    // Robust gegen Formvarianten: entferne Leerzeichen
    const compact = val.replace(/\s+/g, '')
    // Zeige bei negativen Zeiten rot, bei positiven grün, sonst weiß.
    const sign = compact.startsWith('-') ? -1 : 1
    // Stunden extrahieren
    const hMatch = compact.match(/-?\d+(?=h)/i)
    const mMatch = compact.match(/-?\d+(?=m)/i)
    const hours = hMatch ? parseInt(hMatch[0], 10) : 0
    const minutes = mMatch ? parseInt(mMatch[0], 10) : 0
    const total = sign * (Math.abs(hours) * 60 + Math.abs(minutes))

    if (total > 0) return 'text-green-200'
    if (total < 0) return 'text-red-200'
    return 'text-white'
})

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
