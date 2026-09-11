<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Gli avvisi della pagina: "salvato", "non salvato", e il perche'.
 *
 *     $this->Alert->Success('Categoria salvata.');
 *     $this->Alert->Fail('Non salvata: ' . $esito->Avviso(' '));
 *     $this->Alert->Fail('Il file supera gli 8 MB.', true);   // modale: si deve cliccare OK
 *
 * QUESTA CLASSE NON DISEGNA NIENTE: e' una coda di messaggi. A mostrarli e' il controllo
 * <dw:Alert> che sta nella master page, una volta sola per tutto il sito - come
 * l'UpdateProgress. Il vantaggio di separarli e' che una pagina non deve sapere dove finisce
 * l'avviso: lo dice e basta, e se un giorno la cornice cambia posto ai messaggi, cambia in un
 * posto solo.
 *
 * SOPRAVVIVONO AL CAMBIO DI PAGINA. La coda vive in un campo #[Portable] della pagina, quindi
 * viaggia nel pacchetto firmato che il browser tiene in memoria: un handler puo' dire
 * "salvato" e subito dopo mandare altrove, e il messaggio compare sulla pagina di arrivo. Qui
 * si puo' fare perche' una navigazione e' una fetch e un morph, non un ricarico - la memoria
 * del browser non si azzera per strada.
 *
 * Chi li rende li CONSUMA: appena disegnati la coda si svuota, e il pacchetto che parte con
 * quella stessa risposta non li porta piu'. Senza, ricomparirebbero ad ogni click.
 *
 * Un ricarico vero - F5, indirizzo scritto a mano - li perde, come tutto lo stato portatile.
 * E' il comportamento giusto: un avviso e' la risposta a un gesto, non un dato della pagina.
 */
class Alert
{
    public const SUCCESS = 'successo';
    public const FAILURE  = 'fallito';

    /**
     * Quanti se ne tengono in coda. E' anche il tetto di quelli che si vedono insieme, ed
     * evita che un ciclo che sbaglia riempia il pacchetto portatile, che ha un limite suo.
     */
    private const MASSIMO = 5;

    public function __construct(private readonly Page $pagina)
    {
    }

    /**
     * @param bool $modale false = un riquadro in alto a destra che se ne va da solo;
     *                     true  = una finestra al centro, con lo sfondo oscurato, che si
     *                             chiude solo cliccando OK. Serve quando il messaggio va
     *                             letto per forza, non quando e' importante: la differenza
     *                             e' che il modale ferma quello che l'utente stava facendo.
     */
    public function Success(string $testo, bool $modale = false): void
    {
        $this->Aggiungi(self::SUCCESS, $testo, $modale);
    }

    public function Fail(string $testo, bool $modale = false): void
    {
        $this->Aggiungi(self::FAILURE, $testo, $modale);
    }

    /** @return array<int,array{tipo:string,testo:string,modale:bool,chiave:string}> */
    public function Messages(): array
    {
        return $this->pagina->DwAlerts;
    }

    public function IsEmpty(): bool
    {
        return $this->pagina->DwAlerts === [];
    }

    /** Li butta via: lo chiama chi li ha appena disegnati, e nessun altro. */
    public function ClearItems(): void
    {
        $this->pagina->DwAlerts = [];
    }

    private function Aggiungi(string $tipo, string $testo, bool $modale): void
    {
        $testo = trim($testo);

        if ($testo === '')
            return;

        $coda = $this->pagina->DwAlerts;

        $coda[] = [
            'tipo'   => $tipo,
            'testo'  => $testo,
            'modale' => $modale,
            //la chiave distingue un messaggio nuovo da uno gia' in pagina: il morph riusa il
            //nodo che sta in quella posizione, e senza un id diverso il client crederebbe di
            //aver gia' fatto partire il timer di questo.
            //
            //uniqid e non random_bytes: qui non serve un segreto, serve che due messaggi non
            //si chiamino uguale. Il caso casuale vero lo si tiene dove protegge qualcosa - il
            //token CSRF, la firma dello stato - e li' non si cattura nemmeno l'eccezione: se
            //il sistema non sa piu' generare numeri sicuri, il posto giusto per accorgersene
            //e' un errore, non un token debole.
            'chiave' => uniqid('', true),
        ];

        //si tengono gli ultimi: se ne arrivano sei, quello vecchio e' anche quello che
        //l'utente ha gia' avuto il tempo di leggere
        $this->pagina->DwAlerts = array_slice($coda, -self::MASSIMO);
    }
}
