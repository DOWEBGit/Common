<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Un controllo composto, con markup e codebehind propri: l'equivalente di un .ascx.
 *
 * Tre file come una pagina, e per lo stesso motivo:
 *
 *   UserControls/PageNavigator.php           markup
 *   UserControls/PageNavigator.code.php      classe \UserControls\PageNavigator
 *   UserControls/PageNavigator.designer.php  generato
 *
 * Si mette in una pagina cosi':
 *
 *   <dw:UserControl id="pgSopra" src="UserControls/PageNavigator" OnPageChanged="PaginaCambiata" />
 *
 * Tre proprieta' lo rendono utile:
 *
 * 1. E' un CONTENITORE DI DENOMINAZIONE. Gli id dei suoi figli vengono qualificati con il
 *    suo, cosi' due istanze dello stesso controllo nella stessa pagina - un paginatore
 *    sopra e uno sotto - non si pestano gli id. Dentro, si continua a scrivere
 *    $this->lnkNext senza sapere niente di questo.
 *
 * 2. HA UN CICLO DI VITA suo: OnInit, OnLoad, OnPreRender, chiamati dalla pagina nelle
 *    stesse fasi in cui chiama i propri.
 *
 * 3. PARLA CON LA PAGINA PER EVENTI, non per nome. Il markup della pagina dichiara
 *    OnPageChanged="PaginaCambiata" e il controllo chiama RaiseHostEvent('OnPageChanged'):
 *    non sa chi lo ospita ne' come si chiama il metodo, quindi si riusa altrove.
 */
abstract class UserControl extends Control
{
    /** Tag dell'elemento che avvolge il controllo. */
    public string $Tag = 'div';

    /** @var array<string,string> nome evento del markup => metodo della pagina ospite */
    private array $hostHandlers = [];

    // ---------------------------------------------------------------- ciclo di vita

    public function OnInit(): void
    {
    }

    public function OnLoad(): void
    {
    }

    public function OnPreRender(): void
    {
    }

    // ---------------------------------------------------------------- eventi verso la pagina

    /**
     * Gli attributi On... del markup diventano gli eventi che questo controllo puo'
     * sollevare verso la pagina.
     */
    public function BindHostHandlers(array $attr): void
    {
        foreach ($attr as $nome => $valore)
            if (str_starts_with($nome, 'On') && $valore !== '')
                $this->hostHandlers[$nome] = $valore;
    }

    /**
     * Solleva verso la pagina ospite l'evento dichiarato nel suo markup.
     *
     * Se la pagina non lo ha dichiarato non succede niente: un paginatore messo li' per
     * far vedere i numeri e' legittimo, e non deve rompersi perche' nessuno lo ascolta.
     */
    protected function RaiseHostEvent(string $eventName, string $argument = ''): void
    {
        if (!isset($this->hostHandlers[$eventName]))
            return;

        $this->Page->InvokeHandler($this->hostHandlers[$eventName], $this, $argument);
    }

    /**
     * Popola le proprieta' del designer con i propri figli.
     *
     * Va chiamata PRIMA che gli id vengano qualificati: le proprieta' si chiamano come nel
     * markup ("lnkFirst"), non come finiranno nell'HTML ("lnkFirst__pgSopra"). Gli oggetti
     * sono gli stessi, quindi qualificarli dopo non invalida i riferimenti.
     */
    public function BindDesignerFields(): void
    {
        foreach ($this->Controls as $child)
            $this->BindDesignerField($child);
    }

    private function BindDesignerField(Control $control): void
    {
        if ($control->Id !== '' && property_exists($this, $control->Id))
            $this->{$control->Id} = $control;

        foreach ($control->Controls as $child)
            $this->BindDesignerField($child);
    }

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Tag']);
    }

    /**
     * Gli handler dichiarati dalla pagina fanno parte dello stato: senza, al postback il
     * controllo non saprebbe piu' chi avvisare e l'evento si perderebbe in silenzio.
     */
    public function SaveViewState(): array
    {
        $state = parent::SaveViewState();

        $state['__host'] = $this->hostHandlers;

        return $state;
    }

    public function LoadViewState(array $state): void
    {
        parent::LoadViewState($state);

        $this->hostHandlers = $state['__host'] ?? [];
    }

    public function Render(): string
    {
        $tag = preg_match('/^[a-z][a-z0-9]*$/', $this->Tag) === 1 ? $this->Tag : 'div';

        return '<' . $tag . $this->RenderAttributes() . '>' . $this->RenderChildren() . '</' . $tag . '>';
    }
}
