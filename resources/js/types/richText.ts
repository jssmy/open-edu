export interface RichTextMark {
    type: 'bold' | 'italic' | 'link';
    attrs?: {
        href?: string;
        target?: string | null;
        rel?: string | null;
        class?: string | null;
        title?: string | null;
    };
}

export interface RichTextNode {
    type: string;
    attrs?: Record<string, string | number | null>;
    content?: RichTextNode[];
    marks?: RichTextMark[];
    text?: string;
}

export interface RichTextDocument {
    type: 'doc';
    content: RichTextNode[];
}

export function emptyRichTextDocument(): RichTextDocument {
    return {
        type: 'doc',
        content: [{ type: 'paragraph' }],
    };
}
