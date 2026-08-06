<template>
    <div class="flex items-center gap-x-2">
        <!-- Existing file: download link + remove -->
        <template v-if="hasFile">
            <component :is="IconFile" class="size-5 shrink-0 text-text-subtle" aria-hidden="true"/>
            <button type="button"
                    class="truncate font-medium text-accent-600 hover:text-accent-700 hover:underline text-left"
                    :title="fileName"
                    @click="download">
                {{ fileName }}
            </button>
            <button type="button"
                    class="text-text-subtle hover:text-danger hover:animate-pulse duration-200 ease-in-out shrink-0"
                    :disabled="busy"
                    @click="remove">
                <component :is="IconTrash" class="h-5 w-5" aria-hidden="true"/>
            </button>
        </template>

        <!-- No file yet: select to upload immediately -->
        <template v-else>
            <label class="flex items-center gap-x-2 cursor-pointer text-text-subtle hover:text-text-muted">
                <component :is="busy ? IconLoader2 : IconUpload"
                           :class="['size-5 shrink-0', busy ? 'animate-spin' : '']" aria-hidden="true"/>
                <span class="truncate font-medium">
                    {{ busy ? $t('Uploading…') : (required ? $t('Select a file') + ' *' : $t('Select a file')) }}
                </span>
                <input ref="inputRef" type="file" class="sr-only" :disabled="busy" @change="onSelect"/>
            </label>
        </template>
    </div>
    <p v-if="error" class="text-xs text-danger mt-1">{{ error }}</p>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { IconFile, IconTrash, IconUpload, IconLoader2 } from '@tabler/icons-vue'

const { t } = useI18n()

const props = defineProps({
    modelValue: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
const busy = ref(false)
const error = ref('')

const hasFile = computed(() => typeof props.modelValue === 'string' && props.modelValue.length > 0)
const fileName = computed(() => (hasFile.value ? props.modelValue.split('/').pop() : ''))

const onSelect = async (event) => {
    const file = event.target.files?.[0]
    if (!file) {
        return
    }

    error.value = ''
    busy.value = true

    const formData = new FormData()
    formData.append('file', file)

    try {
        const { data } = await axios.post(
            route('inventory-management.articles.property-file.upload'),
            formData,
        )
        emit('update:modelValue', data.path)
    } catch (e) {
        const messages = e?.response?.data?.errors?.file
        error.value = Array.isArray(messages) ? messages[0] : t('Upload failed')
    } finally {
        busy.value = false
        if (inputRef.value) {
            inputRef.value.value = ''
        }
    }
}

const download = () => {
    if (!hasFile.value) {
        return
    }
    const link = document.createElement('a')
    link.href = route('inventory-management.articles.property-file.download', { path: props.modelValue })
    link.click()
}

const remove = async () => {
    const path = props.modelValue
    busy.value = true
    error.value = ''
    try {
        if (typeof path === 'string' && path.length > 0) {
            await axios.delete(route('inventory-management.articles.property-file.delete'), {
                data: { path },
            })
        }
    } catch (e) {
        // Even if the file could not be deleted server-side, clear the value so
        // the user can pick a new one; the orphan can be cleaned up later.
    } finally {
        busy.value = false
        emit('update:modelValue', null)
    }
}
</script>
