<script setup lang="ts">
import { ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import type { JSONContent } from '@tiptap/core';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import { emptyRichTextDocument, type RichTextDocument } from '@/types/richText';
import { richTextExtensions } from '@/components/editor/extensions';

interface ImageUploadResponse {
    id: string;
    src: string;
}

interface ErrorResponse {
    message?: string;
    errors?: Record<string, string | string[]>;
}

const props = defineProps<{
    id: string;
    label: string;
    modelValue: RichTextDocument | null;
    courseId?: number | null;
    error?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [document: RichTextDocument];
}>();

const documentValue = (props.modelValue ?? emptyRichTextDocument()) as JSONContent;
const editor = useEditor({
    content: documentValue,
    extensions: richTextExtensions(),
    editorProps: {
        attributes: {
            id: props.id,
            role: 'textbox',
            'aria-label': props.label,
            'aria-multiline': 'true',
            'aria-invalid': props.error ? 'true' : 'false',
            'aria-describedby': props.error ? `${props.id}-error` : '',
            class: 'min-h-40 w-full rounded-control border border-border bg-panel p-3 text-base leading-7 text-text outline-none focus-visible:ring-2 focus-visible:ring-primary',
        },
    },
    onUpdate: ({ editor: currentEditor }) => {
        emit('update:modelValue', currentEditor.getJSON() as RichTextDocument);
    },
});
const showImageTools = ref(false);
const showLinkTools = ref(false);
const decorativeImage = ref(false);
const imageAlt = ref('');
const linkUrl = ref('');
const imageUrl = ref('');
const selectedFile = ref<globalThis.File | null>(null);
const editorError = ref('');
const imageStatus = ref('');
const uploading = ref(false);
const imageFileId = `${props.id}-rich-image-file`;
const imageUrlId = `${props.id}-rich-image-url`;
const imageAltId = `${props.id}-rich-image-alt`;
const linkUrlId = `${props.id}-rich-link-url`;

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) {
            return;
        }

        const nextContent = value ?? emptyRichTextDocument();
        if (JSON.stringify(editor.value.getJSON()) !== JSON.stringify(nextContent)) {
            editor.value.commands.setContent(nextContent as JSONContent, { emitUpdate: false });
        }
    },
    { deep: true },
);

watch(
    () => props.error,
    (error) => {
        editor.value?.view.dom.setAttribute('aria-invalid', error ? 'true' : 'false');
        editor.value?.view.dom.setAttribute('aria-describedby', error ? `${props.id}-error` : '');
    },
);

function toggleMark(mark: 'bold' | 'italic') {
    if (mark === 'bold') {
        editor.value?.chain().focus().toggleBold().run();
    } else {
        editor.value?.chain().focus().toggleItalic().run();
    }
}

function toggleBlock(block: 'bulletList' | 'orderedList' | 'blockquote') {
    const chain = editor.value?.chain().focus();
    if (!chain) {
        return;
    }

    if (block === 'bulletList') {
        chain.toggleBulletList().run();
    } else if (block === 'orderedList') {
        chain.toggleOrderedList().run();
    } else {
        chain.toggleBlockquote().run();
    }
}

function setHeading(level: 1 | 2 | 3) {
    editor.value?.chain().focus().toggleHeading({ level }).run();
}

function safeHttpsUrl(value: string): string | null {
    try {
        const url = new globalThis.URL(value);
        return url.protocol === 'https:' &&
            url.hostname.length > 0 &&
            url.username === '' &&
            url.password === ''
            ? url.href
            : null;
    } catch {
        return null;
    }
}

function insertLink() {
    const url = linkUrl.value.trim();
    if (!isValidLinkUrl(url)) {
        editorError.value = 'El enlace debe usar HTTP o HTTPS.';
        return;
    }

    const selection = editor.value?.state.selection;
    if (!selection || selection.empty) {
        editorError.value = 'Selecciona el texto que deseas enlazar.';
        return;
    }

    editorError.value = '';
    imageStatus.value = '';
    editor.value?.chain().focus().setLink({ href: url }).run();
    showLinkTools.value = false;
    linkUrl.value = '';
}

