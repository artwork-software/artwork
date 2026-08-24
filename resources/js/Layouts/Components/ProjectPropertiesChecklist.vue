<template>
    <div>
        <button type="button"
                class="flex items-center gap-x-1.5 cursor-pointer"
                @click="expanded = !expanded">
            <span class="font-semibold text-sm">{{ t('Project properties') }}</span>
            <span v-if="!expanded && selectedCount > 0" class="text-xs text-text-subtle">
                · {{ t('{0} selected', [selectedCount]) }}
            </span>
            <IconChevronDown class="h-4 w-4 text-text-subtle transition-transform duration-150"
                             :class="{ 'rotate-180': expanded }"
                             aria-hidden="true"/>
        </button>
        <div v-if="expanded" class="mt-3 space-y-4">
            <template v-for="group in groups" :key="group.field">
            <div v-if="group.items?.length > 0">
                <h3 class="text-xs font-medium text-text-subtle mb-2">{{ t(group.label) }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                    <div v-for="item in group.items"
                         :key="item.id"
                         class="flex items-center justify-between gap-2 min-w-0">
                        <label class="flex items-center gap-3 cursor-pointer min-w-0">
                            <div class="group grid size-4 shrink-0 grid-cols-1">
                                <input type="checkbox"
                                       v-model="form[group.field]"
                                       :value="item.id"
                                       @change="syncMain(group)"
                                       class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-white checked:border-accent-600 checked:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 forced-colors:appearance-auto"/>
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span class="flex items-center gap-2 text-sm text-text truncate">
                                <span v-if="item.color"
                                      class="size-2.5 rounded-full border border-border-subtle shrink-0"
                                      :style="{ backgroundColor: item.color }"/>
                                {{ item.name }}
                            </span>
                        </label>
                        <label v-if="form[group.field]?.includes(item.id)"
                               class="flex items-center gap-0.5 text-[10px] text-text-subtle cursor-pointer shrink-0"
                               :title="t(group.mainLabel)">
                            <input type="checkbox"
                                   class="size-3 accent-warning"
                                   :checked="form[group.mainField] === item.id"
                                   @change="toggleMain(group.mainField, item.id)"/>
                            <span>{{ t('Main') }}</span>
                        </label>
                    </div>
                </div>
            </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { IconChevronDown } from '@tabler/icons-vue';
import { useTranslation } from '@/Composeables/Translation.js';

const props = defineProps({
    // useForm-Objekt mit assignedCategoryIds/assignedGenreIds/assignedSectorIds + main*Id-Feldern
    form: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    genres: { type: Array, default: () => [] },
    sectors: { type: Array, default: () => [] },
});

const t = useTranslation();

const selectedCount = computed(() =>
    (props.form.assignedCategoryIds?.length ?? 0)
    + (props.form.assignedGenreIds?.length ?? 0)
    + (props.form.assignedSectorIds?.length ?? 0)
);

// Bei bestehender Auswahl (Edit) direkt aufgeklappt, sonst eingeklappt
const expanded = ref(selectedCount.value > 0);

const groups = computed(() => [
    { label: 'Category', items: props.categories, field: 'assignedCategoryIds', mainField: 'mainCategoryId', mainLabel: 'Main category' },
    { label: 'Genre', items: props.genres, field: 'assignedGenreIds', mainField: 'mainGenreId', mainLabel: 'Main genre' },
    { label: 'Areas', items: props.sectors, field: 'assignedSectorIds', mainField: 'mainSectorId', mainLabel: 'Main sector' },
]);

const toggleMain = (mainField, id) => {
    props.form[mainField] = props.form[mainField] === id ? null : id;
};

const syncMain = (group) => {
    if (props.form[group.mainField] && !props.form[group.field]?.includes(props.form[group.mainField])) {
        props.form[group.mainField] = null;
    }
};
</script>
