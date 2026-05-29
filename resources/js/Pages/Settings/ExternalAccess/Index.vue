<template>
    <AppLayout :title="$t('External access settings')">
        <div class="mx-auto max-w-3xl mt-6 px-6 pb-20 space-y-8">
            <header>
                <h1 class="text-xl font-semibold">{{ $t('External access settings') }}</h1>
                <p class="mt-2 text-sm text-zinc-500">
                    {{ $t('Configure defaults and notification recipients for external access invitations.') }}
                </p>
            </header>

            <form @submit.prevent="save" class="space-y-8">
                <!-- Invitation wording -->
                <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">{{ $t('Invitation wording') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ $t('The company name used in invitation emails.') }}</p>
                    <div class="mt-4">
                        <label class="componentLabel">{{ $t('Company name (override)') }}</label>
                        <input
                            v-model="form.company_name_override"
                            type="text"
                            :placeholder="settings.effective_company_name_without_override"
                            class="mt-1 block w-full rounded-lg border-zinc-300 text-sm"
                        />
                        <p v-if="!form.company_name_override" class="text-xs text-zinc-500 mt-1">
                            {{ $t('Will use:') }} <strong>{{ settings.effective_company_name_without_override }}</strong>
                        </p>
                    </div>
                </section>

                <!-- Default durations -->
                <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">{{ $t('Default durations') }}</h2>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <label class="componentLabel">{{ $t('CRM access default (months)') }}</label>
                            <input v-model.number="form.default_crm_access_months" type="number" min="1" max="120" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm" />
                        </div>
                        <div>
                            <label class="componentLabel">{{ $t('Tab access default (days)') }}</label>
                            <input v-model.number="form.default_tab_access_days" type="number" min="1" max="3650" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm" />
                        </div>
                    </div>
                </section>

                <!-- Security -->
                <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                    <button type="button" class="flex w-full items-center justify-between" @click="securityOpen = !securityOpen">
                        <div class="text-left">
                            <h2 class="text-lg font-semibold">{{ $t('Security & rate limiting') }}</h2>
                            <p class="text-sm text-zinc-500">{{ $t('Advanced settings — change with care.') }}</p>
                        </div>
                        <span class="text-zinc-400">{{ securityOpen ? '−' : '+' }}</span>
                    </button>
                    <div v-if="securityOpen" class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <label class="componentLabel">{{ $t('Magic link lifetime (minutes)') }}</label>
                            <input v-model.number="form.login_token_lifetime_minutes" type="number" min="5" max="60" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm" />
                            <p v-if="errors.login_token_lifetime_minutes" class="text-xs text-red-600 mt-1">{{ errors.login_token_lifetime_minutes }}</p>
                        </div>
                        <div>
                            <label class="componentLabel">{{ $t('Session idle timeout (minutes)') }}</label>
                            <input v-model.number="form.session_idle_timeout_minutes" type="number" min="15" max="480" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm" />
                            <p v-if="errors.session_idle_timeout_minutes" class="text-xs text-red-600 mt-1">{{ errors.session_idle_timeout_minutes }}</p>
                        </div>
                        <div>
                            <label class="componentLabel">{{ $t('Session absolute lifetime (minutes)') }}</label>
                            <input v-model.number="form.session_absolute_lifetime_minutes" type="number" min="60" max="1440" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm" />
                        </div>
                        <div>
                            <label class="componentLabel">{{ $t('Link requests per email per hour') }}</label>
                            <input v-model.number="form.rate_limit_request_link_per_email_per_hour" type="number" min="1" max="100" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm" />
                        </div>
                        <div>
                            <label class="componentLabel">{{ $t('Link requests per IP per hour') }}</label>
                            <input v-model.number="form.rate_limit_request_link_per_ip_per_hour" type="number" min="1" max="1000" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm" />
                        </div>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing" class="ui-button-add">{{ $t('Save settings') }}</button>
                </div>
            </form>

            <!-- Recipients -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                <header class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $t('Blanket notification recipients') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            {{ $t('These recipients receive a copy of every external access notification.') }}
                        </p>
                    </div>
                    <button class="ui-button" @click="openAdd">{{ $t('Add recipient') }}</button>
                </header>

                <ul v-if="notificationRecipients.length" class="mt-6 space-y-2">
                    <li v-for="r in notificationRecipients" :key="r.id" class="flex items-center justify-between border border-zinc-100 rounded-lg px-4 py-3">
                        <div>
                            <div class="text-sm font-medium">{{ r.label }}</div>
                            <div class="text-xs text-zinc-500 mt-0.5">{{ formatTypes(r.notification_types) }}</div>
                        </div>
                        <div class="flex gap-2">
                            <button class="text-xs text-blue-600 hover:underline" @click="openEdit(r)">{{ $t('Edit') }}</button>
                            <button class="text-xs text-red-600 hover:underline" @click="remove(r)">{{ $t('Remove') }}</button>
                        </div>
                    </li>
                </ul>
                <p v-else class="mt-6 text-sm text-zinc-500">
                    {{ $t('No blanket recipients configured. Only inviters and project managers receive notifications.') }}
                </p>
            </section>
        </div>

        <!-- Add recipient dialog -->
        <ArtworkBaseModal v-if="addOpen" :title="$t('Add recipient')" :description="''" @close="addOpen = false">
            <div class="mt-4 space-y-4">
                <div>
                    <label class="componentLabel">{{ $t('Linked to') }}</label>
                    <select v-model="addForm.option" class="menu-button bg-white mt-1">
                        <optgroup :label="$t('Users')">
                            <option v-for="o in recipientOptions.users" :key="`u-${o.id}`" :value="o">{{ o.label }}</option>
                        </optgroup>
                        <optgroup :label="$t('Departments')">
                            <option v-for="o in recipientOptions.departments" :key="`d-${o.id}`" :value="o">{{ o.label }}</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="componentLabel">{{ $t('Notifications') }}</label>
                    <label v-for="t in availableNotificationTypes" :key="t.value" class="flex items-center gap-2 mt-1 text-sm">
                        <input type="checkbox" :value="t.value" v-model="addForm.types" class="rounded" /> {{ t.label }}
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button class="ui-button-cancel" @click="addOpen = false">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" :disabled="!addForm.option || !addForm.types.length" @click="addRecipient">{{ $t('Add recipient') }}</button>
            </div>
        </ArtworkBaseModal>

        <!-- Edit recipient dialog -->
        <ArtworkBaseModal v-if="editOpen" :title="$t('Edit')" :description="''" @close="editOpen = false">
            <div class="mt-4 space-y-2">
                <div class="text-sm font-medium">{{ editForm.label }}</div>
                <label v-for="t in availableNotificationTypes" :key="t.value" class="flex items-center gap-2 text-sm">
                    <input type="checkbox" :value="t.value" v-model="editForm.types" class="rounded" /> {{ t.label }}
                </label>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button class="ui-button-cancel" @click="editOpen = false">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" :disabled="!editForm.types.length" @click="updateRecipient">{{ $t('Save settings') }}</button>
            </div>
        </ArtworkBaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()
