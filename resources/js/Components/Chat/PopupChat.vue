<template>
    <!-- Toasts bleiben global -->
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-50">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <ChatToastNotification
                v-for="(toast, index) in toastMessages"
                :key="index"
                :avatar="toast.avatar"
                :name="toast.name"
                :message="toast.message"
                :time="toast.time"
                @click="() => openChatPage(toast.chatId)"
            />
        </div>
    </div>

    <!-- Chat: fixed, aber innerhalb der Main-Grenzen offsetten -->
    <div :class="positionClass" :style="positionStyle" ref="chatRoot" data-chat-root>
        <div v-if="!isChatOpen">
            <div class="rounded-full shadow-lg bg-white p-2 cursor-pointer border border-gray-200 relative" @click="openChat">
                <component is="IconBubbleText" class="size-10" />

                <span v-if="totalUnreadCount > 0" class="absolute inline-flex items-center w-6 h-6 -top-2 -right-2">
                    <!--<span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>-->
                    <span class="relative inline-flex items-center justify-center w-6 h-6 text-xs font-bold leading-none text-red-700 border-red-100 border bg-red-50 rounded-full">
                        {{ totalUnreadCount }}
                    </span>
                </span>
            </div>
        </div>

        <div v-if="isChatOpen">
            <div class="w-96">
                <div class="card white overflow-hidden">
                    <div class="w-full px-4 py-3">
                        <div class="flex items-center justify-between w-full">
                            <div class="text-sm font-bold">
                                {{ $t('Chat') }}
                            </div>
                            <div class="flex items-center gap-x-2">
                                <!-- Add new Chat Button -->
                                <div v-if="!checkIfChatIsSelected" @click="openAddNewChat = true">
                                    <ToolTipComponent
                                        direction="top"
                                        icon="IconPlus"
                                        :tooltip-text="$t('Add new Chat')"
                                        class="cursor-pointer p-1 hover:bg-gray-100 rounded transition-colors duration-200"
                                        icon-class="size-5"
                                    />
                                </div>

                                <!-- Change Chat position Button -->
                                <div @click="showPositionPicker = true">
                                    <ToolTipComponent
                                        direction="top"
                                        icon="IconArrowsMove"
                                        :tooltip-text="$t('Change Chat position')"
                                        class="cursor-pointer p-1 hover:bg-gray-100 rounded transition-colors duration-200"
                                        icon-class="size-5"
                                    />
                                </div>

                                <div class="" @click="closeChat">
                                    <component is="IconX" class="size-5 cursor-pointer transition-colors duration-200 ease-in-out" aria-hidden="true" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-100/60 min-h-[600px]">
                        <div class="">
                            <div v-if="!checkIfChatIsSelected" class="px-3 py-1">
                                <div class="grid grid-cols-1">
                                    <input v-model="searchChat" type="text" name="account-number" id="account-number" class="col-start-1 border border-gray-200 row-start-1 block w-full rounded-md bg-white py-3 pr-10 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:pr-9 sm:text-sm/6" :placeholder="$t('Search in chats')" />
                                    <component is="IconSearch" class="pointer-events-none col-start-1 row-start-1 mr-3 size-5 self-center justify-self-end text-gray-400 sm:size-4" aria-hidden="true" />
                                </div>
                            </div>
                            <div v-else class="bg-white px-4">
                                <div v-if="!chatPartner.is_group" class="py-2">
                                    <div class="flex items-center">
                                        <div class="mr-1 cursor-pointer" @click="goBackToChatList">
                                            <ToolTipComponent
                                                direction="top"
                                                icon="IconChevronLeft"
                                                :tooltip-text="$t('Back to Chat List')"
                                            />
                                        </div>
                                        <span class="relative inline-block">
                                            <img class="size-9 rounded-full object-cover" :src="chatPartner.users.find(user => user.id !== usePage().props.auth.user.id)?.profile_photo_url" alt="" />
                                            <span class="absolute right-0.5 bottom-0.5 block size-2 rounded-full  ring-2 ring-white" :class="{ 'bg-red-400': status === 'offline', 'bg-orange-400': status === 'away', 'bg-green-400': status === 'online'}" />
                                        </span>
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-900">{{ chatPartner.users.find(user => user.id !== usePage().props.auth.user.id)?.full_name }}</p>
                                            <p class="text-xs text-gray-500 first-letter:capitalize">{{ status ? $t(status) : '' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="py-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="mr-1 cursor-pointer" @click="goBackToChatList">
                                                <ToolTipComponent
                                                    direction="top"
                                                    icon="IconChevronLeft"
                                                    :tooltip-text="$t('Back to Chat List')"
                                                    use-translation
                                                />
                                            </div>
                                            <component is="IconUsersGroup" class="size-10 rounded-full object-cover p-3 bg-blue-50 text-blue-500 border border-blue-100" />
                                            <div class="ml-3">
                                                <p class="text-sm text-gray-900">{{ chatPartner.name }}</p>
                                                <p class="text-[9px] text-gray-500">{{ chatPartner.users.map((user) => user.full_name).join(', ') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-x-1">
                                            <!-- Rename Group Chat Button -->
                                            <div @click="showRenameModal = true">
                                                <ToolTipComponent
                                                    direction="top"
                                                    icon="IconEdit"
                                                    :tooltip-text="$t('Rename Group Chat')"
                                                    class="cursor-pointer p-1 hover:bg-gray-100 rounded transition-colors duration-200 chat-tooltip-black-icon"
                                                    icon-size="size-4"
                                                />
                                            </div>
                                            <!-- Delete Group Chat Button -->
                                            <div @click="showDeleteConfirm = true">
                                                <ToolTipComponent
                                                    direction="top"
                                                    icon="IconTrash"
                                                    :tooltip-text="$t('Delete Group Chat')"
                                                    class="cursor-pointer p-1 hover:bg-gray-100 rounded transition-colors duration-200 chat-tooltip-black-icon"
                                                    icon-size="size-4"
                                                />
                                            </div>
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
                        <div v-else class="px-3">
                            <div
                                class="rounded-b-lg max-h-[600px] overflow-y-auto max-w-96"
                                ref="scrollContainer"
                                :key="chatPartner?.id || 'chat-list'"
                                @scroll="handleScroll"
                            >
                                <div  v-if="!checkIfChatIsSelected">
                                    <div v-if="filteredChats.length > 0" class="space-y-1">
                                        <div class="bg-white hover:bg-blue-50 rounded-lg px-2 py-2 transition duration-200 ease-in-out" v-for="chat in filteredChats">
                                            <div class="w-full flex items-center justify-between cursor-pointer" @click="openChatPage(chat.id)">
                                                <div>
                                                    <SingleChatInOverview :chat="chat" />
                                                </div>
                                                <span v-if="chat.unread_count > 0" class="inline-flex items-center justify-center w-6 h-6 min-w-6 min-h-6 text-xs font-bold leading-none text-red-700 border-red-100 border  bg-red-50 font-lexend   rounded-full">
                                                    {{ chat.unread_count }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="px-4">
                                        <div class="rounded-md bg-red-50 p-4 mb-5">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <component is="IconInfoSquareRoundedFilled" class="size-6 text-red-400" aria-hidden="true" />
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-xs font-lexend font-medium text-red-800">
                                                        {{ $t("No chats found. Click the plus icon to start a new chat.") }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-3 py-2" v-else>
                                    <div>
                                        <!-- Ladeindikator oben beim Nachladen -->
                                        <div v-if="isLoadingMore" class="flex justify-center py-2 text-xs text-gray-500">
                                            <svg class="animate-spin h-4 w-4 mr-2 text-gray-400" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z" />
                                            </svg>
                                            {{ $t('Messages are loading...') }}
                                        </div>

                                        <!-- Nachrichten + Datumstrenner -->
                                        <template v-for="item in messagesWithSeparators" :key="item.key">
                                            <div v-if="item.type === 'date'" class="flex items-center my-3">
                                                <div class="flex-1 h-px bg-gray-200"></div>
                                                <span class="mx-3 text-[10px] text-gray-500 whitespace-nowrap">{{ item.label }}</span>
                                                <div class="flex-1 h-px bg-gray-200"></div>
                                            </div>
                                            <SingleMessageInChat
                                                v-else
                                                :message="item.message"
                                                :chat="chatPartner"
                                            />
                                        </template>
                                        <!-- Scroll-Anker am Ende -->
                                        <div ref="scrollAnchor" style="height: 1px;"></div>
                                    </div>
                                </div>

                            </div>


                        </div>

                    </div>
                    <!-- send message in chat -->
                    <div v-if="checkIfChatIsSelected" class="rounded-b-lg px-3 py-4 bg-white">
                        <div class="flex items-center gap-2">
                            <textarea
                                class="w-full border resize-none border-gray-200 rounded-xl bg-white py-1.5 pr-10 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline-1 focus:-outline-offset-1 focus:outline-artwork-buttons-create sm:pr-9 sm:text-xs font-lexend"
                                :placeholder="$t('Write a message')"
                                v-model="newMessage"
                                rows="1"
                                @keydown.enter.exact.prevent="sendMessage"
                                @keyup.enter.exact="scrollToBottom"
                            />
                            <div @click="sendMessage" class="rounded-full p-2 bg-artwork-buttons-create cursor-pointer hover:bg-artwork-buttons-hover duration-200 ease-in-out transition-colors">
                                <component is="IconBrandTelegram" class="size-4 cursor-pointer text-white" aria-hidden="true" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay bleibt relativ zum Main (absolute + inset-0) -->
    <div v-if="showPositionPicker" class="fixed inset-0 z-[100] pointer-events-auto">
        <div class="w-full h-full">
            <!-- halbtransparenter Hintergrund -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm">

                <div class="flex items-center justify-center h-full w-fill ">
                    <div class="text-center">
                        <div class="text-white text-lg font-bold">{{ $t('Set chat position') }}</div>
                        <p class="text-white/90 text-sm mt-1">
                            {{ $t('Click on one of the marked fields (corners, sides, or center) to select the position of the chat window. You can change this later at any time.') }}
                        </p>
                    </div>
                </div>

            </div>
            <!-- vier Targets -->
            <button
                class="absolute top-4 left-4 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Top left')"
                @click="setChatPosition('top-left')"
            />
            <button
                class="absolute top-4 right-4 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Top right')"
                @click="setChatPosition('top-right')"
            />
            <button
                class="absolute bottom-4 left-4 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Bottom left')"
                @click="setChatPosition('bottom-left')"
            />
            <button
                class="absolute bottom-4 right-4 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Bottom right')"
                @click="setChatPosition('bottom-right')"
            />
            <!-- NEU: Mitte links -->
            <button
                class="absolute top-1/2 left-4 -translate-y-1/2 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Middle left')"
                @click="setChatPosition('middle-left')"
            />
            <!-- NEU: Mitte rechts -->
            <button
                class="absolute top-1/2 right-4 -translate-y-1/2 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Middle right')"
                @click="setChatPosition('middle-right')"
            />
            <!-- NEU: Oben zentriert -->
            <button
                class="absolute top-4 left-1/2 -translate-x-1/2 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Top center')"
                @click="setChatPosition('top-center')"
            />
            <!-- NEU: Unten zentriert -->
            <button
                class="absolute bottom-4 left-1/2 -translate-x-1/2 h-20 w-28 rounded-lg border-2 border-dashed border-white/70 bg-white/20 hover:bg-white/30 focus:outline-none"
                :title="$t('Bottom center')"
                @click="setChatPosition('bottom-center')"
            />
            <!-- Schließen -->
            <div class="absolute top-2 right-2">
                <component is="IconSquareRoundedX" class="size-6 text-white cursor-pointer" @click="showPositionPicker = false" />
            </div>
        </div>
    </div>

    <!-- Rename Group Chat Modal -->
    <RenameGroupChatModal
        v-if="showRenameModal"
        :chat="chatPartner"
        @close="showRenameModal = false"
        @renamed="handleChatRenamed"
    />

    <!-- Delete Group Chat Confirmation Modal -->
    <DeleteGroupChatModal
        v-if="showDeleteConfirm"
        :chat="chatPartner"
        @close="showDeleteConfirm = false"
        @deleted="handleChatDeleted"
    />

    <AddNewChat
        v-if="openAddNewChat"
        @close="closeAddNewChat"
    />
</template>

<script setup>
import {computed, nextTick, onBeforeUnmount, onMounted, ref, watch} from "vue";
import AddNewChat from "@/Components/Chat/AddNewChat.vue";
import {router, usePage} from "@inertiajs/vue3";
import SingleChatInOverview from "@/Components/Chat/Components/SingleChatInOverview.vue";
import SingleMessageInChat from "@/Components/Chat/Components/SingleMessageInChat.vue";
import ChatToastNotification from "@/Components/Chat/Components/ChatToastNotification.vue";
import {useUserStatus} from "@/Composeables/useUserStatus.js";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import RenameGroupChatModal from "@/Components/Chat/Modals/RenameGroupChatModal.vue";
import DeleteGroupChatModal from "@/Components/Chat/Modals/DeleteGroupChatModal.vue";
const { getUserStatus } = useUserStatus()

const props = defineProps({})
const chats = ref([])
const openAddNewChat = ref(false);
const isLoading = ref(true);
const isChatOpen = ref(false)
const chatPartner = ref([])
const newMessage = ref('')
const scrollContainer = ref(null);
// NEU: Root-Ref deklarieren (wird im Template genutzt)
const chatRoot = ref(null);
const paginationPage = ref(1);
const hasMoreMessages = ref(true);
const isLoadingMore = ref(false);
const initialScrollDone = ref(false);
const openShowChatInfo = ref(false);
const searchChat = ref('')
const currentUserId = usePage().props.auth.user.id
const status = ref(null)
const userIdForStatus = ref(null)
const isAutoScrolling = ref(false);
const scrollAnchor = ref(null);
const showRenameModal = ref(false);
const showDeleteConfirm = ref(false);
// Wenn sich chatPartner  ändert, Status neu laden
const closeChat = () => {
    isChatOpen.value = false;
    // Prüfe, ob ein Chat ausgewählt war
    if (chatPartner.value && chatPartner.value.id) {
        // Finde den Chat in der Übersicht
        const target = chats.value.find(c => c.id === chatPartner.value.id);
        if (target) {
            // Prüfe, ob alle Nachrichten gelesen sind
            const unread = chatPartner.value.messages?.filter(msg =>
                msg.sender_id !== currentUserId &&
                !msg.reads?.some(r => r.user_id === currentUserId)
            );
            if (!unread || unread.length === 0) {
                target.unread_count = 0;
            }
        }
    }
    chatPartner.value = null;
}

const closeAddNewChat = (chatId) => {
    isLoading.value = false
    openAddNewChat.value = false
    if (chatId) {
        openChatPage(chatId)
    } else {
        openChat()
    }
    scrollToBottom(true);
}

// Simple and reliable scroll-to-bottom function
const scrollToBottom = async (force = false) => {
    await nextTick();

    const container = scrollContainer.value;
    if (!container) return;

    // Simple scroll to bottom - most reliable approach
    container.scrollTop = container.scrollHeight;
};

const isNearBottom = () => {
    const c = scrollContainer.value;
    if (!c) return false;
    return (c.scrollHeight - c.scrollTop - c.clientHeight) < 48;
};

// Bei neuen Nachrichten nur folgen, wenn der User ohnehin unten ist
watch(
    () => chatPartner.value?.messages?.length,
    (newLength, oldLength) => {
        if (!oldLength) return; // initialer Load → handled in openChatPage
        if (isNearBottom()) {
            scrollToBottom();
        }
    }
);


// Optional: direkter Force-Scroll (ohne nextTick)
const forceScrollToBottom = (instant = false) => {
    const c = scrollContainer.value;
    if (!c) return;
    c.scrollTo({ top: c.scrollHeight, behavior: instant ? 'auto' : 'smooth' });
};


const openChat = async () => {
    // generateKeypair(); // entfernt

    try {
        const response = await axios.get(route('chat-system.get-chats'))
        chats.value = response.data.chats
        // Neu: sicherheitshalber sortieren (neueste oben)
        resortChats()
        isChatOpen.value = true
        isLoading.value = false
    } catch (error) {
        console.error("Fehler beim Laden der Chats:", error)
    }
}

const openChatPage = async (chatId) => {
    if (!isChatOpen.value) {
        isChatOpen.value = true;
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
        // von alt→neu sortieren
        chatPartner.value.messages = response.data.messages.data.reverse();

        // einmalig Reads markieren
        await markMessagesAsRead(chatPartner.value.messages);

        isLoading.value = false;

        // WICHTIG: Warte bis DOM aktualisiert ist, dann scrolle
        await nextTick();
        await scrollToBottom(true);
        initialScrollDone.value = true;

        // Unread Badge in der Übersicht zurücksetzen
        const target = chats.value.find(c => c.id === chatId);
        if (target) target.unread_count = 0;

    } catch (error) {
        console.error("Fehler beim Laden der Chat-Nachrichten:", error);
        isLoading.value = false;
    }
};

const sendMessage = async () => {
    const plainText = newMessage.value?.trim();
    if (!plainText) return;

    const payload = {
        chat_id: chatPartner.value.id,
        message: plainText,
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

        chatPartner.value.messages.push({
            ...result.message,
            reads: [],
        });

        // Chat-Preview in der Übersicht aktualisieren
        const target = chats.value.find(c => c.id === chatPartner.value.id);
        if (target) {
            target.last_message = result.message;
            moveChatToTop(target.id);
        }

        // Nach dem Senden immer nach unten scrollen
        await scrollToBottom(true);

    } catch (e) {
        console.error("Fehler beim Senden der Nachricht:", e);
    } finally {
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
        // NEU: beim Initialisieren sortieren
        resortChats();

        // ALT: Direkt hier Listener definieren
        // NEU: für jeden Chat registrieren
        chats.value.forEach(chat => {
            registerChatListeners(chat);
        });

        // Neuer Chat kommt rein
        window.Echo.private(`user.${userId}`)
            .listen('.chat.created', (e) => {
                if (!chats.value.some(c => c.id === e.chat.id)) {
                    // NEU: oben einfügen
                    chats.value.unshift(e.chat);
                    registerChatListeners(e.chat);
                } else {
                    // Falls bereits vorhanden, nach oben schieben
                    moveChatToTop(e.chat.id);
                }
            });
    });

    window.Echo.channel('users.status')
        .listen('UserStatusUpdated', (data) => {

            const userId = data?.userId;
            const newStatus = data?.status;

            if (userId === userIdForStatus.value) {
                // Wenn der Status des Chat-Partners aktualisiert wird
                status.value = newStatus;
            }
        })
});


onBeforeUnmount(() => {
    chats.value.forEach(chat => {
        window.Echo.leave(`chat.${chat.id}`);
    });

    window.Echo.leave(`user.${usePage().props.auth?.user?.id}`);
});

const handleScroll = async () => {
    // Verhindere Scroll-Handler während Auto-Scroll
    if (isAutoScrolling.value) return;

    if (
        !initialScrollDone.value ||
        !scrollContainer.value ||
        isLoadingMore.value ||
        !hasMoreMessages.value
    ) return;

    if (!chatPartner.value || !chatPartner.value.id) return;

    // Nur wenn der User manuell hochscrollt
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

            await nextTick();

            const newHeight = scrollContainer.value.scrollHeight;
            scrollContainer.value.scrollTop = newHeight - previousHeight;

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

const showToast = (message, chat) => {
    // Direkt Plaintext ohne Entschlüsselung
    const text = message.message
    const shortened = text.length > 80 ? text.slice(0, 77) + '...' : text;

    // Zeit HH:mm aus created_at_iso/created_at ableiten (Fallback: jetzt)
    let time = ''
    try {
        const src = message.created_at_iso || message.created_at
        const d = src ? new Date(src) : new Date()
        if (!Number.isNaN(d.getTime())) {
            time = new Intl.DateTimeFormat('de-DE', { hour: '2-digit', minute: '2-digit' }).format(d)
        }
    } catch (e) {
        time = ''
    }

    toastMessages.value.push({
        avatar: chat.users.find(u => u.id === message.sender_id)?.profile_photo_url,
        name: chat.users.find(u => u.id === message.sender_id)?.full_name,
        message: shortened,
        chatId: chat.id,
        time,
    });

    setTimeout(() => {
        toastMessages.value.shift()
    }, 5500)
}


watch(
    () => chatPartner.value,
    async (newVal) => {
        if (newVal && newVal.users) {
            const partner = newVal.users.find(user => user.id !== currentUserId)
            if (partner) {
                status.value = await getUserStatus(partner.id)
                userIdForStatus.value = partner.id
            } else {
                status.value = null
                userIdForStatus.value = null
            }
        } else {
            status.value = null
            userIdForStatus.value = null
        }
    },
    { immediate: true, deep: true } // auch beim ersten Laden ausführen
)

// Watcher: Immer nach Chat-Wechsel nach unten scrollen
watch(
    () => chatPartner.value?.id,
    async (newId, oldId) => {
        if (newId) {
            await nextTick();
            scrollToBottom(); // Sofort nach unten scrollen
            initialScrollDone.value = true;
        }
    }
);

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



// Position speichern/auswählen
const chatPosition = ref(usePage().props?.auth?.user?.chat_popup_position ?? 'bottom-right');
const showPositionPicker = ref(false);

// FIXED + dynamische Offsets
const positionClass = computed(() => {
    // Nur fixed + z-Index; Offsets kommen aus positionStyle
    return 'z-[100] fixed';
});

const SIDE_OFFSET = 20; // entspricht top-5/right-5/...
const mainRect = ref({ left: 0, right: window.innerWidth, width: window.innerWidth });

let ro = null;
const updateMainRect = () => {
    const el = document.getElementById('main-content-wrapper');
    if (el) {
        const rect = el.getBoundingClientRect();
        mainRect.value = { left: rect.left, right: rect.right, width: rect.width };
    } else {
        mainRect.value = { left: 0, right: window.innerWidth, width: window.innerWidth };
    }
};

const positionStyle = computed(() => {
    const gap = `${SIDE_OFFSET}px`;
    const leftPx = (px) => `${px}px`;
    const rightInset = (px) => `${px}px`;
    const rightGap = window.innerWidth - mainRect.value.right + SIDE_OFFSET;
    const leftGap = mainRect.value.left + SIDE_OFFSET;

    switch (chatPosition.value) {
        case 'top-left':
            return { top: gap, left: leftPx(leftGap) };
        case 'top-right':
            return { top: gap, right: rightInset(rightGap) };
        case 'bottom-left':
            return { bottom: gap, left: leftPx(leftGap) };
        case 'bottom-right':
            return { bottom: gap, right: rightInset(rightGap) };
        case 'middle-left':
            return { top: '50%', left: leftPx(leftGap), transform: 'translateY(-50%)' };
        case 'middle-right':
            return { top: '50%', right: rightInset(rightGap), transform: 'translateY(-50%)' };
        case 'top-center': {
            const center = mainRect.value.left + mainRect.value.width / 2;
            return { top: gap, left: leftPx(center), transform: 'translateX(-50%)' };
        }
        case 'bottom-center': {
            const center = mainRect.value.left + mainRect.value.width / 2;
            return { bottom: gap, left: leftPx(center), transform: 'translateX(-50%)' };
        }
        default:
            return { bottom: gap, right: rightInset(rightGap) };
    }
});

onMounted(() => {
    updateMainRect();
    window.addEventListener('resize', updateMainRect);
    // NEU: bei Scroll ebenfalls aktualisieren (auch in verschachtelten Scrollern)
    window.addEventListener('scroll', updateMainRect, true);

    const el = document.getElementById('main-content-wrapper');
    if (el && 'ResizeObserver' in window) {
        ro = new ResizeObserver(() => updateMainRect());
        ro.observe(el);
    }

    document.addEventListener('mousedown', onGlobalPointerDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateMainRect);
    window.removeEventListener('scroll', updateMainRect, true);
    if (ro) ro.disconnect();

    document.removeEventListener('mousedown', onGlobalPointerDown);
});

// Outside-Click Handler
const onGlobalPointerDown = (e) => {
    // Nur schließen, wenn Chat offen und KEIN Positions-Picker aktiv
    if (!isChatOpen.value || showPositionPicker.value) return;

    const root = chatRoot.value;
    if (!root) return;

    // 1) Klick in Teleport/Portal, der zum Chat gehört, optional ignorieren:
    //    Falls du Teleports nutzt, gib deren Root z.B. data-chat-portal mit.
    const target = e.target;
    if (target?.closest?.('[data-chat-portal]')) return;

    // 2) Robust gegen Shadow DOM / Teleports: composedPath nutzen
    const path = typeof e.composedPath === 'function' ? e.composedPath() : [];
    const clickedInside =
        (path.length && path.includes(root)) || root.contains(target);

    if (clickedInside) return;

    // → außerhalb geklickt
    //closeChat();
};

const setChatPosition = async (pos) => {
    chatPosition.value = pos;
    showPositionPicker.value = false;
    try {
        await axios.patch(route('user.chat.popup-settings', { user: usePage().props.auth.user.id }), {
            chat_popup_position: pos,
        });
    } catch (e) {
        console.error('Fehler beim Speichern der Chat-Position:', e);
    }
};

// NEU: Listener-Registrierung für einen Chat
const registerChatListeners = (chat) => {
    window.Echo.private(`chat.${chat.id}`)
        .listen('.new.message', async (e) => {
            const target = chats.value.find(c => c.id === chat.id);
            if (target) {
                target.unread_count = (target.unread_count || 0) + 1;
                target.last_message = e.message;
                moveChatToTop(chat.id);
            }

            if (e.message.sender_id === usePage().props.auth.user.id) return;

            if (isChatOpen.value && checkIfChatIsSelected.value && chatPartner.value.id === chat.id) {
                const alreadyExists = chatPartner.value.messages.some(m => m.id === e.message.id);
                if (!alreadyExists) {
                    // Prüfe Position VOR dem Hinzufügen
                    const container = scrollContainer.value;
                    const wasNearBottom = container &&
                        (container.scrollHeight - container.scrollTop - container.clientHeight) < 100;

                    chatPartner.value.messages.push(e.message);
                    chatPartner.value.messages.sort((a, b) => {
                        const da = new Date(a.created_at_iso || a.created_at);
                        const db = new Date(b.created_at_iso || b.created_at);
                        return da.getTime() - db.getTime();
                    });

                    // Nur scrollen wenn wir vorher schon unten waren
                    if (wasNearBottom) {
                        await nextTick();
                        await scrollToBottom();
                    }

                    await markMessagesAsRead([e.message]);
                }
            }

            if (e.message.sender_id !== usePage().props.auth.user.id) {
                const targetChat = chats.value.find(c => c.id === chat.id);
                showToast(e.message, targetChat);
            }
        });

    window.Echo.private(`chat.${chat.id}`)
        .listen('.message.read', (e) => {
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
};

// Hilfsfunktionen für Datumstrenner
const toDateKey = (isoOrStr) => {
    const d = new Date(isoOrStr);
    if (Number.isNaN(d.getTime())) return '';
    // YYYY-MM-DD
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

const dateHeaderLabel = (isoOrStr) => {
    const d = new Date(isoOrStr);
    try {
        const weekday = new Intl.DateTimeFormat('de-DE', { weekday: 'long' }).format(d);
        const dateStr = new Intl.DateTimeFormat('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(d);
        return `${weekday} ${dateStr}`;
    } catch {
        return isoOrStr; // Fallback
    }
};

// Nachrichten inkl. Datumstrenner
const messagesWithSeparators = computed(() => {
    if (!chatPartner.value?.messages?.length) return [];

    const list = [];
    let lastKey = null;

    // Annahme: messages sind bereits zeitlich aufsteigend
    for (const msg of chatPartner.value.messages) {
        const src = msg.created_at_iso || msg.created_at;
        const key = toDateKey(src);

        if (key && key !== lastKey) {
            list.push({
                type: 'date',
                key: `date-${key}`,
                label: dateHeaderLabel(src),
            });
            lastKey = key;
        }

        list.push({
            type: 'message',
            key: `msg-${msg.id}`,
            message: msg,
        });
    }

    return list;
});

// NEU: Sortier-/Move-Helfer
const chatSortKey = (chat) => {
    const src = chat?.last_message?.created_at_iso
        || chat?.last_message?.created_at
        || chat?.updated_at;
    const d = src ? new Date(src) : new Date(0);
    return d.getTime();
};

const resortChats = () => {
    chats.value.sort((a, b) => chatSortKey(b) - chatSortKey(a));
};

const moveChatToTop = (chatId) => {
    const idx = chats.value.findIndex(c => c.id === chatId);
    if (idx > 0) {
        const [item] = chats.value.splice(idx, 1);
        chats.value.unshift(item);
    }
};

// Event handlers for modal components
const handleChatRenamed = (updatedChat) => {
    // Update the current chat partner name
    chatPartner.value.name = updatedChat.name;

    // Update the name in the chats list
    const chatInList = chats.value.find(c => c.id === chatPartner.value.id);
    if (chatInList) {
        chatInList.name = updatedChat.name;
    }
};

const handleChatDeleted = (chatId) => {
    // Remove chat from chats list
    chats.value = chats.value.filter(c => c.id !== chatId);

    // Go back to chat list
    chatPartner.value = null;
};




</script>

<style scoped>
/* ...existing code... */
</style>
