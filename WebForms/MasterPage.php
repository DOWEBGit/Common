<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * La cornice condivisa dalle pagine: l'equivalente di una .master di WebForms.
 *
 * Tre file come tutto il resto:
 *
 *   Layouts/Sito.php            markup: intestazione, menu, <dw:ContentPlaceHolder>, footer
 *   Layouts/Sito.code.php       classe \Layouts\Sito
 *   Layouts/Sito.designer.php   generato
 *
 * La pagina la dichiara nella propria riga di avvio, che e' il posto dove WebForms mette la
 * direttiva MasterPageFile:
 *
 *   Page::Run(__FILE__, \WebForms\Brand::class, 'Layouts/Sito');
 *
 * e riempie i segnaposto:
 *
 *   <dw:Content placeholder="corpo"> ... </dw:Content>
 *
 * Una master E' UN UserControl che contiene la pagina invece di esserne contenuto: stesso
 * ciclo di vita (OnInit dal basso, OnLoad e OnPreRender dall'alto), stesso designer
 * tipizzato, stesso modo di parlare col resto del mondo. L'unica differenza vera e' che sta
 * alla radice dell'albero.
 *
 * DUE COSE CHE NON FA, ed e' voluto:
 *
 * 1. NON avvolge il contenuto in un elemento. Un UserControl rende un <div> attorno a se';
 *    la master invece e' il corpo della pagina, e un <div> in piu' attorno a tutto
 *    cambierebbe il CSS di ogni sito che la adotta.
 *
 * 2. NON e' un contenitore di denominazione. Di master ce n'e' una sola per pagina, quindi
 *    non c'e' niente da disambiguare, e gli id restano quelli scritti nel markup - sia i
 *    suoi sia quelli della pagina. E' la regola su cui si regge il morph, e in WebForms era
 *    proprio la master a romperla, trasformando txtFiltro in ctl00$corpo$txtFiltro.
 */
abstract class MasterPage extends UserControl
{
    public function Render(): string
    {
        return $this->Visible ? $this->RenderChildren() : '';
    }
}
