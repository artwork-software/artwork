<template>
    <div>
        <template v-if="property.type === 'text' || property.type === 'link'">
            <BaseInput
                :id="`prop_${property.id}`"
                :model-value="value"
                @update:model-value="$emit('update:value', $event)"
                :label="$t(property.name) + (required ? ' *' : '')"
                :disabled="disabled"
                @focusout="$emit('update:value', $event.target.value)"
            />
        </template>

        <template v-else-if="property.type === 'textarea'">
            <BaseTextarea
                :id="`prop_${property.id}`"
                :model-value="value"
                @update:model-value="$emit('update:value', $event)"
                :label="$t(property.name) + (required ? ' *' : '')"
                :disabled="disabled"
            />
        </template>

        <template v-else-if="property.type === 'number'">
            <BaseInput
                :id="`prop_${property.id}`"
                type="number"
                :model-value="value"
                @update:model-value="$emit('update:value', $event)"
                :label="$t(property.name) + (required ? ' *' : '')"
                :disabled="disabled"
            />
        </template>

        <template v-else-if="property.type === 'date'">
            <BaseInput
                :id="`prop_${property.id}`"
                type="date"
                :model-value="value"
                @update:model-value="$emit('update:value', $event)"
                :label="$t(property.name) + (required ? ' *' : '')"
                :disabled="disabled"
            />
        </template>

        <template v-else-if="property.type === 'checkbox'">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    :checked="value === '1'"
                    @change="$emit('update:value', $event.target.checked ? '1' : '0')"
                    :disabled="disabled"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <span class="text-sm text-gray-700">{{ $t(property.name) }}<span v-if="required" class="text-red-500 ml-0.5">*</span></span>
            </label>
        </template>

        <template v-else-if="property.type === 'select'">
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <ArtworkBaseListbox
                        :model-value="selectedOption"
                        @update:model-value="onSelectChange"
                        :items="selectOptions"
                        by="id"
                        option-label="name"
                        option-key="id"
                        :label="$t(property.name) + (required ? ' *' : '')"
                        :use-translations="true"
                        :placeholder="$t('Select...')"
                        :disabled="disabled"
                    />
                </div>
                <button
                    v-if="value && !disabled"
                    type="button"
                    @click="$emit('update:value', '')"
                    class="mb-0.5 p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-gray-100"
                    :title="$t('Clear selection')"
                >
                    <IconX class="h-4 w-4" />
                </button>
            </div>
        </template>

        <template v-else-if="property.type === 'upload'">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t(property.name) }}<span v-if="required" class="text-red-500 ml-0.5">*</span></label>

                <!-- Existing file -->
                <div v-if="value" class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 mb-2">
                    <component :is="IconFile" class="h-5 w-5 text-gray-400 shrink-0" />
                    <a :href="'/storage/' + value" target="_blank" download class="min-w-0 flex-1 truncate text-sm font-medium text-blue-700 hover:underline">
                        {{ fileName }}
                    </a>
                    <button
                        v-if="contactId"
                        type="button"
                        class="rounded-md p-1 text-gray-400 hover:text-red-600"
                        @click="deleteFile"
                        :disabled="deleting"
                    >
                        <component :is="IconTrash" class="h-4 w-4" />
                    </button>
                </div>

                <!-- Upload dropzone -->
                <div v-if="contactId">
                    <button
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-4 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        :class="uploading ? 'opacity-50 pointer-events-none' : ''"
                    >
                        <component :is="IconUpload" class="mx-auto h-8 w-8 text-gray-400" stroke-width="1" />
                        <span class="mt-1 block text-sm text-gray-600">
                            {{ uploading ? $t('Uploading...') : $t('Select file') }}
                        </span>
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            @change="uploadFile"
                        />
                    </button>
                </div>
            </div>
        </template>

        <template v-else>
            <BaseInput
                :id="`prop_${property.id}`"
                :model-value="value"
                @update:model-value="$emit('update:value', $event)"
                :label="$t(property.name) + (required ? ' *' : '')"
                :disabled="disabled"
            />
        </template>

        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import { IconFile, IconTrash, IconUpload, IconX } from '@tabler/icons-vue'

const props = defineProps({
    property: { type: Object, required: true },
    value: { type: String, default: '' },
    contactId: { type: Number, default: null },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
})

const emit = defineEmits(['update:value'])

// -- Select type helpers --
const selectOptions = computed(() => {
    return (props.property.select_values ?? [])
        .filter(val => val !== '' && val != null)
        .map(val => ({ id: val, name: val }))
})

const selectedOption = computed(() => {
    if (!props.value) return null
    return selectOptions.value.find(o => o.id === props.value) ?? null
})

const onSelectChange = (option) => {
    emit('update:value', option?.id ?? '')
}

// -- Upload type helpers --
const uploading = ref(false)
const deleting = ref(false)

const fileName = computed(() => {
    if (!props.value) return ''
    return props.value.split('/').pop()
})

const uploadFile = (event) => {
    const file = event.target.files[0]
    if (!file || !props.contactId) return

    uploading.value = true
    router.post(route('crm.contacts.property-file.upload', props.contactId), {
        property_id: props.property.id,
        file: file,
    }, {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            uploading.value = false
            event.target.value = ''
        },
    })
}

const deleteFile = () => {
    if (!props.contactId) return

    deleting.value = true
    router.delete(route('crm.contacts.property-file.delete', props.contactId), {
        data: { property_id: props.property.id },
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { deleting.value = false },
    })
}
</script>
