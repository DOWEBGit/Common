<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;
use Common\WebForms\Upload;

/**
 * Campo di caricamento, con area di trascinamento.
 *
 * Il file NON viaggia nel postback: viene caricato a parte su FileUploadHandler.php, che lo mette da
 * parte e restituisce un token. Qui dentro passa solo il token, quindi lo stato della pagina
 * resta leggero anche con immagini da megabyte.
 *
 * Il codebehind vede HasFile, FileName e Bytes() e non deve sapere niente di tutto questo.
 */
class FileUpload extends Control
{
    /** Filtro del selettore di file, come l'attributo accept dell'HTML. */
    public string $Accept = 'image/*';

    /**
     * Area di trascinamento.
     *
     * Spenta di default, e il controllo rende il solo <input type="file"> come in WebForms:
     * la zona di drop ha bisogno di un <div> attorno, e un elemento in piu' nel markup non
     * si aggiunge a chi non l'ha chiesto.
     */
    public bool $AllowDrop = false;

    /** Testo dell'area di trascinamento. Ignorato se AllowDrop e' falso. */
    public string $Text = 'Trascina qui un file, o clicca per sceglierlo';

    public bool $Enabled = true;

    /** Handler chiamato appena il file e' stato messo da parte: serve per l'anteprima. */
    public string $OnFileUploaded = '';

    /** Token del file messo da parte. E' l'unica cosa che attraversa il postback. */
    public string $Token = '';

    /**
     * Il campo del Model a cui questo file appartiene: "Model\AllegatiOrdine::Documento".
     *
     * Da li' il controllo legge le regole - estensioni ammesse e peso massimo - che sono
     * quelle dichiarate nel PANNELLO e copiate sul Model dal generatore. Non ne ha di
     * proprie, e non c'e' un secondo elenco da tenere allineato.
     *
     * Il controllo non sta nell'attributo accept: quello e' il filtro del selettore di file,
     * lo applica il browser e si aggira trascinando. Qui invece si rifiuta appena il token
     * arriva, prima che la pagina lo veda; e l'ultima parola resta a Kestrel, che
     * ricontrolla al salvataggio.
     *
     * Senza Vincoli il campo accetta tutto quello che il canale sa riconoscere: va bene per
     * una vetrina, non per un campo che finisce in un database.
     */
    public string $Vincoli = '';

    /** Perche' il file e' stato rifiutato. Vuoto se non c'e' niente da dire. */
    public string $Errore = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Accept', 'AllowDrop', 'Text', 'Enabled', 'OnFileUploaded', 'Token', 'Vincoli']
        );
    }

    public function HasFile(): bool
    {
        return Upload::Info($this->Token) !== null;
    }

    public function FileName(): string
    {
        $info = Upload::Info($this->Token);

        return $info === null ? '' : (string)$info['nome'];
    }

    public function Size(): int
    {
        $info = Upload::Info($this->Token);

        return $info === null ? 0 : (int)$info['dimensione'];
    }

    /** Il contenuto del file, letto dal temporaneo solo quando serve davvero. */
    public function Bytes(): ?string
    {
        return Upload::Contenuto($this->Token);
    }

    /** Dopo il salvataggio: butta il temporaneo e dimentica il token. */
    public function Clear(): void
    {
        Upload::Consuma($this->Token);

        $this->Token = '';
    }

    public function LoadPostData(array $post): void
    {
        if (!array_key_exists($this->Id . '__token', $post))
            return;

        $this->Token = (string)$post[$this->Id . '__token'];

        $this->Errore = '';

        $info = Upload::Info($this->Token);

        if ($info === null)
            return;

        //si ricontrolla anche qui e non solo al caricamento: quello passa dal client, che
        //potrebbe aver dichiarato i vincoli di un altro campo. Qui il riferimento arriva dal
        //markup, e il markup non lo scrive il browser
        $this->Errore = Upload::ControllaVincoli($info, $this->Vincoli);

        if ($this->Errore !== '')
            $this->Clear();
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if ($evento === 'upload' && $this->OnFileUploaded !== '')
            $this->Page->InvokeHandler($this->OnFileUploaded, $this, $argomento);
    }

    public function Render(): string
    {
        //il token viaggia in un campo a parte: e' l'unica cosa che il postback porta con se'
        $nascosto = '<input type="hidden" name="' . self::HtmlEncode($this->Id) . '__token"'
            . ' value="' . self::HtmlEncode($this->Token) . '">';

        $marcatori = ' data-dw-upload="1" data-dw-id="' . self::HtmlEncode($this->Id) . '"'
            . ($this->Vincoli === '' ? '' : ' data-dw-vincoli="' . self::HtmlEncode($this->Vincoli) . '"');

        $scegli = '<input type="file" accept="' . self::HtmlEncode($this->Accept) . '"'
            . ($this->Enabled ? '' : ' disabled');

        if (!$this->AllowDrop)
            return $nascosto . $scegli . $this->RenderAttributes() . $marcatori . '>';

        //con il trascinamento serve un contenitore su cui appoggiare gli eventi e lo stile:
        //li' finiscono id e marcatori, e l'input diventa una superficie trasparente sopra
        return '<div' . $this->RenderAttributes()
            . ($this->CssClass === '' ? ' class="dw-drop"' : '')
            . $marcatori . ' data-dw-drop="1">'
            . $nascosto
            . $scegli . '>'
            . '<span class="dw-drop-testo">' . self::HtmlEncode($this->Text) . '</span>'
            . '</div>';
    }
}
