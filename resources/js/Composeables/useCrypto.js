
import { ref } from 'vue'
import CryptoHelper from '../Helper/cryptoHelper'
import {router} from "@inertiajs/vue3";

const publicKey = ref(CryptoHelper.getStoredKeys().publicKey)
const privateKey = ref(CryptoHelper.getStoredKeys().privateKey)

export default function useCrypto() {
  const hasKeypair = ref(!!publicKey.value && !!privateKey.value)

  const generateKeypair = async () => {
    if (hasKeypair.value) return
    const keys = await CryptoHelper.generateKeyPair()
    publicKey.value = keys.publicKey
    privateKey.value = keys.privateKey
    hasKeypair.value = true

    await router.post(route('keypair.store'), {
          chat_public_key: keys.publicKey,
        },
        {
          onSuccess: () => {
              console.log('Public key set successfully')
          },
          onError: (error) => {
              console.error('Error setting public key:', error)
          },
    })

    /*await fetch('/api/user/set-public-key', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ public_key: keys.publicKey }),
    })*/
  }

  const encrypt = async (message, remotePublicKey) =>
    await CryptoHelper.encryptMessage(message, remotePublicKey)

  const decrypt = async (encryptedText) =>
    await CryptoHelper.decryptMessage(encryptedText, privateKey.value)

  /*const clearKeys = () => {
    CryptoHelper.clearStoredKeys()
    publicKey.value = null
    privateKey.value = null
    hasKeypair.value = false
  }*/

  return {
    publicKey,
    privateKey,
    hasKeypair,
    generateKeypair,
    encrypt,
    decrypt,
    //clearKeys,
  }
}
