import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import CryptoHelper from '../Helper/cryptoHelper'

export default function useCrypto() {
  const userId = usePage().props.auth.user?.id
  const publicKey = ref(null)
  const privateKey = ref(null)
  const hasKeypair = ref(false)

  const loadKeypair = () => {
    const stored = CryptoHelper.getStoredKeysFor(userId)
    publicKey.value = stored.publicKey
    privateKey.value = stored.privateKey
    hasKeypair.value = !!stored.publicKey && !!stored.privateKey
  }

  const generateKeypair = async () => {
    if (hasKeypair.value /*&& !confirm('Existierendes Keypair überschreiben?')*/) return

    const keys = await CryptoHelper.generateKeyPair()
    CryptoHelper.saveKeypairFor(userId, keys)
    publicKey.value = keys.publicKey
    privateKey.value = keys.privateKey
    hasKeypair.value = true

    // Public Key an Server senden
    await fetch(route('keypair.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ public_key: keys.publicKey }),
    })
  }

  const encrypt = async (message, remotePublicKey) =>
      await CryptoHelper.encryptMessage(message, remotePublicKey)

  const decrypt = async (cipher) =>
      await CryptoHelper.decryptMessage(cipher, privateKey.value)

  const clearKeys = () => {
    CryptoHelper.clearStoredKeypairFor(userId)
    publicKey.value = null
    privateKey.value = null
    hasKeypair.value = false
  }

  // Auto-Load bei Verwendung
  if (userId) loadKeypair()

  return {
    userId,
    publicKey,
    privateKey,
    hasKeypair,
    generateKeypair,
    encrypt,
    decrypt,
    clearKeys,
    loadKeypair,
  }
}
