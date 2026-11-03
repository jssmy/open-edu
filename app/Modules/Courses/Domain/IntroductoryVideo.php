<?php

namespace App\Modules\Courses\Domain;

final readonly class IntroductoryVideo
{
    private function __construct(
        public string $provider,
        public string $id,
    ) {}

    public static function fromUrl(?string $url): ?self
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $video = match (true) {
            in_array($host, ['youtube.com', 'www.youtube.com'], true)
                && preg_match('#^/watch$#', $path) === 1
                && is_string($query['v'] ?? null)
                && preg_match('/^[A-Za-z0-9_-]{11}$/', $query['v']) === 1 => new self('youtube', $query['v']),
            in_array($host, ['youtu.be', 'www.youtu.be'], true)
                && preg_match('#^/([A-Za-z0-9_-]{11})/?$#', $path, $matches) === 1 => new self('youtube', $matches[1]),
            in_array($host, ['youtube.com', 'www.youtube.com'], true)
                && preg_match('#^/embed/([A-Za-z0-9_-]{11})/?$#', $path, $matches) === 1 => new self('youtube', $matches[1]),
            in_array($host, ['vimeo.com', 'www.vimeo.com'], true)
                && preg_match('#^/([0-9]{6,12})/?$#', $path, $matches) === 1 => new self('vimeo', $matches[1]),
            $host === 'player.vimeo.com'
                && preg_match('#^/video/([0-9]{6,12})/?$#', $path, $matches) === 1 => new self('vimeo', $matches[1]),
            default => null,
        };

        return $video;
    }

    public static function fromStoredValues(?string $provider, ?string $id): ?self
    {
        $validId = match ($provider) {
            'youtube' => is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1,
            'vimeo' => is_string($id) && preg_match('/^[0-9]{6,12}$/', $id) === 1,
            default => false,
        };

        return $validId ? new self($provider, $id) : null;
    }

    public function canonicalUrl(): string
    {
        return $this->provider === 'youtube'
            ? 'https://www.youtube.com/watch?v='.$this->id
            : 'https://vimeo.com/'.$this->id;
    }

    public function embedUrl(): string
    {
        return $this->provider === 'youtube'
            ? 'https://www.youtube-nocookie.com/embed/'.$this->id
            : 'https://player.vimeo.com/video/'.$this->id;
    }
}
