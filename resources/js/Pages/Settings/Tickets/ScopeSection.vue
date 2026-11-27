<template>
    <section class="grid gap-4 border-t border-border-hairline pt-5">
        <div class="flex items-baseline justify-between gap-3">
            <h3 class="font-lexend text-[13px] font-semibold text-text">{{ $t('Valid for') }}</h3>
            <span v-if="!allEvents && productions.length > 0" class="text-xs text-text-subtle tabular-nums">
                {{ $t('{count} of {total} productions', { count: coveredCount, total: productions.length }) }}
            </span>
        </div>
        <ArtworkBaseToggle :id="`${idPrefix}-all-events`" v-model="allEvents" :label="$t('Valid for all productions')" :description="allDescription" is-small />
        <template v-if="!allEvents">
            <p v-if="productions.length === 0" class="text-xs text-text-subtle">{{ $t('Artwork-Tickets has no productions yet. Release one from a project first.') }}</p>
            <template v-else>
                <div>
                    <span :class="labelClass">{{ $t('Production groups') }}</span>
                    <div v-if="groups.length > 0" class="grid gap-2 sm:grid-cols-2">
                        <div v-for="group in groups" :key="group.id" class="rounded-lg border px-3 py-2.5 transition-colors"
                             :class="groupIds.includes(group.id) ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface'">
                            <BaseCheckbox :id="`${idPrefix}-group-${group.id}`" :label="group.name"
                                          :description="$t('{count} productions', { count: group.eventIds.length }, group.eventIds.length)"
                                          :model-value="groupIds.includes(group.id)"
                                          @update:model-value="(on) => pickGroup(group.id, on)" />
                        </div>
                    </div>
                    <p v-if="groups.length > 0" class="mt-1 text-xs text-text-subtle">{{ $t('Also counts for whatever joins a group later.') }}</p>
                    <p v-else class="text-xs text-text-subtle">
                        {{ $t('No production groups yet.') }}
                        <Link :href="route('settings.tickets.groups')" class="text-text underline underline-offset-2">{{ $t('Create one under Production groups, e.g. for all workshops.') }}</Link>
                    </p>
                </div>
                <div>
                    <span :class="labelClass">{{ $t('Single productions') }}</span>
                    <ProductionPicker v-model="eventIds" :productions="productions" :covered-by="coveredBy" :id-prefix="`${idPrefix}-production`" />
                </div>
                <p v-if="coveredCount === 0" class="text-xs text-text-subtle">{{ emptyHint }}</p>
            </template>
        </template>
    </section>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import ProductionPicker from '@/Pages/Settings/Tickets/ProductionPicker.vue'

/* Where a pass or discount counts: every production, or picked groups plus single productions. */
const props = defineProps({
    productions: { type: Array, required: true },
    groups: { type: Array, required: true },
    idPrefix: { type: String, required: true },
    allDescription: { type: String, required: true },
    emptyHint: { type: String, required: true },
})

const allEvents = defineModel('allEvents', { type: Boolean, required: true })
const eventIds = defineModel('eventIds', { type: Array, required: true })
const groupIds = defineModel('groupIds', { type: Array, required: true })

const labelClass = 'mb-1 block font-lexend text-xs font-medium text-[#3F424A]'

/** Productions counted through a picked group: id → the group names. */
const coveredBy = computed(() => {
    const names = {}
    for (const group of props.groups.filter((candidate) => groupIds.value.includes(candidate.id))) {
        for (const id of group.eventIds) {
            names[id] = names[id] ? `${names[id]}, ${group.name}` : group.name
        }
    }
    return names
})

const coveredCount = computed(() => {
    const listed = new Set(props.productions.map((production) => production.id))
    return new Set([...eventIds.value, ...Object.keys(coveredBy.value)].filter((id) => listed.has(id))).size
})

function pickGroup(id, on) {
    groupIds.value = on ? [...groupIds.value, id] : groupIds.value.filter((groupId) => groupId !== id)
}
</script>