function isValidLinkUrl(value: string): boolean {
    try {
        const url = new globalThis.URL(value);
        return (
            ['http:', 'https:'].includes(url.protocol) &&
            url.hostname.length > 0 &&
            url.username === '' &&
            url.password === ''
        );
    } catch {
        return false;
    }
}

function imageAlternative(): string | null {
    if (decorativeImage.value) {
        return '';
    }

    const alternative = imageAlt.value.trim();
    if (alternative === '') {
        editorError.value = 'Escribe un texto alternativo o marca la imagen como decorativa.';
        return null;
    }

    return alternative;
}

function insertExternalImage() {
    editorError.value = '';
    imageStatus.value = '';
    const alternative = imageAlternative();
    if (alternative === null) {
        return;
    }

    const source = safeHttpsUrl(imageUrl.value.trim());
    if (source === null) {
        editorError.value = 'La imagen externa debe usar una URL HTTPS válida.';
        return;
    }

    editor.value?.chain().focus().setImage({ src: source, alt: alternative }).run();
    resetImageForm();
}

function csrfToken(): string | null {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null;
}

function isImageUploadResponse(value: unknown): value is ImageUploadResponse {
    return (
        typeof value === 'object' &&
        value !== null &&
        'id' in value &&
        typeof value.id === 'string' &&
        'src' in value &&
        typeof value.src === 'string'
    );
}

async function uploadImage() {
    editorError.value = '';
    imageStatus.value = '';

    if (!props.courseId) {
        editorError.value = 'Guarda el curso antes de subir imágenes.';
        return;
    }

    const alternative = imageAlternative();
    if (alternative === null) {
        return;
    }
    if (selectedFile.value === null) {
        editorError.value = 'Selecciona una imagen JPEG, PNG o WebP.';
        return;
    }

    const token = csrfToken();
    if (token === null) {
        editorError.value = 'No se pudo validar la sesión. Recarga la página e inténtalo nuevamente.';
        return;
    }

    const formData = new globalThis.FormData();
    formData.append('file', selectedFile.value);
    uploading.value = true;

    try {
        const response = await globalThis.fetch(`/courses/${props.courseId}/content-images`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': token,
            },
        });

        const payload: unknown = await response.json();
        if (!response.ok) {
            const errorPayload = payload as ErrorResponse;
            const fieldError = errorPayload.errors?.file;
            throw new Error(
                Array.isArray(fieldError)
                    ? fieldError[0]
                    : (fieldError ?? errorPayload.message ?? 'No se pudo subir la imagen.'),
            );
        }

        if (!isImageUploadResponse(payload)) {
            throw new Error('El servidor devolvió una respuesta de imagen no válida.');
        }

        editor.value?.chain().focus().setImage({ src: payload.src, alt: alternative }).run();
        imageStatus.value = 'Imagen insertada en el contenido.';
        resetImageForm();
    } catch (error: unknown) {
        editorError.value =
            error instanceof Error ? error.message : 'No se pudo completar la carga de la imagen.';
    } finally {
        uploading.value = false;
    }
}

