<script setup lang="ts">
import { watch } from 'vue';
import type { JSONContent } from '@tiptap/core';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { emptyRichTextDocument, type RichTextDocument } from '@/types/richText';
import { richTextExtensions } from '@/components/editor/extensions';

const props = withDefaults(
    defineProps<{
        content: RichTextDocument | null;
        label?: string;
    }>(),
    { label: 'Contenido enriquecido' },
);

const editor = useEditor({
    content: (props.content ?? emptyRichTextDocument()) as JSONContent,
    editable: false,
    extensions: richTextExtensions(),
    editorProps: {
        attributes: {
            class: 'min-w-0 break-words leading-7 text-text',
        },
    },
});

watch(
    () => props.content,
    (content) => {
        editor.value?.commands.setContent((content ?? emptyRichTextDocument()) as JSONContent, {
            emitUpdate: false,
        });
    },
    { deep: true },
);
</script>

<template>
    <div
        class="min-w-0 leading-7 text-text [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-primary [&_blockquote]:pl-4 [&_h1]:text-2xl [&_h1]:font-semibold [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:text-lg [&_h3]:font-semibold [&_img]:h-auto [&_img]:max-w-full [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6"
        :aria-label="label"
    >
        <EditorContent :editor="editor" />
    </div>
</template>
