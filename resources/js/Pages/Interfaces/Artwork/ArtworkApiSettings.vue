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

    <div class="bg-white shadow rounded-lg overflow-hidden">
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
              {{ $t('Last Used') }}
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
              {{ token.last_used_at ? formatDate(token.last_used_at) : $t('Never') }}
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
                     class="origin-top-right absolute right-0 mt-2 w-28 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                >
                  <div class="py-1">
                    <button
                      @click="showToken(token)"
                      class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      {{ $t('Show') }}
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

    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showCreateModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div class="absolute top-0 right-0 pt-4 pr-4">
            <button @click="showCreateModal = false" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-primary">
              <span class="sr-only">Close</span>
              <XIcon class="h-6 w-6" aria-hidden="true" />
            </button>
          </div>
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                {{ $t('Create New API Key') }}
              </h3>
              <div class="mt-4">
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
              </div>
            </div>
          </div>
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
      </div>
    </div>

    <div v-if="selectedToken" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="selectedToken = null"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div class="absolute top-0 right-0 pt-4 pr-4">
            <button @click="selectedToken = null" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-artwork-primary">
              <span class="sr-only">Close</span>
              <XIcon class="h-6 w-6" aria-hidden="true" />
            </button>
          </div>
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                {{ $t('API Key Details') }}
              </h3>
              <div class="mt-4">
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
            </div>
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
      </div>
    </div>

    <confirmation-component
      v-if="tokenToDelete"
      :titel="$t('Revoke API Key')"
      :description="$t('Are you sure you want to revoke this API key? This action cannot be undone.')"
      :confirm="$t('Revoke')"
      @closed="handleDeleteConfirmation"
    />
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
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
    }
  }
})
</script>

<style scoped>
.origin-top-right {
  transform-origin: top right;
}
</style>
