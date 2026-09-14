<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

class DropDownList extends Control
{
    /** @var array<int|string,string> valore => testo. Una chiave numerica PHP la fa int: si confronta come testo */
    public array $Items = [];

    public string $SelectedValue = '';

    public bool $Enabled = true;

    public bool $AutoPostBack = false;

    public string $OnSelectedIndexChanged = '';

    /** Come sui bottoni: comando verso il contenitore invece di un metodo della pagina. */
    public string $CommandName = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Items', 'SelectedValue', 'Enabled', 'AutoPostBack', 'OnSelectedIndexChanged', 'CommandName']
        );
    }

    public function LoadPostData(array $post): void
    {
        if (!array_key_exists($this->Id, $post))
            return;

        $valore = (string)$post[$this->Id];

        //il valore arriva dal client: si accetta solo se e' una delle voci che il server ha
        //davvero reso, altrimenti la selezione diventa un campo di testo libero
        if (array_key_exists($valore, $this->Items))
            $this->SelectedValue = $valore;
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if ($evento !== 'change')
            return;

        if ($this->OnSelectedIndexChanged !== '')
        {
            $this->Page->InvokeHandler($this->OnSelectedIndexChanged, $this, $argomento);
            return;
        }

        if ($this->CommandName !== '')
            $this->RaiseBubbleEvent($this, $this->CommandName, $this->SelectedValue);
    }

    public function Render(): string
    {
        $html = '<select' . $this->RenderAttributes() . ' name="' . self::HtmlEncode($this->Id) . '"';

        if (!$this->Enabled)
            $html .= ' disabled';

        if ($this->AutoPostBack)
            $html .= $this->PostBackAttribute('change');

        $html .= '>';

        foreach ($this->Items as $valore => $testo)
        {
            //la chiave di un array PHP puo' essere diventata int: si confronta come testo
            $valore = (string)$valore;

            $html .= '<option value="' . self::HtmlEncode($valore) . '"';

            if ($valore === $this->SelectedValue)
                $html .= ' selected';

            $html .= '>' . self::HtmlEncode($testo) . '</option>';
        }

        return $html . '</select>';
    }
}
