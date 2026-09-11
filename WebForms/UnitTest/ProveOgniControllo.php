<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Control;

/**
 * Le stesse tre domande, fatte a TUTTI i controlli.
 *
 * Le prove scritte a mano coprono quello a cui si e' pensato. Queste no: pescano i controlli
 * dalla cartella e li interrogano uno per uno, quindi il controllo NUOVO che qualcuno
 * aggiungera' fra sei mesi e' gia' sotto prova senza che nessuno scriva niente.
 *
 * Le tre domande:
 *
 * 1. LE PROPRIETA' SONO NELLO STATO? Una proprieta' pubblica che un handler puo' cambiare e
 *    che non compare in ViewStateProperties() si azzera al click dopo. E' il difetto piu'
 *    silenzioso del motore: la pagina funziona, ma un valore sparisce quando l'utente fa
 *    un'altra cosa, e sembra un problema del browser.
 *
 * 2. LO STATO TORNA INDIETRO INTERO? Salvare e ricaricare deve dare gli stessi valori. Se una
 *    proprieta' e' elencata ma il tipo non attraversa la serializzazione, si scopre qui.
 *
 * 3. IL VALORE ESCE ESCAPATO? Ogni proprieta' di testo si riempie con un pezzo di HTML
 *    cattivo, e nell'uscita non deve comparire com'era. Vale per i controlli di oggi e per
 *    quelli di domani: e' la regola che tiene su tutto il resto.
 */
class ProveOgniControllo
{
    /**
     * Le proprieta' pubbliche che NON sono stato, con il motivo.
     *
     * Chi ne aggiunge una qui si assume la responsabilita' di dire perche': senza questo
     * elenco la prova 1 non potrebbe distinguere una scelta da una dimenticanza.
     */
    private const array FUORI = [
        'Id'           => 'viene dal markup e non cambia mai',
        'Parent'       => 'l\'albero si ricostruisce ad ogni richiesta',
        'Controls'     => 'idem',
        'Page'         => 'idem',
        'NamingKey'    => 'la mette il Repeater quando crea la riga',
        'ViewStateMode' => 'viene dal markup e decide se lo stato esiste: non puo\' starci dentro',
        'Error'       => 'e\' l\'esito di QUESTA richiesta, non uno stato da portarsi dietro',
        'ItemTemplate' => 'viene dal markup, che si rilegge ogni volta',
        'DataSource'   => 'le righe rese stanno nello stato del Repeater, non qui',
        'DataItem'     => 'vale solo dentro OnItemDataBound',
    ];

    /**
     * Src non si riempie di spazzatura: <dw:Stylesheet> e <dw:Script> vogliono un file che
     * esiste davvero, e con un valore finto si fermerebbero prima di rendere niente. Nella
     * prova dell'escape gli si da' un file vero (vedi Prepara), cosi' il render si fa sul
     * serio invece di essere saltato.
     */
    private const array NIENTE_ESCAPE = ['Src'];
    /** Un file dei sorgenti che c'e' di sicuro: e' il motore lato browser. */
    private const string FILE_VERO = 'Common/WebForms/runtime.js';
    private const string CATTIVO = '<script>"x"&\'y\'</script>';
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('tutti i controlli, uno per uno');

