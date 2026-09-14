<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ControlBuilder;
use Common\WebForms\EntityEvents;
use Common\WebForms\Page;
use Common\WebForms\PageParser;
use Common\WebForms\ViewState;

/**
 * Notify() con un oggetto dietro: arriva all'handler di chi e' iscritto, com'era, e firmato.
 *
 * Il giro vero e' server -> hub -> browser -> postback -> server, e qui se ne provano i due
 * capi che sono PHP: il messaggio che parte (Compose) e quello che torna (il postback __push
 * con i dati). In mezzo il browser riporta il pacchetto com'e', e se lo tocca la firma non
 * regge e l'handler gira senza dati.
 */
class ProveEventiConDati
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('eventi con dati: un oggetto da un server all\'altro');

        // --- il messaggio che parte

        $utente = new class {
            public string $Nome = 'Anna';
            public string $Cognome = 'Bianchi';
            public string $Email = 'anna@esempio.it';
            public string $Immagine = 'data:image/svg+xml;base64,PHN2Zy8+';
            private string $segreto = 'non deve uscire';
        };

        EntityEvents::Notify('Utente', dati: $utente);
        EntityEvents::Notify('Categorie');

        $messaggio = json_decode((string)EntityEvents::Compose(), true);

        $p->Uguale('i topic ci sono tutti e due', ['Utente', 'Categorie'], $messaggio['t']);
        $p->Uguale('i dati ci sono solo per il topic che li porta', ['Utente'], array_keys($messaggio['d']));
        $p->Uguale('e la coda si e\' svuotata', null, EntityEvents::Compose());

        $pacchetto = $messaggio['d']['Utente'];

        $p->Uguale('il pacchetto e\' firmato come il ViewState: si riapre solo con la chiave',
            true, ViewState::Unpack($pacchetto) !== null);

        $dati = EntityEvents::Dati('Utente', $pacchetto);

        $p->Uguale('l\'oggetto arriva come array con le proprieta\' pubbliche',
            ['Nome' => 'Anna', 'Cognome' => 'Bianchi', 'Email' => 'anna@esempio.it', 'Immagine' => 'data:image/svg+xml;base64,PHN2Zy8+'],
            $dati);

        $p->Uguale('quelle private non escono', false, array_key_exists('segreto', $dati));

        // --- il pacchetto non si tocca

        $p->Uguale('un pacchetto manomesso non da\' dati', [], EntityEvents::Dati('Utente', substr($pacchetto, 0, -4) . 'AAAA'));
        $p->Uguale('un pacchetto buono ma di un altro topic non da\' dati', [], EntityEvents::Dati('Categorie', $pacchetto));
        $p->Uguale('spazzatura non da\' dati', [], EntityEvents::Dati('Utente', 'ciao'));

        // --- un array e uno scalare passano uguale

        EntityEvents::Notify('Numero', dati: 42);
        EntityEvents::Notify('Elenco', dati: ['a', 'b']);

        $m = json_decode((string)EntityEvents::Compose(), true);

        $p->Uguale('uno scalare arriva sotto la chiave valore', ['valore' => 42], EntityEvents::Dati('Numero', $m['d']['Numero']));
        $p->Uguale('un array arriva com\'e\'', ['a', 'b'], EntityEvents::Dati('Elenco', $m['d']['Elenco']));

        // --- un secondo Notify senza dati non cancella i dati del primo

        EntityEvents::Notify('Utente', dati: ['Nome' => 'Marco']);
        EntityEvents::Notify('Utente');

        $m = json_decode((string)EntityEvents::Compose(), true);

        $p->Uguale('il topic e\' uno', ['Utente'], $m['t']);
        $p->Uguale('e i dati sono rimasti', ['Nome' => 'Marco'], EntityEvents::Dati('Utente', $m['d']['Utente']));

        // --- il capo che riceve: il postback __push porta i dati all'handler iscritto

        $pagina = self::PaginaIscritta();

        EntityEvents::Notify('Utente', dati: $utente);

        $m = json_decode((string)EntityEvents::Compose(), true);

        self::Push($pagina, ['t' => ['Utente'], 'd' => ['Utente' => $m['d']['Utente']]]);

        $p->Uguale('l\'handler iscritto riceve l\'oggetto come array', 'Anna Bianchi', $pagina->Ricevuto);

        //la forma vecchia, solo un elenco di nomi, si accetta ancora
        $pagina = self::PaginaIscritta();

        self::Push($pagina, ['Utente']);

        $p->Uguale('senza dati l\'handler gira con un array vuoto', 'nessuno', $pagina->Ricevuto);

        //un pacchetto toccato dal browser: l'handler gira, ma senza dati
        $pagina = self::PaginaIscritta();

        self::Push($pagina, ['t' => ['Utente'], 'd' => ['Utente' => 'v1.finto.firma']]);

        $p->Uguale('un pacchetto finto non porta niente all\'handler', 'nessuno', $pagina->Ricevuto);

        // --- la radice dice a cosa e' iscritta la pagina

        $p->Contiene('la radice porta i topic iscritti, cosi\' il browser fa il postback solo per quelli',
            'data-dw-topics="[&quot;Utente&quot;]"', (new \ReflectionMethod($pagina, 'RenderPage'))->invoke($pagina));
    }

    private static function PaginaIscritta(): Page
    {
        $pagina = new class extends Page {
            public string $Ricevuto = '';

            public function Iscriviti(): void
            {
                $this->Subscribe('Utente', function (array $dati): void
                {
                    $this->Ricevuto = $dati === [] ? 'nessuno' : trim(($dati['Nome'] ?? '') . ' ' . ($dati['Cognome'] ?? ''));
                });
            }
        };

        $albero = ControlBuilder::Build(PageParser::ParseTesto('<dw:Label id="lbl" />'), $pagina);

        (new \ReflectionProperty(Page::class, 'Controls'))->setValue($pagina, $albero);
        (new \ReflectionProperty(Page::class, 'markupRoot'))->setValue($pagina, $albero);

        $pagina->Iscriviti();

        return $pagina;
    }

    /** Il postback di notifica come lo manda il runtime: __dw_target = __push, __dw_arg = json. */
    private static function Push(Page $pagina, array $arg): void
    {
        $_POST = ['__dw_target' => '__push', '__dw_arg' => json_encode($arg)];

        (new \ReflectionMethod($pagina, 'ProcessPostBackEvent'))->invoke($pagina);

        $_POST = [];
    }
}
