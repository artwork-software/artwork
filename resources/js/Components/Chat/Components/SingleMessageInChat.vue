<template>
    <div class="flex items-start gap-2.5 my-4" v-if="message.sender_id !== currentUserId">
        <img class="size-8 rounded-full object-cover" :src="message.sender?.profile_photo_url" alt="avatar">
        <div class="flex flex-col gap-1">
            <div class="text-xs font-semibold text-gray-900">{{ message.sender.full_name }}</div>
            <div class="px-4 py-2 bg-blue-100 text-blue-800 rounded-e-xl rounded-es-xl">
                <p class="text-xs">{{ decryptedText }}</p>
            </div>
            <span class="text-[9px]">{{ message.created_at }}</span>
        </div>
    </div>

    <div class="flex items-start justify-end gap-2.5 my-4" v-else>
        <div class="flex flex-col gap-1">
            <div class="text-xs font-semibold text-gray-900 text-right">{{ $t('You') }}</div>
            <div class="px-4 py-2 bg-gray-100 text-gray-800 rounded-s-xl rounded-ee-xl">
                <p class="text-xs">{{ decryptedText }}</p>
            </div>
            <div class="flex items-center justify-end gap-x-1">
                <span class="text-[9px]">{{ message.created_at }}</span>
                <div class="flex items-center gap-1 text-[10px] text-gray-400">
                    <ToolTipComponent
                        :tooltip-text="tooltipText ? tooltipText : $t('Unread')"
                        icon-size="size-4"
                        :icon="tooltipText ? 'IconChecks' : 'IconCheck'"
                        :classes="tooltipText ? '!text-blue-500' : '!text-gray-400'"
                    />
                </div>
            </div>
        </div>
        <img class="size-8 rounded-full object-cover" :src="message.sender?.profile_photo_url" alt="avatar">

    </div>

</template>

<script setup>

import {usePage} from "@inertiajs/vue3";
import useCrypto from "@/Composeables/useCrypto.js";
import {computed, ref, watchEffect} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const {
    hasKeypair,
    decrypt,
} = useCrypto()

const props = defineProps({
    message: {
        type: Object,
        required: true
    }
})

const decryptedText = ref('[entschlüsseln...]')

const currentUserId = usePage().props.auth.user.id

watchEffect(async () => {
    if (!hasKeypair.value || !props.message) return

    const isOwnMessage = props.message.sender_id === currentUserId
    const cipher = isOwnMessage
        ? props.message.cipher_for_sender
        : props.message.ciphers_json?.[currentUserId]

    if (!cipher) {
        decryptedText.value = '[nicht verfügbar]'
        return
    }

    try {
        decryptedText.value = await decrypt(cipher)
    } catch {
        decryptedText.value = '[nicht entschlüsselbar]'
    }
})

const isGroupChat = computed(() => {
    return props.message.chat?.is_group === true;
});


const readTimestamps = computed(() => {
    if (!props.message.reads) return [];

    return props.message.reads
        .filter(read => read.user_id !== currentUserId)
        .map(read => ({
            name: read.user?.full_name ?? 'Unbekannt',
            time: read.read_at ?? '-',
        }));
});

const tooltipText = computed(() => {
    if (!readTimestamps.value.length) return '';

    if (isGroupChat.value) {
        return readTimestamps.value
            .map(read => `${read.name}: ${read.time}`)
            .join('\n');
    }

    return `Gelesen am: ${readTimestamps.value[0]?.time}`;
});


</script>

<style scoped>

</style>