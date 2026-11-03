<?php

namespace App\Shared\RichText;

final class RichTextDocumentValidator
{
    private const NODE_TYPES = [
        'doc',
        'paragraph',
        'text',
        'heading',
        'bulletList',
        'orderedList',
        'listItem',
        'blockquote',
        'image',
    ];

    private const MARK_TYPES = ['bold', 'italic', 'link'];

    public static function isValid(mixed $document, ?int $courseId = null): bool
    {
        if (! is_array($document) || ! self::hasOnlyKeys($document, ['type', 'content'])) {
            return false;
        }

        return ($document['type'] ?? null) === 'doc'
            && is_array($document['content'] ?? null)
            && array_is_list($document['content'])
            && self::isValidNodeList($document['content'], $courseId, 0);
    }

    public static function plainText(mixed $document): string
    {
        if (! is_array($document)) {
            return '';
        }

        if (($document['type'] ?? null) === 'text') {
            return is_string($document['text'] ?? null) ? $document['text'] : '';
        }

        $text = '';

        foreach (is_array($document['content'] ?? null) ? $document['content'] : [] as $node) {
            if (! is_array($node)) {
                continue;
            }

            $text .= self::plainText($node);

            if (in_array($node['type'] ?? null, ['paragraph', 'heading', 'listItem'], true)) {
                $text .= ' ';
            }
        }

        return $text;
    }

