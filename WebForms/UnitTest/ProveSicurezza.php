<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\Panel;
use Common\WebForms\Csrf;
use Common\WebForms\PageParser;
use Common\WebForms\Response;

/**
 * Le difese del motore, una per una.
 *
 * Ogni prova qui sotto corrisponde a un buco vero, trovato leggendo il codice: se qualcuno
 * toglie la correzione, la prova diventa rossa e dice quale difesa e' saltata.
 */
class ProveSicurezza
{
    public static function Esegui(Prova $p): void
    {
        self::Nascosti($p);
        self::Redirect($p);
        self::CrossSite($p);
    }

    /**
     * Un controllo nascosto non scatena eventi.
     *
     * Qui un controllo invisibile viene reso lo stesso, con l'attributo hidden, perche' il
     * morph lo ritrovi quando torna visibile. Il rovescio e' che il suo id resta nella
     * pagina: senza questa guardia, dalla console si preme il bottone di una scheda chiusa.
     */
    private static function Nascosti(Prova $p): void
    {
        $p->Sezione('quello che e\' nascosto non risponde');

        $controlli = ControlBuilder::Build(PageParser::ParseTesto(
            '<dw:Panel id="pnlScheda"><dw:Panel id="dentro"><dw:Button id="btnSalva" Text="Salva" /></dw:Panel></dw:Panel>'
        ), null);

        /** @var Panel $scheda */
        $scheda = $controlli[0];

        $bottone = $scheda->FindControl('btnSalva');

        $p->Uguale('un controllo visibile risponde', true, $bottone->Attivabile());

        $scheda->Visible = false;

        $p->Uguale('un controllo dentro un pannello chiuso non risponde, per quanto sia visibile lui',
            false, $bottone->Attivabile());

        $p->Uguale('il pannello chiuso non risponde nemmeno lui', false, $scheda->Attivabile());

        $scheda->Visible = true;
        $bottone->Visible = false;

        $p->Uguale('un controllo nascosto per conto suo non risponde', false, $bottone->Attivabile());

        //e resta comunque nella pagina: e' esattamente il motivo per cui la guardia serve
        $bottone->Visible = false;

        $p->Contiene('un controllo nascosto viene reso lo stesso, con hidden',
            'hidden', $scheda->Render());
    }

    /** Un redirect porta dentro il sito, o non parte. */
    private static function Redirect(Prova $p): void
    {
        $p->Sezione('redirect: solo dentro il sito');

        $_SERVER['HTTP_HOST'] = 'sito.example:8081';

        $dentro = [
            '/public/php/Northwind/Ordini.php',
            'Ordini.php',
            '/public/php/Northwind/Ordini.php?id=3#riga',
            'http://sito.example/public/php/Home.php',
            'https://sito.example:8081/public/php/Home.php',
        ];

        foreach ($dentro as $url)
            $p->Uguale('e\' dentro il sito: ' . $url, true, Response::Interno($url));

        $fuori = [
            '//altrosito.example/x'                => 'due barre sono un indirizzo assoluto travestito da percorso',
            '\\\\altrosito.example\\x'             => 'la barra rovescia doppia la normalizzano come le due barre',
            'https://altrosito.example/x'          => 'un altro host',
            'http://sito.example.altro.example/x'  => 'un host che COMINCIA come il nostro',
            'javascript:alert(1)'                  => 'javascript: non e\' una navigazione',
            'data:text/html,<script>x</script>'    => 'data: nemmeno',
            "/x\r\nLocation: https://altrosito.example" => 'un a capo aggiungerebbe un\'intestazione',
            ''                                     => 'l\'indirizzo vuoto',
        ];

        foreach ($fuori as $url => $perche)
            $p->Uguale('si rifiuta: ' . $perche, false, Response::Interno($url));
    }

    /**
     * Il doppio invio del CSRF.
     *
     * Il caso che conta e' l'ultimo: il cookie e' SameSite=Lax e in una POST cross-site non
     * viene mandato, quindi "cookie assente" e' proprio la situazione da fermare. Prima qui
     * si tornava true, e la protezione esisteva solo sulla carta.
     */
    private static function CrossSite(Prova $p): void
    {
        $p->Sezione('CSRF a doppio invio');

        $cookie = str_repeat('a', 64);

        $_COOKIE['dw_csrf'] = $cookie;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $cookie;

        $p->Uguale('intestazione uguale al cookie: passa', true, Csrf::Verifica());

        $_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('b', 64);

        $p->Uguale('intestazione diversa: non passa', false, Csrf::Verifica());

        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        $p->Uguale('nessuna intestazione: non passa', false, Csrf::Verifica());

        $_SERVER['HTTP_X_CSRF_TOKEN'] = $cookie;
        unset($_COOKIE['dw_csrf']);

        $p->Uguale('nessun cookie: NON passa (e\' il caso della POST cross-site)',
            false, Csrf::Verifica());

        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }
}
