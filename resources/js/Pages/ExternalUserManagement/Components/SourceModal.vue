<template>
    <ArtworkBaseModal
        :open="show"
        @close="$emit('close')"
        :title="source ? $t('Edit LDAP Source') : $t('Add LDAP Source')"
        :description="source ? $t('Edit the LDAP connection settings') : $t('Configure a new LDAP / Active Directory connection')"
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

            <!-- Active Toggle -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="active"
                    v-model="form.active"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="active" class="ml-2 block text-sm text-gray-900">
                    {{ $t('Active') }}
                </label>
            </div>

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
                <p class="mt-1 text-xs text-gray-500">
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
                <p class="mt-1 text-xs text-gray-500">
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
                <p class="mt-1 text-xs text-gray-500">
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
                <p class="mt-1 text-xs text-gray-500">
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
            </div>

            <!-- SSL/TLS Options -->
            <div class="space-y-3">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="use_ssl"
                        v-model="form.config.use_ssl"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label for="use_ssl" class="ml-2 block text-sm text-gray-900">
                        {{ $t('Use SSL (LDAPS)') }}
                    </label>
                </div>

                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="use_tls"
                        v-model="form.config.use_tls"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label for="use_tls" class="ml-2 block text-sm text-gray-900">
                        {{ $t('Use StartTLS') }}
                    </label>
                </div>
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
                <p class="mt-1 text-xs text-gray-500">
                    {{ $t('LDAP filter to determine which users should be synchronized. Example: (objectClass=user) or (&(objectClass=user)(memberOf=CN=Artwork_Users,OU=Groups,DC=domain,DC=tld))') }}
                </p>
            </div>

            <!-- Identifier Attribute -->
            <div>
                <BaseInput
                    id="identifier_attribute"
                    :label="$t('Identifier Attribute')"
                    v-model="form.config.identifier_attribute"
                    :error="form.errors['config.identifier_attribute']"
                    placeholder="objectGUID"
                />
                <p class="mt-1 text-xs text-gray-500">
                    {{ $t('LDAP attribute used as unique identifier (default: objectGUID)') }}
                </p>
            </div>

            <!-- Connection Test Result -->
            <div v-if="testResult" class="p-3 rounded"
                 :class="testResult.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                {{ testResult.message }}
            </div>

            <!-- Error Messages -->
            <div v-if="form.hasErrors" class="bg-red-50 border border-red-200 rounded p-4">
                <p class="text-sm font-medium text-red-800">{{ $t('Please correct the following errors:') }}</p>
                <ul class="mt-2 list-disc list-inside text-sm text-red-700">
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
                        class="px-4 py-2 text-sm bg-blue-50 text-blue-700 rounded hover:bg-blue-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="testingConnection">{{ $t('Testing...') }}</span>
                        <span v-else>{{ $t('Test Connection') }}</span>
                    </button>
                </div>
                <button
                    type="button"
                    class="text-sm text-gray-500 hover:text-gray-700"
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
        }
    },
    emits: ['close', 'saved'],
    data() {
        return {
            form: useForm({
                name: '',
                active: true,
                type: 'ldap',
                config: {
                    host: '',
                    port: 389,
                    base_dn: '',
                    bind_dn: '',
                    bind_password: '',
                    use_ssl: false,
                    use_tls: false,
                    user_filter: '(objectClass=user)',
                    identifier_attribute: 'objectGUID',
                }
            }),
            testingConnection: false,
            testResult: null,
        }
    },
    computed: {
        canTestConnection() {
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
                        ...this.form.config,
                        ...newSource.config,
                        // Passwort nicht aus DB laden aus Sicherheitsgründen
                        bind_password: ''
                    };
                } else {
                    this.form.reset();
                    this.form.active = true;
                    this.form.type = 'ldap';
                }
            }
        }
    },
    methods: {
        saveSource() {
            const routeName = this.source
                ? 'tool.external-user-management.sources.update'
                : 'tool.external-user-management.sources.store';

            const routeParams = this.source
                ? { externalUserSource: this.source.id }
                : {};

            this.form[this.source ? 'put' : 'post'](
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
                        config: this.form.config
                    }
                );

                this.testResult = {
                    success: response.data.success,
                    message: response.data.message
                };
            } catch (error) {
                this.testResult = {
                    success: false,
                    message: error.response?.data?.message || this.$t('Connection test failed')
                };
            } finally {
                this.testingConnection = false;
            }
        }
    }
})
</script>

