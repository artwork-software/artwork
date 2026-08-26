<template>
    <ArtworkBaseModal
        :title="$t('Articles already in this issue')"
        :description="$t('Some articles from the set are already part of this material issue.')"
        modal-size="max-w-2xl"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <p class="text-sm text-text-muted">
                {{ $t('Should the set quantities be added to the existing quantities? Articles that are not yet part of the issue will be added in both cases.') }}
            </p>

            <div class="overflow-hidden rounded-xl border border-border-subtle">
                <table class="min-w-full divide-y divide-border-subtle text-sm">
                    <thead class="bg-surface-sunken/60">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-text-muted">
                            {{ $t('Article') }}
                        </th>
                        <th scope="col" class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-text-muted">
                            {{ $t('Existing quantity') }}
                        </th>
                        <th scope="col" class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-text-muted">
                            {{ $t('Quantity in set') }}
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle bg-white">
                    <tr v-for="overlap in overlaps" :key="overlap.id">
                        <td class="px-4 py-2.5 font-medium text-text">{{ overlap.name }}</td>
                        <td class="px-4 py-2.5 text-right text-text-muted">{{ overlap.existingQuantity }}</td>
                        <td class="px-4 py-2.5 text-right text-text-muted">+ {{ overlap.setQuantity }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <BaseUIButton is-cancel-button :label="$t('Cancel')" @click="$emit('close')" />
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <BaseUIButton variant="secondary" hide-icon :label="$t('Keep existing quantities')" @click="$emit('apply', false)" />
                    <BaseUIButton variant="primary" hide-icon :label="$t('Add quantities')" @click="$emit('apply', true)" />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineProps({
    // [{ id, name, existingQuantity, setQuantity }]
    overlaps: {
        type: Array,
        required: true
    }
})

defineEmits(['close', 'apply'])
</script>
