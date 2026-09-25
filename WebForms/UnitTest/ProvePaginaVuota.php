<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Control;
use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\Button;
use Common\WebForms\Controls\Label;
use Common\WebForms\Controls\LinkButton;
use Common\WebForms\Controls\Panel;
use Common\WebForms\Controls\Repeater;
use Common\WebForms\Controls\RepeaterItem;
use Common\WebForms\Controls\TextBox;
use Common\WebForms\Page;
use Common\WebForms\PageParser;
use Common\WebForms\ViewState;

/**
 * La pagina vuota: markup senza un controllo, e il codebehind che monta tutto.
 *
 * Un Panel con stile e attributi, una casella, un bottone, un'etichetta, un Repeater con il
 * suo template - tutto attaccato dal codice al primo caricamento - e poi un CRUD intero
 * fatto a postback: crea, legge, aggiorna, elimina. Ogni passo e' un giro vero di stato:
 * Pack, pagina nuova, Unpack - lo stato rientra in un oggetto pagina appena nato, com'e' ogni
 * postback - e alla fine dev'esserci tutto, e funzionare ancora.
 *
 * Lo stesso scenario gira due volte: attaccando alla RADICE della pagina, che non ha nessun
 * tag attorno, e dentro un Panel dichiarato nel markup. Sono due strade diverse nel motore -
 * la radice non e' un Control - e devono dare lo stesso risultato.
 */
