<?php
declare(strict_types=1);

namespace Common;

class TextUtilities
{
    /**
     * Funzione che pulisce tutti gli stili in linea quando si salva, utile per le richtextbox per mantenere gli stili di un sito
     * consistenti.
     * Tag consentiti dal wysiwyg (bold, italic, underline) + tag strutturali di base
     * @param string $text
     * @param array $tagConsentiti
     * @return string
     */
    public static function CleanText(string $text, array $tagConsentiti = ['strong', 'b', 'em', 'i', 'u', 'p', 'br', 'ul', 'ol', 'li']): string
    {
        if (empty($text))
            return '';

        // Rimuove tutti gli attributi da tutti i tag (style, class, color, face, size, ecc.)
        $text = preg_replace('/<([a-zA-Z][a-zA-Z0-9]*)\s[^>]*>/i', '<$1>', $text);

        // Strip tutti i tag non consentiti mantenendo il contenuto interno
        $text = preg_replace_callback(
            '/<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/i',
            function (array $matches) use ($tagConsentiti): string {
                $tag = strtolower($matches[1]);

                if (in_array($tag, $tagConsentiti, true))
                    return $matches[0];

                return '';
            },
            $text
        );

        // Rimuove paragrafi vuoti o con soli spazi/nbsp rimasti dopo la pulizia
        $text = preg_replace('/<p>(\s|&nbsp;)*<\/p>/i', '', $text);

        // Normalizza spazi multipli
        $text = preg_replace('/[ \t]+/', ' ', $text);

        return trim($text);
    }
}