function resetImageForm() {
    imageAlt.value = '';
    imageUrl.value = '';
    selectedFile.value = null;
    decorativeImage.value = false;
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap gap-2" role="toolbar" :aria-label="`Formato de ${label}`">
            <Button
                class="min-h-11 min-w-11"
                type="button"
                variant="secondary"
                :aria-pressed="editor?.isActive('bold') ?? false"
                aria-label="Negrita"
                @click="toggleMark('bold')"
            >
                B
            </Button>
            <Button
                class="min-h-11 min-w-11"
                type="button"
                variant="secondary"
                :aria-pressed="editor?.isActive('italic') ?? false"
                aria-label="Cursiva"
                @click="toggleMark('italic')"
            >
                I
            </Button>
            <Button
                class="min-h-11"
                type="button"
                variant="secondary"
                aria-label="Encabezado nivel 2"
                @click="setHeading(2)"
            >
                Título
            </Button>
            <Button
                class="min-h-11"
                type="button"
                variant="secondary"
                aria-label="Lista con viñetas"
                @click="toggleBlock('bulletList')"
            >
                Viñetas
            </Button>
            <Button
                class="min-h-11"
                type="button"
                variant="secondary"
                aria-label="Lista numerada"
                @click="toggleBlock('orderedList')"
            >
                Numerada
            </Button>
            <Button
                class="min-h-11"
                type="button"
                variant="secondary"
                aria-label="Cita"
                @click="toggleBlock('blockquote')"
            >
                Cita
            </Button>
            <Button
                class="min-h-11"
                type="button"
                variant="secondary"
                aria-label="Mostrar opciones de enlace"
                :aria-expanded="showLinkTools"
                @click="showLinkTools = !showLinkTools"
            >
                Enlace
            </Button>
            <Button
                class="min-h-11"
                type="button"
                variant="secondary"
                aria-label="Mostrar opciones de imagen"
                :aria-expanded="showImageTools"
                @click="showImageTools = !showImageTools"
            >
                Imagen
            </Button>
        </div>

        <div
            class="rounded-control border border-border bg-panel p-2 leading-7 text-text [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-primary [&_blockquote]:pl-4 [&_h1]:text-2xl [&_h1]:font-semibold [&_h2]:text-xl [&_h2]:font-semibold [&_h3]:text-lg [&_h3]:font-semibold [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6"
        >
            <EditorContent :editor="editor" />
        </div>
        <p v-if="error" :id="`${id}-error`" class="text-sm text-danger" role="alert">{{ error }}</p>
        <p v-if="editorError" class="text-sm text-danger" role="alert">{{ editorError }}</p>
        <p v-if="imageStatus" class="text-sm text-text-muted" role="status">{{ imageStatus }}</p>

        <section v-if="showLinkTools" class="flex flex-col gap-3 rounded-control bg-surface p-3">
            <FormField :id="linkUrlId" label="URL del enlace (HTTP o HTTPS)">
                <Input :id="linkUrlId" v-model="linkUrl" type="url" />
            </FormField>
            <Button type="button" class="self-start" aria-label="Aplicar enlace" @click="insertLink">
                Aplicar enlace
            </Button>
        </section>

        <section v-if="showImageTools" class="flex flex-col gap-3 rounded-control bg-surface p-3">
            <h3 class="font-medium">Insertar imagen</h3>
            <FormField :id="imageFileId" label="Subir imagen (JPEG, PNG o WebP, máximo 5 MB)">
                <input
                    :id="imageFileId"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="min-h-11 w-full rounded-control border border-border bg-panel p-2 text-sm focus-visible:outline-2 focus-visible:outline-primary"
                    @change="
                        selectedFile = ($event.target as HTMLInputElement).files?.[0] ?? null;
                        editorError = '';
                    "
                />
            </FormField>
            <FormField :id="imageUrlId" label="O URL HTTPS de una imagen externa">
                <Input :id="imageUrlId" v-model="imageUrl" type="url" />
            </FormField>
            <FormField :id="imageAltId" label="Texto alternativo">
                <Input
                    :id="imageAltId"
                    v-model="imageAlt"
                    :disabled="decorativeImage"
                    :required="!decorativeImage"
                />
            </FormField>
            <label class="flex min-h-11 items-center gap-2 text-sm">
                <input v-model="decorativeImage" type="checkbox" class="size-5 accent-primary" />
                Esta imagen es decorativa
            </label>
            <p v-if="!courseId" class="text-sm text-text-muted">
                Guarda el curso para habilitar la carga de imágenes.
            </p>
            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="secondary"
                    :disabled="uploading"
                    aria-label="Insertar imagen por URL"
                    @click="insertExternalImage"
                >
                    Insertar URL externa
                </Button>
                <Button
                    type="button"
                    :loading="uploading"
                    :disabled="!courseId"
                    aria-label="Subir e insertar imagen"
                    @click="uploadImage"
                >
                    Subir e insertar
                </Button>
            </div>
        </section>
    </div>
</template>
