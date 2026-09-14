<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class UpdateProgressExample extends Page
{
    use UpdateProgressDesigner;

    protected function FastClick(): void
    {
        $this->__Literal_Last->Text = 'veloce, alle ' . date('H:i:s');
    }

    protected function SlowClick(): void
    {
        usleep(1_200_000);

        $this->__Literal_Last->Text = 'lento, finito alle ' . date('H:i:s');
    }

    protected function VerySlowClick(): void
    {
        sleep(3);

        $this->__Literal_Last->Text = 'molto lento, finito alle ' . date('H:i:s');
    }
}
