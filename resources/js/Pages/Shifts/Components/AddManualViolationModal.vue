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
                <label class="block text-xs font-semibold tracking-wide text-zinc-500 uppercase mb-1.5">
                    {{ $t('Rule') }}
                </label>
                <select
                    v-model="form.shift_rule_id"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-artwork-buttons-hover focus:ring-1 focus:ring-artwork-buttons-hover"
                >
                    <option :value="null" disabled>{{ $t('Select rule') }}</option>
                    <option
                        v-for="rule in availableRules"
                        :key="rule.id"
                        :value="rule.id"
                    >
                        {{ rule.name }}
                    </option>
                </select>
                <p v-if="form.errors.shift_rule_id" class="mt-1 text-xs text-red-500">
                    {{ form.errors.shift_rule_id }}
                </p>
            </div>

            <!-- Date -->
            <div>
                <label class="block text-xs font-semibold tracking-wide text-zinc-500 uppercase mb-1.5">
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
                <p v-if="form.errors.violation_date" class="mt-1 text-xs text-red-500">
                    {{ form.errors.violation_date }}
                </p>
            </div>

            <!-- Severity -->
            <div>
                <label class="block text-xs font-semibold tracking-wide text-zinc-500 uppercase mb-1.5">
                    {{ $t('Severity') }}
                </label>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            v-model="form.severity"
                            value="warning"
                            class="text-amber-500 focus:ring-amber-500"
                        />
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ $t('Warning') }}
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            v-model="form.severity"
                            value="error"
                            class="text-red-500 focus:ring-red-500"
                        />
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            {{ $t('Error') }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Reason -->
            <div>
                <label class="block text-xs font-semibold tracking-wide text-zinc-500 uppercase mb-1.5">
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
            <div class="flex justify-between pt-2 border-t border-zinc-100 mt-2">
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
