<template>
    <ArtworkBaseModal :title="definition.title" modal-size="sm:max-w-2xl" @close="$emit('close')">
        <div class="space-y-5">
            <div>
                <p class="text-sm text-text">{{ $t(definition.effect) }}</p>
                <p class="mt-1 font-mono text-[11px] text-text-subtle">{{ definition.name }}</p>
            </div>

            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-[150px_1fr]">
                <template v-if="definition.unlocks?.length">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('Unlocks') }}</dt>
                    <dd class="text-sm text-text-muted">
                        <ul class="space-y-0.5">
                            <li v-for="item in definition.unlocks" :key="item">{{ $t(item) }}</li>
                        </ul>
                    </dd>
                </template>
                <template v-if="definition.allows?.length">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('Allows') }}</dt>
                    <dd class="text-sm text-text-muted">
                        <ul class="space-y-0.5">
                            <li v-for="item in definition.allows" :key="item">{{ $t(item) }}</li>
                        </ul>
                    </dd>
                </template>
                <template v-if="requirements.length">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('Requirements') }}</dt>
                    <dd>
                        <PermissionRequirementChips :requirements="requirements" :titles="titles" @jump="$emit('jump', $event)" />
                    </dd>
                </template>
                <template v-if="definition.implies?.length">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('Includes') }}</dt>
                    <dd class="text-sm text-text-muted">{{ definition.implies.map((n) => $t(titles[n] ?? n)).join(' · ') }}</dd>
                </template>
                <template v-if="supersets.length">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('Included in') }}</dt>
                    <dd class="text-sm text-text-muted">{{ supersets.map((n) => $t(titles[n] ?? n)).join(' · ') }}</dd>
                </template>
                <template v-if="definition.personas?.length">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('Typical for') }}</dt>
                    <dd class="text-sm text-text-muted">{{ definition.personas.map((p) => $t(p)).join(' · ') }}</dd>
                </template>
                <template v-if="definition.note">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('Note') }}</dt>
                    <dd class="text-sm text-text-muted">{{ $t(definition.note) }}</dd>
                </template>
                <template v-if="usageCount !== null">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle pt-0.5">{{ $t('In use') }}</dt>
                    <dd class="text-sm text-text-muted">{{ $t('Currently granted to {count} people', { count: usageCount }) }}</dd>
                </template>
            </dl>
        </div>
        <template #footer>
            <div class="mt-6 flex items-center justify-between gap-3">
                <BaseUIButton
                    v-if="editable"
                    :variant="granted ? 'secondary' : 'primary'"
                    hide-icon
                    :label="granted ? 'Remove permission' : 'Grant permission'"
                    @click="$emit('toggle')"
                />
                <span v-else></span>
                <BaseUIButton variant="ghost" hide-icon label="Close" @click="$emit('close')" />
            </div>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PermissionRequirementChips from '@/Artwork/Permissions/PermissionRequirementChips.vue'

defineProps({
    definition: { type: Object, required: true },
    requirements: { type: Array, default: () => [] },
    /** gesetzte Supersets, die dieses Recht enthalten */
    supersets: { type: Array, default: () => [] },
    titles: { type: Object, default: () => ({}) },
    granted: { type: Boolean, default: false },
    editable: { type: Boolean, default: true },
    /** Anzahl Personen mit diesem Recht (null = unbekannt) */
    usageCount: { type: Number, default: null },
})
defineEmits(['close', 'toggle', 'jump'])
</script>
