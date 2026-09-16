<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class ErrorsExample extends Page
{
    use ErrorsDesigner;

    public int $Ok = 0;

    /** Di proposito: e' quello che succede quando un handler sbaglia. */
    protected function ThrowClick(): void
    {
        throw new \RuntimeException('Eccezione voluta dall\'esempio, alle ' . date('H:i:s') . '.');
    }

    protected function FineClick(): void
    {
        $this->Ok++;
    }

    protected function OnPreRender(): void
    {
        $this->__Literal_Ok->Text = (string)$this->Ok;
    }
}
