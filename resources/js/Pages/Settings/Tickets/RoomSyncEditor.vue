<template>
    <div class="flex flex-col gap-3">
        <p v-if="rooms.length === 0" class="text-sm">{{ $t('There are no rooms yet. You can sync them once they exist.') }}</p>
        <div v-for="room in rooms" :key="room.id" class="rounded-lg border bg-surface transition-[box-shadow,border-color]"
             :class="room.selected ? 'border-accent-200 shadow-[0_0_0_3px_#EEF4FA]' : 'border-border-subtle'">
            <div class="flex items-center gap-3 px-4 py-3.5">
                <BaseCheckbox v-model="room.selected" :id="`tickets-room-${room.id}`">
                    <template #label>
                        <span class="font-lexend text-sm font-medium" :class="room.selected ? 'text-text' : 'text-text-subtle'">{{ room.name }}</span>
                        <span class="ml-2 text-xs text-text-subtle">{{ room.capacity ? $t('{count} places in artwork', { count: room.capacity }) : $t('no capacity recorded') }}</span>
                    </template>
                </BaseCheckbox>
                <div class="ml-auto flex items-center gap-2">
                    <BaseChip v-if="room.linked" variant="neutral">{{ $t('In Artwork-Tickets') }}</BaseChip>
                    <BaseChip v-if="room.selected" variant="success">
                        {{ $t('{count} price classes · {places} places', { count: room.zones.length, places: zonePlaces(room) }) }}
                    </BaseChip>
                    <span v-else class="text-xs text-text-subtle">{{ room.linked ? $t('Not synced now') : $t('Sells no tickets') }}</span>
                </div>
            </div>
            <div v-if="room.selected" class="border-t border-border-hairline pl-11 pr-4 pt-3.5 pb-4">
                <span class="font-lexend mb-1.5 block text-xs font-medium text-[#3F424A]">{{ $t('Address on the ticket') }}</span>
                <div class="grid grid-cols-[minmax(0,1fr)_110px_minmax(0,1fr)_170px] items-end gap-2.5">
                    <BaseInput :id="`tickets-room-${room.id}-street`" v-model="room.street" :label="$t('Street and number')" :show-label="false" :placeholder="$t('Street and number')" is-small />
                    <BaseInput :id="`tickets-room-${room.id}-postal-code`" v-model="room.postal_code" :label="$t('Postal code')" :show-label="false" :placeholder="$t('Postal code')" is-small class="tabular-nums" />
                    <BaseInput :id="`tickets-room-${room.id}-city`" v-model="room.city" :label="$t('City')" :show-label="false" :placeholder="$t('City')" is-small />
                    <SearchableSelect v-model="room.country" :options="countryOptions" :placeholder="$t('Country')" />
                </div>

                <div class="mt-4 grid grid-cols-[minmax(0,1fr)_120px_150px_28px] gap-2.5 mb-1.5 font-lexend text-xs font-medium text-[#3F424A]">
                    <span>{{ $t('Price class') }}</span><span>{{ $t('Places') }}</span><span>{{ $t('Default price') }}</span><span></span>
                </div>
                <div class="flex flex-col gap-2">
                    <div v-for="(zone, zi) in room.zones" :key="zi" class="grid grid-cols-[minmax(0,1fr)_120px_150px_28px] items-center gap-2.5">
                        <BaseInput :id="`tickets-room-${room.id}-zone-${zi}-name`" v-model="zone.name" :label="$t('Price class')" :show-label="false" required is-small />
                        <BaseInput :id="`tickets-room-${room.id}-zone-${zi}-capacity`" v-model="zone.capacity" type="number" :label="$t('Places')" :show-label="false" required :min="0" is-small class="tabular-nums" />
                        <BaseInput :id="`tickets-room-${room.id}-zone-${zi}-price`" v-model="zone.price" type="number" :label="$t('Default price')" :show-label="false" :min="0" step="0.01" placeholder="0,00 €" is-small class="tabular-nums" />
                        <button type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-danger hover:bg-danger-surface disabled:opacity-40 disabled:hover:bg-transparent"
                                :disabled="room.zones.length === 1" :aria-label="$t('Remove')" @click="room.zones.splice(zi, 1)">
                            <IconTrash class="size-[15px]" />
                        </button>
                    </div>
                </div>
                <button type="button" class="mt-2.5 inline-flex items-center gap-1.5 text-[13px] font-medium text-accent-600 hover:underline" @click="room.zones.push(emptyZone($t('Free seating')))">
                    <IconPlus class="size-3.5" stroke-width="2.5" />{{ $t('Add price class') }}
                </button>
                <p class="mt-2.5 text-xs leading-[18px] text-text-subtle">{{ $t('The default price applies while nothing else has been charged in this room. Leave empty if there is none.') }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { IconPlus, IconTrash } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import SearchableSelect from '@/Artwork/Listbox/SearchableSelect.vue'
import { emptyZone, zonePlaces } from '@/Pages/Settings/Tickets/drafts.js'

/* Edits the room drafts in place (see drafts.js); the parent owns the array. */
const props = defineProps({
    rooms: { type: Array, required: true },
    countries: { type: Array, required: true },
})

const { t } = useI18n()

const countryNames = {
    DE: t('Germany'), AT: t('Austria'), CH: t('Switzerland'), LI: t('Liechtenstein'), LU: t('Luxembourg'), NL: t('Netherlands'),
    BE: t('Belgium'), FR: t('France'), DK: t('Denmark'), PL: t('Poland'), CZ: t('Czechia'), IT: t('Italy'),
}

const countryOptions = computed(() => props.countries.map((code) => ({ id: code, name: countryNames[code] ?? code })))
</script>
