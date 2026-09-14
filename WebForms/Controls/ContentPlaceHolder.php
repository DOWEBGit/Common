<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

/**
 * Il buco nella master che la pagina riempie.
 *
 *   nella master:  <dw:ContentPlaceHolder id="corpo">Nessun contenuto.</dw:ContentPlaceHolder>
 *   nella pagina:  <dw:Content placeholder="corpo"> ... </dw:Content>
 *
 * Quello che sta scritto dentro il segnaposto e' il contenuto PREDEFINITO: resta li' se la
 * pagina non dichiara nessun <dw:Content> per quell'id. Serve piu' di quanto sembri - un
 * titolo, una briciola di pane, un blocco che quasi tutte le pagine vogliono uguale - e
 * risparmia di ripetere in ogni pagina un Content che dice sempre la stessa cosa.
 *
 * Non rende niente di suo, come il PlaceHolder da cui deriva: la cornice la disegna la
 * master attorno, non questo.
 */
class ContentPlaceHolder extends PlaceHolder
{
}
