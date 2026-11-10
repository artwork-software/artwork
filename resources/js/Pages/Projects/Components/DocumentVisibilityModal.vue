<script setup>
import { computed, ref } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

/**
 * "Sichtbar in": Upload-Bereiche der Dateiupload-Komponente, in denen eine Datei erscheint.
 * Vorbelegt mit dem aktuellen Stand; "Standard" = alle Bereiche, deren Tab-Auswahl den Upload-Tab enthält.
 */
const props = defineProps({
    placements: { type: Array, required: true }, // [{ key, label }]
    selectedKeys: { type: Array, required: true },
    defaultKeys: { type: Array, default: () => [] },
    currentKey: { type: String, default: null },
    fileName: { type: String, default: '' },
    saving: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'save'])

const selected = ref([...props.selectedKeys])
const description = computed(() => props.fileName
    ? $t('Choose in which upload areas "{name}" is shown.', { name: props.fileName })
    : $t('Choose in which upload areas new files are shown.'))
const isDefault = computed(() =>
    [...selected.value].sort().join('|') === [...props.defaultKeys].sort().join('|')
)

function resetToDefault() {
    selected.value = [...props.defaultKeys]
}
</script>

<template>
    <ArtworkBaseModal
        modal-size="sm:max-w-xl"
        :title="$t('Visible in')"
        :description="description"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <div class="max-h-72 space-y-2 overflow-auto rounded-lg border border-border-subtle p-3">
                <label
                    v-for="placement in placements"
                    :key="placement.key"
                    class="flex items-center gap-2 text-sm text-text"
                >
                    <input v-model="selected" type="checkbox" class="input-checklist" :value="placement.key" />
                    <span>{{ placement.label || $t('Upload area') }}</span>
                    <span v-if="placement.key === currentKey" class="rounded-full bg-accent-50 px-2 py-0.5 text-[11px] text-accent-700">
                        {{ $t('This area') }}
                    </span>
                </label>
            </div>
            <div class="flex items-center justify-between gap-3">
                <button
                    type="button"
                    class="text-xs text-accent-600 underline underline-offset-2 disabled:text-text-subtle disabled:no-underline"
                    :disabled="isDefault"
                    @click="resetToDefault"
                >
                    {{ $t('Reset to standard (all areas showing this tab)') }}
                </button>
                <div class="flex items-center gap-3">
                    <BaseUIButton type="button" hide-icon @click="emit('close')">{{ $t('Cancel') }}</BaseUIButton>
                    <BaseUIButton
                        type="button"
                        variant="primary"
                        hide-icon
                        :disabled="saving || selected.length === 0"
                        @click="emit('save', isDefault ? null : selected)"
                    >
                        {{ $t('Save') }}
                    </BaseUIButton>
                </div>
            </div>
            <p v-if="selected.length === 0" class="text-xs text-danger">{{ $t('Select at least one upload area.') }}</p>
        </div>
    </ArtworkBaseModal>
</template>
