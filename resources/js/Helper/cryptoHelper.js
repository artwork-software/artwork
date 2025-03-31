
const CryptoHelper = {
  STORAGE_KEYS: {
    public: 'chat_public_key',
    private: 'chat_private_key',
  },
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
    );
    const publicKey = await crypto.subtle.exportKey('spki', keyPair.publicKey);
    const privateKey = await crypto.subtle.exportKey('pkcs8', keyPair.privateKey);
    const pub = CryptoHelper.arrayBufferToBase64(publicKey);
    const priv = CryptoHelper.arrayBufferToBase64(privateKey);
    localStorage.setItem(CryptoHelper.STORAGE_KEYS.public, pub);
    localStorage.setItem(CryptoHelper.STORAGE_KEYS.private, priv);
    return { publicKey: pub, privateKey: priv };
  },
  async encryptMessage(message, base64PublicKey) {
    const publicKey = await CryptoHelper.importPublicKey(base64PublicKey);
    const encoded = new TextEncoder().encode(message);
    const encrypted = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, publicKey, encoded);
    return CryptoHelper.arrayBufferToBase64(encrypted);
  },
  async decryptMessage(base64Encrypted, base64PrivateKey = null) {
    const privateKey = await CryptoHelper.importPrivateKey(
      base64PrivateKey || CryptoHelper.getStoredKeys().privateKey
    );
    const encryptedBytes = CryptoHelper.base64ToArrayBuffer(base64Encrypted);
    const decrypted = await crypto.subtle.decrypt({ name: 'RSA-OAEP' }, privateKey, encryptedBytes);
    return new TextDecoder().decode(decrypted);
  },
  async importPublicKey(base64Key) {
    const binary = CryptoHelper.base64ToArrayBuffer(base64Key);
    return await crypto.subtle.importKey(
      'spki',
      binary,
      { name: 'RSA-OAEP', hash: 'SHA-256' },
      false,
      ['encrypt']
    );
  },
  async importPrivateKey(base64Key) {
    const binary = CryptoHelper.base64ToArrayBuffer(base64Key);
    return await crypto.subtle.importKey(
      'pkcs8',
      binary,
      { name: 'RSA-OAEP', hash: 'SHA-256' },
      false,
      ['decrypt']
    );
  },
  getStoredKeys() {
    return {
      publicKey: localStorage.getItem(CryptoHelper.STORAGE_KEYS.public),
      privateKey: localStorage.getItem(CryptoHelper.STORAGE_KEYS.private),
    };
  },
  clearStoredKeys() {
    localStorage.removeItem(CryptoHelper.STORAGE_KEYS.public);
    localStorage.removeItem(CryptoHelper.STORAGE_KEYS.private);
  },
  arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    return btoa(String.fromCharCode(...bytes));
  },
  base64ToArrayBuffer(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
  }
};
export default CryptoHelper;
