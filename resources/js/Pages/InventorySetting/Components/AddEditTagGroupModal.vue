<template>
    <ArtworkBaseModal
        :title="tagGroup ? $t('Edit tag group') : $t('Add tag group')"
        :description="tagGroup ? $t('Edit the tag group and its tags.') : $t('Create a new tag group and assign tags to it.')"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 gap-4">
                <BaseInput
                    id="name"
                    v-model="form.name"
                    :label="$t('Tag group name')"
                    required
                    :error="form.errors.name"
                />
            </div>

            <!--<div class="space-y-2">
                <BasePageTitle
                    :title="$t('Tags in this group')"
                    :description="$t('Add or remove tags that belong to this group.')"
                />

                <div class="flex flex-wrap gap-2 mb-3">
                    <TagComponent
                        v-for="tag in selectedTags"
                        :key="tag.id"
                        :property="tag"
                        :displayed-text="tag.name"
                        :method="removeTag"
                    />
                    <span v-if="selectedTags.length === 0" class="text-xs text-gray-400">
                        {{ $t('No tags in this group yet.') }}
                    </span>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-end gap-3">
                        <BaseCombobox
                            class="flex-1"
                            :label="$t('Add existing tag to this group')"
                            :items="availableExistingTags"
                            item-label="name"
                            item-value="id"
                            v-model="selectedExistingTagId"
                            :placeholder="$t('Select a tag')"
                        />
                        <BaseUIButton
                            type="button"
                            is-add-button
                            :label="$t('Add')"
                            @click="addExistingTag"
                            :disabled="!selectedExistingTagId"
                        />
                    </div>
                </div>
            </div>-->

            <div class="flex items-center justify-between gap-3 pt-4 border-t border-gray-100">
                <BaseUIButton
                    is-cancel-button
                    :label="$t('Cancel')"
                    type="button"
                    @click="$emit('close')"
                />
                <BaseUIButton
                    type="submit"
                    is-add-button
                    :label="tagGroup ? $t('Save') : $t('Create')"
                    :disabled="form.processing"
                />
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import {computed, ref} from 'vue'
import {useForm} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCombobox from '@/Artwork/Inputs/BaseCombobox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import TagComponent from '@/Layouts/Components/TagComponent.vue'
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";

const props = defineProps({
    tagGroup: {
        type: Object,
        required: false,
        default: null
    },
    allTags: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['close', 'saved'])

const form = useForm({
    id: props.tagGroup?.id || null,
    name: props.tagGroup?.name || '',
})

const selectedTags = ref([
    ...(props.tagGroup?.tags || [])
])

const selectedExistingTagId = ref(null)

const availableExistingTags = computed(() => {
    const selectedIds = new Set(selectedTags.value.map(t => t.id))
    return props.allTags.filter(tag => !selectedIds.has(tag.id))
})

function addExistingTag() {
    if (!selectedExistingTagId.value) return
    const tag = props.allTags.find(t => t.id === selectedExistingTagId.value)
    if (!tag) return

    // frontend: nur Anzeige, Zuordnung läuft über eigenes Backend-Feature (z.B. Drag&Drop + reorder)
    selectedTags.value.push(tag)
    selectedExistingTagId.value = null
}

function removeTag(tag) {
    selectedTags.value = selectedTags.value.filter(t => t.id !== tag.id)
}

function submit() {
    if (props.tagGroup) {
        form.put(route('settings.inventory-tag-groups.update', props.tagGroup.id), {
            preserveScroll: false,
            preserveState: true,
            onSuccess: () => {
                emit('saved')
                emit('close')
            }
        })
    } else {
        form.post(route('settings.inventory-tag-groups.store'), {
            preserveScroll: false,
            preserveState: true,
            onSuccess: () => {
                emit('saved')
                emit('close')
            }
        })
    }
}
</script>

<style scoped>

</style>
