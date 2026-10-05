<template>
    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ $t('Invitation expired') }} - {{ $page.props.page_title }}</title>
    </Head>
    <div class="py-8 px-8 md:px-64">
        <div class="space-y-6">
            <div class="text-2xl font-bold text-black">
                <img src="/Svgs/Logos/artwork_logo_big.svg" alt="artwork_logo_big"/>
            </div>
            <div class="flex items-center">
                <h2 class="mt-6 text-3xl font-lexend font-bold text-text">{{ $t('Invitation expired') }}</h2>
            </div>
            <p class="text-text">
                {{ $t('This invitation has expired. Please ask the person who invited you to send a new invitation.') }}
            </p>
            <p v-if="email" class="text-sm text-text-subtle">
                {{ $t('Invited e-mail address') }}: {{ email }}
            </p>
        </div>
    </div>
</template>

<script setup>
import {Head} from '@inertiajs/vue3';

defineProps({
    email: {
        type: String,
        default: null,
    },
});
</script>
