<template>
  <div>
    <h2 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text mb-4">{{ $t('API Keys Management') }}</h2>
    <SettingsGuideBanner
      class="mb-6"
      variant="static"
      title="How do API keys work?"
      :paragraphs="[
        'An API key is an access token that allows an external application to read data from your artwork.',
        'The token is shown only once after creation — store it securely. Setting an expiry date is recommended.',
        'Revoking a key is permanent and immediately blocks its access. The log lists every request made with a key.',
      ]"
    />
    <div class="grid grid-cols-12 gap-4 mb-6">
      <div class="col-span-3 flex justify-end">
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-accent-600 hover:bg-accent-700 text-white rounded-md flex items-center transition-all duration-150 ease-in-out"
        >
          <IconPlus class="h-5 w-5 mr-2" />
          {{ $t('Create New API Key') }}
        </button>
      </div>
    </div>

    <div class="bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-border-subtle">
        <thead class="bg-surface-sunken">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Name') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Permissions') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Created') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Expires') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Status') }}
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-border-subtle">
          <tr v-if="tokens.length === 0">
            <td colspan="6" class="px-6 py-4 text-center text-sm text-text-subtle">
              {{ $t('No API keys found') }}
            </td>
          </tr>
          <tr v-for="token in tokens" :key="token.id" class="hover:bg-surface-sunken">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text">
              {{ token.name }}
            </td>
            <td class="px-6 py-4 text-sm text-text-subtle">
              <div v-if="token.scopes && token.scopes.length" class="flex flex-wrap gap-1">
                <span
                  v-for="scope in token.scopes"
                  :key="scope"
                  class="px-2 py-0.5 rounded bg-surface-sunken text-xs font-mono"
                >
                  {{ scope }}
                </span>
              </div>
              <span v-else class="text-xs italic">
                {{ $t('None — created before permissions were introduced') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
              {{ formatDate(token.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
              {{ token.expires_at ? formatDate(token.expires_at) : $t('Never') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span
                :class="[
                  getStatusClass(token),
                  'px-2 py-1 rounded-full text-xs'
                ]"
              >
                {{ getStatusText(token) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="relative" v-if="!token.revoked && !isExpired(token)">
                <div>
                  <button
                    @click="toggleDropdown(token.id)"
                    class="inline-flex justify-center rounded-md border border-border shadow-sm px-3 py-1.5 bg-white text-sm font-medium text-text-muted hover:bg-surface-sunken focus:ring-2 focus:ring-offset-2 focus:ring-accent-600"
                  >
                    {{ $t('Actions') }}
                    <IconChevronDown class="ml-2 -mr-0.5 h-4 w-4" aria-hidden="true" />
                  </button>
                </div>
                <div v-if="activeDropdown === token.id"
                     class="origin-top-right absolute right-0 mt-2 w-28 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10 dropdown-menu"
                     style="max-height: 200px; overflow-y: auto;"
                >
                  <div class="py-1">
                    <button
                      @click="showLogs(token)"
                      class="w-full text-left block px-4 py-2 text-sm text-text-muted hover:bg-surface-sunken"
                    >
                      {{ $t('Log') }}
                    </button>
                    <button
                      @click="confirmDeleteToken(token)"
                      class="w-full text-left block px-4 py-2 text-sm text-danger hover:bg-surface-sunken"
                    >
                      {{ $t('Revoke') }}
                    </button>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <ArtworkBaseModal
      v-if="showCreateModal"
      title="Create New API Key"
      modal-size="sm:max-w-lg"
      @close="closeCreateModal"
    >
      <form id="createApiKeyForm" class="space-y-4" @submit.prevent="createToken">
        <BaseInput
          id="apiKeyName"
          v-model="form.name"
          label="Name"
          required
          autofocus
          :error="form.errors.name"
        />
        <fieldset>
          <legend class="font-lexend text-xs font-medium text-text">
            {{ $t('Permissions') }} <span class="text-danger">*</span>
          </legend>
          <p class="mt-1 mb-2 text-xs text-text-muted">
            {{ $t('A key can only do what you allow here. Grant as little as possible.') }}
          </p>
          <div class="space-y-2 rounded-lg border border-border-subtle bg-surface-sunken p-3">
            <BaseCheckbox
              v-for="scope in availableScopes"
              :key="scope.id"
              :id="'apiKeyScope-' + scope.id"
              :model-value="form.scopes.includes(scope.id)"
              :description="scope.description"
              @update:model-value="(checked) => toggleScope(scope.id, checked)"
            >
              <template #label>
                <span class="font-mono text-xs">{{ scope.id }}</span>
              </template>
            </BaseCheckbox>
          </div>
          <p v-if="form.errors.scopes" class="mt-1 text-[11.5px] text-danger">
            {{ form.errors.scopes }}
          </p>
        </fieldset>
        <BaseInput
          id="apiKeyExpiresAt"
          v-model="form.expires_at"
          type="date"
          label="Expires At"
          :error="form.errors.expires_at"
        />
      </form>
      <template #footer>
        <BaseUIButton
          label="Cancel"
          is-cancel-button
          hide-icon
          :disabled="form.processing"
          @click="closeCreateModal"
        />
        <BaseUIButton
          type="submit"
          form="createApiKeyForm"
          label="Create"
          variant="primary"
          icon="IconKey"
          :processing="form.processing"
          :disabled="form.processing"
        />
      </template>
    </ArtworkBaseModal>

    <ArtworkBaseModal
      v-if="newlyCreatedToken"
      title="Your new API key"
      modal-size="sm:max-w-lg"
      @close="newlyCreatedToken = null"
    >
      <div class="space-y-3">
        <BaseAlertComponent
          message="Copy this key now. It is not stored and cannot be shown again — if you lose it, revoke the key and create a new one."
          use-translation
          type="warning"
        />
        <div class="flex items-center gap-2 rounded-md border border-border-subtle bg-surface-sunken p-3">
          <code class="grow break-all font-mono text-xs text-text">{{ newlyCreatedToken }}</code>
          <BaseUIButton
            label="Copy"
            variant="ghost"
            size="sm"
            icon="IconClipboardCopy"
            @click="copyToken(newlyCreatedToken)"
          />
        </div>
      </div>
      <template #footer>
        <BaseUIButton
          label="Close"
          variant="secondary"
          hide-icon
          @click="newlyCreatedToken = null"
        />
      </template>
    </ArtworkBaseModal>

    <confirmation-component
      v-if="tokenToDelete"
      :titel="$t('Revoke API Key')"
      :description="$t('Are you sure you want to revoke this API key? This action cannot be undone.')"
      :confirm="$t('Revoke')"
      @closed="handleDeleteConfirmation"
    />

    <ArtworkBaseModal
      v-if="showLogModal"
      title="API Logs"
      :description="currentLogToken?.name ?? ''"
      modal-size="sm:max-w-4xl"
      @close="closeLogModal"
    >
      <div v-if="loadingLogs" class="flex justify-center items-center py-8">
        <PropertyIcon name="IconLoader2" class="size-6 animate-spin text-text-muted" aria-hidden="true"/>
        <span class="sr-only">{{ $t('Loading...') }}</span>
      </div>

      <div v-else-if="logs.data && logs.data.length > 0">
        <div class="overflow-x-auto rounded-lg border border-border-subtle">
          <table class="min-w-full divide-y divide-border-subtle">
            <thead class="bg-surface-sunken">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">
                  {{ $t('Date') }}
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">
                  {{ $t('IP') }}
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">
                  {{ $t('Method') }}
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">
                  {{ $t('URL') }}
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">
                  {{ $t('UserAgent') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-surface divide-y divide-border-subtle">
              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-surface-hover">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-text">
                  {{ formatDate(log.created_at) }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-text-muted">
                  {{ log.ip }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm">
                  <span
                    :class="getMethodClass(log.method)"
                    class="px-2 py-1 rounded text-xs font-semibold"
                  >
                    {{ log.method }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-text max-w-xs truncate" :title="log.url">
                  {{ log.url }}
                </td>
                <td class="px-4 py-3 text-sm text-text max-w-xs truncate" :title="log.user_agent">
                  {{ log.user_agent }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-between gap-4">
          <div class="text-sm text-text-muted tabular-nums">
            {{ $t('Showing') }} {{ logs.from }} {{ $t('to') }} {{ logs.to }} {{ $t('of') }} {{ logs.total }} {{ $t('entries') }}
          </div>
          <div class="flex items-center gap-2">
            <BaseUIButton
              label="Previous"
              variant="secondary"
              size="sm"
              icon="IconChevronLeft"
              :disabled="!logs.prev_page_url"
              @click="loadLogs(logs.current_page - 1)"
            />
            <span class="text-sm font-medium text-text-muted tabular-nums">
              {{ logs.current_page }} / {{ logs.last_page }}
            </span>
            <BaseUIButton
              label="Next"
              variant="secondary"
              size="sm"
              icon="IconChevronRight"
              :disabled="!logs.next_page_url"
              @click="loadLogs(logs.current_page + 1)"
            />
          </div>
        </div>
      </div>

      <EmptyState
        v-else
        icon="IconHistory"
        :title="$t('No logs found')"
      />
    </ArtworkBaseModal>
  </div>
</template>

<script>
import {IconChevronDown, IconPlus} from "@tabler/icons-vue";
import { defineComponent } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import dayjs from 'dayjs'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'

export default defineComponent({
  components: {
    SettingsGuideBanner,
    ConfirmationComponent,
    ArtworkBaseModal,
    BaseUIButton,
    BaseInput,
    BaseCheckbox,
    EmptyState,
    PropertyIcon,
    BaseAlertComponent,
    IconPlus,
    IconChevronDown
  },
  props: {
    tokens: {
      type: Array,
      default: () => []
    },
    availableScopes: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      showCreateModal: false,
      tokenToDelete: null,
      activeDropdown: null,
      newlyCreatedToken: null,
      showLogModal: false,
      currentLogToken: null,
      logs: {
        data: [],
        current_page: 1,
        last_page: 1,
        total: 0,
        from: 0,
        to: 0,
        prev_page_url: null,
        next_page_url: null
      },
      loadingLogs: false,
      form: useForm({
        name: '',
        expires_at: null,
        scopes: []
      })
    }
  },
  mounted() {
    document.addEventListener('click', this.closeDropdownOnClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeDropdownOnClickOutside);
  },
  methods: {
    formatDate(date) {
      return dayjs(date).format('DD.MM.YYYY HH:mm')
    },
    closeCreateModal() {
      this.showCreateModal = false
      this.form.clearErrors()
    },
    toggleScope(scopeId, checked) {
      const selected = this.form.scopes.filter((id) => id !== scopeId)
      if (checked) {
        selected.push(scopeId)
      }
      this.form.scopes = selected
    },
    createToken() {
      if (this.form.processing) {
        return
      }
      this.form.post(route('api-management.store'), {
        preserveScroll: true,
        onSuccess: () => {
          this.showCreateModal = false
          this.form.reset()
          // Einzige Gelegenheit, den Klartext zu lesen: er wird serverseitig nicht gespeichert.
          this.newlyCreatedToken = this.$page.props.flash.plainTextToken ?? null
        }
      })
    },
    confirmDeleteToken(token) {
      this.activeDropdown = null;
      this.tokenToDelete = token
    },
    handleDeleteConfirmation(confirmed) {
      if (confirmed && this.tokenToDelete) {
        this.$inertia.delete(route('api-management.destroy', this.tokenToDelete.id), {
          preserveScroll: true
        })
      }
      this.tokenToDelete = null
    },
    copyToken(token) {
      navigator.clipboard.writeText(token)
        .then(() => {
          if (this.$toast) {
            this.$toast.success(this.$t('Token copied to clipboard'))
          }
        })
        .catch(err => {
          console.error('Could not copy text: ', err)
        })
    },
    toggleDropdown(tokenId) {
      if (this.activeDropdown === tokenId) {
        this.activeDropdown = null;
      } else {
        this.activeDropdown = tokenId;
      }
    },
    closeDropdownOnClickOutside(event) {
      if (this.activeDropdown !== null && !event.target.closest('.relative')) {
        this.activeDropdown = null;
      }
    },
    isExpired(token) {
      if (!token.expires_at) return false;
      return dayjs(token.expires_at).isBefore(dayjs());
    },
    getStatusText(token) {
      if (token.revoked) {
          return this.$t('Revoked');
      }

      if (this.isExpired(token)) {
          return this.$t('Expired');
      }

      return this.$t('Active');
    },
    getStatusClass(token) {
      if (token.revoked) return 'bg-danger-surface text-danger';
      if (this.isExpired(token)) return 'bg-special-orange-surface text-special-orange';
      return 'bg-success-surface text-success';
    },
    showLogs(token) {
      this.activeDropdown = null;
      this.currentLogToken = token;
      this.showLogModal = true;
      this.loadLogs(1);
    },
    async loadLogs(page = 1) {
      this.loadingLogs = true;
      try {
        const response = await axios.get(route('tool.interfaces.api.logs', this.currentLogToken.id), {
          params: { page }
        });
        this.logs = response.data.logs;
      } catch (error) {
        console.error('Error loading logs:', error);
        if (this.$toast) {
          this.$toast.error(this.$t('Failed to load logs'));
        }
      } finally {
        this.loadingLogs = false;
      }
    },
    closeLogModal() {
      this.showLogModal = false;
      this.currentLogToken = null;
      this.logs = {
        data: [],
        current_page: 1,
        last_page: 1,
        total: 0,
        from: 0,
        to: 0,
        prev_page_url: null,
        next_page_url: null
      };
    },
    getMethodClass(method) {
      const classes = {
        'GET': 'bg-accent-100 text-accent-700',
        'POST': 'bg-success-surface text-success',
        'PUT': 'bg-warning-surface text-warning',
        'PATCH': 'bg-special-orange-surface text-special-orange',
        'DELETE': 'bg-danger-surface text-danger'
      };
      return classes[method] || 'bg-surface-sunken text-text';
    },
    getHttpStatusClass(statusCode) {
      if (statusCode >= 200 && statusCode < 300) {
        return 'bg-success-surface text-success';
      } else if (statusCode >= 300 && statusCode < 400) {
        return 'bg-accent-100 text-accent-700';
      } else if (statusCode >= 400 && statusCode < 500) {
        return 'bg-special-orange-surface text-special-orange';
      } else if (statusCode >= 500) {
        return 'bg-danger-surface text-danger';
      }
      return 'bg-surface-sunken text-text';
    }
  }
})
</script>

<style scoped>
.origin-top-right {
  transform-origin: top right;
}

.dropdown-menu {
  position: absolute;
  right: 0;
  transform: translateY(0);
  max-height: 200px;
  overflow-y: auto;
}
</style>
