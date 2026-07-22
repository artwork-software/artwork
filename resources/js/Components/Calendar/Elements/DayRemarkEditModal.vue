<template>
    <ArtworkBaseModal
        title="Day remark"
        :description="displayDate"
        :is-in-shift-plan="isInShiftPlan"
        modal-size="sm:max-w-xl"
        @close="$emit('close')"
    >
        <div class="space-y-3">
            <BaseTextarea
                id="day-remark-text"
                v-model="remarkText"
                :label="$t('Day remark')"
                :rows="4"
            />
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span v-if="remark?.updated_by">
                    {{ $t('Last edited by {0} on {1}', [remark.updated_by, remark.updated_at]) }}
                </span>
                <span v-else></span>
                <span :class="{ 'text-red-500 font-semibold': remarkText.length >= maxLength }">
                    {{ remarkText.length }}/{{ maxLength }}
                </span>
            </div>
            <div class="flex items-center justify-between pt-2">
                <BaseUIButton
                    v-if="remark?.text"
                    :label="$t('Remove remark')"
                    is-delete-button
                    :disabled="saving"
                    @click="removeRemark"
                />
                <span v-else></span>
                <BaseUIButton
                    :label="$t('Save')"
                    is-add-button
                    :disabled="saving"
                    @click="save"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useDayRemarks, DAY_REMARK_MAX_LENGTH } from '@/Composeables/useDayRemarks.js'

const props = defineProps({
    // ISO-Datum (YYYY-MM-DD) des Tages
    date: { type: String, required: true },
    // Anzeigedatum (DD.MM.YYYY) für den Modal-Kopf
    displayDate: { type: String, required: true },
    // { text, updated_by, updated_at } | null
    remark: { type: Object, default: null },
    isInShiftPlan: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'saved'])

const { saveDayRemark } = useDayRemarks()

const maxLength = DAY_REMARK_MAX_LENGTH
const remarkText = ref(props.remark?.text ?? '')
const saving = ref(false)

// BaseTextarea reicht kein maxlength ans <textarea> durch — Limit hier durchsetzen
watch(remarkText, (value) => {
    if (value.length > maxLength) {
        remarkText.value = value.slice(0, maxLength)
    }
})

const persist = (text) => {
    saving.value = true
    saveDayRemark(props.date, text)
        .then((data) => {
            emit('saved', data)
            emit('close')
        })
        .finally(() => {
            saving.value = false
        })
}

const save = () => persist(remarkText.value)
const removeRemark = () => persist('')
</script>
