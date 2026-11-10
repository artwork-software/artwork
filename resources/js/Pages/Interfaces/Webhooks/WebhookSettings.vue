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

    <ArtworkBaseModal
      v-if="showFormModal"
      :title="editing ? 'Edit endpoint' : 'Add endpoint'"
      modal-size="sm:max-w-lg"
      @close="closeForm"
    >
      <form id="webhookEndpointForm" class="space-y-4" @submit.prevent="submitForm">
        <BaseInput
          id="webhookEndpointName"
          v-model="form.name"
          label="Name"
          required
          autofocus
          :error="form.errors.name"
        />
        <div>
          <BaseInput
            id="webhookEndpointUrl"
            v-model="form.url"
            type="url"
            label="URL"
            placeholder="https://"
            required
            :error="form.errors.url"
          />
          <p class="mt-1 text-xs text-text-muted">{{ $t('Must be https — payloads are signed, but not encrypted.') }}</p>
        </div>
        <fieldset>
          <legend class="mb-2 font-lexend text-xs font-medium text-text">
            {{ $t('Events') }} <span class="text-danger">*</span>
          </legend>
          <p v-if="availableEvents.length === 0" class="text-xs text-text-muted">
            {{ $t('No events are available yet.') }}
          </p>
          <div v-else class="space-y-2 rounded-lg border border-border-subtle bg-surface-sunken p-3">
            <BaseCheckbox
              v-for="event in availableEvents"
              :key="event.name"
              :id="'webhookEvent-' + event.name"
              :model-value="form.subscribed_events.includes(event.name)"
              :description="$t(event.description)"
              @update:model-value="(checked) => toggleEvent(event.name, checked)"
            >
              <template #label>
                <span class="font-mono text-xs">{{ event.name }}</span>
              </template>
            </BaseCheckbox>
          </div>
          <p v-if="form.errors.subscribed_events" class="mt-1 text-[11.5px] text-danger">
            {{ form.errors.subscribed_events }}
          </p>
        </fieldset>
        <BaseCheckbox
          id="webhookEndpointActive"
          v-model="form.is_active"
          :label="$t('Active')"
        />
      </form>
      <template #footer>
        <BaseUIButton
          label="Cancel"
          is-cancel-button
          hide-icon
          :disabled="form.processing"
          @click="closeForm"
        />
        <BaseUIButton
          type="submit"
          form="webhookEndpointForm"
          label="Save"
          variant="primary"
          icon="IconDeviceFloppy"
          :processing="form.processing"
          :disabled="form.processing"
        />
      </template>
    </ArtworkBaseModal>

    <ArtworkBaseModal
      v-if="newSecret"
      title="Endpoint secret"
      modal-size="sm:max-w-lg"
      @close="newSecret = null"
    >
      <div class="space-y-3">
        <BaseAlertComponent
          message="Store this secret in the receiving system now. It is not shown again."
          use-translation
          type="warning"
        />
        <div class="flex items-center gap-2 rounded-md border border-border-subtle bg-surface-sunken p-3">
          <code class="grow break-all font-mono text-xs text-text">{{ newSecret }}</code>
          <BaseUIButton
            label="Copy"
            variant="ghost"
            size="sm"
            icon="IconClipboardCopy"
            @click="copy(newSecret)"
          />
        </div>
        <p class="text-xs text-text-muted">
          {{ $t('The receiver verifies the X-Artwork-Signature header as sha256=HMAC_SHA256(timestamp + "." + body, secret).') }}
        </p>
      </div>
      <template #footer>
        <BaseUIButton
          label="Close"
          variant="secondary"
          hide-icon
          @click="newSecret = null"
        />
      </template>
    </ArtworkBaseModal>

    <confirmation-component
      v-if="endpointToDelete"
      :titel="$t('Delete endpoint')"
      :description="$t('Deliveries already logged are removed together with the endpoint. Continue?')"
      :confirm="$t('Delete')"
      @closed="handleDelete"
    />

    <ArtworkBaseModal
      v-if="deliveryEndpoint"
      title="Deliveries"
      :description="deliveryEndpoint.name"
      modal-size="sm:max-w-4xl"
      @close="closeDeliveries"
    >
      <div v-if="loadingDeliveries" class="flex justify-center py-8">
        <PropertyIcon name="IconLoader2" class="size-6 animate-spin text-text-muted" aria-hidden="true"/>
        <span class="sr-only">{{ $t('Loading...') }}</span>
      </div>
      <div v-else-if="deliveries.data && deliveries.data.length" class="overflow-x-auto rounded-lg border border-border-subtle">
        <table class="min-w-full divide-y divide-border-subtle">
          <thead class="bg-surface-sunken">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase">{{ $t('Date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase">{{ $t('Event') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase">{{ $t('Status') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase">{{ $t('Attempts') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-text-muted uppercase">{{ $t('Actions') }}</th>
            </tr>
          </thead>
          <tbody class="bg-surface divide-y divide-border-subtle">
            <tr v-for="delivery in deliveries.data" :key="delivery.id">
              <td class="px-4 py-3 whitespace-nowrap text-sm text-text">{{ formatDate(delivery.created_at) }}</td>
              <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-text">{{ delivery.event_name }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-sm">
                <span :class="statusClass(delivery.status)" class="px-2 py-1 rounded text-xs font-semibold">
                  {{ delivery.status }}<template v-if="delivery.response_status"> · {{ delivery.response_status }}</template>
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm text-text-muted">{{ delivery.attempt }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                <!-- Nur erschöpfte Zustellungen: bei pending/failed läuft die Retry-Kette noch -->
                <BaseUIButton
                  v-if="delivery.status === 'exhausted'"
                  label="Send again"
                  variant="ghost"
                  size="sm"
                  icon="IconSend"
                  @click="redeliver(delivery)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <EmptyState
        v-else
        icon="IconWebhook"
        :title="$t('No deliveries yet')"
      />
      <template #footer>
        <BaseUIButton
          label="Close"
          variant="secondary"
          hide-icon
          @click="closeDeliveries"
        />
      </template>
    </ArtworkBaseModal>
  </div>
</template>

<script>
import { IconPlus } from '@tabler/icons-vue'
import { defineComponent } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import dayjs from 'dayjs'

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
    IconPlus
  },
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
      this.form.subscribed_events = [...(endpoint.subscribed_events ?? [])]
      this.form.is_active = endpoint.is_active
      this.showFormModal = true
    },
    closeForm() {
      this.showFormModal = false
      this.editing = null
      this.form.clearErrors()
    },
    toggleEvent(eventName, checked) {
      const selected = this.form.subscribed_events.filter((name) => name !== eventName)
      if (checked) {
        selected.push(eventName)
      }
      this.form.subscribed_events = selected
    },
    submitForm() {
      if (this.form.processing) {
        return
      }
      const options = {
        preserveScroll: true,
        onSuccess: () => {
          this.showFormModal = false
          // Nur beim Anlegen liefert der Server ein Geheimnis; danach ist es nicht mehr abrufbar.
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
    closeDeliveries() {
      this.deliveryEndpoint = null
      this.deliveries = { data: [] }
    },
    async showDeliveries(endpoint) {
      // Liste des zuvor geöffneten Empfängers nicht stehen lassen (auch nicht bei Ladefehler)
      if (this.deliveryEndpoint?.id !== endpoint.id) {
        this.deliveries = { data: [] }
      }
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
        onSuccess: () => {
          // Modal kann inzwischen geschlossen sein
          if (this.deliveryEndpoint) {
            this.showDeliveries(this.deliveryEndpoint)
          }
        }
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
