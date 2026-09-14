<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Eventi di dominio: "questa entita' e' cambiata".
 *
 * Chi salva segnala; le pagine aperte altrove che si erano iscritte a quel topic rileggono
 * quello che le riguarda. Regole, tutte volute:
 *
 * - L'EVENTO NON PORTA DATI, solo il nome del topic. Il salvataggio e' avvenuto nel
 *   contesto di sicurezza di qualcun altro: spedire i valori significherebbe farli
 *   attraversare un confine di autorizzazione. Chi riceve rilegge con i PROPRI permessi, e
 *   se quel record non puo' vederlo semplicemente non gli torna nulla. Nessun controllo da
 *   scrivere a mano. In piu' l'aggiornamento diventa idempotente: eventi doppi o fuori
 *   ordine non fanno danno.
 *
 * - DUE LIVELLI DI TOPIC: "Clienti" per gli elenchi, "Clienti/1042" per i dettagli. Senza
 *   il primo una griglia dovrebbe iscriversi a cinquanta topic; senza il secondo ogni
 *   salvataggio sveglierebbe ogni scheda di dettaglio del sito.
 *
 * - SI ACCUMULA E SI EMETTE UNA VOLTA SOLA, a fine richiesta. Un ordine salvato con venti
 *   righe costa un solo giro sul pipe, non ventuno.
 */
class EntityEvents
{
    /**
     * Topic accumulati nella richiesta, gia' deduplicati. Il valore e' true per un evento
     * senza dati, o i dati stessi - come array - per uno che li porta.
     *
     * @var array<string,true|array>
     */
    private static array $queue = [];

    private static bool $suspended = false;

    private static bool $registered = false;

    /** PushId della pagina che sta scrivendo, per non far rimbalzare l'evento al mittente. */
    public static string $Origin = '';

    public static function Suspend(): void
    {
        self::$suspended = true;
    }

    public static function Resume(): void
    {
        self::$suspended = false;
    }

    /**
     * @param string $entita nome della classe modello, es. "Prodotti"
     * @param int    $id     0 per segnalare solo il tipo (utile dopo un'importazione)
     * @param mixed  $dati   un oggetto o un array da consegnare agli iscritti INSIEME al nome:
     *                       arriva all'handler di Subscribe() come array, com'era, in ogni
     *                       pagina aperta. Vedi sotto prima di usarlo.
     *
     * SUI DATI. La regola del motore e' che l'evento non li porta: chi riceve rilegge con i
     * suoi permessi. Quando li si passa, quella regola si sospende PER QUEL TOPIC: i dati
     * escono dal contesto di chi salva ed entrano in ogni browser del dominio, e da li'
     * tornano al server di ogni pagina iscritta. Quindi ci va cio' che tutti gli utenti del
     * sito possono vedere. Il pacchetto e' FIRMATO come il ViewState - un browser non puo'
     * cambiarlo ne' inventarne uno - ma la firma protegge l'integrita', non la riservatezza.
     *
     * Un oggetto passa da json_encode, quindi escono le sue proprieta' pubbliche; l'handler
     * riceve un array con quelle chiavi. Niente risorse, connessioni, closure.
     */
    public static function Notify(string $entita, int $id = 0, mixed $dati = null): void
    {
        if (self::$suspended || $entita === '')
            return;

        //i dati si appiattiscono subito in array: cosi' un oggetto qualunque passa, e a
        //destinazione non c'e' nessuna classe da ricostruire
        $carico = $dati === null ? true : json_decode(json_encode($dati, JSON_THROW_ON_ERROR), true);

        if (!is_array($carico) && $carico !== true)
            $carico = ['valore' => $carico];

        //un secondo Notify dello stesso topic senza dati non deve cancellare i dati del primo
        if ($carico !== true || !isset(self::$queue[$entita]))
            self::$queue[$entita] = $carico;

        if ($id !== 0)
            self::$queue[$entita . '/' . $id] = $carico;

        if (self::$registered)
            return;

        self::$registered = true;

        //a fine richiesta: la risposta e' gia' partita, quindi il giro sul pipe non pesa
        //sul tempo che l'utente aspetta
        register_shutdown_function([self::class, 'Flush']);
    }

