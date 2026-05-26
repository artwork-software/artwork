<template>
    <div class="mt-10 border-t border-gray-200 pt-8">
        <div class="sm:flex sm:items-center mb-6">
            <div class="sm:flex-auto">
                <h2 class="text-lg font-semibold text-gray-900">{{ $t('BI Tags') }}</h2>
                <p class="text-sm text-gray-500">{{ $t('Define tags for BI counting of event types.') }}</p>
            </div>
        </div>

        <!-- Add new tag form -->
        <div class="flex items-end gap-3 mb-6 max-w-2xl">
            <BaseInput id="tag_name" v-model="newTag.name" :label="$t('Internal name')" class="flex-1" />
            <BaseInput id="tag_name_de" v-model="newTag.name_de" :label="$t('Display name (DE)')" class="flex-1" />
            <BaseInput id="tag_color" v-model="newTag.color" :label="$t('Color')" type="color" class="w-20" />
            <BaseUIButton @click="createTag" :label="$t('Add')" is-add-button :disabled="!newTag.name || !newTag.name_de" />
        </div>

        <!-- Warning: tags without linked event types -->
        <div v-if="tagsWithoutEventTypes.length > 0" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $t('The following tags are not linked to any event type yet, so they will not be counted in the dashboard and exports:') }}
            <span class="font-medium">{{ tagsWithoutEventTypes.map(t => t.name_de).join(', ') }}</span>
        </div>

        <!-- Tags list -->
        <div class="space-y-4" v-if="tags.length > 0">
            <div v-for="tag in tags" :key="tag.id" class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span v-if="tag.color" class="w-4 h-4 rounded-full" :style="{ backgroundColor: tag.color }"></span>
                        <span class="font-medium text-sm">{{ tag.name_de }}</span>
                        <span class="text-xs text-gray-400">({{ tag.name }})</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="deleteTag(tag.id)" class="text-xs text-red-500 hover:text-red-700">
                            {{ $t('Delete') }}
                        </button>
                    </div>
                </div>

                <!-- Event type assignment -->
                <div class="mt-2">
                    <ArtworkBaseListbox
                        :model-value="getAssignedEventTypes(tag)"
                        @update:model-value="syncEventTypes(tag.id, $event)"
                        :items="eventTypes"
                        by="id"
                        option-label="name"
                        :label="$t('Assigned event types')"
                        :placeholder="$t('Select event types')"
                        multiple
                    />
                </div>
            </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('No BI tags defined yet.') }}</p>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';

const props = defineProps({
    eventTypes: { type: Array, default: () => [] },
});

const tags = ref([]);

const tagsWithoutEventTypes = computed(() => tags.value.filter(tag => !(tag.event_types && tag.event_types.length)));
const newTag = ref({ name: '', name_de: '', color: '#6366f1' });

const fetchTags = async () => {
    try {
        const response = await axios.get(route('bi.tags.index'));
        tags.value = response.data;
    } catch (error) {
        console.error('Error loading BI tags', error);
    }
};

const createTag = async () => {
    if (!newTag.value.name || !newTag.value.name_de) return;
    try {
        await axios.post(route('bi.tags.store'), newTag.value);
        newTag.value = { name: '', name_de: '', color: '#6366f1' };
        await fetchTags();
    } catch (error) {
        console.error('Error creating tag', error);
    }
};

const deleteTag = async (tagId) => {
    if (!confirm('Delete this BI tag?')) return;
    try {
        await axios.delete(route('bi.tags.destroy', tagId));
        await fetchTags();
    } catch (error) {
        console.error('Error deleting tag', error);
    }
};

const getAssignedEventTypes = (tag) => {
    return tag.event_types || [];
};

const syncEventTypes = async (tagId, selectedEventTypes) => {
    try {
        const ids = selectedEventTypes.map(et => et.id);
        await axios.post(route('bi.tags.sync-event-types', tagId), { event_type_ids: ids });
        await fetchTags();
    } catch (error) {
        console.error('Error syncing event types', error);
    }
};

onMounted(fetchTags);
</script>
