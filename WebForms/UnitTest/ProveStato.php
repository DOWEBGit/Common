<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ViewState;

/**
 * Lo stato che viaggia: ViewState e pacchetto #[Portable].
 *
 * Sono lo stesso pacchetto - serialize, gzip, base64, HMAC - e la firma e' la sola cosa che
 * impedisce al client di riscriversi lo stato: se saltasse, le proprieta' dei controlli
 * diventerebbero quello che decide lui. Quindi le prove che contano non sono sul giro
 * andata-ritorno, sono su cosa viene RIFIUTATO.
 */
class ProveStato
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('ViewState');

        $dati = ['righe' => [1, 2, 3], 'nome' => 'L\'Oreal', 'vero' => true, 'niente' => null];

        $pacchetto = ViewState::Pack($dati);

        $p->Uguale('lo stato torna indietro identico, tipi compresi', $dati, ViewState::Unpack($pacchetto));

        $p->Uguale('uno stato manomesso viene rifiutato', null, ViewState::Unpack($pacchetto . 'x'));
        $p->Uguale('uno stato senza firma viene rifiutato', null, ViewState::Unpack('v1.abc'));
        $p->Uguale('uno stato vuoto viene rifiutato', null, ViewState::Unpack(''));
        $p->Uguale('uno stato che non e\' nemmeno un pacchetto viene rifiutato', null,
            ViewState::Unpack('questo non e\' un pacchetto'));

        $pezzi = explode('.', $pacchetto);

        $p->Uguale('uno stato con una firma di comodo viene rifiutato', null,
            ViewState::Unpack($pezzi[0] . '.' . $pezzi[1] . '.' . str_repeat('0', 64)));

        //il corpo cambiato e la firma vecchia: e' il caso in cui qualcuno prova a cambiare un
        //valore tenendosi la firma che aveva
        $alterato = ViewState::Pack(['righe' => [9]]);
        $pezziAlterato = explode('.', $alterato);

        $p->Uguale('un corpo cambiato con la firma di un altro stato viene rifiutato', null,
            ViewState::Unpack($pezziAlterato[0] . '.' . $pezziAlterato[1] . '.' . $pezzi[2]));

        //un pacchetto di una versione futura non si prova nemmeno a leggere
        $p->Uguale('un pacchetto di un\'altra versione viene rifiutato', null,
            ViewState::Unpack('v2.' . $pezzi[1] . '.' . $pezzi[2]));

        //serve a chi lo usa per il token dell'upload: dentro ci finiscono stringhe lunghe
        $lungo = ViewState::Pack(['percorso' => str_repeat('x', 4000)]);

        $p->Uguale('un pacchetto lungo torna indietro intero',
            str_repeat('x', 4000), ViewState::Unpack($lungo)['percorso']);

        //due chiavi diverse non devono dare lo stesso pacchetto: se la compressione o la
        //firma si mangiassero qualcosa, due stati diversi diventerebbero lo stesso
        $p->Uguale('due stati diversi danno pacchetti diversi', false,
            ViewState::Pack(['a' => 1]) === ViewState::Pack(['a' => 2]));
    }
}
