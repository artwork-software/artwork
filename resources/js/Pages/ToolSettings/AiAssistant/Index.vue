<template>
    <ToolSettingsHeader :title="$t('AI assistant')">
        <div v-if="$page.props.flash && $page.props.flash.success"
             class="w-full font-bold text-sm border-1 border-success rounded bg-success p-2 text-white mb-3">
            {{ $page.props.flash.success }}
        </div>

        <div v-if="!instanceEnabled"
             class="mt-6 rounded border-1 border-warning bg-warning-surface p-3 text-sm text-warning">
            {{ $t('The AI assistant is switched off for this instance. The settings below can be prepared, but take effect only once the operator enables it.') }}
        </div>

        <div class="max-w-2xl">
            <form @submit.prevent="save" class="grid grid-cols-1 gap-6 mt-6">
                <ArtworkBaseToggle
                    id="ai_enabled"
                    v-model="form.enabled"
                    :label="$t('Use AI assistant')"
                    :description="$t('Makes the assistant available to everyone in this house.')"
                />

                <ArtworkBaseToggle
                    id="ai_level_b_enabled"
                    v-model="form.level_b_enabled"
                    :disabled="!form.enabled"
                    :label="$t('Stage: data analysis')"
                    :description="$t('The assistant may read data of this house — always only what the asking person is allowed to see.')"
                />

                <ArtworkBaseToggle
                    id="ai_level_c_enabled"
                    v-model="form.level_c_enabled"
                    :disabled="!form.enabled || !form.level_b_enabled"
                    :label="$t('Stage: actions')"
                    :description="$t('The assistant may prepare changes. Every change needs an explicit confirmation in artwork.')"
                />
                <div v-if="form.errors.level_c_enabled" class="text-xs text-danger -mt-4">
                    {{ form.errors.level_c_enabled }}
                </div>

                <div>
                    <BaseInput
                        id="ai_transcript_retention_days"
                        v-model="form.transcript_retention_days"
                        type="number"
                        min="0"
                        :label="$t('Keep chat transcripts for (days)')"
                        :error="form.errors.transcript_retention_days"
                    />
                    <p class="text-xs text-text-subtle mt-1">{{ $t('0 = keep indefinitely.') }}</p>
                </div>

                <div class="mt-2">
                    <button type="submit" :disabled="form.processing"
                            class="rounded-full bg-accent-600 px-8 py-3 text-sm font-semibold text-white hover:bg-accent-700 disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed">
                        {{ $t('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import {useForm} from "@inertiajs/vue3";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseToggle from "@/Artwork/Toggles/ArtworkBaseToggle.vue";

export default defineComponent({
    name: 'AiAssistantSettingsIndex',
    components: {
        ToolSettingsHeader,
        BaseInput,
        ArtworkBaseToggle,
    },
    props: {
        aiAssistantSettings: {
            type: Object,
            required: true,
        },
        instanceEnabled: {
            type: Boolean,
            required: true,
        },
    },
    data() {
        return {
            form: useForm({
                enabled: this.aiAssistantSettings.enabled,
                level_b_enabled: this.aiAssistantSettings.level_b_enabled,
                level_c_enabled: this.aiAssistantSettings.level_c_enabled,
                transcript_retention_days: this.aiAssistantSettings.transcript_retention_days,
            }),
        };
    },
    watch: {
        // Eine abgeschaltete Stufe zieht die darauf aufbauende mit. Der Server erzwingt dieselbe
        // Regel (422), das hier verhindert nur, dass ein unmoeglicher Zustand im Formular steht.
        'form.enabled'(value) {
            if (!value) {
                this.form.level_b_enabled = false;
                this.form.level_c_enabled = false;
            }
        },
        'form.level_b_enabled'(value) {
            if (!value) {
                this.form.level_c_enabled = false;
            }
        },
    },
    methods: {
        save() {
            this.form.patch(route('tool.ai-assistant.update'), {
                preserveScroll: true,
            });
        },
    },
});
</script>
