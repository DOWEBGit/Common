<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;
use Common\WebForms\DateTimeMode;

/**
 * Un selettore di data, o di data e ora, con il calendario nativo del browser.
 *
 *     <dw:DatePicker id="dtDal" Mode="Date" AutoPostBack="true" OnDateChanged="DalCambiato" />
 *
 *     $this->dtDal->Value = new \DateTimeImmutable('2026-09-14');
 *     $quando = $this->dtDal->Value;   // ?DateTimeImmutable, null se vuoto
 *
 * Si parla in DATE, non in stringhe: Value, Min e Max sono DateTimeImmutable, e chi scrive la
 * pagina non deve sapere come il browser scrive un giorno. Il formato lo decide Mode - solo
 * data, o data e ora al minuto - ed e' l'unica cosa che cambia fra <input type="date"> e
 * <input type="datetime-local">.
 *
 * Nello stato viaggia il TESTO, nel formato dell'input, non l'oggetto: il pacchetto si riapre
 * senza classi, di proposito. Value e' una vista su quel testo, letta e scritta al volo.
 *
 * Quello che arriva dal browser si rilegge come data e si riscrive: un valore che non e' una
 * data - dalla console, da uno script - diventa vuoto invece di finire in pagina com'e'; e
 * una data con l'ora mandata a un controllo che vuole solo il giorno perde l'ora, invece di
 * essere rifiutata dal browser e sparire in silenzio.
 */
class DatePicker extends Control
{
    /**
     * Il valore com'e' scritto nell'input: "2026-09-14" o "2026-09-14T10:30". E' quello che
     * sta nello stato; chi scrive la pagina passa da Value.
     */
    public string $Text = '';

    /** Solo la data, o data e ora. Nel markup: Mode="Date" o Mode="DateTime". */
    public DateTimeMode $Mode = DateTimeMode::Date;

    /** Il primo e l'ultimo giorno scelto: il browser non fa scegliere fuori. Vuoti = senza limite. */
    public string $MinText = '';

    public string $MaxText = '';

    public bool $Enabled = true;

    /** Se true, scegliere una data fa partire un postback e scatta OnDateChanged. */
    public bool $AutoPostBack = false;

    /**
     * Handler di pagina chiamato quando la data cambia:
     *
     *     protected function DalCambiato(DatePicker $sender): void
     */
    public string $OnDateChanged = '';

    /**
     * La data scelta, null se il campo e' vuoto.
     *
     * Il set e' a blocco e non a freccia di proposito: "set => espressione" assegnerebbe il
     * risultato alla proprieta' stessa, e qui la proprieta' non ha niente dietro - e' una
     * vista su Text. Con il blocco resta virtuale, ed e' quello che si vuole.
     */
    public ?\DateTimeImmutable $Value {
        get => self::Leggi($this->Text);
        set { $this->Text = $this->Scrivi($value); }
    }

    public ?\DateTimeImmutable $Min {
        get => self::Leggi($this->MinText);
        set { $this->MinText = $this->Scrivi($value); }
    }

    public ?\DateTimeImmutable $Max {
        get => self::Leggi($this->MaxText);
        set { $this->MaxText = $this->Scrivi($value); }
    }

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Text', 'Mode', 'MinText', 'MaxText', 'Enabled', 'AutoPostBack', 'OnDateChanged']
        );
    }

    public function LoadPostData(array $post): void
    {
        if (!array_key_exists($this->Id, $post))
            return;

        //si rilegge come data e si riscrive nel formato di questo Mode: quello che non e' una
        //data diventa vuoto, e un'ora mandata a un controllo che vuole il giorno cade
        $this->Value = self::Leggi((string)$post[$this->Id]);
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if ($evento === 'change' && $this->OnDateChanged !== '')
            $this->Page->InvokeHandler($this->OnDateChanged, $this, $argomento);
    }

    public function Render(): string
    {
        //il testo si rinormalizza al formato del Mode di ADESSO: se il Mode e' cambiato dopo
        //che il valore era stato scritto, il browser rifiuterebbe "2026-09-14T10:30" in un
        //type="date" e mostrerebbe il campo vuoto senza dire niente
        $html = '<input' . $this->RenderAttributes()
            . ' type="' . $this->Mode->value . '"'
            . ' name="' . self::HtmlEncode($this->Id) . '"'
            . ' value="' . self::HtmlEncode($this->Scrivi($this->Value)) . '"';

        if ($this->Min !== null)
            $html .= ' min="' . self::HtmlEncode($this->Scrivi($this->Min)) . '"';

        if ($this->Max !== null)
            $html .= ' max="' . self::HtmlEncode($this->Scrivi($this->Max)) . '"';

        if (!$this->Enabled)
            $html .= ' disabled';

        if ($this->AutoPostBack)
            $html .= $this->PostBackAttribute('change');

        return $html . '>';
    }

    /**
     * Da testo a data. Accetta i due formati degli input e quelli con i secondi o con lo
     * spazio al posto della T, cosi' ci si puo' scrivere anche il valore di un database.
     * Tutto il resto e' null.
     */
    private static function Leggi(string $testo): ?\DateTimeImmutable
    {
        if ($testo === '')
            return null;

        foreach (['Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $formato)
        {
            //il "!" azzera quello che il formato non nomina: senza, un "Y-m-d" prenderebbe
            //l'ora di adesso e due letture della stessa data darebbero due istanti diversi
            $data = \DateTimeImmutable::createFromFormat('!' . $formato, $testo);

            if ($data !== false && $data->format($formato) === $testo)
                return $data;
        }

        return null;
    }

    /** Da data a testo, nel formato del Mode di adesso. */
    private function Scrivi(?\DateTimeImmutable $data): string
    {
        return $data === null ? '' : $data->format($this->Mode->Format());
    }
}