class ProvePaginaVuota
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('pagina vuota, tutto dal codice');

        self::Scenario($p, '', '', 'alla radice');
        self::Scenario($p, '<dw:Panel id="cornice" CssClass="c" />', 'cornice', 'dentro un Panel del markup');
    }

    private static function Scenario(Prova $p, string $markup, string $dentro, string $dove): void
    {
        // --- giro 0: primo caricamento, il codice monta tutto

        $pagina = self::Pagina($markup);

        $pagina->Monta($dentro);

        $p->Contiene($dove . ': il pannello montato dal codice si vede, con stile e attributi',
            '<div id="pnl" class="scheda" style="border-left:4px solid teal" data-prova="si">', self::Render($pagina));

        $p->Contiene($dove . ': l\'elenco vuoto ha il suo contenitore', '<tbody id="rpt">', self::Render($pagina));

        // --- CREATE: si scrive nella casella e si preme aggiungi, due volte

        $pagina = self::Giro($pagina, $markup);

        $pagina->FindControl('txtNome')->LoadPostData(['txtNome' => 'Alfa']);
        $pagina->FindControl('btnAggiungi')->RaisePostBackEvent('click', '');

        $pagina = self::Giro($pagina, $markup);

        $pagina->FindControl('txtNome')->LoadPostData(['txtNome' => 'Beta']);
        $pagina->FindControl('btnAggiungi')->RaisePostBackEvent('click', '');

        $p->Uguale($dove . ': create - due righe nella variabile di pagina, senza attributi',
            ['Alfa', 'Beta'], array_column($pagina->Righe, 'Nome'));

        // --- READ: al giro dopo, senza rifare niente, le righe ci sono e sono vestite

        $pagina = self::Giro($pagina, $markup);

        $html = self::Render($pagina);

        $p->Contiene($dove . ': read - la riga 1 e\' resa', '<td>Alfa</td>', $html);
        $p->Contiene($dove . ': read - la riga 2 e\' resa', '<td>Beta</td>', $html);
        $p->Contiene($dove . ': read - il colore dato in OnItemDataBound e\' rimasto',
            'style="color:crimson"', $html);
        $p->Contiene($dove . ': read - e il title del link di riga anche',
            'title="Modifica Beta"', $html);
        $p->Uguale($dove . ': read - l\'etichetta di riepilogo dice 2', '2 righe', $pagina->FindControl('lblEsito')->Text);

        // --- UPDATE: si preme modifica sulla 2, si cambia il nome, si salva

        $pagina->FindControl('lnkModifica__2')->RaisePostBackEvent('click', '');

        $p->Uguale($dove . ': update - la casella si riempie col nome da modificare',
            'Beta', $pagina->FindControl('txtNome')->Text);

        $pagina = self::Giro($pagina, $markup);

        $p->Uguale($dove . ': update - la pagina ricorda cosa sta modificando', 2, $pagina->InModifica);

        $pagina->FindControl('txtNome')->LoadPostData(['txtNome' => 'Beta corretta']);
        $pagina->FindControl('btnAggiungi')->RaisePostBackEvent('click', '');

        $pagina = self::Giro($pagina, $markup);

        $p->Uguale($dove . ': update - la riga e\' cambiata, e non se n\'e\' aggiunta una',
            ['Alfa', 'Beta corretta'], array_column($pagina->Righe, 'Nome'));

        $p->Contiene($dove . ': update - e si vede', '<td>Beta corretta</td>', self::Render($pagina));

        // --- DELETE: via la 1

        $pagina->FindControl('lnkElimina__1')->RaisePostBackEvent('click', '');

        $pagina = self::Giro($pagina, $markup);

        $p->Uguale($dove . ': delete - resta solo la 2', ['Beta corretta'], array_column($pagina->Righe, 'Nome'));

        $p->Manca($dove . ': delete - la riga 1 non si vede piu\'', '<td>Alfa</td>', self::Render($pagina));
        $p->Manca($dove . ': delete - e nemmeno il suo link', 'lnkElimina__1', self::Render($pagina));

        // --- CAMBIO PAGINA E RITORNO: lo stato rientra in una pagina appena nata

        $primaDiUscire = self::Render($pagina);

        $tornata = self::Giro($pagina, $markup);

        $p->Uguale($dove . ': tornando, la pagina e\' identica a com\'era', $primaDiUscire, self::Render($tornata));

        $p->Uguale($dove . ': tornando, la variabile di pagina c\'e\'', ['Beta corretta'], array_column($tornata->Righe, 'Nome'));
        $p->Uguale($dove . ': tornando, il contatore degli id non e\' ripartito', 2, $tornata->Ultimo);

        $p->Contiene($dove . ': tornando, il Repeater dinamico ha ancora il suo template',
            '<td>Beta corretta</td>', self::Render($tornata));

        //e funziona ancora: un'altra riga, e via anche quella
        $tornata->FindControl('txtNome')->LoadPostData(['txtNome' => 'Gamma']);
        $tornata->FindControl('btnAggiungi')->RaisePostBackEvent('click', '');

        $tornata = self::Giro($tornata, $markup);

        $p->Uguale($dove . ': tornando, si puo\' ancora creare', ['Beta corretta', 'Gamma'], array_column($tornata->Righe, 'Nome'));

        $tornata->FindControl('lnkElimina__2')->RaisePostBackEvent('click', '');

        $tornata = self::Giro($tornata, $markup);

        $p->Uguale($dove . ': tornando, si puo\' ancora eliminare', ['Gamma'], array_column($tornata->Righe, 'Nome'));
        $p->Uguale($dove . ': e l\'etichetta segue', '1 riga', $tornata->FindControl('lblEsito')->Text);
    }

    // ---------------------------------------------------------------- il giro di stato

    /** Un postback, o un ritorno sulla pagina: Pack, pagina nuova, Unpack. */
    private static function Giro(PaginaVuota $pagina, string $markup): PaginaVuota
    {
        $pacco = ViewState::Pack((new \ReflectionMethod($pagina, 'SaveViewState'))->invoke($pagina));

        $nuova = self::Pagina($markup);

        (new \ReflectionMethod($nuova, 'LoadViewState'))->invoke($nuova, ViewState::Unpack($pacco));

        return $nuova;
    }

    private static function Pagina(string $markup): PaginaVuota
    {
        $pagina = new PaginaVuota();

        $albero = ControlBuilder::Build(PageParser::ParseTesto($markup), $pagina);

        //quello che fa ProcessRequest dopo aver costruito l'albero
        (new \ReflectionProperty(Page::class, 'Controls'))->setValue($pagina, $albero);
        (new \ReflectionProperty(Page::class, 'markupRoot'))->setValue($pagina, $albero);

        return $pagina;
    }

    private static function Render(Page $pagina): string
    {
        return (new \ReflectionMethod($pagina, 'RenderPage'))->invoke($pagina);
    }
}

