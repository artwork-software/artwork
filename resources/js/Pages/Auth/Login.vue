<template>
    <div>
        <Head>
            <link rel="icon" type="image/png" :href="$page.props.small_logo" />
            <title>{{ $t('Login') }} - {{ $page.props.page_title }}</title>
        </Head>

        <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">
            <!-- Left: Form -->
            <section class="flex items-center justify-center py-10">
                <div class="w-full max-w-md px-6">
                    <!-- Branding + Titel -->
                    <div class="flex items-center gap-4 mb-8">
                        <img :src="$page.props.big_logo" class="h-10 w-auto" alt="Artwork Logo" />
                        <h1 class="font-lexend text-2xl font-bold text-text tracking-tight">
                            {{ $t('Login') }}
                        </h1>
                    </div>

                    <!-- Status (z. B. „Passwort zurückgesetzt“) -->
                    <p v-if="status" class="mb-4 rounded-xl border border-success-border bg-success-surface px-4 py-2 text-sm text-success">
                        {{ status }}
                    </p>

                    <!-- Card -->
                    <div class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                        <form class="p-6 space-y-6" @submit.prevent="submit">
                            <div class="space-y-4">
                                <BaseInput
                                    id="email"
                                    v-model="form.email"
                                    :label="$t('Email') + '*'"
                                    autocomplete="email"
                                    required
                                />

                                <!-- Passwort mit Toggle -->
                                <div>
                                    <div class="flex items-center justify-end">
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="text-xs font-medium text-accent-600 hover:text-accent-700 focus:outline-none focus:underline"
                                        >
                                            {{ showPassword ? $t('Hide password') : $t('Show password') }}
                                        </button>
                                    </div>

                                    <div class="mt-1">
                                        <BaseInput
                                            id="password"
                                            :type="showPassword ? 'text' : 'password'"
                                            v-model="form.password"
                                            autocomplete="current-password"
                                            required
                                            :label="$t('Password') + '*'"
                                            @keydown.enter.prevent="submit"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Fehler -->
                            <div class="space-y-2">
                                <JetInputError :message="errors.email" />
                                <JetInputError :message="errors.password" />
                                <JetInputError :message="form.error" />
                            </div>

                            <!-- Remember + Passwort vergessen -->
                            <div class="flex items-center justify-between">
                                <Checkbox class="justify-between text-sm" :item="rememberCheckbox" />
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-xs text-accent-600 hover:text-accent-700 hover:font-semibold"
                                >
                                    {{ $t('Forgot your password?') }}
                                </Link>
                            </div>

                            <!-- Submit -->
                            <div class="pt-2">
                                <BaseUIButton
                                    :label="$t('Login')"
                                    use-translation
                                    is-add-button
                                    icon="IconLogin"
                                    type="submit"
                                    :disabled="isDisabled"
                                />
                            </div>
                        </form>
                    </div>

                    <!-- SSO / OIDC Login -->
                    <div v-if="identityProviderSources && identityProviderSources.length" class="mt-6">
                        <div class="relative flex items-center">
                            <div class="flex-grow border-t border-border-subtle"></div>
                            <span class="mx-3 text-xs uppercase tracking-wide text-text-subtle">
                                {{ $t('or') }}
                            </span>
                            <div class="flex-grow border-t border-border-subtle"></div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <a
                                v-for="source in identityProviderSources"
                                :key="source.id"
                                :href="route('auth.oidc.redirect', { externalUserSource: source.id })"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-border-subtle bg-white px-4 py-2.5 text-sm font-medium text-text-muted shadow-sm transition hover:bg-surface-sunken hover:text-accent-700"
                            >
                                {{ $t('Sign in with :name', { name: source.name }) }}
                            </a>
                        </div>
                    </div>

                    <!-- Footer Links -->
                    <div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-text-muted">
                        <template v-if="$page.props.impressumLink">
                            <a :href="$page.props.impressumLink" target="_blank" class="hover:text-accent-700 hover:underline">
                                {{ $t('Imprint') }}
                            </a>
                            <span class="text-text-subtle">|</span>
                        </template>

                        <template v-if="$page.props.privacyLink">
                            <a :href="$page.props.privacyLink" target="_blank" class="hover:text-accent-700 hover:underline">
                                {{ $t('Privacy Policy') }}
                            </a>
                            <span class="text-text-subtle">|</span>
                        </template>

                        <a href="https://artwork.software/" target="_blank" class="hover:text-accent-700 hover:underline">
                            {{ $t('About the tool') }}
                        </a>
                    </div>
                </div>
            </section>

            <!-- Right: Banner -->
            <section class="relative hidden lg:flex">
                <img
                    class="absolute inset-0 h-full w-full object-cover"
                    :src="$page.props.banner"
                    alt="Banner"
                />
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import Checkbox from "@/Layouts/Components/Checkbox.vue"
import JetInputError from "@/Jetstream/InputError.vue"
import BaseInput from "@/Artwork/Inputs/BaseInput.vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"
import { reloadRolesAndPermissions } from 'laravel-permission-to-vuejs'

defineProps<{
    canResetPassword: boolean
    status?: string
    identityProviderSources?: { id: number; name: string }[]
}>()

const page = usePage()
const errors = computed(() => page.props.errors as Record<string, string>)

const form = useForm({
    email: '',
    password: '',
    remember: false,
    error: '',
})

const rememberCheckbox = ref({
    name: page.props.localization?.['Remember me'] ?? 'Remember me',
    checked: false,
    showIcon: false,
})

const showPassword = ref(false)

const isDisabled = computed(() =>
    !form.email?.trim() || !form.password?.trim()
)

function submit() {
    form.transform((data) => ({
        ...data,
        remember: rememberCheckbox.value.checked ? 'on' : ''
    }))
        .post(route('login'), {
            onFinish: () => {
                reloadRolesAndPermissions()
                form.reset('password')
            },
        })
}
</script>

<style scoped>
/* Dezente Focus-Optimierung für Inputs innerhalb der Card (fallback, falls BaseInput es nicht schon setzt) */
:deep(input[type="text"]),
:deep(input[type="email"]),
:deep(input[type="password"]) {
    transition: box-shadow .15s ease, border-color .15s ease;
}
:deep(input:focus) {
    box-shadow: 0 0 0 4px rgba(14, 165, 233, .15); /* sky-500 als weicher Ring */
    border-color: rgb(56, 189, 248);
}
</style>
