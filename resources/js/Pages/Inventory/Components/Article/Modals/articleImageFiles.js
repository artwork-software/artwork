export const ALLOWED_ARTICLE_IMAGE_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/bmp',
    'image/svg+xml',
    'image/heic',
    'image/heif',
]

const HEIC_FILE_NAME = /\.(heic|heif)$/i

export function getDroppedArticleImageFiles(event) {
    return Array.from(event?.dataTransfer?.files ?? [])
}

export function validateArticleImageFiles(files, maxSizeMb, translate) {
    const errors = []
    const validFiles = []
    const maxSizeBytes = maxSizeMb * 1024 * 1024

    for (const file of Array.from(files ?? [])) {
        if (!ALLOWED_ARTICLE_IMAGE_MIME_TYPES.includes(file.type) && !HEIC_FILE_NAME.test(file.name)) {
            errors.push(translate(
                'The image "{0}" has an unsupported format – allowed are JPG, PNG, GIF, WEBP, BMP, SVG and HEIC.',
                [file.name]
            ))
            continue
        }

        if (file.size > maxSizeBytes) {
            errors.push(translate(
                'The image "{0}" is too large ({1} MB) – each image may be a maximum of {2} MB.',
                [file.name, (file.size / 1024 / 1024).toFixed(1), maxSizeMb]
            ))
            continue
        }

        validFiles.push(file)
    }

    return {validFiles, errors}
}
