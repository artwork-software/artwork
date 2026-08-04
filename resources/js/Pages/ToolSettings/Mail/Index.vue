<template>
    <ToolSettingsHeader :title="$t('Mail')">
        <div v-if="$page.props.flash && $page.props.flash.success"
             class="w-full font-bold text-sm border-1 border-success rounded bg-success p-2 text-white mb-3">
            {{ $page.props.flash.success }}
        </div>

        <SettingsGuideBanner
            class="mt-6 mb-6"
            storage-key="settings-guide.tool.mail"
            title="How does this area work?"
            :paragraphs="[
                'These settings control the mail account for all outgoing emails. Empty fields fall back to the server configuration (.env) — the effective fallback values are shown below the fields.',
                'Changes take effect immediately after saving.',
            ]"
        />

        <div class="max-w-2xl">
            <!-- SMTP settings -->
            <form @submit.prevent="save" class="grid grid-cols-1 gap-4">
                <h3 class="text-sm font-semibold text-text">{{ $t('SMTP server') }}</h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <BaseInput id="mail_host" v-model="form.host" :label="$t('Host')" :error="form.errors.host"/>
                        <p v-if="mailSettings.fallback.host" class="text-xs text-secondary mt-1">
                            {{ $t('Fallback: :value', { value: mailSettings.fallback.host }) }}
                        </p>
                    </div>
                    <div>
                        <BaseInput id="mail_port" v-model="form.port" type="number" :label="$t('Port')" :error="form.errors.port"/>
                        <p v-if="mailSettings.fallback.port" class="text-xs text-secondary mt-1">
                            {{ $t('Fallback: :value', { value: mailSettings.fallback.port }) }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label for="mail_encryption" class="block text-xs font-medium text-text-muted mb-1">
                            {{ $t('Encryption') }}
                        </label>
                        <select id="mail_encryption" v-model="form.encryption"
                                class="w-full rounded-lg border-border text-sm focus:border-artwork-buttons-create focus:ring-artwork-buttons-create">
                            <option value="">{{ $t('Fallback (.env)') }}</option>
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                        <p v-if="form.errors.encryption" class="mt-1 text-xs text-artwork-messages-error">
                            {{ form.errors.encryption }}
                        </p>
                        <p v-if="mailSettings.fallback.encryption" class="text-xs text-secondary mt-1">
                            {{ $t('Fallback: :value', { value: mailSettings.fallback.encryption }) }}
                        </p>
                    </div>
                    <div>
                        <BaseInput id="mail_username" v-model="form.username" :label="$t('Username')" :error="form.errors.username"/>
                    </div>
                </div>

                <div>
                    <BaseInput id="mail_password" v-model="form.password" type="password"
                               :label="$t('Password')" :error="form.errors.password"
                               :disabled="form.clear_password"
                               :placeholder="mailSettings.has_password ? '••••••••' : ''"/>
                    <p class="text-xs text-secondary mt-1">
                        {{ mailSettings.has_password
                            ? $t('A password is stored. Leave blank to keep it, or enter a new one to replace it.')
                            : $t('Leave blank to use the server configuration (.env).') }}
                    </p>
                    <label v-if="mailSettings.has_password" class="mt-2 flex items-center gap-2 text-xs text-secondary">
                        <input type="checkbox" v-model="form.clear_password"
                               class="rounded border-border text-artwork-buttons-create focus:ring-artwork-buttons-create"/>
                        {{ $t('Remove stored password (fall back to the server configuration)') }}
                    </label>
                </div>

                <h3 class="text-sm font-semibold text-text mt-4">{{ $t('Sender (From)') }}</h3>
                <SettingsGuideBanner
                    variant="static"
                    title="Interaction with Communication & Legal"
                    :paragraphs="[
                        'Most notifications use the support email address from Communication & Legal as their sender. The fields here only override that if you set them deliberately.',
                    ]"
                />
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <BaseInput id="mail_from_address" v-model="form.from_address" :label="$t('From address')" :error="form.errors.from_address"/>
                        <p v-if="mailSettings.fallback.from_address" class="text-xs text-secondary mt-1">
                            {{ $t('Fallback: :value', { value: mailSettings.fallback.from_address }) }}
                        </p>
                    </div>
                    <div>
                        <BaseInput id="mail_from_name" v-model="form.from_name" :label="$t('From name')" :error="form.errors.from_name"/>
                        <p v-if="mailSettings.fallback.from_name" class="text-xs text-secondary mt-1">
                            {{ $t('Fallback: :value', { value: mailSettings.fallback.from_name }) }}
                        </p>
                    </div>
                </div>

                <div class="mt-2">
                    <button type="submit" :disabled="form.processing"
                            class="rounded-full bg-artwork-buttons-create px-8 py-3 text-sm font-semibold text-white hover:bg-artwork-buttons-hover disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed">
                        {{ $t('Save') }}
                    </button>
                </div>
            </form>

            <!-- Test mail -->
            <div class="mt-10 border-t border-border-subtle pt-6">
                <h3 class="text-sm font-semibold text-text">{{ $t('Test mail') }}</h3>
                <p class="text-sm text-secondary mt-1 mb-4">
                    {{ $t('Send a test email using the saved settings. Save your changes first.') }}
                </p>

                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <BaseInput id="mail_test_recipient" v-model="testRecipient" type="email" :label="$t('Recipient')"/>
                    </div>
                    <button type="button" @click="sendTestMail" :disabled="testing || !testRecipient"
                            class="rounded-full border border-artwork-buttons-create px-8 py-3 text-sm font-semibold text-artwork-buttons-create hover:bg-artwork-buttons-create/10 disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed">
                        {{ testing ? $t('Sending…') : $t('Test mail') }}
                    </button>
                </div>

                <div v-if="testResult"
                     :class="[
                        'mt-4 rounded p-3 text-sm border-1 whitespace-pre-wrap break-words',
                        testResult.success
                            ? 'border-success bg-success-surface text-success'
                            : 'border-danger bg-danger-surface text-danger'
                     ]">
                    {{ testResult.message }}
                </div>
            </div>
        </div>
    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import {useForm} from "@inertiajs/vue3";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

export default defineComponent({
    name: 'MailSettingsIndex',
    components: {
        ToolSettingsHeader,
        BaseInput,
        SettingsGuideBanner,
    },
    props: {
        mailSettings: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            form: useForm({
                host: this.mailSettings.host ?? '',
                port: this.mailSettings.port ?? '',
                encryption: this.mailSettings.encryption ?? '',
                username: this.mailSettings.username ?? '',
                password: '',
                clear_password: false,
                from_address: this.mailSettings.from_address ?? '',
                from_name: this.mailSettings.from_name ?? '',
            }),
            testRecipient: this.$page.props.auth?.user?.email ?? '',
            testing: false,
            testResult: null,
        };
    },
    methods: {
        save() {
            this.form.patch(route('tool.mail.update'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.form.password = '';
                    this.form.clear_password = false;
                },
            });
        },
        async sendTestMail() {
            if (!this.testRecipient) {
                return;
            }

            this.testing = true;
            this.testResult = null;

            try {
                const response = await axios.post(route('tool.mail.test'), {
                    recipient: this.testRecipient,
                });

                this.testResult = {
                    success: response.data.success,
                    message: response.data.message,
                };
            } catch (error) {
                this.testResult = {
                    success: false,
                    message: error.response?.data?.message
                        || error.response?.data?.errors?.recipient?.[0]
                        || this.$t('Sending the test email failed.'),
                };
            } finally {
                this.testing = false;
            }
        },
    },
});
</script>
