import assert from 'node:assert/strict'
import test from 'node:test'
import {
    getDroppedArticleImageFiles,
    validateArticleImageFiles,
} from '../../resources/js/Pages/Inventory/Components/Article/Modals/articleImageFiles.js'

const translate = (message, replacements) => replacements.reduce(
    (translated, replacement, index) => translated.replace(`{${index}}`, replacement),
    message,
)

const imageFile = (name, type, size = 1024) => ({name, type, size})

test('extracts every image from a drop event', () => {
    const files = [
        imageFile('front.jpg', 'image/jpeg'),
        imageFile('back.png', 'image/png'),
    ]

    assert.deepEqual(getDroppedArticleImageFiles({dataTransfer: {files}}), files)
    assert.deepEqual(getDroppedArticleImageFiles(null), [])
})

test('accepts supported images and HEIC files without a browser MIME type', () => {
    const jpeg = imageFile('article.jpg', 'image/jpeg')
    const heic = imageFile('article.HEIC', '')

    const result = validateArticleImageFiles([jpeg, heic], 10, translate)

    assert.deepEqual(result.validFiles, [jpeg, heic])
    assert.deepEqual(result.errors, [])
})

test('rejects unsupported and oversized dropped files with the existing validation messages', () => {
    const textFile = imageFile('notes.txt', 'text/plain')
    const oversizedImage = imageFile('large.png', 'image/png', 11 * 1024 * 1024)

    const result = validateArticleImageFiles([textFile, oversizedImage], 10, translate)

    assert.deepEqual(result.validFiles, [])
    assert.equal(result.errors.length, 2)
    assert.match(result.errors[0], /notes\.txt.*unsupported format/)
    assert.match(result.errors[1], /large\.png.*too large \(11\.0 MB\)/)
})
