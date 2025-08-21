<template>
    <div class="flex items-start gap-2.5 my-4" v-if="message.sender_id !== currentUserId">
       <!-- <img class="size-8 rounded-full object-cover" :src="message.sender?.profile_photo_url" alt="avatar">-->
        <div class="flex flex-col gap-1">
            <div class="text-[9px] text-gray-900" v-if="chat.is_group">{{ message.sender.full_name }}</div>
            <div class="px-4 py-2 bg-white text-gray-950 rounded-e-md rounded-es-md">
                <p class="text-xs" v-html="plainText"></p>
                <span class="text-[9px]">{{ timeOnly }}</span>
            </div>

        </div>
    </div>

    <div class="flex items-start justify-end gap-2.5 my-4" v-else>
        <div class="flex flex-col gap-1">
            <!--<div class="text-xs font-semibold text-gray-900 text-right">{{ $t('You') }}</div>-->
            <div class="px-4 py-2 bg-[#015df2] text-white rounded-s-md rounded-ee-md">
                <p class="text-xs" v-html="plainText"></p>
                <div class="flex items-center justify-end gap-x-1">
                    <span class="text-[9px]">{{ timeOnly }}</span>
                    <div class="flex items-center gap-1 text-[10px]">
                        <ToolTipComponent
                            :tooltip-text="tooltipText ? tooltipText : $t('Unread')"
                            icon-size="size-4"
                            white-icon
                            :icon="tooltipText ? 'IconChecks' : 'IconCheck'"
                            :classes="tooltipText ? '!text-white-500' : '!text-gray-300'"
                        />
                    </div>
                </div>
            </div>

        </div>
        <!--<img class="size-8 rounded-full object-cover" :src="message.sender?.profile_photo_url" alt="avatar">-->

    </div>

</template>

<script setup>
import {usePage} from "@inertiajs/vue3";
import {computed} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const props = defineProps({
    message: {
        type: Object,
        required: true
    },
    chat:{
        type: Object,
        required: false,
        default: null
    }
})

// Plaintext direkt aus dem Backend-Feld "message"
const plainText = computed(() => props.message?.message ?? '')

const currentUserId = usePage().props.auth.user.id

// Nur Uhrzeit (HH:mm). Fallback: created_at_time bzw. created_at
const timeOnly = computed(() => {
    const iso = props.message?.created_at_iso;
    if (iso) {
        const d = new Date(iso);
        if (!Number.isNaN(d.getTime())) {
            try {
                return new Intl.DateTimeFormat('de-DE', { hour: '2-digit', minute: '2-digit' }).format(d);
            } catch {}
        }
    }
    // Falls Backend bereits created_at_time mitsendet
    if (props.message?.created_at_time) return props.message.created_at_time;
    // Letzter Fallback
    return props.message?.created_at ?? '';
});


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
