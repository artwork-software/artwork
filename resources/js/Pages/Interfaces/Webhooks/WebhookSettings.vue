<template>
  <div>
    <h2 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text mb-4">
      {{ $t('Webhooks') }}
    </h2>
    <SettingsGuideBanner
      class="mb-6"
      variant="static"
      title="How do webhooks work?"
      :paragraphs="[
        'A webhook sends data from artwork to an external system as soon as something happens — no polling required.',
        'Every delivery is signed with the endpoint secret. The secret is shown only once when the endpoint is created.',
        'Failed deliveries are retried with growing delays. The delivery log shows what was sent and how the receiver answered.',
      ]"
    />

    <div class="flex justify-end mb-6">
      <button
        @click="openCreate"
        class="px-4 py-2 bg-accent-600 hover:bg-accent-700 text-white rounded-md flex items-center transition-all duration-150 ease-in-out"
      >
        <IconPlus class="h-5 w-5 mr-2" />
        {{ $t('Add endpoint') }}
      </button>
    </div>

    <div class="bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-border-subtle">
        <thead class="bg-surface-sunken">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Name') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('URL') }}
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
              {{ $t('Events') }}
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
          <tr v-if="endpoints.length === 0">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-text-subtle">
              {{ $t('No endpoints configured') }}
            </td>
          </tr>
          <tr v-for="endpoint in endpoints" :key="endpoint.id" class="hover:bg-surface-sunken">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text">{{ endpoint.name }}</td>
            <td class="px-6 py-4 text-sm text-text-subtle max-w-xs truncate" :title="endpoint.url">
              {{ endpoint.url }}
            </td>
            <td class="px-6 py-4 text-sm">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="event in endpoint.subscribed_events"
                  :key="event"
                  class="px-2 py-0.5 rounded bg-surface-sunken text-xs font-mono"
                >
                  {{ event }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span
                :class="endpoint.is_active ? 'bg-success-surface text-success' : 'bg-surface-sunken text-text-subtle'"
                class="px-2 py-1 rounded-full text-xs"
              >
                {{ endpoint.is_active ? $t('Active') : $t('Inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button @click="openEdit(endpoint)" class="text-accent-700 hover:underline">{{ $t('Edit') }}</button>
              <button @click="showDeliveries(endpoint)" class="text-accent-700 hover:underline">{{ $t('Log') }}</button>
              <button @click="endpointToDelete = endpoint" class="text-danger hover:underline">{{ $t('Delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BaseModal v-if="showFormModal" @closed="closeForm" modalSize="sm:max-w-lg">
      <div>
        <h3 class="text-lg leading-6 font-medium text-text mb-4">
          {{ editing ? $t('Edit endpoint') : $t('Add endpoint') }}
        </h3>

        <div class="mb-4">
          <label class="block text-sm font-medium text-text-muted">{{ $t('Name') }} <span class="text-danger">*</span></label>
          <input v-model="form.name" type="text"
                 class="mt-1 block w-full border border-border rounded-md shadow-sm py-2 px-3 sm:text-sm" />
          <div v-if="form.errors.name" class="text-danger text-xs mt-1">{{ form.errors.name }}</div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-text-muted">{{ $t('URL') }} <span class="text-danger">*</span></label>
          <input v-model="form.url" type="url" placeholder="https://"
                 class="mt-1 block w-full border border-border rounded-md shadow-sm py-2 px-3 sm:text-sm" />
          <p class="text-xs text-text-subtle mt-1">{{ $t('Must be https — payloads are signed, but not encrypted.') }}</p>
          <div v-if="form.errors.url" class="text-danger text-xs mt-1">{{ form.errors.url }}</div>
        </div>

        <div class="mb-4">
          <span class="block text-sm font-medium text-text-muted">{{ $t('Events') }} <span class="text-danger">*</span></span>
          <div v-if="availableEvents.length === 0" class="text-xs text-text-subtle mt-2">
            {{ $t('No events are available yet.') }}
          </div>
          <div v-else class="space-y-2 mt-2">
            <label v-for="event in availableEvents" :key="event.name" class="flex items-start gap-2 cursor-pointer">
              <input type="checkbox" :value="event.name" v-model="form.subscribed_events"
                     class="mt-0.5 rounded border-border text-accent-600 focus:ring-accent-600" />
              <span class="text-sm">
                <span class="font-mono text-xs">{{ event.name }}</span>
                <span class="block text-xs text-text-subtle">{{ event.description }}</span>
              </span>
            </label>
          </div>
          <div v-if="form.errors.subscribed_events" class="text-danger text-xs mt-1">
            {{ form.errors.subscribed_events }}
          </div>
        </div>

        <label class="flex items-center gap-2 cursor-pointer mb-4">
          <input type="checkbox" v-model="form.is_active"
                 class="rounded border-border text-accent-600 focus:ring-accent-600" />
          <span class="text-sm text-text-muted">{{ $t('Active') }}</span>
        </label>

        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
          <button @click="submitForm" type="button" :disabled="form.processing"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-accent-600 text-base font-medium text-white hover:bg-accent-700 sm:ml-3 sm:w-auto sm:text-sm">
            {{ $t('Save') }}
          </button>
          <button @click="closeForm" type="button"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-border shadow-sm px-4 py-2 bg-white text-base font-medium text-text-muted hover:bg-surface-sunken sm:mt-0 sm:w-auto sm:text-sm">
            {{ $t('Cancel') }}
          </button>
        </div>
      </div>
    </BaseModal>

    <BaseModal v-if="newSecret" @closed="newSecret = null" modalSize="sm:max-w-lg">
      <div>
        <h3 class="text-lg leading-6 font-medium text-text mb-4">{{ $t('Endpoint secret') }}</h3>
        <p class="text-sm text-text-muted mb-3">
          {{ $t('Store this secret in the receiving system now. It is not shown again.') }}
        </p>
        <div class="flex items-center bg-surface-sunken p-3 rounded border border-border-subtle">
          <code class="text-xs break-all mr-2 flex-grow">{{ newSecret }}</code>
          <button @click="copy(newSecret)" class="p-1 text-text-muted hover:bg-border-subtle rounded">
            <IconClipboardCopy class="h-5 w-5" />
          </button>
        </div>
        <p class="text-xs text-text-subtle mt-3">
          {{ $t('The receiver verifies the X-Artwork-Signature header as sha256=HMAC_SHA256(timestamp + "." + body, secret).') }}
        </p>
        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
          <button @click="newSecret = null" type="button"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-border shadow-sm px-4 py-2 bg-white text-base font-medium text-text-muted hover:bg-surface-sunken sm:mt-0 sm:w-auto sm:text-sm">
            {{ $t('Close') }}
          </button>
        </div>
      </div>
    </BaseModal>

    <confirmation-component
      v-if="endpointToDelete"
      :titel="$t('Delete endpoint')"
      :description="$t('Deliveries already logged are removed together with the endpoint. Continue?')"
      :confirm="$t('Delete')"
      @closed="handleDelete"
    />

    <BaseModal v-if="deliveryEndpoint" @closed="deliveryEndpoint = null" modalSize="sm:max-w-4xl">
      <div>
        <h3 class="text-lg leading-6 font-medium text-text mb-4">
          {{ $t('Deliveries') }}: {{ deliveryEndpoint.name }}
        </h3>
        <div v-if="loadingDeliveries" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-accent-600"></div>
        </div>
        <div v-else-if="deliveries.data && deliveries.data.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-border-subtle">
            <thead class="bg-surface-sunken">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Date') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Event') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Status') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Attempts') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-text-subtle uppercase">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-border-subtle">
              <tr v-for="delivery in deliveries.data" :key="delivery.id">
                <td class="px-4 py-3 whitespace-nowrap text-sm">{{ formatDate(delivery.created_at) }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-xs">{{ delivery.event_name }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm">
                  <span :class="statusClass(delivery.status)" class="px-2 py-1 rounded text-xs font-semibold">
                    {{ delivery.status }}<template v-if="delivery.response_status"> · {{ delivery.response_status }}</template>
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-text-subtle">{{ delivery.attempt }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                  <button v-if="delivery.status !== 'success'" @click="redeliver(delivery)"
                          class="text-accent-700 hover:underline">
                    {{ $t('Send again') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-8 text-text-subtle">{{ $t('No deliveries yet') }}</div>
      </div>
    </BaseModal>
  </div>
</template>

<script>
import { IconClipboardCopy, IconPlus } from '@tabler/icons-vue'
import { defineComponent } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import dayjs from 'dayjs'

export default defineComponent({
  components: { SettingsGuideBanner, ConfirmationComponent, BaseModal, IconPlus, IconClipboardCopy },
  props: {
    endpoints: { type: Array, default: () => [] },
    availableEvents: { type: Array, default: () => [] }
  },
  data() {
    return {
      showFormModal: false,
      editing: null,
      newSecret: null,
      endpointToDelete: null,
      deliveryEndpoint: null,
      deliveries: { data: [] },
      loadingDeliveries: false,
      form: useForm({ name: '', url: '', subscribed_events: [], is_active: true })
    }
  },
  methods: {
    formatDate(date) {
      return dayjs(date).format('DD.MM.YYYY HH:mm')
    },
    statusClass(status) {
      return {
        success: 'bg-success-surface text-success',
        pending: 'bg-accent-100 text-accent-700',
        failed: 'bg-special-orange-surface text-special-orange',
        exhausted: 'bg-danger-surface text-danger'
      }[status] || 'bg-surface-sunken text-text'
    },
    openCreate() {
      this.editing = null
      this.form.defaults({ name: '', url: '', subscribed_events: [], is_active: true })
      this.form.reset()
      this.showFormModal = true
    },
    openEdit(endpoint) {
      this.editing = endpoint
      this.form.name = endpoint.name
      this.form.url = endpoint.url
      this.form.subscribed_events = [...endpoint.subscribed_events]
      this.form.is_active = endpoint.is_active
      this.showFormModal = true
    },
    closeForm() {
      this.showFormModal = false
      this.editing = null
      this.form.clearErrors()
    },
    submitForm() {
      const options = {
        preserveScroll: true,
        onSuccess: () => {
          this.showFormModal = false
          // Nur beim Anlegen liefert der Server ein Geheimnis — es ist danach nicht mehr abrufbar.
          this.newSecret = this.$page.props.flash.webhookSecret ?? null
          this.editing = null
          this.form.reset()
        }
      }

      if (this.editing) {
        this.form.patch(route('webhooks.update', this.editing.id), options)
      } else {
        this.form.post(route('webhooks.store'), options)
      }
    },
    handleDelete(confirmed) {
      if (confirmed && this.endpointToDelete) {
        router.delete(route('webhooks.destroy', this.endpointToDelete.id), { preserveScroll: true })
      }
      this.endpointToDelete = null
    },
    async showDeliveries(endpoint) {
      this.deliveryEndpoint = endpoint
      this.loadingDeliveries = true
      try {
        const response = await axios.get(route('webhooks.deliveries', endpoint.id))
        this.deliveries = response.data.deliveries
      } catch (error) {
        if (this.$toast) {
          this.$toast.error(this.$t('Failed to load deliveries'))
        }
      } finally {
        this.loadingDeliveries = false
      }
    },
    redeliver(delivery) {
      router.post(route('webhooks.deliveries.redeliver', delivery.id), {}, {
        preserveScroll: true,
        onSuccess: () => this.showDeliveries(this.deliveryEndpoint)
      })
    },
    copy(value) {
      navigator.clipboard.writeText(value).then(() => {
        if (this.$toast) {
          this.$toast.success(this.$t('Copied to clipboard'))
        }
      })
    }
  }
})
</script>