    /**
     * Manda un messaggio CON DATI a tutti i browser collegati al dominio, adesso.
     *
     *     EntityEvents::Broadcast('Prezzo', ['id' => 42, 'valore' => 12.5]);
     *
     * e in pagina, in un <dw:Script> o nel JavaScript del sito:
     *
     *     DW.on('Prezzo', dati => { ... });
     *
     * NON e' un evento di dominio, ed e' l'eccezione alla regola qui sopra: Notify() porta
     * solo un nome e lascia che ognuno rilegga con i propri permessi; questo porta i dati, e
     * li vede CHIUNQUE abbia una pagina di questo dominio aperta - anche chi non potrebbe
     * leggerli dal database. Quindi ci va solo roba pubblica per tutti gli utenti del sito: un
     * prezzo in vetrina, un contatore, "qualcuno sta scrivendo", non il record di un cliente.
     * Per quello c'e' Notify(): il nome viaggia, i dati li rilegge chi puo'.
     *
     * Parte subito, non a fine richiesta: e' un messaggio, non un cambiamento di stato da
     * accumulare. I dati passano da json_encode, quindi array e oggetti JsonSerializable;
     * un oggetto qualunque esce con le sue proprieta' pubbliche.
     *
     * @param bool $ancheAlMittente false per non farlo tornare alla pagina che lo manda, che
     *                              di solito si e' gia' aggiornata col postback
     */
    public static function Broadcast(string $nome, mixed $dati, bool $ancheAlMittente = true): void
    {
        if ($nome === '' || $nome === 'DWEventi')
            throw new \RuntimeException('Broadcast: il nome del messaggio non puo\' essere vuoto o "DWEventi", che e\' del motore.');

        $messaggio = json_encode([
            'o' => $ancheAlMittente ? '' : self::$Origin,
            'd' => $dati,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        try
        {
            /** @noinspection PhpUndefinedFunctionInspection */
            PHPDOWEB()->ClientPush($nome, $messaggio);
        }
        catch (\Throwable $e)
        {
            //un push fallito non deve buttare giu' una richiesta che ha gia' fatto il suo
            \Common\Log::Error('WebForms\EntityEvents::Broadcast, ' . $e->getMessage());
        }
    }

    /**
     * Il messaggio da mandare al hub, e la coda si svuota. Null se non c'e' niente.
     *
     *   o  chi manda, per non farlo rimbalzare al mittente
     *   t  i topic
     *   d  per i topic che portano dati, il pacchetto FIRMATO con il segreto del ViewState:
     *      il browser lo riporta al server di ogni pagina iscritta, e il server lo accetta
     *      solo se la firma regge. Un client non puo' inventare dati, ne' toccarli.
     *
     * E' pubblico per poterlo provare senza un hub davanti.
     */
    public static function Compose(): ?string
    {
        if (self::$queue === [])
            return null;

        $messaggio = ['o' => self::$Origin, 't' => array_keys(self::$queue)];

        foreach (self::$queue as $topic => $carico)
            if ($carico !== true)
                $messaggio['d'][$topic] = ViewState::Pack(['topic' => $topic, 'dati' => $carico]);

        self::$queue = [];

        return json_encode($messaggio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * I dati di un evento arrivato dal browser, se il pacchetto e' buono e parla di QUEL
     * topic. Un pacchetto manomesso, o preso da un altro topic, da' niente: l'handler gira
     * come per un evento senza dati.
     */
    public static function Dati(string $topic, string $pacchetto): array
    {
        $aperto = ViewState::Unpack($pacchetto);

        if ($aperto === null || ($aperto['topic'] ?? null) !== $topic || !is_array($aperto['dati'] ?? null))
            return [];

        return $aperto['dati'];
    }

    public static function Flush(): void
    {
        $messaggio = self::Compose();

        if ($messaggio === null)
            return;

        try
        {
            /** @noinspection PhpUndefinedFunctionInspection */
            $obj = PHPDOWEB();

            //diffusione a tutto il dominio: il messaggio e' solo un nome di topic, e ogni
            //browser filtra sulle proprie iscrizioni. Costa un frame WebSocket minuscolo a
            //testa ed evita di dover tenere sul server un registro topic -> pagine vive.
            $obj->ClientPush('DWEventi', $messaggio);
        }
        catch (\Throwable $e)
        {
            //un push fallito non deve buttare giu' una richiesta che ha gia' salvato
            \Common\Log::Error('WebForms\EntityEvents::Flush, ' . $e->getMessage());
        }
    }
}