/**
 * La pagina del banco: nessun designer, nessun markup, solo codice.
 *
 * Le variabili restano da sole; i controlli si trovano con FindControl perche' il designer
 * non c'e' - non c'e' un markup da cui generarlo.
 */
final class PaginaVuota extends Page
{
    /** @var array<int,array{Id:int,Nome:string}> */
    public array $Righe = [];

    public int $Ultimo = 0;

    /** L'id della riga in modifica, 0 = nessuna. */
    public int $InModifica = 0;

    /** Monta tutto dal codice: alla radice, o dentro il controllo con quell'id. */
    public function Monta(string $dentro): void
    {
        $pnl = new Panel();

        $pnl->Id       = 'pnl';
        $pnl->CssClass = 'scheda';

        $pnl->Style->Add('border-left', '4px solid teal');
        $pnl->Attributes->Add('data-prova', 'si');

        $txt = new TextBox();
        $txt->Id = 'txtNome';

        $btn = new Button();
        $btn->Id      = 'btnAggiungi';
        $btn->Text    = 'Aggiungi';
        $btn->OnClick = 'AggiungiClick';

        $lbl = new Label();
        $lbl->Id   = 'lblEsito';
        $lbl->Text = '0 righe';

        $rpt = new Repeater();
        $rpt->Id              = 'rpt';
        $rpt->Tag             = 'tbody';
        $rpt->ItemTag         = 'tr';
        $rpt->DataKeyField    = 'Id';
        $rpt->OnItemDataBound = 'RigaLegata';
        $rpt->ItemTemplate    = PageParser::ParseTesto(
            '<td>{{Nome}}</td>'
            . '<td><dw:LinkButton id="lnkModifica" Text="modifica" OnClick="ModificaClick" CommandArgument="{{Id}}" /></td>'
            . '<td><dw:LinkButton id="lnkElimina" Text="elimina" OnClick="EliminaClick" CommandArgument="{{Id}}" /></td>'
        );

        $pnl->Add($txt);
        $pnl->Add($btn);
        $pnl->Add($lbl);
        $pnl->Add($rpt);

        if ($dentro === '')
            $this->Add($pnl);
        else
            $this->FindControl($dentro)->Add($pnl);
    }

    // ------------------------------------------------------------ gli handler del CRUD

    protected function AggiungiClick(Control $sender): void
    {
        $nome = trim($this->FindControl('txtNome')->Text);

        if ($nome === '')
            return;

        if ($this->InModifica !== 0)
        {
            foreach ($this->Righe as &$riga)
                if ($riga['Id'] === $this->InModifica)
                    $riga['Nome'] = $nome;

            unset($riga);

            $this->InModifica = 0;
        }
        else
        {
            $this->Righe[] = ['Id' => ++$this->Ultimo, 'Nome' => $nome];
        }

        $this->FindControl('txtNome')->Text = '';

        $this->Rilega();
    }

    protected function ModificaClick(Control $sender, string $id): void
    {
        $this->InModifica = (int)$id;

        foreach ($this->Righe as $riga)
            if ($riga['Id'] === $this->InModifica)
                $this->FindControl('txtNome')->Text = $riga['Nome'];
    }

    protected function EliminaClick(Control $sender, string $id): void
    {
        $this->Righe = array_values(array_filter($this->Righe, static fn($r) => $r['Id'] !== (int)$id));

        $this->Rilega();
    }

    /** Il colore e il title li mette il codice, riga per riga: devono restare. */
    protected function RigaLegata(Repeater $sender, RepeaterItem $riga): void
    {
        $modifica = $riga->FindControl('lnkModifica');

        $modifica->Style->Add('color', 'crimson');
        $modifica->Attributes->Add('title', 'Modifica ' . $riga->DataItem['Nome']);
    }

    private function Rilega(): void
    {
        /** @var Repeater $rpt */
        $rpt = $this->FindControl('rpt');

        $rpt->DataSource = $this->Righe;
        $rpt->DataBind();

        $quante = count($this->Righe);

        $this->FindControl('lblEsito')->Text = $quante . ($quante === 1 ? ' riga' : ' righe');
    }
}
