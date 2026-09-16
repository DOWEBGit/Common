<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\UserControl;

/**
 * Il sorgente della pagina che lo ospita, markup e codebehind, letto dal disco.
 *
 *     <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />
 *
 * Cosi' l'esempio e il suo sorgente non possono divergere: quello che si legge sotto la
 * prova E' il file che l'ha resa. La pagina si trova per riflessione sulla classe del
 * codebehind: e' un .code.php, e il markup e' il .php accanto.
 */
class SourceView extends UserControl
{
    use SourceViewDesigner;

    public function OnPreRender(): void
    {
        $code   = (string)(new \ReflectionClass($this->Page))->getFileName();
        $markup = preg_replace('/\.code\.php$/', '.php', $code) ?? $code;

        //il Literal escapa da se': il markup contiene <dw:...> e deve vedersi com'e' scritto
        $this->__Literal_Markup->Text = rtrim((string)@file_get_contents($markup));
        $this->__Literal_Code->Text   = rtrim((string)@file_get_contents($code));
    }
}
