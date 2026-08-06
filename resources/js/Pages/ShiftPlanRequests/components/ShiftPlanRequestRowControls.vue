<template>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-2xl border border-border-subtle bg-white px-4 py-3 shadow-sm">
        <!-- Typ-Filter als Toggle-Pills -->
        <div class="flex flex-wrap items-center gap-1.5">
            <button
                v-for="option in typeOptions"
                :key="option.type"
                type="button"
                class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
                :class="types[option.type]
                    ? 'border-accent-200 bg-accent-50 text-accent-700'
                    : 'border-border-subtle bg-white text-text-subtle hover:bg-surface-sunken'"
                :aria-pressed="String(!!types[option.type])"
                @click="$emit('toggle-type', option.type)"
            >
                <component :is="types[option.type] ? IconEye : IconEyeOff" class="h-3.5 w-3.5 shrink-0"/>
                <span>{{ $t(option.label) }}</span>
                <span
                    class="inline-flex min-w-[18px] items-center justify-center rounded-full px-1 text-[10px] font-semibold"
                    :class="types[option.type] ? 'bg-accent-100 text-accent-700' : 'bg-surface-sunken text-text-subtle'"
                >
                    {{ counts[option.type] ?? 0 }}
                </span>
            </button>
        </div>

        <!-- Nur Personen mit Einträgen -->
        <ArtworkBaseToggle
            :model-value="onlyWithEntries"
            :label="$t('Only people with shifts')"
            is-small
            @update:model-value="$emit('update:onlyWithEntries', $event)"
        />

        <!-- Sortierung -->
        <div class="ml-auto flex items-center gap-2">
            <IconArrowsSort class="h-4 w-4 shrink-0 text-text-subtle"/>
            <div class="w-56">
                <ArtworkBaseListbox
                    :model-value="selectedSortOption"
                    :items="sortOptions"
                    by="id"
                    option-label="name"
                    use-translations
                    :enable-search="false"
                    :sort-fn="() => 0"
                    is-small
                    :placeholder="$t('Sort')"
                    @update:model-value="$emit('update:sortBy', $event?.id ?? 'type')"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue';
import {IconArrowsSort, IconEye, IconEyeOff} from '@tabler/icons-vue';
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';

const props = defineProps({
    types: {type: Object, required: true},          // {user: bool, freelancer: bool, service_provider: bool}
    counts: {type: Object, default: () => ({})},    // {user: n, freelancer: n, service_provider: n}
    onlyWithEntries: {type: Boolean, default: false},
    sortBy: {type: String, default: 'type'},
});

defineEmits(['toggle-type', 'update:onlyWithEntries', 'update:sortBy']);

const typeOptions = [
    {type: 'user', label: 'Internal employees'},
    {type: 'freelancer', label: 'Freelancer'},
    {type: 'service_provider', label: 'Service provider'},
];

const sortOptions = [
    {id: 'type', name: 'Grouped by type'},
    {id: 'name_asc', name: 'Name (A–Z)'},
    {id: 'name_desc', name: 'Name (Z–A)'},
    {id: 'shifts_desc', name: 'Most shifts first'},
    {id: 'shifts_asc', name: 'Fewest shifts first'},
    {id: 'hours_desc', name: 'Most hours first'},
];

const selectedSortOption = computed(
    () => sortOptions.find(o => o.id === props.sortBy) ?? sortOptions[0]
);
</script>
