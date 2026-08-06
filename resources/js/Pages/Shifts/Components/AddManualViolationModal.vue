<template>
    <ArtworkBaseModal
        :title="$t('Add rule violation')"
        description=""
        modal-size="max-w-lg"
        @close="$emit('close')"
    >
        <div class="space-y-5 text-sm">
            <!-- Rule selection -->
            <div>
                <label class="block text-xs font-semibold tracking-wide text-text-subtle uppercase mb-1.5">
                    {{ $t('Rule') }}
                </label>
                <SearchableSelect
                    v-model="form.shift_rule_id"
                    :options="availableRules"
                    value-key="id"
                    label-key="name"
                    :placeholder="$t('Select rule')"
                />
                <p v-if="form.errors.shift_rule_id" class="mt-1 text-xs text-danger">
                    {{ form.errors.shift_rule_id }}
                </p>
            </div>

            <!-- Date -->
            <div>
                <label class="block text-xs font-semibold tracking-wide text-text-subtle uppercase mb-1.5">
                    {{ $t('Date') }}
                </label>
                <BaseInput
                    type="date"
                    id="violation_date"
                    v-model="form.violation_date"
                    :label="$t('Date')"
                    :show-label="false"
                    no-margin-top
                />
                <p v-if="form.errors.violation_date" class="mt-1 text-xs text-danger">
                    {{ form.errors.violation_date }}
                </p>
            </div>

            <!-- Severity -->
            <div>
                <label class="block text-xs font-semibold tracking-wide text-text-subtle uppercase mb-1.5">
                    {{ $t('Severity') }}
                </label>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            v-model="form.severity"
                            value="warning"
                            class="text-warning focus:ring-warning"
                        />
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-warning"></span>
                            {{ $t('Warning') }}
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            v-model="form.severity"
                            value="error"
                            class="text-danger focus:ring-danger"
                        />
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-danger"></span>
                            {{ $t('Error') }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Reason -->
            <div>
                <label class="block text-xs font-semibold tracking-wide text-text-subtle uppercase mb-1.5">
                    {{ $t('Reason') }}
                </label>
                <BaseTextarea
                    id="reason"
                    v-model="form.reason"
                    :label="$t('Reason')"
                    :show-label="false"
                    no-margin-top
                />
            </div>

            <!-- Footer -->
            <div class="flex justify-between pt-2 border-t border-border-subtle mt-2">
                <BaseUIButton
                    :label="$t('Cancel')"
                    is-cancel-button
                    @click="$emit('close')"
                />
                <BaseUIButton
                    :label="$t('Save')"
                    is-add-button
                    :disabled="form.processing"
                    @click="submit"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import SearchableSelect from '@/Artwork/Listbox/SearchableSelect.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const props = defineProps({
    userId: { type: Number, required: true },
    date: { type: String, required: true },
    availableRules: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'created']);

const form = useForm({
    user_id: props.userId,
    shift_rule_id: null,
    violation_date: props.date,
    reason: '',
    severity: 'warning',
});

function submit() {
    form.post(route('shift-rule-violations.manual.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('created');
            emit('close');
        },
    });
}
</script>
