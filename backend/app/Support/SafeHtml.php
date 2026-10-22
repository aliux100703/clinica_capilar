<?php

namespace App\Support;

use DOMDocument;
use DOMNode;

class SafeHtml
{
    private const ALLOWED = ['p', 'h2', 'h3', 'h4', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'br', 'a', 'code', 'pre', 'hr'];

    public static function url(?string $value): ?string
    {
        if (! $value || preg_match('/[\\\\\x00-\x20]/', $value)) {
            return null;
        }
        if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $value)) {
            return $value;
        }
        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return $value;
        }
        $scheme = strtolower(parse_url($value, PHP_URL_SCHEME) ?: '');

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) && ! parse_url($value, PHP_URL_USER) && ! parse_url($value, PHP_URL_PASS) ? $value : null;
    }

    public static function clean(?string $html): string
    {
        if (! $html) {
            return '';
        }
        if (! preg_match('/<[a-z!\/]/i', $html)) {
            return '<p>'.nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>';
        }
        $dom = new DOMDocument('1.0', 'UTF-8');
        $old = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        $body = $dom->getElementsByTagName('body')->item(0);

        return $body ? self::children($body) : '';
    }

    private static function children(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $out .= htmlspecialchars($child->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, ['script', 'style', 'svg', 'math', 'iframe', 'object', 'embed', 'template', 'noscript', 'form', 'input', 'button'], true)) {
                continue;
            }
            $inside = self::children($child);
            if (! in_array($tag, self::ALLOWED, true)) {
                $out .= $inside;

                continue;
            }
            $attributes = '';
            if ($tag === 'a') {
                $url = self::url($child->getAttribute('href'));
                if ($url) {
                    $attributes = ' href="'.htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" rel="noopener noreferrer"';
                }
            }
            $out .= '<'.$tag.$attributes.'>'.(in_array($tag, ['br', 'hr']) ? '' : $inside.'</'.$tag.'>');
        }

        return $out;
    }
}