    /**
     * @return array{type: 'doc', content: list<array{type: 'paragraph', content: list<array{type: 'text', text: string}>}>}
     */
    public static function fromPlainText(string $text): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => $text === '' ? [] : [['type' => 'text', 'text' => $text]],
                ],
            ],
        ];
    }

    public static function normalizeEmptyImageAlternatives(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (($value['type'] ?? null) === 'image'
            && is_array($value['attrs'] ?? null)
            && array_key_exists('alt', $value['attrs'])
            && $value['attrs']['alt'] === null) {
            $value['attrs']['alt'] = '';
        }

        foreach ($value as $key => $nestedValue) {
            if (is_array($nestedValue)) {
                $value[$key] = self::normalizeEmptyImageAlternatives($nestedValue);
            }
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $nodes
     */
    private static function isValidNodeList(array $nodes, ?int $courseId, int $depth): bool
    {
        if ($depth > 20 || count($nodes) > 1000 || ! array_is_list($nodes)) {
            return false;
        }

        foreach ($nodes as $node) {
            if (! self::isValidNode($node, $courseId, $depth + 1)) {
                return false;
            }
        }

        return true;
    }

    private static function isValidNode(mixed $node, ?int $courseId, int $depth): bool
    {
        if (! is_array($node) || ! is_string($node['type'] ?? null)) {
            return false;
        }

        $type = $node['type'];

        if (! in_array($type, self::NODE_TYPES, true)) {
            return false;
        }

        if ($type === 'text') {
            return self::hasOnlyKeys($node, ['type', 'text', 'marks'])
                && is_string($node['text'] ?? null)
                && mb_strlen($node['text']) <= 10000
                && (! array_key_exists('marks', $node) || self::isValidMarks($node['marks']));
        }

        if ($type === 'image') {
            return self::hasOnlyKeys($node, ['type', 'attrs'])
                && self::isValidImageAttributes($node['attrs'] ?? null, $courseId);
        }

        $allowedNodeKeys = match ($type) {
            'heading', 'orderedList' => ['type', 'attrs', 'content'],
            default => ['type', 'content'],
        };

        if (! self::hasOnlyKeys($node, $allowedNodeKeys)) {
            return false;
        }

        if ($type === 'heading') {
            if (! is_array($node['attrs'] ?? null)
                || ! self::hasOnlyKeys($node['attrs'], ['level'])
                || ! in_array($node['attrs']['level'] ?? null, [1, 2, 3], true)) {
                return false;
            }
        }

        if ($type === 'orderedList') {
            $attributes = $node['attrs'] ?? null;

            if (! is_array($attributes)
                || ! self::hasOnlyKeys($attributes, ['start', 'type'])
                || ! is_int($attributes['start'] ?? null)
                || $attributes['start'] < 1
                || ! in_array($attributes['type'] ?? null, [null, '1', 'a', 'A', 'i', 'I'], true)) {
                return false;
            }
        }

        $content = $node['content'] ?? [];

        if (! is_array($content) || ! array_is_list($content)) {
            return false;
        }

        foreach ($content as $child) {
            $childType = is_array($child) ? ($child['type'] ?? null) : null;

            if (in_array($type, ['paragraph', 'heading'], true)
                && $childType !== 'text') {
                return false;
            }

            if (in_array($type, ['bulletList', 'orderedList'], true)
                && $childType !== 'listItem') {
                return false;
            }

            if (in_array($type, ['listItem', 'blockquote', 'doc'], true)
                && ! in_array($childType, [
                    'paragraph',
                    'heading',
                    'bulletList',
                    'orderedList',
                    'blockquote',
                    'image',
                ], true)) {
                return false;
            }
        }

        if (in_array($type, ['bulletList', 'orderedList', 'blockquote', 'listItem'], true)
            && $content === []) {
            return false;
        }

        return self::isValidNodeList($content, $courseId, $depth);
    }

    private static function isValidMarks(mixed $marks): bool
    {
        if (! is_array($marks) || ! array_is_list($marks) || count($marks) > count(self::MARK_TYPES)) {
            return false;
        }

        $seen = [];
        foreach ($marks as $mark) {
            if (! is_array($mark) || ! in_array($mark['type'] ?? null, self::MARK_TYPES, true)) {
                return false;
            }

            if (in_array($mark['type'], $seen, true)) {
                return false;
            }
            $seen[] = $mark['type'];

            if ($mark['type'] === 'link') {
                if (! self::hasOnlyKeys($mark, ['type', 'attrs'])
                    || ! is_array($mark['attrs'] ?? null)
                    || ! self::hasOnlyKeys($mark['attrs'], ['href', 'target', 'rel', 'class', 'title'])
                    || ! self::isWebUrl($mark['attrs']['href'] ?? null, ['http', 'https'])
                    || ! in_array($mark['attrs']['target'] ?? null, [null, '_blank', '_self'], true)
                    || ! in_array($mark['attrs']['rel'] ?? null, [null, 'noopener noreferrer nofollow'], true)
                    || ! in_array($mark['attrs']['class'] ?? null, [null, ''], true)
                    || (isset($mark['attrs']['title'])
                        && (! is_string($mark['attrs']['title']) || mb_strlen($mark['attrs']['title']) > 255))) {
                    return false;
                }
            } elseif (! self::hasOnlyKeys($mark, ['type'])) {
                return false;
            }
        }

        return true;
    }

    private static function isValidImageAttributes(mixed $attributes, ?int $courseId): bool
    {
        if (! is_array($attributes)
            || ! self::hasOnlyKeys($attributes, ['src', 'alt', 'title', 'width', 'height'])
            || ! is_string($attributes['alt'] ?? null)
            || mb_strlen($attributes['alt']) > 1000) {
            return false;
        }

        if (array_key_exists('title', $attributes)
            && $attributes['title'] !== null
            && (! is_string($attributes['title']) || mb_strlen($attributes['title']) > 255)) {
            return false;
        }

        foreach (['width', 'height'] as $dimension) {
            if (array_key_exists($dimension, $attributes)
                && $attributes[$dimension] !== null
                && (! is_int($attributes[$dimension]) || $attributes[$dimension] < 1 || $attributes[$dimension] > 4096)) {
                return false;
            }
        }

        $source = $attributes['src'] ?? null;

        if (! is_string($source) || str_starts_with($source, '//')) {
            return false;
        }

        if (preg_match(
            '#^/courses/([1-9][0-9]*)/content-images/[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$#i',
            $source,
            $matches,
        ) === 1) {
            return $courseId !== null && (int) $matches[1] === $courseId;
        }

        return self::isWebUrl($source, ['https']);
    }

    /**
     * @param  list<string>  $schemes
     */
    private static function isWebUrl(mixed $url, array $schemes): bool
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), $schemes, true)
            && is_string($parts['host'] ?? null)
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    /**
     * @param  array<mixed>  $value
     * @param  list<string>  $keys
     */
    private static function hasOnlyKeys(array $value, array $keys): bool
    {
        return array_diff(array_keys($value), $keys) === [];
    }
}
