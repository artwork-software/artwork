<template>
    <div class="fixed bottom-5 right-5 z-50">
        <div v-if="!isChatOpen">
            <div class="rounded-full shadow-lg bg-white p-2 cursor-pointer border border-gray-200" @click="openChat">
                <component is="IconBubbleText" class="size-8" />
            </div>
        </div>

        <div v-if="isChatOpen">
            <div class="shadow-lg bg-white rounded-lg w-96">
                <div class="bg-artwork-buttons-default px-4 py-4 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <div class="font-lexend text-white text-xs font-bold">
                            Artwork Chat <span v-if="chatPartner">mit {{ chatPartner}}</span>
                        </div>
                        <div class="flex items-center gap-x-3">
                            <component is="IconMessageCirclePlus" class="size-5 cursor-pointer text-white" @click="openAddNewChat = true" />
                            <component is="IconX" class="size-5 cursor-pointer text-white" @click="closeChat" />
                        </div>
                    </div>
                </div>
                <div class="bg-white px-3 py-2">
                    <div v-if="!chatPartner">
                        <div class="grid grid-cols-1">
                            <input type="text" name="account-number" id="account-number" class="col-start-1 border border-gray-200 row-start-1 block w-full rounded-md bg-white py-1.5 pr-10 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:pr-9 sm:text-sm/6" placeholder="Suche Chat Partner" />
                            <component is="IconSearch" class="pointer-events-none col-start-1 row-start-1 mr-3 size-5 self-center justify-self-end text-gray-400 sm:size-4" aria-hidden="true" />
                        </div>
                    </div>
                    <div v-else>
                        <div class="font-lexend text-xs font-medium flex items-center cursor-pointer" @click="chatPartner = null">
                            <component is="IconArrowLeft" class="size-3" aria-hidden="true" />
                            Zurück zur Chat-Übersicht
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-b-lg max-h-96 min-h-96 overflow-y-auto">
                    <div class="space-y-3" v-if="!chatPartner">
                        <div class="px-3 py-2 hover:bg-blue-50 transition duration-200 ease-in-out" v-for="chat in usePage().props.chats">
                            <SingleChatInOverview :chat="chat" @openChat="(chat) => {
                                chatPartner = chat
                            }" />
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
                            <div class="flex items-start gap-2.5 my-4" v-for="index in 10">
                                <img class="size-8 min-h-8 min-w-8 rounded-full object-cover" src="/storage/profile-photos/photo-1499996860823-5214fcc65f8f.jpg" alt="Jese image">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                        <span class="text-xs font-semibold font-lexend text-gray-900 dark:text-white">Max Mustermann</span>
                                        <!--<span class="text-xs font-lexend font-normal text-gray-500 dark:text-gray-400">11:46</span>-->
                                    </div>
                                    <div class="flex flex-col w-full leading-1.5 p-4 border-blue-200 bg-blue-100 rounded-e-xl rounded-es-xl text-blue-800">
                                        <span class="text-[9px] font-lexend">01.01.2025 11:00:</span>
                                        <p class="font-lexend text-xs">
                                            Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start justify-end gap-2.5 my-4" v-for="index in 10">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center justify-end space-x-2 rtl:space-x-reverse">
                                        <span class="text-xs font-semibold font-lexend text-gray-900 dark:text-white">Du</span>
                                        <!--<span class="text-xs font-lexend font-normal text-gray-500 dark:text-gray-400">11:46</span>-->
                                    </div>
                                    <div class="flex flex-col w-full leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-s-xl rounded-ee-xl text-gray-800">
                                        <span class="text-[9px] font-lexend">01.01.2025 11:00:</span>
                                        <p class="font-lexend text-xs">
                                            Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
                                        </p>
                                    </div>
                                </div>
                                <img class="size-8 min-h-8 min-w-8 rounded-full object-cover" src="/storage/profile-photos/photo-1499996860823-5214fcc65f8f.jpg" alt="Jese image">
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="chatPartner" class="bg-white rounded-b-lg px-3 py-4">
                    <div class="flex items-center gap-2">
                        <textarea
                            class="w-full border border-gray-200 rounded-md bg-white py-1.5 pr-10 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-100 placeholder:text-gray-400 focus:outline-1 focus:-outline-offset-1 focus:outline-artwork-buttons-create sm:pr-9 sm:text-xs font-lexend"
                            placeholder="Schreibe eine Nachricht"
                        />
                        <div class="rounded-full p-2 bg-artwork-buttons-create cursor-pointer hover:bg-artwork-buttons-hover duration-200 ease-in-out transition-colors">
                            <component is="IconBrandTelegram" class="size-6 cursor-pointer text-white" aria-hidden="true" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <AddNewChat
        v-if="openAddNewChat"
        @close="openAddNewChat = false"
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
import {ref} from "vue";
import AddNewChat from "@/Components/Chat/AddNewChat.vue";
import {router, usePage} from "@inertiajs/vue3";
import SingleChatInOverview from "@/Components/Chat/Components/SingleChatInOverview.vue";

const props = defineProps({})

const openAddNewChat = ref(false);

const isChatOpen = ref(false)
const chatPartner = ref(null)

const closeChat = () => {
    isChatOpen.value = false
    chatPartner.value = null
}

const openChat = () => {
    generateKeypair();

    router.reload({
        only: ['chats'],
        onSuccess: () => {
            isChatOpen.value = true
        }
    })


}
</script>

<style scoped>

</style>