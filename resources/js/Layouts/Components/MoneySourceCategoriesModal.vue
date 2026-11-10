<template>
    <ArtworkBaseModal
        title="Manage source categories"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <div v-if="categoryOptions.length > 0" class="space-y-3">
            <ArtworkBaseListbox
                v-model="selectedCategories"
                :items="categoryOptions"
                by="id"
                option-label="name"
                label="Source categories"
                placeholder="Select source categories"
                multiple
            />
            <ul v-if="selectedCategories.length > 0" class="flex flex-wrap gap-2">
                <li
                    v-for="category in selectedCategories"
                    :key="category.id"
                    class="inline-flex items-center gap-x-1 rounded-md border border-border-subtle bg-surface-sunken py-1 pl-2.5 pr-1 text-xs font-medium text-text"
                >
                    <span class="truncate">{{ category.name }}</span>
                    <button
                        type="button"
                        class="inline-flex size-5 shrink-0 cursor-pointer items-center justify-center rounded text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                        :aria-label="$t('Remove')"
                        @click="removeCategory(category)"
                    >
                        <PropertyIcon name="IconX" class="size-3.5" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </li>
            </ul>
        </div>
        <EmptyState
            v-else
            icon="IconCategory"
            :title="$t('No source categories have been created yet')"
        />
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="categoryOptions.length === 0 || processing"
                @click="attachCategories"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineOptions({name: 'MoneySourceCategoriesModal'})

const props = defineProps({
    show: {type: Boolean, default: true},
    closeModal: {type: Function, required: true},
    moneySourceId: {type: Number, required: true},
    moneySourceCategories: {type: Array, default: () => []},
    moneySourceCurrentCategories: {type: Array, default: () => []},
})

const processing = ref(false)

const categoryOptions = computed(() => props.moneySourceCategories ?? [])

// Bereits zugeordnete Kategorien über die Auswahlliste auflösen, damit die Objekte identisch sind
const currentCategoryIds = (props.moneySourceCurrentCategories ?? []).map((category) => category.id)
const selectedCategories = ref(
    categoryOptions.value.filter((category) => currentCategoryIds.includes(category.id))
)

function removeCategory(categoryToRemove) {
    selectedCategories.value = selectedCategories.value.filter((category) => category.id !== categoryToRemove.id)
}

function attachCategories() {
    if (processing.value) {
        return
    }
    processing.value = true
    router.post(
        route('money_sources.categories.sync', {moneySource: props.moneySourceId}),
        {categoryIds: selectedCategories.value.map((category) => category.id)},
        {
            preserveScroll: true,
            onSuccess: () => props.closeModal(),
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>
