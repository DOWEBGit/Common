<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class ResourcesExample extends Page
{
    use ResourcesDesigner;

    public int $Postbacks = 0;

    protected function PostbackClick(): void
    {
        $this->Postbacks++;
    }

    protected function OnPreRender(): void
    {
        $this->__Literal_Count->Text = (string)$this->Postbacks;
    }
}
