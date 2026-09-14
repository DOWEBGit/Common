<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\MasterPage;

/**
 * La cornice degli esempi: un menu a sinistra con tutti i controlli del motore, una pagina
 * per controllo. Il modello e' il sito dell'AjaxControlToolkit: si sceglie a sinistra, a
 * destra c'e' la prova da toccare, le proprieta' e il sorgente.
 *
 * E' anche una prova: una master che vive dentro Common, fuori da qualunque sito, con i suoi
 * controlli nel markup e un'iscrizione a un evento che vale per tutte le pagine che la
 * ereditano - il contatore dei saluti in fondo al menu.
 *
 * L'elenco delle pagine sta qui, in PAGES, ed e' l'unico posto: lo legge il menu, lo legge la
 * pagina Index per le sue schede, e da qui il titolo e il sottotitolo arrivano da soli alla
 * pagina che non li imposta a mano.
 */
class Site extends MasterPage
{
    /**
     * Gruppo => [file senza estensione => [titolo, una riga]].
     *
     * L'ordine e' quello del menu. I nomi dei file sono in inglese, come i nomi delle
     * pagine di un sito; i testi in italiano, come tutto quello che si legge.
     */
    public const array PAGES = [
        'Inizio' => [
            'Index' => ['Panoramica', 'Cosa c\'e\' qui, e come si legge una pagina di esempio.'],
        ],
        'Controlli' => [
            'Literal'        => ['Literal', 'Testo senza un elemento attorno: il controllo piu\' usato.'],
            'Label'          => ['Label', 'Testo in uno <span>, con classe e stile dal codice.'],
            'TextBox'        => ['TextBox', 'Una riga, piu\' righe, password; la ricerca mentre si scrive.'],
            'DatePicker'     => ['DatePicker', 'Il calendario del browser, date vere sul server.'],
            'Button'         => ['Button e LinkButton', 'Il click, la conferma, l\'argomento, il doppio click che non passa.'],
            'CheckBox'       => ['CheckBox', 'Spuntata o no, con postback automatico.'],
            'DropDownList'   => ['DropDownList e ListItem', 'Le voci dal markup o dal codice, la scelta che fa postback.'],
            'ListBox'        => ['ListBox', 'Scelta multipla: tutti i valori, non l\'ultimo.'],
            'HiddenField'    => ['HiddenField', 'Un valore che va e torna, e non e\' un\'autorizzazione.'],
            'Panel'          => ['Panel e PlaceHolder', 'Contenitori: nascondere in un colpo, attaccare dal codice.'],
            'Repeater'       => ['Repeater', 'Le righe dai dati, il template, OnItemDataBound, ClearItems.'],
            'FileUpload'     => ['FileUpload', 'Il file per conto suo, il token nel form, il trascinamento.'],
            'ModalPopup'     => ['ModalPopup', 'Una finestra sopra la pagina: si apre nel browser, la chiude il server.'],
            'Alert'          => ['Alert', 'Salvato, non salvato e perche\': volante o modale.'],
            'UpdateProgress' => ['UpdateProgress', '"Attendere..." solo quando il postback e\' lento davvero.'],
            'Resources'      => ['Stylesheet e Script', 'Un foglio e uno script del sito, con la marca temporale.'],
        ],
        'Motore' => [
            'State'           => ['Lo stato', 'Le variabili restano, #[Portable] attraversa le pagine, il modo WinForms.'],
            'DynamicControls' => ['Controlli creati dal codice', 'In OnInit, in OnLoad, in un handler: restano tutti. Righe di tabella a mano.'],
            'Events'          => ['Eventi fra pagine e browser', 'Notify e Subscribe, un oggetto in viaggio, Broadcast con DW.on.'],
            'Errors'          => ['Gli errori', 'Un\'eccezione in un handler: nel log del sito, e nel riquadro rosso.'],
        ],
    ];

    use SiteDesigner;

    /**
     * L'iscrizione della cornice, ad ogni richiesta: quando un altro browser chiama
     * Notify('Saluti'), il runtime di QUALUNQUE pagina sotto questa cornice fa il suo postback
     * e l'handler gira qui, sul server, con la pagina in mano.
     *
     * Il conteggio sta nel Literal, non in una proprieta' della classe: e' lo stato di un
     * controllo, quindi torna a ogni postback e resta nel cassetto quando si cambia pagina.
     */
    public function OnInit(): void
    {
        $this->Page->Subscribe('Saluti', function (): void
        {
            $arrivati = (int)$this->__Literal_Greetings->Text + 1;

            $this->__Literal_Greetings->Text = (string)$arrivati;
            $this->__Panel_Greetings->Visible = true;

            $this->Page->Alert->Success('Qualcuno ha salutato alle ' . date('H:i:s') . ': lo dice la cornice, su qualunque pagina.');
        });
    }

    public function OnPreRender(): void
    {
        $qui = self::Current();

        $this->__Literal_Menu->Text = $this->Menu($qui);

        //il titolo dall'elenco, se la pagina non ne ha messo uno suo
        if ($this->__Literal_Title->Text === '')
        {
            $voce = self::Entry($qui);

            $this->SetTitle($voce[0] ?? $qui, $voce[1] ?? '');
        }

        //l'ora del SERVER, non del browser: se cambia navigando vuol dire che la pagina e'
        //stata chiesta davvero, e non ricomposta da qualcosa che il client aveva in mano
        $this->__Literal_RenderedAt->Text = date('H:i:s');
    }

    /** Titolo e sottotitolo, per la pagina che vuole dire qualcosa di diverso dall'elenco. */
    public function SetTitle(string $title, string $lead = ''): void
    {
        $this->__Literal_Title->Text = $title;

        $this->__Literal_Lead->Text    = $lead;
        $this->__Literal_Lead->Visible = $lead !== '';

        if ($this->Page->Title === '')
            $this->Page->Title = $title . ' — WebForms';
    }

    /** Il nome della pagina corrente, senza estensione: e' la chiave di PAGES. */
    public static function Current(): string
    {
        return basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '.php');
    }

    /** @return string[] [titolo, una riga] della pagina, o vuoto se non e' nell'elenco */
    public static function Entry(string $page): array
    {
        foreach (self::PAGES as $voci)
            if (isset($voci[$page]))
                return $voci[$page];

        return [];
    }

    /**
     * Il menu, con la voce corrente marcata.
     *
     * Esce come HTML gia' pronto in un Literal in PassThrough: sono <a> senza niente di
     * dinamico dentro, e altrettanti LinkButton sarebbero altrettanti postback al posto di
     * navigazioni.
     */
    private function Menu(string $qui): string
    {
        $html = '';

        foreach (self::PAGES as $gruppo => $voci)
        {
            $html .= '<div class="ex-group">' . Control::HtmlEncode($gruppo) . '</div>';

            foreach ($voci as $file => [$titolo])
                $html .= '<a class="ex-voce' . ($file === $qui ? ' ex-qui' : '') . '" href="' . $file . '.php">'
                    . Control::HtmlEncode($titolo) . '</a>';
        }

        return $html;
    }
}
