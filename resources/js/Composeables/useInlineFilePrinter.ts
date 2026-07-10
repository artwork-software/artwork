type PrintableFile = {
    name: string
    mime_type?: string | null
}

const printableImageMimeTypes = ['image/avif', 'image/bmp', 'image/gif', 'image/jpeg', 'image/png', 'image/webp']
const printableImageExtensions = ['avif', 'bmp', 'gif', 'jpg', 'jpeg', 'png', 'webp']

function fileExtension(name: string): string {
    const normalizedName = name.toLowerCase()

    return normalizedName.includes('.') ? normalizedName.split('.').pop() ?? '' : ''
}

export function isInlinePrintableFile(file: PrintableFile): boolean {
    const mimeType = (file.mime_type || '').toLowerCase()
    const extension = fileExtension(file.name)

    return mimeType === 'application/pdf' ||
        extension === 'pdf' ||
        printableImageMimeTypes.includes(mimeType) ||
        printableImageExtensions.includes(extension)
}

export function withInlineDisposition(fileUrl: string): string {
    const url = new URL(fileUrl, window.location.origin)
    url.searchParams.set('inline', '1')

    if (/^(?:[a-z][a-z\d+\-.]*:)?\/\//i.test(fileUrl)) {
        return url.toString()
    }

    return `${url.pathname}${url.search}${url.hash}`
}

export function useInlineFilePrinter() {
    let printFrame: HTMLIFrameElement | null = null

    function destroyPrintFrame(): void {
        printFrame?.remove()
        printFrame = null
    }

    function printInlineFile(fileUrl: string): void {
        destroyPrintFrame()

        const iframe = document.createElement('iframe')
        iframe.title = 'Print preview'
        iframe.setAttribute('aria-hidden', 'true')
        Object.assign(iframe.style, {
            position: 'fixed',
            right: '0',
            bottom: '0',
            width: '1px',
            height: '1px',
            border: '0',
            opacity: '0',
            pointerEvents: 'none',
        })

        iframe.src = withInlineDisposition(fileUrl)
        iframe.onload = () => {
            try {
                iframe.contentWindow?.focus()
                iframe.contentWindow?.print()
            } catch {
                window.open(iframe.src, '_blank', 'noopener')
            }
        }

        document.body.appendChild(iframe)
        printFrame = iframe
    }

    return {
        destroyPrintFrame,
        printInlineFile,
    }
}
