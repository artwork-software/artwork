<template>
    <ArtworkBaseModal :title="title" modal-size="sm:max-w-xl" @close="$emit('close')">
        <div class="space-y-4">
            <p class="text-sm text-text-muted">{{ $t(description) }}</p>

            <label v-if="exactLabel" class="flex cursor-pointer items-start gap-3 rounded-xl border border-border-subtle bg-surface-sunken/60 px-4 py-3">
                <input
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600"
                    :checked="exact"
                    @change="$emit('update:exact', $event.target.checked)"
                />
                <span>
                    <span class="block text-sm font-medium text-text">{{ $t(exactLabel) }}</span>
                    <span v-if="exactDescription" class="block text-xs text-text-muted">{{ $t(exactDescription) }}</span>
                </span>
            </label>

            <div v-if="added.length" class="rounded-xl border border-success-border bg-success-surface px-4 py-3">
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-success">
                    {{ $t('{count} permissions will be added', { count: added.length }) }}
                </p>
                <ul class="space-y-1 text-sm text-text">
                    <li v-for="name in added" :key="name" class="flex items-start gap-2">
                        <PropertyIcon name="IconPlus" class="mt-0.5 size-3.5 shrink-0 text-success" />
                        <span>
                            {{ $t(titles[name] ?? name) }}
                            <span v-if="modulesByName[name]" class="text-text-subtle"> · {{ $t(modulesByName[name]) }}</span>
                        </span>
                    </li>
                </ul>
            </div>

            <div v-if="removed.length" class="rounded-xl border border-danger-border bg-danger-surface px-4 py-3">
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-danger">
                    {{ $t('{count} permissions will be removed', { count: removed.length }) }}
                </p>
                <ul class="space-y-1 text-sm text-text">
                    <li v-for="name in removed" :key="name" class="flex items-start gap-2">
                        <PropertyIcon name="IconMinus" class="mt-0.5 size-3.5 shrink-0 text-danger" />
                        <span>
                            {{ $t(titles[name] ?? name) }}
                            <span v-if="modulesByName[name]" class="text-text-subtle"> · {{ $t(modulesByName[name]) }}</span>
                        </span>
                    </li>
                </ul>
            </div>

            <p v-if="!added.length && !removed.length" class="rounded-xl border border-dashed border-border px-4 py-3 text-sm text-text-subtle">
                {{ $t('Nothing changes – the person already has all of these permissions.') }}
            </p>
        </div>
        <template #footer>
            <div class="mt-6 flex items-center justify-end gap-3">
                <BaseUIButton variant="ghost" hide-icon label="Cancel" @click="$emit('close')" />
                <BaseUIButton
                    :variant="removed.length ? 'danger' : 'primary'"
                    hide-icon
                    :label="confirmLabel"
                    :disabled="!added.length && !removed.length"
                    @click="$emit('confirm')"
                />
            </div>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
/**
 * Vorschau vor einer Mehrfach-Änderung: Rollenbild anwenden, Rechte wie Person, Stufe entfernen.
 */
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    added: { type: Array, default: () => [] },
    removed: { type: Array, default: () => [] },
    titles: { type: Object, default: () => ({}) },
    /** name => Modul-Titel */
    modulesByName: { type: Object, default: () => ({}) },
    confirmLabel: { type: String, default: 'Apply' },
    /** Optionaler Schalter (z. B. "auch überzählige Rechte entfernen"); null = kein Schalter */
    exactLabel: { type: String, default: null },
    exactDescription: { type: String, default: null },
    exact: { type: Boolean, default: false },
})
defineEmits(['close', 'confirm', 'update:exact'])
</script>
