<template>
    <ArtworkBaseModal @closed="$emit('closed')">
        <template #header>
            {{ $t('Edit individual time') }}
        </template>

        <div class="p-4 space-y-4">
            <!-- Datum (nur Anzeige) -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">
                    {{ $t('Date') }}
                </label>
                <div class="text-sm text-zinc-900 bg-zinc-100 rounded-lg px-3 py-2">
                    {{ formatDate(individualTime.start_date) }}
                </div>
            </div>

            <!-- Titel -->
            <div>
                <BaseInput
                    v-model="form.title"
                    :label="$t('Title')"
                />
            </div>

            <!-- Ganztägig Checkbox -->
            <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    id="full_day"
                    v-model="form.full_day"
                    class="rounded border-zinc-300 text-artwork-buttons-create focus:ring-artwork-buttons-create"
                />
                <label for="full_day" class="text-sm text-zinc-700">
                    {{ $t('All day') }}
                </label>
            </div>

            <!-- Zeiten (nur wenn nicht ganztägig) -->
            <div v-if="!form.full_day" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1">
                        {{ $t('Start time') }}
                    </label>
                    <input
                        type="time"
                        v-model="form.start_time"
                        class="w-full rounded-lg border-zinc-300 shadow-sm focus:border-artwork-buttons-create focus:ring-artwork-buttons-create"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1">
                        {{ $t('End time') }}
                    </label>
                    <input
                        type="time"
                        v-model="form.end_time"
                        class="w-full rounded-lg border-zinc-300 shadow-sm focus:border-artwork-buttons-create focus:ring-artwork-buttons-create"
                    />
                </div>
            </div>

            <!-- Pause -->
            <div v-if="!form.full_day">
                <label class="block text-sm font-medium text-zinc-700 mb-1">
                    {{ $t('Break (minutes)') }}
                </label>
                <input
                    type="number"
                    v-model.number="form.break_minutes"
                    min="0"
                    class="w-full rounded-lg border-zinc-300 shadow-sm focus:border-artwork-buttons-create focus:ring-artwork-buttons-create"
                />
            </div>
        </div>

        <div class="flex justify-end gap-3 p-4 border-t border-zinc-200">
            <button
                type="button"
                @click="$emit('closed')"
                class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50"
            >
                {{ $t('Cancel') }}
            </button>
            <button
                type="button"
                @click="save"
                :disabled="saving"
                class="px-4 py-2 text-sm font-medium text-white bg-artwork-buttons-create rounded-lg hover:bg-artwork-buttons-hover disabled:opacity-50"
            >
                {{ saving ? $t('Saving...') : $t('Save') }}
            </button>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'

const props = defineProps({
    individualTime: { type: Object, required: true }
})

const emit = defineEmits(['closed', 'saved'])

const saving = ref(false)

const form = reactive({
    title: props.individualTime.title ?? '',
    start_time: props.individualTime.start_time ? String(props.individualTime.start_time).slice(0, 5) : '',
    end_time: props.individualTime.end_time ? String(props.individualTime.end_time).slice(0, 5) : '',
    break_minutes: props.individualTime.break_minutes ?? 0,
    full_day: props.individualTime.full_day ?? false,
})

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const dd = String(d.getDate()).padStart(2, '0')
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const yyyy = d.getFullYear()
    return `${dd}.${mm}.${yyyy}`
}

async function save() {
    saving.value = true

    const data = {
        title: form.title,
        start_time: form.full_day ? null : form.start_time,
        end_time: form.full_day ? null : form.end_time,
        break_minutes: form.full_day ? 0 : form.break_minutes,
    }

    router.patch(
        route('individual-times.update-single', { individualTime: props.individualTime.id }),
        data,
        {
            preserveScroll: true,
            onSuccess: () => {
                saving.value = false
                emit('saved')
                emit('closed')
            },
            onError: () => {
                saving.value = false
            }
        }
    )
}
</script>