        foreach (self::Controlli() as $nome => $classe)
        {
            self::Dimenticanze($p, $nome, $classe);
            self::AndataERitorno($p, $nome, $classe);
            self::Escape($p, $nome, $classe);
        }
    }

    /** 1. Nessuna proprieta' pubblica fuori dallo stato senza un motivo scritto. */
    private static function Dimenticanze(Prova $p, string $nome, string $classe): void
    {
        $stato = self::Stato(new $classe());

        $dimenticate = [];

        foreach ((new \ReflectionClass($classe))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property)
        {
            if ($property->isStatic() || in_array($property->getName(), $stato, true))
                continue;

            if (!array_key_exists($property->getName(), self::FUORI))
                $dimenticate[] = $property->getName();
        }

        $p->Uguale($nome . ': ogni proprieta\' pubblica sta nello stato, o e\' scritto perche\' no',
            [], $dimenticate);
    }

    /** 2. Salvato e ricaricato, lo stato e' lo stesso. */
    private static function AndataERitorno(Prova $p, string $nome, string $classe): void
    {
        /** @var Control $controllo */
        $controllo = new $classe();

        $atteso = [];

        foreach (self::Stato($controllo) as $proprieta)
        {
            $valore = self::Sonda($controllo, $proprieta);

            $controllo->$proprieta = $valore;

            $atteso[$proprieta] = $valore;
        }

        /** @var Control $secondo */
        $secondo = new $classe();

        $secondo->LoadViewState($controllo->SaveViewState());

        $tornato = [];

        foreach ($atteso as $proprieta => $_)
            $tornato[$proprieta] = self::Confrontabile($secondo->$proprieta);

        $p->Uguale($nome . ': lo stato torna indietro identico', array_map(self::Confrontabile(...), $atteso), $tornato);
    }

    /** 3. Un valore di testo cattivo non esce mai com'e'. */
    private static function Escape(Prova $p, string $nome, string $classe): void
    {
        /** @var Control $controllo */
        $controllo = new $classe();

        $controllo->Id = 'ctl';

        self::Prepara($controllo);

        $quante = 0;

        foreach (self::Stato($controllo) as $proprieta)
        {
            if (in_array($proprieta, self::NIENTE_ESCAPE, true))
                continue;

            if (self::Tipo($controllo, $proprieta) !== 'string')
                continue;

            $controllo->$proprieta = self::CATTIVO;

            $quante++;
        }

        if ($quante === 0)
            return;

        //l'attributo aggiunto dal codice passa dalla stessa strada di tutti gli altri: se
        //l'escape saltasse li', salterebbe per ogni riga di ogni elenco
        $controllo->Attributes->Add('title', self::CATTIVO);
        $controllo->Style->Add('background-image', self::CATTIVO);

        $p->Manca($nome . ': il testo cattivo non esce com\'e\'', self::CATTIVO, $controllo->Render());
    }

    /**
     * Il poco che serve a un controllo per poter rendere qualcosa.
     *
     * I tag delle risorse statiche compongono un indirizzo, e per farlo hanno bisogno di un
     * file che esiste e di sapere dove sta la radice dei documenti - che da riga di comando
     * non c'e', perche' non c'e' nessun server.
     */
    private static function Prepara(Control $controllo): void
    {
        if (!$controllo instanceof \Common\WebForms\Controls\StaticResource)
            return;

        //.../public/php meno gli ultimi due pezzi: e' quello che il server chiamerebbe
        //radice dei documenti, e da cui esce il prefisso "/public/php"
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

        $controllo->Src = self::FILE_VERO;
    }

    /** I controlli veri, presi dalla cartella: quello nuovo entra da solo. */
    private static function Controlli(): array
    {
        $trovati = [];

        foreach (glob(dirname(__DIR__) . '/Controls/*.php') ?: [] as $file)
        {
            $nome = basename($file, '.php');

            $classe = 'Common\\WebForms\\Controls\\' . $nome;

            if (!class_exists($classe) || (new \ReflectionClass($classe))->isAbstract())
                continue;

            $trovati[$nome] = $classe;
        }

        return $trovati;
    }

    /** ViewStateProperties() e' protetto: lo si chiede per riflessione, come farebbe il motore. */
    private static function Stato(Control $controllo): array
    {
        $metodo = new \ReflectionMethod($controllo, 'ViewStateProperties');

        return $metodo->invoke($controllo);
    }

    private static function Tipo(Control $controllo, string $proprieta): string
    {
        $tipo = (new \ReflectionProperty($controllo, $proprieta))->getType();

        return $tipo instanceof \ReflectionNamedType ? $tipo->getName() : '';
    }

    /** Un valore riconoscibile, del tipo giusto: per i booleani si prende l'opposto. */
    private static function Sonda(Control $controllo, string $proprieta): mixed
    {
        $tipo = self::Tipo($controllo, $proprieta);

        //le collection: un nome buono per tutte e due le grammatiche, e un valore riconoscibile
        if (is_subclass_of($tipo, \Common\WebForms\NamedCollection::class))
            return new $tipo(['data-sonda' => 'valore']);

        return match ($tipo) {
            'bool'  => !$controllo->$proprieta,
            'int'   => 4242,
            'array' => ['sonda' => 'valore'],
            default => 'sonda-' . $proprieta,
        };
    }

    /** Una collection non e' confrontabile con ===: si confronta il suo contenuto. */
    private static function Confrontabile(mixed $valore): mixed
    {
        return $valore instanceof \Common\WebForms\NamedCollection ? $valore->ToArray() : $valore;
    }
}
