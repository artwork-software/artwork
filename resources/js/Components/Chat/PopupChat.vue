<template>
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-50">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <ChatToastNotification
                v-for="(toast, index) in toastMessages"
                :key="index"
                :avatar="toast.avatar"
                :name="toast.name"
                :message="toast.message"
                @click="() => openChatPage(toast.chatId)"
            />
        </div>
    </div>
    <div class="fixed bottom-5 right-5 z-50">
        <div v-if="!isChatOpen">
            <div class="rounded-full shadow-lg bg-white p-2 cursor-pointer border border-gray-200 relative" @click="openChat">
                <component is="IconBubbleText" class="size-10" />

                <span v-if="totalUnreadCount > 0" class="absolute inline-flex items-center w-6 h-6 -top-2 -right-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex items-center justify-center w-6 h-6 text-xs font-bold leading-none text-red-700 border-red-100 border  bg-red-50 rounded-full">
                        {{ totalUnreadCount }}
                    </span>
                </span>

            </div>
        </div>

        <div v-if="isChatOpen">
            <div class="shadow-lg bg-white rounded-lg w-96">
                <div class="bg-artwork-buttons-default px-4 py-4 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <div class="font-lexend text-white text-xs font-bold">
                            Artwork Chat <span v-if="checkIfChatIsSelected">mit {{ getChatName(chatPartner) }}</span>
                        </div>
                        <div class="flex items-center gap-x-3">
                            <component is="IconMessageCirclePlus" class="size-5 cursor-pointer text-white" v-if="!checkIfChatIsSelected" @click="openAddNewChat = true" />
                            <component is="IconInfoSquareRoundedFilled" class="size-5 cursor-pointer text-white" v-if="checkIfChatIsSelected" @click="openShowChatInfo = true" />
                            <component is="IconX" class="size-5 cursor-pointer text-white" @click="closeChat" />
                        </div>
                    </div>
                </div>
                <div class="">
                    <div v-if="!checkIfChatIsSelected" class="bg-white px-3 py-2">
                        <div class="grid grid-cols-1">
                            <input v-model="searchChat" type="text" name="account-number" id="account-number" class="col-start-1 border border-gray-200 row-start-1 block w-full rounded-md bg-white py-1.5 pr-10 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:pr-9 sm:text-sm/6" placeholder="Suche Chat Partner" />
                            <component is="IconSearch" class="pointer-events-none col-start-1 row-start-1 mr-3 size-5 self-center justify-self-end text-gray-400 sm:size-4" aria-hidden="true" />
                        </div>
                    </div>
                    <div v-else class="bg-artwork-buttons-create/20 px-3 py-2">
                        <div class="font-lexend text-xs font-medium flex items-center cursor-pointer " @click="goBackToChatList">
                            <component is="IconArrowLeft" class="size-3" aria-hidden="true" />
                            Zurück zur Chat-Übersicht
                        </div>
                        <div v-if="!chatPartner.is_group" class="mt-2 border-t border-gray-50 pt-2">
                            <div class="flex items-center">
                                <span class="relative inline-block">
                                    <img class="size-10 rounded-full object-cover" :src="chatPartner.users.find(user => user.id !== usePage().props.auth.user.id)?.profile_photo_url" alt="" />
                                    <span class="absolute right-0.5 bottom-0.5 block size-2 rounded-full  ring-2 ring-white" :class="{ 'bg-red-400': status === 'offline', 'bg-orange-400': status === 'away', 'bg-green-400': status === 'online'}" />
                                </span>
                                <div class="ml-3">
                                    <p class="text-sm font-bold text-gray-900 font-lexend">{{ chatPartner.users.find(user => user.id !== usePage().props.auth.user.id)?.full_name }}</p>
                                    <p class="text-xs text-gray-500 font-lexend first-letter:capitalize">{{ status }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="mt-2 border-t border-gray-50 pt-2">
                            <div class="flex items-center">
                                <component is="IconUsersGroup" class="size-10 rounded-full object-cover p-3 bg-blue-50 text-blue-900 border border-blue-100" />
                                <div class="ml-3">
                                    <p class="text-sm font-bold text-gray-900 font-lexend">{{ chatPartner.name }}</p>
                                    <p class="text-xs text-gray-500 font-lexend">{{ chatPartner.users.map((user) => user.full_name).join(', ') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="isLoading">
                    <div class="flex items-center justify-center max-h-96 min-h-96 w-full h-full">
                        <!-- loading -->
                        <component is="IconLoaderQuarter" class="motion-safe:animate-spin" />
                    </div>
                </div>
                <div v-else>
                    <div class="bg-white rounded-b-lg max-h-96 min-h-96 overflow-y-auto" ref="scrollContainer" @scroll="handleScroll">
                        <div class="space-y-3" v-if="!checkIfChatIsSelected">
                            <div class="px-3 py-2 hover:bg-blue-50 transition duration-200 ease-in-out" v-for="chat in filteredChats">
                                <div class=" flex items-center justify-between cursor-pointer" @click="openChatPage(chat.id)">
                                    <div>
                                        <SingleChatInOverview :chat="chat" />
                                    </div>
                                    <span v-if="chat.unread_count > 0" class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold leading-none text-red-700 border-red-100 border  bg-red-50 font-lexend   rounded-full">
                                        {{ chat.unread_count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="px-3 py-2" v-else>
                            <div class="rounded-md bg-yellow-50 p-4 mb-5">
                                <div class="flex items-center">
                                    <div class="shrink-0">
                                        <component is="IconLockAccess" class="size-6 text-yellow-400" aria-hidden="true" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-xs font-lexend font-medium text-yellow-800">
                                            Nachrichten sind Ende-zu-Ende verschlüsselt. Niemand kann sie lesen.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div v-for="message in chatPartner.messages" :key="message.id">
                                    <div v-if="isLoadingMore" class="flex justify-center py-2 text-xs text-gray-500">
                                        <svg class="animate-spin h-4 w-4 mr-2 text-gray-400" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z" />
                                        </svg>
                                        Nachrichten werden geladen...
                                    </div>
                                    <SingleMessageInChat :message="message" />
                                </div>
                                <div ref="scrollBottom"></div>
                            </div>
                        </div>
                    </div>
                    <div v-if="checkIfChatIsSelected" class="bg-white rounded-b-lg px-3 py-4">
                        <div class="flex items-center gap-2">
                            <textarea
                                class="w-full border border-gray-200 rounded-md bg-white py-1.5 pr-10 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline-1 focus:-outline-offset-1 focus:outline-artwork-buttons-create sm:pr-9 sm:text-xs font-lexend"
                                placeholder="Schreibe eine Nachricht"
                                v-model="newMessage"
                            />
                            <div @click="sendMessage" class="rounded-full p-2 bg-artwork-buttons-create cursor-pointer hover:bg-artwork-buttons-hover duration-200 ease-in-out transition-colors">
                                <component is="IconBrandTelegram" class="size-6 cursor-pointer text-white" aria-hidden="true" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <AddNewChat
        v-if="openAddNewChat"
        @close="closeAddNewChat"
    />
</template>

<script setup>
import useCrypto from "@/Composeables/useCrypto.js";

const {
    publicKey,
    privateKey,
    hasKeypair,
    generateKeypair,
    encrypt,
    decrypt,
    clearKeys,
} = useCrypto()
const { getUserStatus } = useUserStatus()
import {computed, nextTick, onBeforeUnmount, onMounted, ref, watch} from "vue";
import AddNewChat from "@/Components/Chat/AddNewChat.vue";
import {router, usePage} from "@inertiajs/vue3";
import SingleChatInOverview from "@/Components/Chat/Components/SingleChatInOverview.vue";
import SingleMessageInChat from "@/Components/Chat/Components/SingleMessageInChat.vue";
import ChatToastNotification from "@/Components/Chat/Components/ChatToastNotification.vue";
import {useUserStatus} from "@/Composeables/useUserStatus.js";

const props = defineProps({})
const chats = ref([])
const openAddNewChat = ref(false);
const isLoading = ref(true);
const isChatOpen = ref(false)
const chatPartner = ref([])
const newMessage = ref('')
const scrollBottom = ref(null);
const scrollContainer = ref(null);
const paginationPage = ref(1);
const hasMoreMessages = ref(true);
const isLoadingMore = ref(false);
const initialScrollDone = ref(false);
const openShowChatInfo = ref(false);
const searchChat = ref('')
const closeChat = () => {
    isChatOpen.value = false
    chatPartner.value = null
}

const closeAddNewChat = (chatId) => {
    isLoading.value = false
    openAddNewChat.value = false
    openChatPage(chatId)
}

const scrollToBottom = (instant = false) => {
    nextTick(() => {
        const container = scrollContainer.value;
        if (!container) return;

        if (instant) {
            container.scrollTop = container.scrollHeight;
        } else {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth',
            });
        }
    });
};


const openChat = async () => {
    generateKeypair();

    try {
        const response = await axios.get(route('chat-system.get-chats'))
        chats.value = response.data.chats
        isChatOpen.value = true
        isLoading.value = false
    } catch (error) {
        console.error("Fehler beim Laden der Chats:", error)
    }
}

const openChatPage = async(chatId) => {

    if(!isChatOpen.value){
        isChatOpen.value = true
    }

    isLoading.value = true;
    paginationPage.value = 1;
    hasMoreMessages.value = true;
    initialScrollDone.value = false;

    try {
        const response = await axios.get(route('chat-system.get-chat-messages', {
            chat: chatId,
            page: 1,
        }));

        chatPartner.value = response.data.chat;
        chatPartner.value.messages = response.data.messages.data.reverse();
        for (const msg of chatPartner.value.messages) {
            // Nicht eigene Nachricht + noch nicht gelesen
            if (
                msg.sender_id !== usePage().props.auth.user.id &&
                !msg.reads?.some(r => r.user_id === usePage().props.auth.user.id)
            ) {
                await markMessagesAsRead(chatPartner.value.messages);
            }
        }
        isLoading.value = false;

        // Reset unread count
        const target = chats.value.find(c => c.id === chatId);
        if (target) target.unread_count = 0;

        await nextTick(() => {
            scrollToBottom(true);
            initialScrollDone.value = true;
        });
    } catch (error) {
        console.error("Fehler beim Laden der Chat-Nachrichten:", error);
    }
};


const sendMessage = async () => {
    const plainText = newMessage.value;
    const isLoading = ref(true);
    const cipherForSender = await encrypt(plainText, usePage().props.auth.user.chat_public_key);

    const ciphers_json = {};
    for (const user of chatPartner.value.users) {
        if (user.id !== usePage().props.auth.user.id && user.chat_public_key) {
            ciphers_json[user.id] = await encrypt(plainText, user.chat_public_key);
        }
    }

    const payload = {
        chat_id: chatPartner.value.id,
        cipher_for_sender: cipherForSender,
        ciphers_json,
    };

    try {
        const response = await fetch(route('chat-system.send-message', { chat: chatPartner.value.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        // Nachricht lokal hinzufügen (mit leeren reads für saubere Broadcast-Reaktion)
        chatPartner.value.messages.push({
            ...result.message,
            reads: [],
        });

        scrollToBottom();
    } catch (e) {
        console.error("Fehler beim Senden der Nachricht:", e);
    } finally {
        isLoading.value = false;
        newMessage.value = '';
    }
};


const goBackToChatList = () => {
    chatPartner.value = []
    openChat()
}

const checkIfChatIsSelected = computed(() => {
    return chatPartner?.value?.length !== 0 && chatPartner?.value?.id !== null && chatPartner?.value?.id !== undefined && chatPartner?.value?.id !== ''
})

const getChatName = (chat) => {
    if (chat.is_group) {
        return chat.name
    }

    return chat.users.find(user => user.id !== usePage().props.auth.user.id)?.full_name
}

const totalUnreadCount = computed(() => {
    return chats.value.reduce((sum, chat) => sum + (chat.unread_count || 0), 0);
});

onMounted(() => {
    const userId = usePage().props.auth.user.id;

    // Nur abonnieren, aber Chat nicht öffnen
    axios.get(route('chat-system.get-chats')).then(response => {
        chats.value = response.data.chats;

        chats.value.forEach(chat => {
            window.Echo.private(`chat.${chat.id}`)
                .listen('.new.message', (e) => {
                    const target = chats.value.find(c => c.id === chat.id);
                    if (target) {
                        target.unread_count = (target.unread_count || 0) + 1;
                        target.last_message = e.message;
                    }

                    // ⛔️ Eigene Nachricht? → abbrechen
                    if (e.message.sender_id === usePage().props.auth.user.id) return;

                    if (isChatOpen.value && checkIfChatIsSelected.value && chatPartner.value.id === chat.id) {
                        const alreadyExists = chatPartner.value.messages.some(m => m.id === e.message.id);
                        if (!alreadyExists) {
                            chatPartner.value.messages.push(e.message);
                            chatPartner.value.messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                            scrollToBottom();
                        }

                        markMessagesAsRead(chatPartner.value.messages);
                    }

                    if (e.message.sender_id !== usePage().props.auth.user.id) {
                        const targetChat = chats.value.find(c => c.id === chat.id);
                        showToast(e.message, targetChat);
                    }
                });

            window.Echo.private(`chat.${chat.id}`)
                .listen('.message.read', (e) => {
                    // 1. Geöffneter Chat → Nachricht suchen
                    const isCurrentOpen = isChatOpen.value &&
                        checkIfChatIsSelected.value &&
                        chatPartner.value?.id === chat.id;

                    const msg = isCurrentOpen
                        ? chatPartner.value.messages.find(m => m.id === e.message_id)
                        : null;

                    if (msg) {
                        if (!msg.reads) msg.reads = [];
                        const alreadyExists = msg.reads.some(r => r.user_id === e.reader_id);
                        if (!alreadyExists) {
                            msg.reads = [...msg.reads, {
                                user_id: e.reader_id,
                                read_at: e.read_at,
                                user: chatPartner.value.users?.find(u => u.id === e.reader_id),
                            }];
                        }
                    } else {
                        // 2. Nachricht nicht im geöffneten Chat → last_message checken
                        const chatItem = chats.value.find(c => c.id === chat.id);
                        if (chatItem?.last_message?.id === e.message_id) {
                            if (!chatItem.last_message.reads) chatItem.last_message.reads = [];
                            const exists = chatItem.last_message.reads.some(r => r.user_id === e.reader_id);
                            if (!exists) {
                                chatItem.last_message.reads = [...chatItem.last_message.reads, {
                                    user_id: e.reader_id,
                                    read_at: e.read_at,
                                    user: chatItem.users?.find(u => u.id === e.reader_id),
                                }];
                            }
                        }
                    }
                });

        });

        // Neuer Chat kommt rein
        window.Echo.private(`user.${userId}`)
            .listen('.chat.created', (e) => {
                chats.value.push(e.chat);
            });
    });
});


onBeforeUnmount(() => {
    chats.value.forEach(chat => {
        window.Echo.leave(`chat.${chat.id}`);
    });

    window.Echo.leave(`user.${usePage().props.auth?.user?.id}`);
});

const handleScroll = async () => {
    if (
        !initialScrollDone.value ||
        !scrollContainer.value ||
        isLoadingMore.value ||
        !hasMoreMessages.value
    ) return;

    if (!chatPartner.value || !chatPartner.value.id) return;



    // Nur wenn der User *manuell hochscrollt*
    if (scrollContainer.value.scrollTop <= 30) {
        isLoadingMore.value = true;
        paginationPage.value += 1;

        try {
            const response = await axios.get(route('chat-system.get-chat-messages', {
                chat: chatPartner.value.id,
                page: paginationPage.value,
            }));

            const newMessages = response.data.messages.data.reverse();

            const previousHeight = scrollContainer.value.scrollHeight;

            chatPartner.value.messages = [...newMessages, ...chatPartner.value.messages];

            nextTick(() => {
                const newHeight = scrollContainer.value.scrollHeight;
                scrollContainer.value.scrollTop = newHeight - previousHeight;
            });

            if (!response.data.messages.next_page_url) {
                hasMoreMessages.value = false;
            }
        } catch (e) {
            console.error('Fehler beim Nachladen von Nachrichten:', e);
        }

        isLoadingMore.value = false;
    }
};
const markMessagesAsRead = async (messages) => {
    const unreadIds = messages
        .filter(msg =>
            msg.sender_id !== usePage().props.auth.user.id &&
            !msg.reads?.some(r => r.user_id === usePage().props.auth.user.id)
        )
        .map(m => m.id);

    if (!unreadIds.length) return;

    try {
        await axios.post(route('chat-system.mark-multiple-as-read'), {
            message_ids: unreadIds,
        });
    } catch (error) {
        console.error('Fehler beim Mehrfach-Gelesen markieren:', error);
    }
};


const toastMessages = ref([])

const showToast = async (message, chat) => {
    const isOwnMessage = message.sender_id === usePage().props.auth.user.id
    const cipher = isOwnMessage
        ? message.cipher_for_sender
        : message.ciphers_json?.[usePage().props.auth.user.id]

    let decrypted = '[nicht verfügbar]'

    if (cipher && hasKeypair.value) {
        try {
            decrypted = await decrypt(cipher)
        } catch {
            decrypted = '[nicht entschlüsselbar]'
        }
    }

    const shortened = decrypted.length > 80
        ? decrypted.slice(0, 77) + '...'
        : decrypted

    toastMessages.value.push({
        avatar: chat.users.find(u => u.id === message.sender_id)?.profile_photo_url,
        name: chat.users.find(u => u.id === message.sender_id)?.full_name,
        message: shortened,
        chatId: chat.id,
    })

    setTimeout(() => {
        toastMessages.value.shift()
    }, 5500)
}

const currentUserId = usePage().props.auth.user.id
const status = ref(null)
// Wenn sich chatPartner ändert, Status neu laden
watch(
    () => chatPartner.value,
    async (newVal) => {
        if (newVal && newVal.users) {
            const partner = newVal.users.find(user => user.id !== currentUserId)
            if (partner) {
                status.value = await getUserStatus(partner.id)
            } else {
                status.value = null
            }
        } else {
            status.value = null
        }
    },
    { immediate: true, deep: true } // auch beim ersten Laden ausführen
)

const filteredChats = computed(() => {
    if (!searchChat.value) return chats.value;

    const searchTerm = searchChat.value.toLowerCase();
    const currentUserId = usePage().props.auth?.user?.id;

    return chats.value.filter(chat => {
        if (chat.is_group) {
            // Suche im Gruppennamen oder in den Namen der Gruppennutzer
            const groupNameMatches = chat.name.toLowerCase().includes(searchTerm);
            const groupUserMatches = chat.users.some(user =>
                user.id !== currentUserId && user.full_name.toLowerCase().includes(searchTerm)
            );
            return groupNameMatches || groupUserMatches;
        } else {
            // Privater Chat: suche im Namen des anderen Nutzers
            const otherUser = chat.users.find(user => user.id !== currentUserId);
            return otherUser?.full_name.toLowerCase().includes(searchTerm);
        }
    });
});



// broadcast
</script>

<style scoped>

</style>