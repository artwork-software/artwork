<template>
    <ExternalGuestLayout :title="$t('Login')">
        <div class="space-y-6">
            <h1 class="font-lexend text-2xl font-bold text-text tracking-tight">
                {{ $t('Login') }}
            </h1>

            <p class="text-sm text-text-muted">
                {{ $t('Enter your email and we will send you a login link.') }}
            </p>

            <p
                v-if="page.props.flash?.status"
                class="rounded-xl border border-success-border bg-success-surface px-4 py-2 text-sm text-success"
            >
                {{ page.props.flash.status }}
            </p>

            <p
                v-if="isExpired"
                class="rounded-xl border border-warning-border bg-warning-surface px-4 py-2 text-sm text-warning"
            >
                {{ $t('Your session has expired. Please request a new login link.') }}
            </p>

            <form class="space-y-4" @submit.prevent="submit">
                <BaseInput
                    id="email"
                    v-model="form.email"
                    :label="$t('Email') + '*'"
                    type="email"
                    autocomplete="email"
                    required
                />
                <JetInputError :message="errors.email" />

                <BaseUIButton
                    :label="$t('Request login link')"
                    use-translation
                    is-add-button
                    icon="IconMail"
                    type="submit"
                    :disabled="!form.email || form.processing"
                />
            </form>
        </div>
    </ExternalGuestLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ExternalGuestLayout from '@/Pages/ExternalAccess/Layouts/ExternalGuestLayout.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import JetInputError from '@/Jetstream/InputError.vue'

const page = usePage()
const errors = computed(() => page.props.errors ?? {})

const isExpired = computed(() => {
    if (typeof window === 'undefined') return false
    return new URLSearchParams(window.location.search).get('expired') === '1'
})

const form = useForm({ email: '' })

function submit() {
    form.post(route('external.login.request'), {
        onSuccess: () => form.reset('email'),
    })
}
</script>
