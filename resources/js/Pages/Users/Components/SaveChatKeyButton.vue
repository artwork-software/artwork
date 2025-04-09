<template>
    <div class="mt-4 flex items-center justify-between">
        <BaseButton
            :text="$t('Save chat key')"
            @click="saveChatKey"
            :disabled="!usePage().props.auth.user.use_chat"
        />

        <!-- upload stored keypair form json  -->
        <form @submit.prevent="loadKeypair">
            <input type="file" id="keypairFile" accept=".json" @change="handleFileUpload" class="hidden" />
            <label for="keypairFile" class="cursor-pointer text-sm text-blue-500 hover:underline">
                {{ $t('Upload a Backup keypair') }}
            </label>
        </form>
    </div>
</template>

<script setup>
import useCrypto from "@/Composeables/useCrypto.js";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import {usePage} from "@inertiajs/vue3";
import {watch} from "vue";
const {
    publicKey,
    privateKey,
    hasKeypair,
    loadKeypair,
    setKeypairByUploadBackup
} = useCrypto()
const props = defineProps({})

const saveChatKey = async () => {
    if (!hasKeypair.value) {
        alert('No keypair found. Please generate a keypair first.')
        return
    }

    const keypair = {
        publicKey: publicKey.value,
        privateKey: privateKey.value,
    }

    const blob = new Blob([JSON.stringify(keypair, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = url
    link.download = `chat-keypair-user-${usePage().props.auth.user.id}-${usePage().props.auth.user.full_name}.json`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
}

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = async (e) => {
        try {
            const content = e.target.result;
            const keypair = JSON.parse(content);

            if (!keypair.publicKey || !keypair.privateKey) {
                alert('Invalid keypair format. Please upload a valid JSON file.');
                return;
            }

            // Passe hier deine eigene Methode zum Setzen der Schlüssel an
            setKeypairByUploadBackup(keypair);
        } catch (error) {
            alert('Error loading keypair: ' + error.message);
        }
    };

    reader.readAsText(file);
};

</script>

<style scoped>

</style>