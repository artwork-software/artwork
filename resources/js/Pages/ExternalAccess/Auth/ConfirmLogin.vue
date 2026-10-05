<template>
    <ExternalGuestLayout :title="$t('Confirm login')">
        <div class="space-y-6">
            <h1 class="font-lexend text-2xl font-bold text-text tracking-tight">
                {{ $t('Confirm login') }}
            </h1>

            <p class="text-sm text-text-muted">
                {{ $t('Click the button to sign in with your login link. The link can only be used once.') }}
            </p>

            <form @submit.prevent="submit">
                <BaseUIButton
                    :label="$t('Sign in')"
                    use-translation
                    is-add-button
                    icon="IconLogin"
                    type="submit"
                    :disabled="form.processing"
                />
            </form>

            <Link
                :href="route('external.login.form')"
                class="inline-flex items-center text-sm font-medium text-accent-600 hover:text-accent-700 hover:underline"
            >
                {{ $t('Request a new login link') }}
            </Link>
        </div>
    </ExternalGuestLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import ExternalGuestLayout from '@/Pages/ExternalAccess/Layouts/ExternalGuestLayout.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    token: { type: String, required: true },
})

const form = useForm({})

function submit() {
    form.post(route('external.login.redeem.store', { token: props.token }))
}
</script>
