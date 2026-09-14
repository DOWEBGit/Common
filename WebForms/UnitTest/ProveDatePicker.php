<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Control;
use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\DatePicker;
use Common\WebForms\DateTimeMode;
use Common\WebForms\Page;
use Common\WebForms\PageParser;
use Common\WebForms\ViewState;

/**
 * Il DatePicker: date vere in ingresso e in uscita, il calendario del browser in mezzo.
 *
 * Quello che si tiene fermo: Mode decide il type dell'input e il formato del valore; Value e'
 * una DateTimeImmutable e non una stringa; quello che arriva dal browser si rilegge come data
 * e si riscrive - la spazzatura diventa vuoto, l'ora di troppo cade; l'evento scatta al
 * cambio; e l'enum attraversa il markup e lo stato.
 */
class ProveDatePicker
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('DatePicker');

        // --- il tipo dell'input segue Mode

        $dt = self::Dal('<dw:DatePicker id="dt" />');

        $p->Uguale('senza dire niente e\' una data', DateTimeMode::Date, $dt->Mode);
        $p->Contiene('e rende un input type="date"', 'type="date"', $dt->Render());

        $dt = self::Dal('<dw:DatePicker id="dt" Mode="DateTime" />');

        $p->Uguale('Mode="DateTime" dal markup', DateTimeMode::DateTime, $dt->Mode);
        $p->Contiene('rende un input type="datetime-local"', 'type="datetime-local"', $dt->Render());

        $p->Uguale('il nome del caso si legge senza badare alle maiuscole',
            DateTimeMode::DateTime, self::Dal('<dw:DatePicker id="dt" Mode="datetime" />')->Mode);

        $p->Solleva('un Mode che non esiste si ferma al markup, con i due nomi buoni',
            'vale Date, DateTime',
            static fn() => self::Dal('<dw:DatePicker id="dt" Mode="Ora" />'));

        // --- Value e' una data, non una stringa

        $dt = new DatePicker();
        $dt->Id = 'dt';

        $p->Uguale('vuoto: Value e\' null', null, $dt->Value);

        $dt->Value = new \DateTimeImmutable('2026-09-14 10:30:45');

        $p->Uguale('in modo Date il testo e\' il giorno, e basta', '2026-09-14', $dt->Text);
        $p->Contiene('e cosi\' esce nell\'input', 'value="2026-09-14"', $dt->Render());
        $p->Uguale('rileggendo Value si ha una data a mezzanotte', '2026-09-14 00:00:00', $dt->Value->format('Y-m-d H:i:s'));

        $dt->Mode = DateTimeMode::DateTime;
        $dt->Value = new \DateTimeImmutable('2026-09-14 10:30:45');

        $p->Uguale('in modo DateTime il testo porta l\'ora al minuto, come il browser', '2026-09-14T10:30', $dt->Text);
        $p->Uguale('e Value la rilegge', '2026-09-14 10:30', $dt->Value->format('Y-m-d H:i'));

        $dt->Value = null;

        $p->Uguale('null svuota', '', $dt->Text);

        // --- cambiare Mode dopo il valore: il render si adegua

        $dt = new DatePicker();
        $dt->Id   = 'dt';
        $dt->Mode = DateTimeMode::DateTime;
        $dt->Value = new \DateTimeImmutable('2026-09-14 10:30');

        $dt->Mode = DateTimeMode::Date;

        $p->Contiene('passando a Date il valore esce senza ora, che il browser rifiuterebbe',
            'type="date" name="dt" value="2026-09-14"', $dt->Render());

        // --- Min e Max

        $dt = new DatePicker();
        $dt->Id  = 'dt';
        $dt->Min = new \DateTimeImmutable('2026-01-01');
        $dt->Max = new \DateTimeImmutable('2026-12-31');

        $p->Contiene('min e max escono nel formato dell\'input', 'min="2026-01-01" max="2026-12-31"', $dt->Render());
        $p->Uguale('e si rileggono come date', '2026-12-31', $dt->Max->format('Y-m-d'));

        // --- quello che arriva dal browser

        $dt = new DatePicker();
        $dt->Id = 'dt';

        $dt->LoadPostData(['dt' => '2026-09-14']);

        $p->Uguale('una data buona entra', '2026-09-14', $dt->Value->format('Y-m-d'));

        $dt->LoadPostData(['dt' => '2026-09-14T10:30']);

        $p->Uguale('un\'ora mandata a un controllo che vuole il giorno cade, non rompe', '2026-09-14', $dt->Text);

        $dt->LoadPostData(['dt' => 'ieri sera']);

        $p->Uguale('quello che non e\' una data diventa vuoto', '', $dt->Text);
        $p->Uguale('e Value e\' null', null, $dt->Value);

        $dt->LoadPostData(['dt' => '2026-02-30']);

        $p->Uguale('il 30 febbraio non esiste: vuoto, non il 2 marzo', '', $dt->Text);

        $dt->LoadPostData(['altro' => 'x']);

        $p->Uguale('se il campo non e\' nel POST non si tocca niente', '', $dt->Text);

        $dt->Mode = DateTimeMode::DateTime;

        $dt->LoadPostData(['dt' => '2026-09-14 10:30:00']);

        $p->Uguale('lo spazio e i secondi di un database si accettano e si normalizzano',
            '2026-09-14T10:30', $dt->Text);

        // --- l'evento

        $pagina = new class extends Page {
            public ?DatePicker $Chiamato = null;

            protected function DataCambiata(Control $sender): void
            {
                $this->Chiamato = $sender;
            }
        };

        $dt = self::Dal('<dw:DatePicker id="dt" AutoPostBack="true" OnDateChanged="DataCambiata" />', $pagina);

        $p->Contiene('con AutoPostBack l\'input porta il marcatore del cambio', 'data-dw-change="1"', $dt->Render());

        $dt->RaisePostBackEvent('change', '');

        $p->Uguale('al cambio scatta l\'handler, con il controllo come sender', $dt, $pagina->Chiamato);

        $pagina->Chiamato = null;

        $dt->RaisePostBackEvent('click', '');

        $p->Uguale('un altro evento non lo scatena', null, $pagina->Chiamato);

        $p->Manca('senza AutoPostBack niente marcatore', 'data-dw-change',
            self::Dal('<dw:DatePicker id="dt" OnDateChanged="DataCambiata" />', $pagina)->Render());

        // --- lo stato: l'enum e la data attraversano il pacchetto vero

        $dt = new DatePicker();
        $dt->Id    = 'dt';
        $dt->Mode  = DateTimeMode::DateTime;
        $dt->Value = new \DateTimeImmutable('2026-09-14 10:30');
        $dt->Min   = new \DateTimeImmutable('2026-01-01');

        $stato = ViewState::Unpack(ViewState::Pack($dt->SaveViewState()));

        $p->Uguale('nel pacchetto l\'enum viaggia come il suo valore, non come oggetto',
            'datetime-local', $stato['Mode']);

        $tornato = new DatePicker();
        $tornato->LoadViewState($stato);

        $p->Uguale('e torna come enum', DateTimeMode::DateTime, $tornato->Mode);
        $p->Uguale('con la data', '2026-09-14 10:30', $tornato->Value->format('Y-m-d H:i'));
        $p->Uguale('e il minimo', '2026-01-01', $tornato->Min->format('Y-m-d'));

        // --- disabilitato

        $dt->Enabled = false;

        $p->Contiene('disabilitato esce disabled', ' disabled', $dt->Render());
    }

    private static function Dal(string $markup, ?Page $pagina = null): DatePicker
    {
        /** @var DatePicker $dt */
        $dt = ControlBuilder::Build(PageParser::ParseTesto($markup), $pagina)[0];

        return $dt;
    }
}
