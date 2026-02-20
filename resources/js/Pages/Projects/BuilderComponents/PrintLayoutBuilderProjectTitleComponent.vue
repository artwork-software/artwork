<template>
    <div class="flex items-center font-lexend font-black tracking-wide text-sm text-primary">
        <!-- Avatar / Key Visual / Icon -->
        <div class="relative mr-2">
            <!-- Gruppen-Badge -->
            <span
                v-if="isGroup"
                class="print:hidden absolute -right-1 -top-2 inline-flex items-center justify-center text-white"
                aria-hidden="true"
            >
        <img src="/Svgs/IconSvgs/icon_group_black.svg" class="size-4 min-w-4 min-h-4" />
      </span>

            <!-- Avatar-Wrapper: gleiche Größe, unterschiedliche Inhalte -->
            <div
                class="mx-auto size-8 min-w-8 min-h-8 rounded-full shadow-xs flex items-center justify-center"
                :class="avatarWrapperClass"
                role="img"
                :aria-label="avatarAriaLabel"
            >
                <!-- 1) Bild (Key Visual) -->
                <img
                    v-if="hasKeyVisual"
                    :src="imageSrc"
                    :alt="$t('Current key visual')"
                    class="size-8 min-w-8 min-h-8 rounded-full object-cover"
                    loading="lazy"
                    decoding="async"
                    @error="onImgError"
                />

                <!-- 2) Icon (Gruppe mit speziellem Icon) -->
                <PropertyIcon
                    v-else-if="isGroup && !!project.icon"
                    :name="String(project.icon)"
                    :style="avatarStyle"
                    class="size-5 min-w-5 min-h-5"
                    aria-hidden="true"
                />

                <!-- 3) Icon (Gruppe ohne spezielles Icon) -->
                <PropertyIcon
                    v-else-if="isGroup && !project.icon"
                    name="IconFolders"
                    :style="avatarStyle"
                    class="size-5 min-w-5 min-h-5"
                    aria-hidden="true"
                />

                <!-- 4) Icon (Einzelprojekt ohne Key Visual) -->
                <PropertyIcon
                    v-else
                    name="IconGeometry"
                    :style="avatarStyle"
                    class="size-5 min-w-5 min-h-5"
                    aria-hidden="true"
                />
            </div>
        </div>

        <!-- Titel -->
        <div class="flex items-center gap-x-1 text-dark transition-colors duration-300 ease-in-out group-hover/project:text-artwork-buttons-create">
            {{ project.title }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';

interface Project {
    title: string;
    is_group?: boolean | 0 | 1;
    key_visual_path?: string | null;
    icon?: string | null;
    color?: string | null;
}

const props = defineProps<{
    project: Project;
}>();

const page = usePage();

// Zustände
const isGroup = computed<boolean>(() => Boolean(props.project?.is_group));
const hasKeyVisual = computed<boolean>(() => Boolean(props.project?.key_visual_path));

// Bildquelle & Fallback
const imageSrc = computed<string>(() =>
    hasKeyVisual.value ? `/storage/keyVisual/${props.project.key_visual_path}` : ''
);

function onImgError(e: Event) {
    const el = e.target as HTMLImageElement | null;
    const fallback = (page?.props as any)?.big_logo;
    if (el && typeof fallback === 'string' && fallback.length) {
        el.src = fallback;
    }
}

// Styles/Klassen
const avatarStyle = computed(() => ({
    color: props.project?.color ?? undefined,
}));

const avatarWrapperClass = computed(() =>
    hasKeyVisual.value
        ? 'bg-transparent border border-transparent'
        : 'bg-zinc-50 border border-zinc-100'
);

const avatarAriaLabel = computed(() => {
    if (hasKeyVisual.value) return String(page?.props?.$t?.('Current key visual') ?? 'Current key visual');
    if (isGroup.value) return 'Group icon';
    return 'Project icon';
});
</script>

<style scoped>
/* Platzhalter für spätere Feinheiten */
</style>
