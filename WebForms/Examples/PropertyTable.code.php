<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\UserControl;

/**
 * La tabella delle proprieta' di un controllo, letta dalla classe: nome, tipo, valore
 * predefinito e il commento scritto sopra la proprieta'.
 *
 *     <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Button,LinkButton" />
 *
 * Non c'e' una tabella scritta a mano da tenere allineata: la descrizione E' il docblock del
 * sorgente, quindi chi aggiunge una proprieta' e la commenta la vede comparire qui. Una
 * proprieta' senza commento compare con la casella vuota, che e' il modo piu' rapido di
 * accorgersi che manca.
 */
class PropertyTable extends UserControl
{
    /** I controlli da descrivere, nomi brevi separati da virgola: "Button,LinkButton". */
    public string $Type = '';

    use PropertyTableDesigner;

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Type']);
    }

    public function OnPreRender(): void
    {
        $html = '';

        foreach (array_filter(array_map('trim', explode(',', $this->Type))) as $nome)
            $html .= $this->Tabella($nome);

        $this->__Literal_Table->Text = $html
            . '<p class="ex-note">Piu\' quelle di ogni controllo: <code>Id</code>, <code>Visible</code>, <code>CssClass</code>, '
            . '<code>Attributes</code>, <code>Style</code>, <code>ViewStateMode</code>. Gli <code>On...</code> sono gli handler: '
            . 'il nome di un metodo del codebehind.</p>';
    }

    private function Tabella(string $nome): string
    {
        $classe = 'Common\\WebForms\\Controls\\' . $nome;

        if (!class_exists($classe))
            return '<p class="ex-ko">' . Control::HtmlEncode($classe) . ' non esiste.</p>';

        $rc = new \ReflectionClass($classe);

        $righe = '';

        foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $p)
        {
            //solo quelle dichiarate QUI: quelle di Control le hanno tutti e stanno nella nota
            if ($p->isStatic() || $p->getDeclaringClass()->getName() !== $classe)
                continue;

            $righe .= '<tr><td>' . Control::HtmlEncode($p->getName()) . '</td>'
                . '<td>' . Control::HtmlEncode(self::Tipo($p)) . '</td>'
                . '<td>' . Control::HtmlEncode(self::Predefinito($p)) . '</td>'
                . '<td>' . Control::HtmlEncode(self::Commento($p->getDocComment())) . '</td></tr>';
        }

        $metodi = '';

        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $m)
        {
            if ($m->isStatic() || $m->getDeclaringClass()->getName() !== $classe || str_starts_with($m->getName(), '__'))
                continue;

            //il ciclo di vita e il render li hanno tutti: qui interessano i metodi SUOI
            if (in_array($m->getName(), ['Render', 'LoadPostData', 'RaisePostBackEvent', 'SaveViewState', 'LoadViewState', 'ApplyAttributes', 'OnBubbleEvent', 'RebuildsChildren', 'DataBind'], true) && $nome !== 'Repeater')
                continue;

            $metodi .= '<tr><td>' . Control::HtmlEncode($m->getName() . '()') . '</td>'
                . '<td>' . Control::HtmlEncode($m->getReturnType() ? (string)$m->getReturnType() : '') . '</td><td></td>'
                . '<td>' . Control::HtmlEncode(self::Commento($m->getDocComment())) . '</td></tr>';
        }

        return '<h3><code>&lt;dw:' . Control::HtmlEncode($nome) . '&gt;</code></h3>'
            . '<table class="ex-props"><thead><tr><th>nome</th><th>tipo</th><th>predefinito</th><th></th></tr></thead>'
            . '<tbody>' . $righe . $metodi . '</tbody></table>';
    }

    private static function Tipo(\ReflectionProperty $p): string
    {
        $tipo = $p->getType();

        if ($tipo === null)
            return '';

        $testo = (string)$tipo;

        //il namespace non dice niente in piu': DateTimeMode basta
        return preg_replace('/(^|\|)\\\\?(?:[A-Za-z_]+\\\\)+/', '$1', $testo) ?? $testo;
    }

    private static function Predefinito(\ReflectionProperty $p): string
    {
        if (!$p->hasDefaultValue())
            return '';

        $v = $p->getDefaultValue();

        return match (true) {
            $v === null           => 'null',
            is_bool($v)           => $v ? 'true' : 'false',
            $v instanceof \UnitEnum => $v->name,
            is_array($v)          => $v === [] ? '[]' : json_encode($v, JSON_UNESCAPED_UNICODE),
            is_string($v)         => $v === '' ? '""' : '"' . $v . '"',
            default               => (string)$v,
        };
    }

    /** Il docblock senza le sue stelline, su una riga. Il primo paragrafo: il resto e' per chi legge il sorgente. */
    private static function Commento(string|false $doc): string
    {
        if ($doc === false)
            return '';

        $righe = [];

        foreach (preg_split('/\R/', $doc) ?: [] as $riga)
        {
            //via /** in testa, */ in coda e la stellina di ogni riga: resta il testo
            $riga = trim(preg_replace('#^\s*(/\*\*|\*/|\*)|\*/\s*$#', '', $riga) ?? '');

            if ($riga === '' && $righe !== [])
                break;

            if ($riga !== '' && !str_starts_with($riga, '@'))
                $righe[] = $riga;
        }

        return implode(' ', $righe);
    }
}