const page = usePage()
const errors = computed(() => page.props.errors ?? {})

const props = defineProps({
    settings: { type: Object, required: true },
    notificationRecipients: { type: Array, required: true },
    availableNotificationTypes: { type: Array, required: true },
    recipientOptions: { type: Object, required: true },
})

const securityOpen = ref(false)

const form = useForm({
    company_name_override: props.settings.company_name_override,
    default_crm_access_months: props.settings.default_crm_access_months,
    default_tab_access_days: props.settings.default_tab_access_days,
    login_token_lifetime_minutes: props.settings.login_token_lifetime_minutes,
    session_idle_timeout_minutes: props.settings.session_idle_timeout_minutes,
    session_absolute_lifetime_minutes: props.settings.session_absolute_lifetime_minutes,
    rate_limit_request_link_per_email_per_hour: props.settings.rate_limit_request_link_per_email_per_hour,
    rate_limit_request_link_per_ip_per_hour: props.settings.rate_limit_request_link_per_ip_per_hour,
})

function save() {
    form.patch(route('settings.external-access.update'), { preserveScroll: true })
}

const addOpen = ref(false)
const addForm = ref({ option: null, types: [] })
const editOpen = ref(false)
const editForm = ref({ id: null, label: '', types: [] })

function openAdd() {
    addForm.value = { option: null, types: [] }
    addOpen.value = true
}

function addRecipient() {
    router.post(route('settings.external-access.recipients.add'), {
        recipient_type: addForm.value.option.type,
        recipient_id: addForm.value.option.id,
        notification_types: addForm.value.types,
    }, { preserveScroll: true, onSuccess: () => { addOpen.value = false } })
}

function openEdit(recipient) {
    editForm.value = { id: recipient.id, label: recipient.label, types: [...recipient.notification_types] }
    editOpen.value = true
}

function updateRecipient() {
    router.patch(route('settings.external-access.recipients.update', editForm.value.id), {
        notification_types: editForm.value.types,
    }, { preserveScroll: true, onSuccess: () => { editOpen.value = false } })
}

function remove(recipient) {
    if (!window.confirm($t('Remove') + '?')) return
    router.delete(route('settings.external-access.recipients.remove', recipient.id), { preserveScroll: true })
}

function formatTypes(types) {
    return types.map((t) => props.availableNotificationTypes.find((a) => a.value === t)?.label ?? t).join(', ')
}
</script>
