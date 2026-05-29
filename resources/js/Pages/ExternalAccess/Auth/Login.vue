<template>
    <ExternalGuestLayout :title="$t('Login')">
        <div class="space-y-6">
            <h1 class="font-lexend text-2xl font-bold text-zinc-900 tracking-tight">
                {{ $t('Login') }}
            </h1>

            <p class="text-sm text-zinc-600">
                {{ $t('Enter your email and we will send you a login link.') }}
            </p>

            <p
                v-if="page.props.flash?.status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700"
            >
                {{ page.props.flash.status }}
            </p>

            <p
                v-if="isExpired"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-700"
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
