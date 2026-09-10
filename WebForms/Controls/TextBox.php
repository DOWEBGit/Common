<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

class TextBox extends Control
{
    public string $Text = '';

    public string $Placeholder = '';

    public string $Type = 'text';

    /** Come in WebForms: SingleLine, MultiLine (textarea), Password. */
    public const SINGLELINE = 'SingleLine';
    public const MULTILINE  = 'MultiLine';
    public const PASSWORD   = 'Password';

    public string $TextMode = self::SINGLELINE;

    public int $Rows = 4;

    public bool $Enabled = true;

    /** Se true il cambio di valore fa partire un postback. */
    public bool $AutoPostBack = false;

    /**
     * Millisecondi di quiete prima del postback, per far scattare l'evento MENTRE si scrive
     * invece che all'uscita dal campo.
     *
     * 0 (predefinito) = si aspetta il blur, come in WebForms. Con un valore > 0 si ascolta
     * ogni tasto ma si parte solo dopo quella pausa: un postback per carattere sarebbe una
     * richiesta HTTP, una ricostruzione dell'albero e un render per ogni lettera digitata.
     * Sotto i ~200 ms si torna praticamente li'; 300-400 e' la finestra onesta.
     */
    public int $AutoPostBackDelay = 0;

    public string $OnTextChanged = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Text', 'Placeholder', 'Type', 'TextMode', 'Rows', 'Enabled',
             'AutoPostBack', 'AutoPostBackDelay', 'OnTextChanged']
        );
    }

    public function LoadPostData(array $post): void
    {
        if (array_key_exists($this->Id, $post))
            $this->Text = (string)$post[$this->Id];
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        //"input" e "change" sono lo stesso evento per chi scrive la pagina: cambia solo
        //quando il runtime decide che l'utente ha finito di digitare
        if (($evento === 'change' || $evento === 'input') && $this->OnTextChanged !== '')
            $this->Page->InvokeHandler($this->OnTextChanged, $this, $argomento);
    }

    public function Render(): string
    {
        if ($this->TextMode === self::MULTILINE)
            return $this->RenderArea();

        $html = '<input' . $this->RenderAttributes()
            . ' type="' . self::HtmlEncode($this->TextMode === self::PASSWORD ? 'password' : $this->Type) . '"'
            . ' name="' . self::HtmlEncode($this->Id) . '"'
            . ' value="' . self::HtmlEncode($this->Text) . '"';

        if ($this->Placeholder !== '')
            $html .= ' placeholder="' . self::HtmlEncode($this->Placeholder) . '"';

        if (!$this->Enabled)
            $html .= ' disabled';

        if ($this->AutoPostBack)
        {
            //due eventi diversi: "input" scatta ad ogni tasto ed e' il debounce a trattenerlo,
            //"change" scatta all'uscita dal campo e non ha niente da trattenere
            $html .= $this->AutoPostBackDelay > 0
                ? $this->PostBackAttribute('input') . ' data-dw-delay="' . $this->AutoPostBackDelay . '"'
                : $this->PostBackAttribute('change');
        }

        return $html . '>';
    }

    /**
     * Il testo di una textarea sta fra i tag, non in un attributo: va escapato lo stesso,
     * altrimenti un </textarea> dentro il contenuto chiude il campo e il resto della pagina
     * finisce fuori posto.
     */
    private function RenderArea(): string
    {
        $html = '<textarea' . $this->RenderAttributes()
            . ' name="' . self::HtmlEncode($this->Id) . '"'
            . ' rows="' . max(2, $this->Rows) . '"';

        if ($this->Placeholder !== '')
            $html .= ' placeholder="' . self::HtmlEncode($this->Placeholder) . '"';

        if (!$this->Enabled)
            $html .= ' disabled';

        if ($this->AutoPostBack)
            $html .= $this->AutoPostBackDelay > 0
                ? $this->PostBackAttribute('input') . ' data-dw-delay="' . $this->AutoPostBackDelay . '"'
                : $this->PostBackAttribute('change');

        return $html . '>' . self::HtmlEncode($this->Text) . '</textarea>';
    }
}
