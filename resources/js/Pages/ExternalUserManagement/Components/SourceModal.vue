<template>
    <ArtworkBaseModal
        :open="show"
        @close="$emit('close')"
        :title="modalTitle"
        :description="modalDescription"
    >
        <form @submit.prevent="saveSource" class="space-y-6">
            <!-- Name -->
            <div>
                <BaseInput
                    id="name"
                    :label="$t('Name')"
                    v-model="form.name"
                    :error="form.errors.name"
                    required
                />
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-text mb-1">
                    {{ $t('Source Type') }}
                </label>
                <select
                    id="type"
                    v-model="form.type"
                    class="block w-full rounded-lg border-border text-sm focus:border-accent-600 focus:ring-accent-600"
                >
                    <option value="ldap">{{ $t('LDAP / Active Directory') }}</option>
                    <option value="identity_provider">{{ $t('Identity Provider (OIDC / SSO)') }}</option>
                </select>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="active"
                    v-model="form.active"
                    class="h-4 w-4 text-accent-600 focus:ring-accent-600 border-border rounded"
                />
                <label for="active" class="ml-2 block text-sm text-text">
                    {{ $t('Active') }}
                </label>
            </div>

            <!-- ==================== LDAP ==================== -->
            <template v-if="form.type === 'ldap'">
                <!-- Host -->
                <div>
                    <BaseInput
                        id="host"
                        :label="$t('LDAP/AD Host')"
                        v-model="form.config.host"
                        :error="form.errors['config.host']"
                        placeholder="ldaps://ad.domain.tld oder ldap://ad.domain.tld"
                        required
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Example: ldaps://ad.domain.tld or ldap://ad.domain.tld') }}
                    </p>
                </div>

                <!-- Port -->
                <div>
                    <BaseInput
                        id="port"
                        type="number"
                        :label="$t('Port')"
                        v-model.number="form.config.port"
                        :error="form.errors['config.port']"
                        placeholder="389"
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Default: 389 (LDAP) or 636 (LDAPS)') }}
                    </p>
                </div>

                <!-- Base DN -->
                <div>
                    <BaseInput
                        id="base_dn"
                        :label="$t('Base DN')"
                        v-model="form.config.base_dn"
                        :error="form.errors['config.base_dn']"
                        placeholder="DC=domain,DC=tld"
                        required
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Example: DC=domain,DC=tld') }}
                    </p>
                </div>

                <!-- Bind DN -->
                <div>
                    <BaseInput
                        id="bind_dn"
                        :label="$t('Bind DN (Service Account)')"
                        v-model="form.config.bind_dn"
                        :error="form.errors['config.bind_dn']"
                        placeholder="CN=ServiceAccount,OU=ServiceAccounts,DC=domain,DC=tld"
                        required
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Distinguished Name of the service account for LDAP queries') }}
                    </p>
                </div>

                <!-- Bind Password -->
                <div>
                    <BaseInput
                        id="bind_password"
                        type="password"
                        :label="$t('Password')"
                        v-model="form.config.bind_password"
                        :error="form.errors['config.bind_password']"
                        required
                    />
                    <p v-if="source" class="mt-1 text-xs text-warning">
                        {{ $t('For security reasons the stored password is never displayed – every edit of this connection requires re-entering it.') }}
                    </p>
                </div>

                <!-- SSL/TLS Options -->
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="use_ssl"
                            v-model="form.config.use_ssl"
                            class="h-4 w-4 text-accent-600 focus:ring-accent-600 border-border rounded"
                        />
                        <label for="use_ssl" class="ml-2 block text-sm text-text">
                            {{ $t('Use SSL (LDAPS)') }}
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="use_tls"
                            v-model="form.config.use_tls"
                            class="h-4 w-4 text-accent-600 focus:ring-accent-600 border-border rounded"
                        />
                        <label for="use_tls" class="ml-2 block text-sm text-text">
                            {{ $t('Use StartTLS') }}
                        </label>
                    </div>
                    <p class="text-xs text-warning">
                        {{ $t('Recommended: the bind sends the password in clear text – use SSL (LDAPS) or StartTLS.') }}
                    </p>
                </div>

                <!-- User Filter -->
                <div>
                    <BaseTextarea
                        id="user_filter"
                        :label="$t('LDAP Filter (User Query)')"
                        :modelValue="form.config.user_filter"
                        @update:modelValue="form.config.user_filter = $event"
                        rows="3"
                        placeholder="(objectClass=user)"
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('LDAP filter to determine which users should be synchronized. Example: (objectClass=user) or (&(objectClass=user)(memberOf=CN=Artwork_Users,OU=Groups,DC=domain,DC=tld))') }}
                    </p>
                </div>

                <!-- Identifier Attribute (LDAP) -->
                <div>
                    <BaseInput
                        id="identifier_attribute"
                        :label="$t('Identifier Attribute')"
                        v-model="form.config.identifier_attribute"
                        :error="form.errors['config.identifier_attribute']"
                        placeholder="objectGUID"
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('LDAP attribute used as unique identifier (default: objectGUID)') }}
                    </p>
                </div>

                <!-- Default Role -->
                <div>
                    <label for="ldap_default_role" class="block text-sm font-medium text-text mb-1">
                        {{ $t('Default Role') }}
                    </label>
                    <select
                        id="ldap_default_role"
                        v-model="form.config.default_role_id"
                        class="block w-full rounded-lg border-border text-sm focus:border-accent-600 focus:ring-accent-600"
                    >
                        <option :value="null">{{ $t('No default role') }}</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Role assigned to accounts newly provisioned through this connection.') }}
                    </p>
                </div>
            </template>

            <!-- ==================== OIDC / Identity Provider ==================== -->
            <template v-else>
                <!-- Provider preset selection -->
                <div>
                    <label class="block text-sm font-medium text-text mb-2">
                        {{ $t('Provider') }}
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            v-for="preset in providerPresets"
                            :key="preset.value"
                            type="button"
                            @click="form.config.provider_preset = preset.value"
                            class="rounded-lg border px-3 py-3 text-sm font-medium transition"
                            :class="form.config.provider_preset === preset.value
                                ? 'border-accent-600 bg-accent-50 text-accent-700 ring-1 ring-accent-600'
                                : 'border-border text-text-muted hover:bg-surface-sunken'"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <!-- Microsoft Tenant ID -->
                <div v-if="form.config.provider_preset === 'microsoft'">
                    <BaseInput
                        id="tenant_id"
                        :label="$t('Tenant ID')"
                        v-model="form.config.tenant_id"
                        :error="form.errors['config.tenant_id']"
                        placeholder="00000000-0000-0000-0000-000000000000"
                        required
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Azure AD / Entra tenant ID – it is part of the authority URL.') }}
                    </p>
                </div>

                <!-- Discovery URL (Custom only) -->
                <div v-if="form.config.provider_preset === 'custom'">
                    <BaseInput
                        id="discovery_url"
                        :label="$t('Discovery URL')"
                        v-model="form.config.discovery_url"
                        :error="form.errors['config.discovery_url']"
                        placeholder="https://idp.example.com/.well-known/openid-configuration"
                        required
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('URL of the OpenID Connect discovery document (.well-known/openid-configuration)') }}
                    </p>
                </div>

                <!-- Client ID -->
                <div>
                    <BaseInput
                        id="client_id"
                        :label="$t('Client ID')"
                        v-model="form.config.client_id"
                        :error="form.errors['config.client_id']"
                        required
                    />
                </div>

                <!-- Client Secret -->
                <div>
                    <BaseInput
                        id="client_secret"
                        type="password"
                        :label="$t('Client Secret')"
                        v-model="form.config.client_secret"
                        :error="form.errors['config.client_secret']"
                        required
                    />
                    <p v-if="source" class="mt-1 text-xs text-warning">
                        {{ $t('For security reasons the stored client secret is never displayed – every edit of this connection requires re-entering it.') }}
                    </p>
                </div>

                <!-- Advanced OIDC fields (Custom only – presets prefill these) -->
                <template v-if="form.config.provider_preset === 'custom'">
                    <!-- Scopes -->
                    <div>
                        <BaseInput
                            id="scopes"
                            :label="$t('Scopes')"
                            v-model="scopesText"
                            placeholder="openid, profile, email"
                        />
                        <p class="mt-1 text-xs text-text-subtle">
                            {{ $t('Comma-separated list of requested scopes (default: openid, profile, email)') }}
                        </p>
                    </div>

                    <!-- Identifier Claim -->
                    <div>
                        <BaseInput
                            id="oidc_identifier_attribute"
                            :label="$t('Identifier Claim')"
                            v-model="form.config.identifier_attribute"
                            :error="form.errors['config.identifier_attribute']"
                            placeholder="sub"
                        />
                        <p class="mt-1 text-xs text-text-subtle">
                            {{ $t('OIDC claim used as unique identifier (default: sub)') }}
                        </p>
                    </div>

                    <!-- Groups Claim -->
                    <div>
                        <BaseInput
                            id="groups_claim"
                            :label="$t('Groups Claim')"
                            v-model="form.config.groups_claim"
                            :error="form.errors['config.groups_claim']"
                            placeholder="groups"
                        />
                        <p class="mt-1 text-xs text-text-subtle">
                            {{ $t('OIDC claim containing the user\'s groups for role mapping (default: groups)') }}
                        </p>
                    </div>
                </template>

                <!-- Allowed Domains -->
                <div>
                    <BaseInput
                        id="allowed_domains"
                        :label="$t('Allowed Email Domains')"
                        v-model="allowedDomainsText"
                        :error="form.errors['config.allowed_domains']"
                        placeholder="example.com, partner.org"
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Enter the permitted user email domains, for example company.com — not the Artwork domain. Only identities with an email address from these domains may sign in.') }}
                    </p>
                </div>

                <!-- Default Role -->
                <div>
                    <label for="oidc_default_role" class="block text-sm font-medium text-text mb-1">
                        {{ $t('Default Role') }}
                    </label>
                    <select
                        id="oidc_default_role"
                        v-model="form.config.default_role_id"
                        class="block w-full rounded-lg border-border text-sm focus:border-accent-600 focus:ring-accent-600"
                    >
                        <option :value="null">{{ $t('No default role') }}</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Role assigned to accounts newly provisioned through this connection.') }}
                    </p>
                </div>

                <!-- Redirect URI hint -->
                <div v-if="source" class="rounded bg-surface-sunken border border-border-subtle p-3">
                    <p class="text-xs font-medium text-text-muted">{{ $t('Redirect URI (register this exact URL in the allowlist at your IdP)') }}</p>
                    <code class="mt-1 block break-all text-xs text-text-muted">{{ redirectUri }}</code>
                </div>
                <p v-else class="text-xs text-text-subtle">
                    {{ $t('The redirect URI becomes available after saving the source.') }}
                </p>
            </template>

            <!-- Connection Test Result -->
            <div v-if="testResult" class="p-3 rounded"
                 :class="testResult.success ? 'bg-success-surface text-success' : 'bg-danger-surface text-danger'">
                <p>{{ testResult.message }}</p>

                <!-- First found users (LDAP preview) -->
                <div v-if="testResult.users && testResult.users.length" class="mt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide opacity-70">
                        {{ $t('First found users') }}
                    </p>
                    <ul class="mt-1 space-y-1">
                        <li v-for="(user, index) in testResult.users" :key="user.identifier || index"
                            class="text-sm">
                            <span class="font-medium">
                                {{ user.display_name || [user.first_name, user.last_name].filter(Boolean).join(' ') || user.dn }}
                            </span>
                            <span v-if="user.email" class="opacity-70"> · {{ user.email }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Error Messages -->
            <div v-if="form.hasErrors" class="bg-danger-surface border border-danger-border rounded p-4">
                <p class="text-sm font-medium text-danger">{{ $t('Please correct the following errors:') }}</p>
                <ul class="mt-2 list-disc list-inside text-sm text-danger">
                    <li v-for="(error, key) in form.errors" :key="key">
                        {{ Array.isArray(error) ? error[0] : error }}
                    </li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-4 border-t">
                <div class="flex items-center gap-3">
                    <BaseUIButton
                        :label="source ? $t('Save') : $t('Create')"
                        is-add-button
                        type="submit"
                        :disabled="form.processing || testingConnection"
                    />
                    <button
                        type="button"
                        @click="testConnection"
                        :disabled="testingConnection || !canTestConnection"
                        class="px-4 py-2 text-sm bg-accent-50 text-accent-700 rounded hover:bg-accent-100 disabled:text-text-subtle disabled:cursor-not-allowed"
                    >
                        <span v-if="testingConnection">{{ $t('Testing...') }}</span>
                        <span v-else>{{ $t('Test Connection') }}</span>
                    </button>
                </div>
                <button
                    type="button"
                    class="text-sm text-text-subtle hover:text-text-muted"
                    @click="$emit('close')"
                >
                    {{ $t('Cancel') }}
                </button>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script>
import {defineComponent} from "vue";
import {useForm} from "@inertiajs/vue3";
import axios from "axios";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

function defaultConfig() {
    return {
        // LDAP
        host: '',
        port: 389,
        base_dn: '',
        bind_dn: '',
        bind_password: '',
        use_ssl: false,
        use_tls: false,
        user_filter: '(objectClass=user)',
        identifier_attribute: 'objectGUID',
        // OIDC
        provider_preset: 'custom',
        tenant_id: '',
        discovery_url: '',
        client_id: '',
        client_secret: '',
        scopes: ['openid', 'profile', 'email'],
        groups_claim: 'groups',
        allowed_domains: [],
        // Shared
        default_role_id: null,
    };
}

export default defineComponent({
    components: {
        ArtworkBaseModal,
        BaseInput,
        BaseTextarea,
        BaseUIButton,
    },
    props: {
        show: {
            type: Boolean,
            required: true
        },
        source: {
            type: Object,
            default: null
        },
        roles: {
            type: Array,
            default: () => []
        }
    },
    emits: ['close', 'saved'],
    data() {
        return {
            form: useForm({
                name: '',
                active: true,
                type: 'ldap',
                config: defaultConfig(),
            }),
            testingConnection: false,
            testResult: null,
        }
    },
    computed: {
        isOidc() {
            return this.form.type === 'identity_provider';
        },
        providerPresets() {
            return [
                { value: 'google', label: this.$t('Google') },
                { value: 'microsoft', label: this.$t('Microsoft') },
                { value: 'custom', label: this.$t('Custom') },
            ];
        },
        modalTitle() {
            if (this.isOidc) {
                return this.source ? this.$t('Edit Identity Provider') : this.$t('Add Identity Provider');
            }
            return this.source ? this.$t('Edit LDAP Source') : this.$t('Add LDAP Source');
        },
        modalDescription() {
            return this.isOidc
                ? this.$t('Configure an OpenID Connect / SSO login source')
                : this.$t('Configure a new LDAP / Active Directory connection');
        },
        scopesText: {
            get() {
                return (this.form.config.scopes || []).join(', ');
            },
            set(value) {
                this.form.config.scopes = this.splitList(value);
            }
        },
        allowedDomainsText: {
            get() {
                return (this.form.config.allowed_domains || []).join(', ');
            },
            set(value) {
                this.form.config.allowed_domains = this.splitList(value);
            }
        },
        redirectUri() {
            if (!this.source) {
                return '';
            }
            return route('auth.oidc.callback', { externalUserSource: this.source.id });
        },
        canTestConnection() {
            if (this.isOidc) {
                const preset = this.form.config.provider_preset;
                if (preset === 'google') {
                    return !!this.form.config.client_id;
                }
                if (preset === 'microsoft') {
                    return !!(this.form.config.tenant_id && this.form.config.client_id);
                }
                return !!(this.form.config.discovery_url && this.form.config.client_id);
            }
            return this.form.config.host &&
                this.form.config.port &&
                this.form.config.base_dn &&
                this.form.config.bind_dn &&
                this.form.config.bind_password;
        }
    },
    watch: {
        source: {
            immediate: true,
            handler(newSource) {
                if (newSource) {
                    this.form.name = newSource.name;
                    this.form.active = newSource.active;
                    this.form.type = newSource.type;
                    this.form.config = {
                        ...defaultConfig(),
                        ...newSource.config,
                        // Secrets nicht aus DB laden - müssen beim Bearbeiten neu eingegeben werden
                        bind_password: '',
                        client_secret: '',
                    };
                } else {
                    this.form.reset();
                    this.form.active = true;
                    this.form.type = 'ldap';
                    this.form.config = defaultConfig();
                }
            }
        },
        'form.type'(newType, oldType) {
            if (newType === oldType) {
                return;
            }
            // Sinnvolle Default-Werte für das Identifier-Feld je Typ setzen
            if (newType === 'identity_provider' && ['', 'objectGUID'].includes(this.form.config.identifier_attribute)) {
                this.form.config.identifier_attribute = 'sub';
            }
            if (newType === 'ldap' && ['', 'sub'].includes(this.form.config.identifier_attribute)) {
                this.form.config.identifier_attribute = 'objectGUID';
            }
        }
    },
    methods: {
        splitList(value) {
            return String(value || '')
                .split(',')
                .map((item) => item.trim())
                .filter((item) => item.length > 0);
        },
        buildConfigForType() {
            const c = this.form.config;
            if (this.isOidc) {
                return {
                    provider_preset: c.provider_preset,
                    tenant_id: c.tenant_id,
                    discovery_url: c.discovery_url,
                    client_id: c.client_id,
                    client_secret: c.client_secret,
                    scopes: c.scopes,
                    identifier_attribute: c.identifier_attribute,
                    groups_claim: c.groups_claim,
                    allowed_domains: c.allowed_domains,
                    default_role_id: c.default_role_id,
                };
            }
            return {
                host: c.host,
                port: c.port,
                base_dn: c.base_dn,
                bind_dn: c.bind_dn,
                bind_password: c.bind_password,
                use_ssl: c.use_ssl,
                use_tls: c.use_tls,
                user_filter: c.user_filter,
                identifier_attribute: c.identifier_attribute,
                default_role_id: c.default_role_id,
            };
        },
        saveSource() {
            const routeName = this.source
                ? 'tool.external-user-management.sources.update'
                : 'tool.external-user-management.sources.store';

            const routeParams = this.source
                ? { externalUserSource: this.source.id }
                : {};

            const method = this.source ? 'put' : 'post';

            this.form
                .transform((data) => ({
                    ...data,
                    config: this.buildConfigForType(),
                }))[method](
                    route(routeName, routeParams),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.$emit('saved');
                        }
                    }
                );
        },
        async testConnection() {
            if (!this.canTestConnection) {
                return;
            }

            this.testingConnection = true;
            this.testResult = null;

            try {
                const response = await axios.post(
                    route('tool.external-user-management.sources.test-connection-config'),
                    {
                        type: this.form.type,
                        config: this.buildConfigForType(),
                    }
                );

                this.testResult = {
                    success: response.data.success,
                    message: response.data.message,
                    users: response.data.users || []
                };
            } catch (error) {
                this.testResult = {
                    success: false,
                    message: error.response?.data?.message || this.$t('Connection test failed'),
                    users: error.response?.data?.users || []
                };
            } finally {
                this.testingConnection = false;
            }
        }
    }
})
</script>
