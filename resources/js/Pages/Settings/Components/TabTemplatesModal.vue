<template>
    <ArtworkBaseModal
        :title="$t('Create tab from template')"
        :description="$t('A template only saves you the manual setup. The result is a normal tab with normal components that you can rename, rearrange, extend or delete like any other.')"
        @close="$emit('close')"
    >
        <div class="mt-4 space-y-4">
            <div
                v-for="template in templates"
                :key="template.key"
                class="rounded-xl border border-border-subtle bg-white p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-text">{{ template.name }}</h3>
                        <p class="mt-1 text-sm text-text-muted">{{ template.description }}</p>
                    </div>
                    <BaseUIButton
                        variant="primary"
                        hide-icon
                        :disabled="applying === template.key"
                        @click="apply(template)"
                    >
                        {{ applying === template.key ? $t('Creating…') : $t('Create tab') }}
                    </BaseUIButton>
                </div>

                <div v-if="template.prerequisites" class="mt-3 rounded-lg bg-accent-50 px-3 py-2 text-xs text-accent-700">
                    {{ template.prerequisites }}
                </div>
                <p v-if="template.sidebar_tabs?.length" class="mt-2 text-xs text-text-subtle">
                    {{ $t('Including sidebar: {names}', { names: template.sidebar_tabs.join(', ') }) }}
                </p>

                <button
                    type="button"
                    class="mt-3 text-xs font-medium text-accent-600 hover:underline"
                    @click="togglePreview(template.key)"
                >
                    {{ openPreview === template.key
                        ? $t('Hide components')
                        : $t('Show components ({count})', { count: template.component_count }) }}
                </button>
                <ol v-if="openPreview === template.key" class="mt-2 max-h-64 overflow-auto rounded-lg border border-border-subtle divide-y divide-border-subtle text-xs">
                    <li
                        v-for="(component, idx) in template.components"
                        :key="idx"
                        class="flex items-center gap-2 px-3 py-1.5"
                        :class="component.type === 'Title' ? 'bg-surface-sunken font-semibold text-text' : 'text-text-muted'"
                    >
                        <span class="w-24 shrink-0 text-[11px] uppercase tracking-wide text-text-subtle">{{ $t(component.type) }}</span>
                        <span class="truncate">{{ component.name }}</span>
                    </li>
                </ol>
            </div>

            <p v-if="!templates.length" class="text-sm text-text-subtle">{{ $t('No templates available.') }}</p>
        </div>

        <div class="flex justify-end mt-6">
            <BaseUIButton variant="secondary" hide-icon @click="$emit('close')">{{ $t('Close') }}</BaseUIButton>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const props = defineProps({
    templates: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'created'])
const $t = useTranslation()

const applying = ref(null)
const openPreview = ref(null)

function togglePreview(key) {
    openPreview.value = openPreview.value === key ? null : key
}

function apply(template) {
    applying.value = template.key
    router.post(route('tab.templates.apply', { template: template.key }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            emit('created', template)
            emit('close')
        },
        onFinish: () => {
            applying.value = null
        },
    })
}
</script>
