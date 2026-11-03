import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';

function isAllowedLink(uri: string): boolean {
    try {
        const url = new globalThis.URL(uri);
        return (
            (url.protocol === 'https:' || url.protocol === 'http:') &&
            url.hostname.length > 0 &&
            url.username === '' &&
            url.password === ''
        );
    } catch {
        return false;
    }
}

export function richTextExtensions() {
    return [
        StarterKit.configure({
            code: false,
            codeBlock: false,
            hardBreak: false,
            heading: { levels: [1, 2, 3] },
            horizontalRule: false,
            link: {
                autolink: false,
                linkOnPaste: false,
                openOnClick: false,
                isAllowedUri: (uri, { defaultValidate }) => defaultValidate(uri) && isAllowedLink(uri),
            },
            strike: false,
            underline: false,
        }),
        Image.configure({
            allowBase64: false,
            HTMLAttributes: { referrerpolicy: 'no-referrer' },
        }),
    ];
}
