<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

/**
 * L'oggetto che l'esempio degli eventi manda in giro con Notify(): un utente con la sua
 * immagine.
 *
 * E' una classe qualunque, senza niente del motore: passa da json_encode, quindi arrivano le
 * proprieta' pubbliche, e l'handler di chi riceve le trova in un array con le stesse chiavi.
 */
final class User
{
    public function __construct(
        public string $FirstName,
        public string $LastName,
        public string $Email,
        /** L'indirizzo dell'immagine: qui un SVG inline, cosi' l'esempio non dipende da niente. */
        public string $Image,
    ) {
    }

    /** Un avatar disegnato al volo con le iniziali, come data URI. */
    public static function Avatar(string $initials, string $color): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48">'
            . '<circle cx="24" cy="24" r="24" fill="' . $color . '"/>'
            . '<text x="24" y="30" font-family="system-ui" font-size="18" fill="#fff" text-anchor="middle">' . $initials . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
