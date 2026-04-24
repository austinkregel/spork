<?php

declare(strict_types=1);

namespace App\Services\Messaging;

class EmailBodySanitizer
{
    /**
     * Convert an email body payload (base64, HTML or plain) into a safe, bounded plain-text string.
     */
    public function safeTextFromBase64(mixed $base64Body): ?string
    {
        if (! is_string($base64Body) || $base64Body === '') {
            return null;
        }

        $raw = base64_decode($base64Body, true);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        // Remove script/style blocks before stripping tags.
        $raw = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', ' ', $raw) ?? $raw;
        $raw = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', ' ', $raw) ?? $raw;

        // Strip all markup, decode entities, normalize whitespace.
        $text = strip_tags($raw);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\r\n|\r|\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // Hard cap: keep well under MySQL TEXT max (64KB). Limit by bytes.
        $maxBytes = 32 * 1024; // 32KB

        return mb_strcut($text, 0, $maxBytes, 'UTF-8');
    }
}
