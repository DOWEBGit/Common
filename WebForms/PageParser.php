<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Il compilatore del markup: da <dw:Tipo> a un albero di nodi.
 *
 * Il markup non viene interpretato ad ogni richiesta. Si compila una volta in un array di
 * nodi e si mette in cache su disco, con la data del sorgente come chiave: finche' il
 * markup non cambia, a runtime si fa solo un include.
 *
 * L'albero prodotto e' DETERMINISTICO: dallo stesso markup escono sempre gli stessi
 * controlli con gli stessi id, nello stesso ordine. E' il vincolo che permette allo stato
 * di riagganciarsi al postback successivo.
 *
 * Nodi:
 *   ['t'=>'html', 'v'=>'<div>...']                testo letterale
 *   ['t'=>'ctl',  'tipo'=>'TextBox', 'id'=>'txt', 'attr'=>[...], 'figli'=>[...]]
 *   ['t'=>'tpl',  'figli'=>[...]]                 <ItemTemplate> di un Repeater
 */
class PageParser
{
    /** @var array<string,array> markup gia' analizzati in questa richiesta */
    private static array $parsed = [];

    /**
     * Nodi del markup, dalla cache se il sorgente non e' cambiato.
     */
    public static function Parse(string $markupFile): array
    {
        $chiave = $markupFile . '|' . filemtime($markupFile);

        if (isset(self::$parsed[$chiave]))
            return self::$parsed[$chiave];

        $cache = self::CachePath($markupFile);

        if (is_file($cache) && filemtime($cache) >= filemtime($markupFile))
        {
            $nodi = include $cache;

            if (is_array($nodi))
                return self::$parsed[$chiave] = $nodi;
        }

        $nodi = self::ParseTesto(self::MarkupSource($markupFile));

        //la scrittura passa da un file temporaneo nella stessa cartella: due richieste che
        //compilano lo stesso markup insieme non possono farsi leggere un file mezzo scritto
        $tmp = $cache . '.' . getmypid() . '.tmp';

        if (@file_put_contents($tmp, "<?php return " . var_export($nodi, true) . ";\n") !== false)
            @rename($tmp, $cache);

        return self::$parsed[$chiave] = $nodi;
    }

    /**
     * Gli stessi nodi, ma da una stringa invece che da un file.
     *
     * Non passa dalla cache e non tocca il disco: serve per provare il compilatore senza
     * dovergli dare un file, ed e' la stessa strada che fa il markup vero - cosi' quello che
     * si prova e' il codice che gira, non una copia somigliante.
     */
    public static function ParseTesto(string $markup): array
    {
        return self::ParseNodes($markup);
    }

    /**
     * Il markup vero comincia dopo il primo "?>": prima c'e' solo la riga di avvio della
     * pagina, che e' codice e non deve finire nell'HTML.
     */
    private static function MarkupSource(string $markupFile): string
    {
        $testo = file_get_contents($markupFile);

        $pos = strpos($testo, '?>');

        return $pos === false ? $testo : substr($testo, $pos + 2);
    }

    private static function CachePath(string $markupFile): string
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '_cache';

        if (!is_dir($dir))
            @mkdir($dir, 0777, true);

        return $dir . DIRECTORY_SEPARATOR . sha1($markupFile) . '.php';
    }

    private const string TAG = '/<(dw:[A-Za-z][A-Za-z0-9]*|ItemTemplate)\b([^>]*?)(\/?)>|<\/(dw:[A-Za-z][A-Za-z0-9]*|ItemTemplate)>/';
    private static function ParseNodes(string $markup): array
    {
        $radice = ['figli' => []];

        //pila dei contenitori aperti: in cima c'e' quello che sta ricevendo i nodi
        $pila = [&$radice];

        $ultimo = 0;

        if (preg_match_all(self::TAG, $markup, $match, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
        {
            foreach ($match as $m)
            {
                //con PREG_OFFSET_CAPTURE ogni match e' [testo, posizione]: la posizione e' un
                //intero, ma dall'array non si vede - senza il cast la somma qui sotto sembra
                //una concatenazione di stringhe scritta con l'operatore sbagliato
                $inizio = (int)$m[0][1];
                $intero = $m[0][0];

                //tutto quello che sta fra il tag precedente e questo e' HTML letterale
                if ($inizio > $ultimo)
                    self::AppendLiteral($pila[count($pila) - 1], substr($markup, $ultimo, $inizio - $ultimo));

                $ultimo = $inizio + strlen($intero);

                $chiusura = $m[4][0] ?? '';

                if ($chiusura !== '')
                {
                    //un </dw:X> senza apertura chiuderebbe il contenitore sbagliato e da li'
                    //in poi il markup finirebbe nel posto sbagliato in silenzio
                    if (count($pila) > 1)
                        array_pop($pila);

                    continue;
                }

                $nome    = $m[1][0];
                $attr    = self::ParseAttributes($m[2][0]);
                $singolo = ($m[3][0] ?? '') === '/';

                if ($nome === 'ItemTemplate')
                    $nodo = ['t' => 'tpl', 'figli' => []];
                else
                    $nodo = [
                        't'     => 'ctl',
                        'tipo'  => substr($nome, 3),
                        'id'    => $attr['id'] ?? '',
                        'attr'  => $attr,
                        'figli' => [],
                    ];

                $cima = count($pila) - 1;

                $pila[$cima]['figli'][] = $nodo;

                if ($singolo)
                    continue;

                //il riferimento deve puntare al nodo appena inserito nell'array del padre,
                //non a $nodo: modificare la copia locale non aggiungerebbe niente all'albero
                $ultimoIndice = count($pila[$cima]['figli']) - 1;

                $pila[] = &$pila[$cima]['figli'][$ultimoIndice];
            }
        }

        if ($ultimo < strlen($markup))
            self::AppendLiteral($pila[count($pila) - 1], substr($markup, $ultimo));

        return $radice['figli'];
    }

    private static function AppendLiteral(array &$contenitore, string $testo): void
    {
        if ($testo === '')
            return;

        $contenitore['figli'][] = ['t' => 'html', 'v' => $testo];
    }

    private static function ParseAttributes(string $text): array
    {
        $attr = [];

        if (preg_match_all('/([A-Za-z_][A-Za-z0-9_:.-]*)\s*=\s*"([^"]*)"/', $text, $m, PREG_SET_ORDER))
            foreach ($m as $a)
                $attr[$a[1]] = $a[2];

        return $attr;
    }
}
