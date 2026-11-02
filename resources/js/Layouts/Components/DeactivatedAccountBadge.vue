<template>
    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold"
          :class="onDark ? 'bg-white/10 text-white/80 ring-1 ring-inset ring-white/20' : 'bg-surface-sunken text-text-subtle ring-1 ring-inset ring-border'">
        <PropertyIcon name="IconUserOff" class="h-3.5 w-3.5"/>
        {{ $t('Account inactive') }}
    </span>
</template>

<script setup>
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

// Vermerk für deaktivierte Accounts in Tooltips, Listen und Profilkopf (statt der E-Mail-Adresse)
defineProps({
    onDark: { type: Boolean, default: false },
})
</script>
