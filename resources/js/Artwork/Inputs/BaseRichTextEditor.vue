<template>
    <div class="w-full">
        <span v-if="label" :id="labelId" class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ label }}</span>

        <div class="overflow-hidden rounded-md border bg-surface transition-[border-color] duration-150 focus-within:border-accent-600" :class="error ? 'border-danger-border' : 'border-border'">
            <div role="toolbar" :aria-label="$t('Formatting')" class="flex h-9 items-center gap-0.5 border-b border-border-hairline px-1.5">
                <template v-if="linkOpen">
                    <IconLink class="ml-1 size-4 shrink-0 text-text-subtle" />
                    <input ref="linkInput" v-model="linkUrl" type="url" placeholder="https://" :aria-label="$t('Link')"
                           class="h-7 min-w-0 flex-1 border-0 bg-transparent px-2 text-sm focus:ring-0 focus:outline-none"
                           @keydown.enter.prevent="applyLink" @keydown.esc.prevent="closeLink" />
                    <button type="button" class="ui-button-small" @click="applyLink">{{ $t('Apply') }}</button>
                    <button v-if="editor?.isActive('link')" type="button" :class="toolButton" :aria-label="$t('Unlink')" :title="$t('Unlink')" @click="unlink">
                        <IconLinkOff class="size-4" />
                    </button>
                    <button type="button" :class="toolButton" :aria-label="$t('Cancel')" :title="$t('Cancel')" @click="closeLink">
                        <IconX class="size-4" />
                    </button>
                </template>
                <template v-else>
                    <template v-for="(group, index) in tools" :key="index">
                        <span v-if="index > 0" class="mx-1 h-4 w-px bg-border-subtle" aria-hidden="true" />
                        <button v-for="tool in group" :key="tool.label" type="button" :class="[toolButton, tool.active?.() ? 'bg-accent-50 text-accent-700' : '']"
                                :aria-label="$t(tool.label)" :title="$t(tool.label)" :aria-pressed="tool.active ? String(tool.active()) : undefined" @click="tool.run">
                            <component :is="tool.icon" class="size-4" />
                        </button>
                    </template>
                </template>
            </div>

            <EditorContent :editor="editor" />
        </div>

        <p v-if="error" class="mt-1 text-[11.5px] text-danger">{{ error }}</p>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Placeholder } from '@tiptap/extensions'
import { Markdown } from '@tiptap/markdown'
import { IconBold, IconHeading, IconItalic, IconLink, IconLinkOff, IconList, IconListNumbers, IconQuote, IconX } from '@tabler/icons-vue'

/* Markdown in and out, limited to what the ticket shop renders: emphasis, one subheading level, lists, quotes, links. */
const props = defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, required: true },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const labelId = `${props.id}-label`

const toolButton = 'flex size-7 shrink-0 items-center justify-center rounded text-text-muted transition-colors hover:bg-surface-sunken hover:text-text focus-visible:outline-2 focus-visible:outline-accent-600'

const content = 'block max-h-[360px] min-h-[132px] overflow-y-auto px-3 py-2 text-sm leading-relaxed text-text outline-none '
    + '[&>*+*]:mt-2 [&>*+h2]:mt-4 [&_h2]:font-lexend [&_h2]:text-[15px] [&_h2]:font-semibold '
    + '[&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_li+li]:mt-0.5 '
    + '[&_blockquote]:border-l-2 [&_blockquote]:border-border-strong [&_blockquote]:pl-3 [&_blockquote]:text-text-muted '
    + '[&_a]:text-accent-700 [&_a]:underline [&_a]:underline-offset-2 '
    + '[&_.is-editor-empty:first-child]:before:pointer-events-none [&_.is-editor-empty:first-child]:before:float-left [&_.is-editor-empty:first-child]:before:h-0 '
    + '[&_.is-editor-empty:first-child]:before:text-text-subtle [&_.is-editor-empty:first-child]:before:content-[attr(data-placeholder)]'

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: { levels: [2] },
            code: false,
            codeBlock: false,
            horizontalRule: false,
            strike: false,
            underline: false,
            link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
        }),
        Placeholder.configure({ placeholder: () => props.placeholder }),
        /* Single line breaks stay line breaks, as they were in the plain-text descriptions. */
        Markdown.configure({ markedOptions: { breaks: true } }),
    ],
    content: props.modelValue,
    contentType: 'markdown',
    editorProps: {
        attributes: {
            id: props.id,
            class: content,
            role: 'textbox',
            'aria-multiline': 'true',
            ...(props.label ? { 'aria-labelledby': labelId } : {}),
        },
    },
    onUpdate: ({ editor: changed }) => emit('update:modelValue', markdownOf(changed)),
})

/* Without the empty paragraph the editor keeps at the end. */
function markdownOf(current) {
    return current.getMarkdown().trimEnd()
}

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== markdownOf(editor.value)) {
        editor.value.commands.setContent(value, { contentType: 'markdown', emitUpdate: false })
    }
})

onBeforeUnmount(() => editor.value?.destroy())

const chain = () => editor.value.chain().focus()

const tools = [
    [
        { label: 'Bold', icon: IconBold, run: () => chain().toggleBold().run(), active: () => editor.value?.isActive('bold') },
        { label: 'Italic', icon: IconItalic, run: () => chain().toggleItalic().run(), active: () => editor.value?.isActive('italic') },
    ],
    [
        { label: 'Subheading', icon: IconHeading, run: () => chain().toggleHeading({ level: 2 }).run(), active: () => editor.value?.isActive('heading') },
        { label: 'Bulleted list', icon: IconList, run: () => chain().toggleBulletList().run(), active: () => editor.value?.isActive('bulletList') },
        { label: 'Numbered list', icon: IconListNumbers, run: () => chain().toggleOrderedList().run(), active: () => editor.value?.isActive('orderedList') },
        { label: 'Quote', icon: IconQuote, run: () => chain().toggleBlockquote().run(), active: () => editor.value?.isActive('blockquote') },
    ],
    [
        { label: 'Link', icon: IconLink, run: () => openLink(), active: () => editor.value?.isActive('link') },
    ],
]

const linkOpen = ref(false)
const linkUrl = ref('')
const linkInput = ref(null)

async function openLink() {
    linkUrl.value = editor.value.getAttributes('link').href ?? ''
    linkOpen.value = true
    await nextTick()
    linkInput.value?.focus()
}

function closeLink() {
    linkOpen.value = false
    editor.value.commands.focus()
}

function applyLink() {
    const href = linkUrl.value.trim()
    if (!href) return unlink()
    chain().extendMarkRange('link').setLink({ href }).run()
    linkOpen.value = false
}

function unlink() {
    chain().extendMarkRange('link').unsetLink().run()
    linkOpen.value = false
}
</script>
