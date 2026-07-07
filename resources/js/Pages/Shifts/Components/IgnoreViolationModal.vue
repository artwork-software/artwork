<template>
    <ArtworkBaseModal
        :title="$t('Ignore rule violation')"
        :description="$t('Please provide a reason for ignoring this violation.')"
        @close="$emit('close')"
    >
        <div class="space-y-4 text-sm">
            <div
                class="rounded-lg border px-3 py-2.5"
                :style="{
                    borderColor: violation.warning_color || violation.shift_rule?.warning_color || '#ff0000',
                    backgroundColor: (violation.warning_color || violation.shift_rule?.warning_color || '#ff0000') + '10',
                }"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="inline-block h-2.5 w-2.5 rounded-full shrink-0"
                        :style="{ backgroundColor: violation.warning_color || violation.shift_rule?.warning_color || '#ff0000' }"
                    ></span>
                    <span class="text-xs font-semibold text-zinc-900">
                        {{ violation.rule_name || violation.shift_rule?.name }}
                    </span>
                    <span v-if="violation.violation_date" class="text-xs text-zinc-500 ml-auto">
                        {{ formatDate(violation.violation_date) }}
                    </span>
                </div>
            </div>

            <div>
                <BaseTextarea
                    id="ignore_reason"
                    v-model="ignoreReason"
                    :label="$t('Reason for ignoring')"
                />
                <p v-if="showError && !ignoreReason.trim()" class="mt-1 text-xs text-red-500">
                    {{ $t('Reason for ignoring') }}
                </p>
            </div>

            <div class="flex justify-between pt-2 border-t border-zinc-100">
                <BaseUIButton
                    :label="$t('Cancel')"
                    is-cancel-button
                    @click="$emit('close')"
                />
                <BaseUIButton
                    :label="$t('Ignore')"
                    is-delete-button
                    :disabled="submitting"
                    @click="submit"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const props = defineProps({
    violation: { type: Object, required: true },
});

const emit = defineEmits(['close', 'ignored']);

const ignoreReason = ref('');
const showError = ref(false);
const submitting = ref(false);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function submit() {
    showError.value = true;
    if (!ignoreReason.value.trim()) return;

    submitting.value = true;
    router.post(route('shift-rule-violations.ignore', { violation: props.violation.id }), {
        ignore_reason: ignoreReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => emit('ignored'),
        onFinish: () => { submitting.value = false; },
    });
}
</script>
