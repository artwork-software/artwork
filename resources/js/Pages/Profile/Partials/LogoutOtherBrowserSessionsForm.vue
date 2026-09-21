<template>
    <div class="space-y-4">
        <div>
            <h4 class="text-sm font-semibold text-text">
                {{ $t('Active browser sessions') }}
            </h4>
            <p class="mt-1 text-sm text-text-muted">
                {{ $t('If necessary, you can log out of all other browser sessions on all your devices. If you suspect your account has been compromised, you should also change your password.') }}
            </p>
        </div>

        <div v-if="sessions.length > 0" class="space-y-3">
            <div v-for="(session, i) in sessions" :key="i" class="flex items-center gap-3">
                <component
                    :is="session.agent.is_desktop ? IconDeviceDesktop : IconDeviceMobile"
                    class="h-6 w-6 shrink-0 text-text-subtle"
                    stroke-width="1.5"
                />
                <div class="min-w-0">
                    <div class="text-sm text-text-muted">
                        {{ session.agent.platform || $t('Unknown device') }} – {{ session.agent.browser || $t('Unknown browser') }}
                    </div>
                    <div class="text-xs text-text-subtle">
                        {{ session.ip_address }},
                        <span v-if="session.is_current_device" class="font-semibold text-success">{{ $t('This device') }}</span>
                        <span v-else>{{ $t('Last active') }} {{ session.last_active }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!confirming" class="flex items-center gap-3">
            <BaseUIButton
                :label="$t('Log Out Other Browser Sessions')"
                use-translation
                variant="secondary"
                hide-icon
                @click="confirming = true"
            />
            <span v-if="recentlyLoggedOut" class="text-sm text-success">{{ $t('Other browser sessions have been logged out.') }}</span>
        </div>

        <form v-else class="space-y-3" @submit.prevent="logoutOtherBrowserSessions">
            <p class="text-sm text-text-muted">
                {{ $t('Please enter your password to confirm that you want to log out of all other browser sessions.') }}
            </p>
            <BaseInput
                id="logout_other_sessions_password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                :label="$t('Password')"
                required
            />
            <JetInputError :message="form.errors.password" />
            <div class="flex items-center gap-3">
                <BaseUIButton
                    :label="$t('Log Out Other Browser Sessions')"
                    use-translation
                    is-add-button
                    hide-icon
                    type="submit"
                    :disabled="form.processing || !form.password"
                />
                <BaseUIButton
                    :label="$t('Cancel')"
                    use-translation
                    is-cancel-button
                    hide-icon
                    type="button"
                    @click="cancel"
                />
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { IconDeviceDesktop, IconDeviceMobile } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import JetInputError from '@/Jetstream/InputError.vue'

// Nutzt die Web-Route user.browser-sessions.destroy; die Jetstream-Route hängt am api-Guard und ist für Web-Sessions tot.
const sessions = ref([])
const confirming = ref(false)
const recentlyLoggedOut = ref(false)

const form = useForm({ password: '' })

async function loadSessions() {
    try {
        const { data } = await axios.get(route('user.browser-sessions.index'))
        sessions.value = data?.sessions ?? []
    } catch (e) {
        sessions.value = []
    }
}

function cancel() {
    confirming.value = false
    form.reset()
    form.clearErrors()
}

function logoutOtherBrowserSessions() {
    form.delete(route('user.browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            confirming.value = false
            recentlyLoggedOut.value = true
            setTimeout(() => (recentlyLoggedOut.value = false), 4000)
            loadSessions()
        },
        onFinish: () => form.reset(),
    })
}

onMounted(loadSessions)
</script>
