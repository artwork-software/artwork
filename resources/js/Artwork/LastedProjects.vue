<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, computed } from 'vue';

type LastedProject = {
    id: number | string;
    name: string;
    updatedAt?: string;
    key_visual_path?: string | null;
    is_group?: boolean;
};

const props = withDefaults(
    defineProps<{
        storageKey?: string;
        limit?: number;
        emptyText?: string;
        withoutGroup?: boolean;
        onlyGroups?: boolean;
    }>(),
    {
        storageKey: 'lastedProjects',
        limit: 10,
        emptyText: 'Keine zuletzt geöffneten Projekte',
        withoutGroup: false,
        onlyGroups: false,
    }
);

const emit = defineEmits<{
    (e: 'select', project: LastedProject): void;
    (e: 'clear'): void;
}>();

const items = ref<LastedProject[]>([]);
const isLoading = ref(true);

function load() {
    try {
        const raw = localStorage.getItem(props.storageKey || 'lastedProjects');
        const parsed = raw ? JSON.parse(raw) : [];
        items.value = Array.isArray(parsed) ? parsed.slice(0, props.limit) : [];
    } catch {
        items.value = [];
    } finally {
        isLoading.value = false;
    }
}

function onSelect(p: LastedProject) {
    emit('select', p);
}

function onKeydown(e: KeyboardEvent, p: LastedProject) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        onSelect(p);
    }
}

function clearList() {
    localStorage.removeItem(props.storageKey);
    items.value = [];
    emit('clear');
}

onMounted(() => {
    load();
    window.addEventListener('storage', load);
});

onBeforeUnmount(() => {
    window.removeEventListener('storage', load);
});

// 👇 Filterlogik erweitert: onlyGroups hat Vorrang
const filteredItems = computed(() => {
    if (props.onlyGroups) return items.value.filter(p => p.is_group);
    if (props.withoutGroup) return items.value.filter(p => !p.is_group);
    return items.value;
});

const hasItems = computed(() => filteredItems.value.length > 0);
</script>

<template>
    <div class="w-full my-4">
        <!-- Kopfzeile -->
        <div class="mb-2 flex items-center justify-between">
            <h3 class="text-sm font-semibold leading-none">
                {{
                    props.onlyGroups
                        ? $t('Recently opened project groups')
                        : props.withoutGroup
                            ? $t('Recently opened individual projects')
                            : $t('Recently opened projects')
                }}
            </h3>
            <button
                v-if="hasItems"
                class="text-[11px] underline underline-offset-2 hover:text-blue-600 duration-200 ease-in-out cursor-pointer"
                type="button"
                @click="clearList"
            >
                {{ $t('Clear') }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <div v-for="i in 6" :key="i" class="animate-pulse rounded-lg border p-2 flex gap-2">
                <div class="h-8 w-8 rounded bg-gray-200" />
                <div class="flex-1">
                    <div class="h-3 bg-gray-200 rounded w-3/4 mb-1" />
                    <div class="h-2.5 bg-gray-200 rounded w-1/3" />
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div v-else-if="!hasItems" class="text-xs text-gray-500 italic py-2">
            {{ emptyText }}
        </div>

        <!-- Liste -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <div
                v-for="p in filteredItems"
                :key="p.id"
                class="group rounded-lg border border-zinc-300 p-2 flex gap-2 items-center hover:shadow-sm transition cursor-pointer"
                role="button"
                tabindex="0"
                @click="onSelect(p)"
                @keydown="onKeydown($event, p)"
                :aria-label="`Projekt öffnen: ${p.name}`"
                :title="p.name"
            >
                <!-- Key Visual -->
                <div class="relative shrink-0">
                    <img
                        v-if="p.key_visual_path"
                        :src="'/storage/keyVisual/' + p.key_visual_path"
                        alt=""
                        class="h-8 w-8 rounded object-cover"
                        @error="(e:any) => (e.target.style.display='none')"
                    />
                    <div v-else class="h-8 w-8 rounded bg-gray-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4 6a2 2 0 012-2h3l2 2h7a2 2 0 012 2v1M4 6v12a2 2 0 002 2h12a2 2 0 002-2V9M8 13h8M8 17h5"/>
                        </svg>
                    </div>
                </div>

                <!-- Text -->
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-medium leading-4">{{ p.name }}</p>
                    <p v-if="p.is_group" class="text-[10px] text-gray-500 leading-none mt-0.5">
                        {{ $t('Group') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
[role="button"]:focus {
    outline: 2px solid rgba(59,130,246,0.6);
    outline-offset: 2px;
    border-color: rgba(59,130,246,0.4);
}
</style>
