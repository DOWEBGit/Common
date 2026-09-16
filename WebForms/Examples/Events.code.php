<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\EntityEvents;
use Common\WebForms\Page;
use Common\WebForms\Portable;

class EventsExample extends Page
{
    use EventsDesigner;

    /** Quanti saluti sono arrivati qui da altre schede: aggiornato sul server, dall'evento. */
    public int $Greetings = 0;

    /** La stessa chiave della pagina dello stato: e' lo stesso valore. */
    #[Portable]
    public string $CarriedName = '';

    /**
     * Le iscrizioni si dichiarano in OnInit, ad ogni richiesta: quando un altro browser chiama
     * Notify('Saluti'), il runtime di QUESTA pagina fa un postback vuoto con quel nome e il
     * motore chiama l'handler qui dentro - sul server, con lo stato della pagina in mano.
     */
    protected function OnInit(): void
    {
        $this->Subscribe('Saluti', function (): void
        {
            $this->Greetings++;
        });

        //l'evento con dati: $data e' l'oggetto mandato di la', appiattito in array. Il pacchetto
        //e' firmato, quindi e' quello che il server ha scritto; ma il contenuto lo si tratta come
        //qualunque cosa arrivi dal browser - si escapa, si controlla
        $this->Subscribe('User', function (array $data): void
        {
            $photo = (string)($data['Image'] ?? '');

            $this->__Literal_UserName->Text  = trim((string)($data['FirstName'] ?? '') . ' ' . ($data['LastName'] ?? ''));
            $this->__Literal_Email->Text     = (string)($data['Email'] ?? '');
            $this->__Literal_ArrivedAt->Text = date('H:i:s');

            //solo un data URI di immagine passa nel src: e' l'unica cosa che ci si aspetta
            $this->__Literal_Photo->Text = str_starts_with($photo, 'data:image/')
                ? '<img src="' . Control::HtmlEncode($photo) . '" width="48" height="48" alt="" style="vertical-align:middle;margin-right:8px">'
                : '';

            $this->__Panel_User->Visible = true;

            $this->Alert->Success('E\' arrivato ' . $this->__Literal_UserName->Text . '.');
        });
    }

    protected function GreetClick(): void
    {
        EntityEvents::Notify('Saluti');

        $this->Alert->Success('Saluto mandato: chi e\' iscritto lo riceve sul suo server.');
    }

    /** Un oggetto in giro per il dominio: chi e' iscritto a "User" lo riceve come array. */
    protected function UserClick(): void
    {
        $users = [
            ['Anna',   'Bianchi', 'anna.bianchi@esempio.it',  '#1d4ed8'],
            ['Marco',  'Rossi',   'marco.rossi@esempio.it',   '#15803d'],
            ['Giulia', 'Verdi',   'giulia.verdi@esempio.it',  '#b91c1c'],
        ];

        [$first, $last, $email, $color] = $users[array_rand($users)];

        $user = new User($first, $last, $email, User::Avatar($first[0] . $last[0], $color));

        EntityEvents::Notify('User', dati: $user);

        $this->Alert->Success('Mandato ' . $first . ' ' . $last . ' a chi e\' iscritto.');
    }

    /** Parte subito, a ogni browser del dominio, anche a questo: lo riceve DW.on nella pagina. */
    protected function BroadcastClick(): void
    {
        EntityEvents::Broadcast('Message', ['text' => $this->__TextBox_Message->Text, 'at' => date('H:i:s')]);
    }

    protected function UpperClick(): void
    {
        if ($this->CarriedName === '')
        {
            $this->Alert->Fail('Non e\' arrivato niente: scrivi un nome sulla pagina dello stato.');

            return;
        }

        $this->CarriedName = mb_strtoupper($this->CarriedName, 'UTF-8');

        $this->Alert->Success('Adesso e\' "' . $this->CarriedName . '", anche di la\'.');
    }

    protected function OnPreRender(): void
    {
        $this->__Literal_Greetings->Text = (string)$this->Greetings;
        $this->__Literal_Carried->Text   = $this->CarriedName === '' ? '(niente)' : $this->CarriedName;
    }
}
