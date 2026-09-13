<template>
    <!-- Umschalter zwischen den beiden Sichten auf Regeln: nach Regel (Regelliste) / nach Vertrag (Zuordnung) -->
    <div class="inline-flex items-center rounded-lg border border-border-subtle bg-surface-sunken p-0.5 text-xs font-medium">
        <Link
            :href="route('shift-rules.index')"
            class="rounded-md px-3 py-1.5 transition"
            :class="current === 'rules' ? 'bg-surface text-text shadow-sm' : 'text-text-subtle hover:text-text'"
        >
            {{ $t('By rule') }}
        </Link>
        <Link
            :href="route('shift-rules.contracts.index')"
            class="rounded-md px-3 py-1.5 transition"
            :class="current === 'contracts' ? 'bg-surface text-text shadow-sm' : 'text-text-subtle hover:text-text'"
        >
            {{ $t('By contract') }}
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    current: { type: String, default: 'rules' },
})
</script>
