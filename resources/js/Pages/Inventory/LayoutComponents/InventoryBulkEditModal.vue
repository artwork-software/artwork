<template>
    <!-- Sammeländerung der ausgewählten Artikel: Kategorie setzen, Tags hinzufügen oder entfernen -->
    <ArtworkBaseModal
        modalSize="sm:max-w-lg"
        :title="title"
        :description="$t('The change applies to the {count} selected articles.', { count: articleIds.length })"
        @close="$emit('close')"
    >
        <div class="mt-2 space-y-4">
            <template v-if="mode === 'category'">
                <ArtworkBaseListbox
                    v-model="selectedCategory"
                    :items="categoryOptions"
                    by="id"
                    option-label="name"
                    :label="$t('Category')"
                    :placeholder="$t('Please select')"
                />
                <ArtworkBaseListbox
                    v-if="subCategoryOptions.length > 1"
                    v-model="selectedSubCategory"
                    :items="subCategoryOptions"
                    by="id"
                    option-label="name"
                    :label="$t('Subcategory')"
                />
                <p class="text-xs text-text-subtle">
                    {{ $t('Property values of the articles are kept. The previous subcategory is replaced.') }}
                </p>
            </template>

            <template v-else>
                <p v-if="!tagOptions.length" class="text-sm text-text-subtle">{{ $t('No tags available.') }}</p>
                <div v-else class="flex flex-wrap gap-2">
                    <button
                        v-for="tag in tagOptions"
                        :key="tag.id"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-colors"
                        :class="selectedTagIds.includes(tag.id) ? 'border-accent-600 bg-accent-50 text-accent-700' : 'border-border-subtle bg-white text-text-muted hover:bg-surface-sunken'"
                        :aria-pressed="selectedTagIds.includes(tag.id)"
                        @click="toggleTag(tag.id)"
                    >
                        <span class="inline-block size-2 rounded-full" :style="{ backgroundColor: tag.color || '#4f46e5' }"></span>
                        {{ tag.name }}
                        <IconCheck v-if="selectedTagIds.includes(tag.id)" class="size-3.5" />
                    </button>
                </div>
            </template>

            <p v-if="errorMessage" class="text-sm text-danger" role="alert">{{ errorMessage }}</p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <BaseUIButton type="button" variant="secondary" hide-icon label="Cancel" use-translation @click="$emit('close')" />
                <BaseUIButton type="button" hide-icon label="Apply" use-translation :disabled="!canSubmit || processing" @click="submit" />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import { IconCheck } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useTranslation } from '@/Composeables/Translation.js'

defineOptions({ name: 'InventoryBulkEditModal' })

const $t = useTranslation()

const props = defineProps({
    // 'category' | 'add_tags' | 'remove_tags'
    mode: { type: String, required: true },
    articleIds: { type: Array, required: true },
    categories: { type: [Array, Object], default: () => [] },
    tags: { type: [Array, Object], default: () => [] },
})

const emit = defineEmits(['close', 'done'])

const title = computed(() => ({
    category: $t('Change category'),
    add_tags: $t('Add tags'),
    remove_tags: $t('Remove tags'),
}[props.mode]))

const categoryOptions = computed(() => Object.values(props.categories ?? {}))
const tagOptions = computed(() => Object.values(props.tags ?? {}))

const selectedCategory = ref(null)
const noSubCategory = { id: null, name: $t('Without Subcategory') }
const selectedSubCategory = ref(noSubCategory)
const subCategoryOptions = computed(() => [noSubCategory, ...(selectedCategory.value?.subcategories ?? [])])
// andere Kategorie: die gewählte Unterkategorie passt nicht mehr
watch(selectedCategory, () => {
    selectedSubCategory.value = noSubCategory
})

const selectedTagIds = ref([])
const toggleTag = (tagId) => {
    selectedTagIds.value = selectedTagIds.value.includes(tagId)
        ? selectedTagIds.value.filter((id) => id !== tagId)
        : [...selectedTagIds.value, tagId]
}

const canSubmit = computed(() =>
    props.mode === 'category' ? !!selectedCategory.value?.id : selectedTagIds.value.length > 0
)

const processing = ref(false)
const errorMessage = ref('')

const submit = () => {
    if (!canSubmit.value || processing.value) return
    processing.value = true
    errorMessage.value = ''

    const payload = { ids: props.articleIds, action: props.mode }
    if (props.mode === 'category') {
        payload.inventory_category_id = selectedCategory.value.id
        payload.inventory_sub_category_id = selectedSubCategory.value?.id ?? null
    } else {
        payload.tag_ids = selectedTagIds.value
    }

    axios.post(route('inventory-management.articles.bulk-update'), payload)
        .then(() => emit('done'))
        .catch((error) => {
            const errors = error?.response?.data?.errors
            errorMessage.value = (errors ? Object.values(errors).flat()[0] : null)
                ?? error?.response?.data?.message
                ?? $t('The change could not be saved.')
        })
        .finally(() => {
            processing.value = false
        })
}
</script>
