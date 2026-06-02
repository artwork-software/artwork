<template>
    <ExternalGuestLayout :title="$t('Link invalid')">
        <div class="space-y-6">
            <h1 class="font-lexend text-2xl font-bold text-zinc-900 tracking-tight">
                {{ $t('Link invalid') }}
            </h1>

            <p class="text-sm text-zinc-600">
                {{ $t('The link is invalid or has expired.') }}
            </p>

            <Link
                :href="route('external.login.form')"
                class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline"
            >
                {{ $t('Request a new login link') }}
            </Link>
        </div>
    </ExternalGuestLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ExternalGuestLayout from '@/Pages/ExternalAccess/Layouts/ExternalGuestLayout.vue'
</script>
