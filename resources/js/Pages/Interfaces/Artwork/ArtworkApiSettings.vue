<template>
  <div>
    <h2 class="headline2 mb-4">{{ $t('API Keys Management') }}</h2>
    <div class="grid grid-cols-12 gap-4 mb-6">
      <div class="col-span-3 flex justify-end">
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-artwork-buttons-create hover:bg-artwork-buttons-create-hover text-white rounded-md flex items-center transition-all duration-150 ease-in-out"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          {{ $t('Create New API Key') }}
        </button>
      </div>
    </div>

    <div class="bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ $t('Name') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ $t('Created') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ $t('Expires') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ $t('Status') }}
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ $t('Actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="tokens.length === 0">
            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
              {{ $t('No API keys found') }}
            </td>
          </tr>
          <tr v-for="token in tokens" :key="token.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ token.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(token.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                    class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-3 py-1.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-primary"
                  >
                    {{ $t('Actions') }}
                    <ChevronDownIcon class="ml-2 -mr-0.5 h-4 w-4" aria-hidden="true" />
                  </button>
                </div>
                <div v-if="activeDropdown === token.id"
                     class="origin-top-right absolute right-0 mt-2 w-28 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10 dropdown-menu"
                     style="max-height: 200px; overflow-y: auto;"
                >
                  <div class="py-1">
                    <button
                      @click="showToken(token)"
                      class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      {{ $t('Show') }}
                    </button>
                    <button
                      @click="showLogs(token)"
                      class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      {{ $t('Log') }}
                    </button>
                    <button
                      @click="confirmDeleteToken(token)"
                      class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
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

    <BaseModal
      v-if="showCreateModal"
      @closed="showCreateModal = false"
      modalSize="sm:max-w-lg"
    >
      <div>
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
          {{ $t('Create New API Key') }}
        </h3>
        <form @submit.prevent="createToken">
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">
              {{ $t('Name') }} <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-artwork-primary focus:border-artwork-primary sm:text-sm"
              required
            />
            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">
              {{ form.errors.name }}
            </div>
          </div>

          <div class="mb-4">
            <label for="expires_at" class="block text-sm font-medium text-gray-700">
              {{ $t('Expires At') }}
            </label>
            <input
              id="expires_at"
              v-model="form.expires_at"
              type="date"
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-artwork-primary focus:border-artwork-primary sm:text-sm"
            />
            <div v-if="form.errors.expires_at" class="text-red-500 text-xs mt-1">
              {{ form.errors.expires_at }}
            </div>
          </div>
        </form>

        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
          <button
            @click="createToken"
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-artwork-buttons-create text-base font-medium text-white hover:bg-artwork-buttons-create-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-primary sm:ml-3 sm:w-auto sm:text-sm"
            :disabled="form.processing"
          >
            {{ $t('Create') }}
          </button>
          <button
            @click="showCreateModal = false"
            type="button"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-primary sm:mt-0 sm:w-auto sm:text-sm"
          >
            {{ $t('Cancel') }}
          </button>
        </div>
      </div>
    </BaseModal>

    <BaseModal
      v-if="selectedToken"
      @closed="selectedToken = null"
      modalSize="sm:max-w-lg"
    >
      <div>
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
          {{ $t('API Key Details') }}
        </h3>
        <div>
          <div v-if="selectedToken.token" class="mt-1 flex items-center bg-gray-50 p-3 rounded border border-gray-200">
            <code class="text-xs break-all mr-2 flex-grow">{{ selectedToken.token }}</code>
            <button
              @click="copyToken(selectedToken.token)"
              class="p-1 text-gray-700 hover:bg-gray-200 rounded"
            >
              <ClipboardCopyIcon class="h-5 w-5" />
            </button>
          </div>
          <p v-else class="text-sm text-gray-500 mt-2">
            {{ $t('Can not show token') }}
          </p>
        </div>

        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
          <button
            @click="selectedToken = null"
            type="button"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-primary sm:mt-0 sm:w-auto sm:text-sm"
          >
            {{ $t('Close') }}
          </button>
        </div>
      </div>
    </BaseModal>

    <confirmation-component
      v-if="tokenToDelete"
      :titel="$t('Revoke API Key')"
      :description="$t('Are you sure you want to revoke this API key? This action cannot be undone.')"
      :confirm="$t('Revoke')"
      @closed="handleDeleteConfirmation"
    />

    <BaseModal
      v-if="showLogModal"
      @closed="closeLogModal"
      modalSize="sm:max-w-4xl"
    >
      <div>
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            {{ $t('API Logs') }}: {{ currentLogToken?.name }}
          </h3>
          <button @click="closeLogModal" class="text-gray-400 hover:text-gray-500">
            <XIcon class="h-5 w-5" />
          </button>
        </div>

        <div v-if="loadingLogs" class="flex justify-center items-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-artwork-primary"></div>
        </div>

        <div v-else>
          <div v-if="logs.data && logs.data.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('Date') }}
                  </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('IP') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('Method') }}
                  </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('URL') }}
                  </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $t('UserAgent') }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(log.created_at) }}
                  </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
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
                    <td class="px-4 py-3 text-sm text-gray-900 max-w-xs truncate" :title="log.url">
                    {{ log.url }}
                  </td>
                    <td class="px-4 py-3 text-sm text-gray-900 max-w-xs truncate" :title="log.url">
                    {{ log.user_agent }}
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6 mt-4">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                  {{ $t('Showing') }} {{ logs.from }} {{ $t('to') }} {{ logs.to }} {{ $t('of') }} {{ logs.total }} {{ $t('entries') }}
                </div>
                <div class="flex space-x-2">
                  <button
                    @click="loadLogs(logs.current_page - 1)"
                    :disabled="!logs.prev_page_url"
                    class="relative inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ $t('Previous') }}
                  </button>
                  <span class="relative inline-flex items-center px-3 py-1 text-sm font-medium text-gray-700">
                    {{ logs.current_page }} / {{ logs.last_page }}
                  </span>
                  <button
                    @click="loadLogs(logs.current_page + 1)"
                    :disabled="!logs.next_page_url"
                    class="relative inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ $t('Next') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8 text-gray-500">
            {{ $t('No logs found') }}
          </div>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import {
  PlusIcon,
  CheckCircleIcon,
  ClipboardCopyIcon,
  ChevronDownIcon,
  XIcon
} from '@heroicons/vue/solid'
import dayjs from 'dayjs'

