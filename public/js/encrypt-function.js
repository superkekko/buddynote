async function deriveKeyFromPassword(password, salt) {
  const encoder = new TextEncoder();
  const keyMaterial = await crypto.subtle.importKey(
    'raw',
    encoder.encode(password),
    { name: 'PBKDF2' },
    false,
    ['deriveBits', 'deriveKey']
  );

  const key = await crypto.subtle.deriveKey(
    {
      name: 'PBKDF2',
      salt: encoder.encode(salt),
      iterations: 10000,
      hash: 'SHA-256',
    },
    keyMaterial,
    { name: 'AES-CBC', length: 256 },
    true,
    ['encrypt', 'decrypt']
  );

  return key;
}

// Function to decode a Base64 string to an ArrayBuffer
function base64ToArrayBuffer(base64) {
  const binaryString = atob(base64);
  const length = binaryString.length;
  const arrayBuffer = new ArrayBuffer(length);
  const uint8Array = new Uint8Array(arrayBuffer);

  for (let i = 0; i < length; i++) {
    uint8Array[i] = binaryString.charCodeAt(i);
  }

  return arrayBuffer;
}

async function encryptString(text, password, salt) {
  const encoder = new TextEncoder();
  const data = encoder.encode(text);
  const iv = crypto.getRandomValues(new Uint8Array(16));

  const key = await deriveKeyFromPassword(password, salt);

  const encryptedData = await crypto.subtle.encrypt(
    { name: 'AES-CBC', iv: iv },
    key,
    data
  );

  const combinedArray = new Uint8Array(iv.length + encryptedData.byteLength);
  combinedArray.set(iv);
  combinedArray.set(new Uint8Array(encryptedData), iv.length);

  return btoa(String.fromCharCode.apply(null, combinedArray));
}

async function decryptString(encryptedString, password, salt) {
  const decoder = new TextDecoder();
  const combinedArray = new Uint8Array(base64ToArrayBuffer(encryptedString));
  const iv = combinedArray.slice(0, 16);
  const data = combinedArray.slice(16);

  const key = await deriveKeyFromPassword(password, salt);

  const decryptedData = await crypto.subtle.decrypt(
    { name: 'AES-CBC', iv: iv },
    key,
    data
  );

  return decoder.decode(decryptedData);
}