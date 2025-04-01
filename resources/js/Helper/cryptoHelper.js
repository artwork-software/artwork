const CryptoHelper = {
  async generateKeyPair() {
    const keyPair = await window.crypto.subtle.generateKey(
        {
          name: 'RSA-OAEP',
          modulusLength: 4096,
          publicExponent: new Uint8Array([1, 0, 1]),
          hash: 'SHA-256',
        },
        true,
        ['encrypt', 'decrypt']
    )

    const publicKey = await crypto.subtle.exportKey('spki', keyPair.publicKey)
    const privateKey = await crypto.subtle.exportKey('pkcs8', keyPair.privateKey)

    const pub = this.arrayBufferToBase64(publicKey)
    const priv = this.arrayBufferToBase64(privateKey)

    return { publicKey: pub, privateKey: priv }
  },

  async encryptMessage(message, base64PublicKey) {
    const publicKey = await this.importPublicKey(base64PublicKey)
    const encoded = new TextEncoder().encode(message)
    const encrypted = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, publicKey, encoded)
    return this.arrayBufferToBase64(encrypted)
  },

  async decryptMessage(base64Encrypted, base64PrivateKey) {
    const privateKey = await this.importPrivateKey(base64PrivateKey)
    const encryptedBytes = this.base64ToArrayBuffer(base64Encrypted)
    const decrypted = await crypto.subtle.decrypt({ name: 'RSA-OAEP' }, privateKey, encryptedBytes)
    return new TextDecoder().decode(decrypted)
  },

  async importPublicKey(base64Key) {
    const binary = this.base64ToArrayBuffer(base64Key)
    return await crypto.subtle.importKey(
        'spki',
        binary,
        { name: 'RSA-OAEP', hash: 'SHA-256' },
        false,
        ['encrypt']
    )
  },

  async importPrivateKey(base64Key) {
    const binary = this.base64ToArrayBuffer(base64Key)
    return await crypto.subtle.importKey(
        'pkcs8',
        binary,
        { name: 'RSA-OAEP', hash: 'SHA-256' },
        false,
        ['decrypt']
    )
  },

  getStoredKeysFor(userId) {
    const raw = localStorage.getItem(`chat_keypair_user_${userId}`)
    return raw ? JSON.parse(raw) : { publicKey: null, privateKey: null }
  },

  saveKeypairFor(userId, keypair) {
    localStorage.setItem(`chat_keypair_user_${userId}`, JSON.stringify(keypair))
  },

  clearStoredKeypairFor(userId) {
    localStorage.removeItem(`chat_keypair_user_${userId}`)
  },

  arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer)
    return btoa(String.fromCharCode(...bytes))
  },

  base64ToArrayBuffer(base64) {
    const binary = atob(base64)
    const bytes = new Uint8Array(binary.length)
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i)
    }
    return bytes.buffer
  }
}

export default CryptoHelper
