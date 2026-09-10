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
    /** @var array<string,bool> topic accumulati nella richiesta, gia' deduplicati */
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
     */
    public static function Notify(string $entita, int $id = 0): void
    {
        if (self::$suspended || $entita === '')
            return;

        self::$queue[$entita] = true;

        if ($id !== 0)
            self::$queue[$entita . '/' . $id] = true;

        if (self::$registered)
            return;

        self::$registered = true;

        //a fine richiesta: la risposta e' gia' partita, quindi il giro sul pipe non pesa
        //sul tempo che l'utente aspetta
        register_shutdown_function([self::class, 'Flush']);
    }

    public static function Flush(): void
    {
        if (self::$queue === [])
            return;

        $topic = array_keys(self::$queue);

        self::$queue = [];

        $messaggio = json_encode(['o' => self::$Origin, 't' => $topic]);

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
