import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import CryptoHelper from '../Helper/cryptoHelper'

export default function useCrypto() {
  const userId = usePage().props.auth.user.id
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

    if (!hasKeypair.value) {
      alert('No keypair found. Please generate a keypair first.')
      return
    }

    // show alert if click on button download keypair
    if (confirm('Möchtest du dein Keypair herunterladen? \n\n Mit dem Keypair kannst du Nachrichten verschlüsseln und entschlüsseln. \n\n Bitte bewahre es sicher auf!')) {
      downloadKeypair()
    }
  }

  const setKeypairByUploadBackup = async (keypair) => {
    if (keypair && keypair.publicKey && keypair.privateKey) {
      publicKey.value = keypair.publicKey
      privateKey.value = keypair.privateKey
      hasKeypair.value = true

      CryptoHelper.saveKeypairFor(userId, keypair)

      await fetch(route('keypair.store'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ public_key: publicKey }),
      })
    } else {
      alert('Invalid keypair format.')
    }
  }

  const downloadKeypair = () => {
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
    setKeypairByUploadBackup,
  }
}