export default defineComponent({
  components: {
    ConfirmationComponent,
    BaseModal,
    PlusIcon,
    CheckCircleIcon,
    ClipboardCopyIcon,
    ChevronDownIcon,
    XIcon
  },
  props: {
    tokens: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      showCreateModal: false,
      tokenToDelete: null,
      activeDropdown: null,
      selectedToken: null,
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
        expires_at: null
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
    createToken() {
      this.form.post(route('api-management.store'), {
        preserveScroll: true,
        onSuccess: () => {
          this.showCreateModal = false
          this.form.reset()
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
    showToken(token) {
      this.activeDropdown = null;

      if (token.access_token) {
        this.selectedToken = {
          ...token,
          token: token.access_token
        };
      } else if (this.$page.props.flash.plainTextToken && token.id === this.tokens[0]?.id) {
        this.selectedToken = {
          ...token,
          token: this.$page.props.flash.plainTextToken
        };
      } else {
        this.selectedToken = {
          ...token,
          token: null
        };
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
      if (token.revoked) return 'bg-red-100 text-red-800';
      if (this.isExpired(token)) return 'bg-orange-100 text-orange-800';
      return 'bg-green-100 text-green-800';
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
        'GET': 'bg-blue-100 text-blue-800',
        'POST': 'bg-green-100 text-green-800',
        'PUT': 'bg-yellow-100 text-yellow-800',
        'PATCH': 'bg-orange-100 text-orange-800',
        'DELETE': 'bg-red-100 text-red-800'
      };
      return classes[method] || 'bg-gray-100 text-gray-800';
    },
    getHttpStatusClass(statusCode) {
      if (statusCode >= 200 && statusCode < 300) {
        return 'bg-green-100 text-green-800';
      } else if (statusCode >= 300 && statusCode < 400) {
        return 'bg-blue-100 text-blue-800';
      } else if (statusCode >= 400 && statusCode < 500) {
        return 'bg-orange-100 text-orange-800';
      } else if (statusCode >= 500) {
        return 'bg-red-100 text-red-800';
      }
      return 'bg-gray-100 text-gray-800';
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
