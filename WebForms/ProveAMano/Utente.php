<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

/**
 * L'oggetto che il banco manda in giro con Notify(): un utente con la sua immagine.
 *
 * E' una classe qualunque, senza niente del motore: passa da json_encode, quindi arrivano le
 * proprieta' pubbliche, e l'handler di chi riceve le trova in un array con le stesse chiavi.
 */
final class Utente
{
    public function __construct(
        public string $Nome,
        public string $Cognome,
        public string $Email,
        /** L'indirizzo dell'immagine: qui un SVG inline, cosi' il banco non dipende da niente. */
        public string $Immagine,
    ) {
    }

    /** Un avatar disegnato al volo con le iniziali, come data URI. */
    public static function Avatar(string $iniziali, string $colore): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48">'
            . '<circle cx="24" cy="24" r="24" fill="' . $colore . '"/>'
            . '<text x="24" y="30" font-family="system-ui" font-size="18" fill="#fff" text-anchor="middle">' . $iniziali . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
