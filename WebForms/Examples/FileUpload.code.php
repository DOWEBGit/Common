<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Controls\FileUpload;
use Common\WebForms\Page;

class FileUploadExample extends Page
{
    use FileUploadDesigner;

    /** Sollevato appena il file e' stato messo da parte: qui si aggiorna solo l'anteprima. */
    protected function Uploaded(Control $sender): void
    {
        /** @var FileUpload $sender */
        if ($sender->Error !== '')
        {
            //il controllo ha gia' rifiutato il file; il perche' va detto, o sembra sparito nel nulla
            $this->Alert->Fail($sender->Id . ': ' . $sender->Error);
        }

        $this->Show();
    }

    /** Consuma i file: in un sito vero qui si passano Bytes() al Controller. */
    protected function SaveClick(): void
    {
        $salvati = [];

        foreach ([$this->__FileUpload_Simple, $this->__FileUpload_Drop] as $upload)
        {
            if (!$upload->HasFile())
                continue;

            $salvati[] = $upload->FileName() . ' (' . strlen((string)$upload->Bytes()) . ' byte letti dal temporaneo)';

            $upload->Clear();
        }

        $this->Alert->Success($salvati === [] ? 'Nessun file da salvare.' : 'Salvati: ' . implode(', ', $salvati));

        $this->Show();
    }

    protected function DiscardClick(): void
    {
        $this->__FileUpload_Simple->Clear();
        $this->__FileUpload_Drop->Clear();

        $this->Show();
    }

    private function Show(): void
    {
        $html = '';

        foreach ([$this->__FileUpload_Simple, $this->__FileUpload_Drop] as $upload)
        {
            if (!$upload->HasFile())
                continue;

            $url = '/public/php/Common/WebForms/FileUploadHandler.php?token=' . rawurlencode($upload->Token);

            $html .= '<div><b>' . Control::HtmlEncode($upload->Id) . '</b>: ' . Control::HtmlEncode($upload->FileName())
                . ' — ' . number_format($upload->Size() / 1024, 1, ',', '.') . ' kB — '
                . '<a href="' . Control::HtmlEncode($url) . '" target="_blank">apri l\'anteprima</a>'
                . '</div>';
        }

        $this->__Literal_Status->Text = $html === '' ? 'Nessun file messo da parte.' : $html;
    }
}
