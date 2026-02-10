<template>
    <ArtworkBaseModal
        :title="$t('Edit individual time')"
        :description="$t('Edit the details of this individual time entry.')"
        @close="$emit('closed')"
    >

        <div class="p-4 space-y-4">
            <!-- Datum (nur Anzeige) -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">
                    {{ $t('Date') }}
                </label>
                <div class="text-sm text-zinc-900 bg-zinc-100 rounded-lg px-3 py-2">
                    {{ formatDate(individualTime._day || individualTime.start_date) }}
                </div>
            </div>

            <!-- Titel -->
            <BaseInput
                id="title"
                v-model="form.title"
                :label="$t('Title')"
            />

            <!-- Zeiten -->
            <div class="flex items-center gap-1">
                <BaseInput
                    type="time"
                    id="start_time"
                    classes="rounded-r-none"
                    v-model="form.start_time"
                    :label="$t('Start time')"
                />
                <BaseInput
                    type="time"
                    id="end_time"
                    v-model="form.end_time"
                    classes="border-l-0 rounded-l-none"
                    :label="$t('End time')"
                />
            </div>

            <!-- Pause -->
            <BaseInput
                type="number"
                id="break_minutes"
                v-model.number="form.break_minutes"
                :label="$t('Break (minutes)')"
                :min="0"
                :step="1"
            />
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
})

function formatDate(dateStr) {
    if (!dateStr) return ''
    // Handle YYYY-MM-DD format directly to avoid timezone issues
    if (typeof dateStr === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        const [yyyy, mm, dd] = dateStr.split('-')
        return `${dd}.${mm}.${yyyy}`
    }
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
        start_time: form.start_time,
        end_time: form.end_time,
        break_minutes: form.break_minutes,
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
