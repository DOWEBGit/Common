<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * L'elenco delle pagine del sito, come enum.
 *
 * A cosa serve: a scrivere
 *
 *     $this->RedirectToPage(\Pagine::Northwind_Categorie);
 *
 * invece di
 *
 *     $this->Redirect('/public/php/Northwind/Categorie.php');
 *
 * La differenza non e' estetica. Una stringa la si sbaglia a scrivere e non se ne accorge
 * nessuno finche' un utente non ci clicca sopra; e quando una pagina si sposta o si rinomina
 * restano in giro dei rimandi rotti che non compaiono in nessuna ricerca, perche' sono
 * pezzi di testo. Un caso dell'enum invece l'IDE lo completa, lo rinomina e lo trova.
 *
 * COSA CI FINISCE. Solo le PAGINE: i file markup che chiamano Page::Run(). Non i codebehind,
 * non i designer, non gli UserControl e non le master, che una pagina non e' e che chiesti
 * da un indirizzo rispondono 404. Non i file di Common, ne' vendor, ne' le cartelle di
 * servizio.
 *
 * QUANDO SI RIGENERA. Da sola, quando manca il file o quando la pagina che si sta aprendo
 * non c'e' dentro: cosi' una pagina nuova entra nell'elenco la prima volta che la si apre,
 * senza doversi ricordare di niente. In produzione, dove i file ci sono gia' e non cambiano,
 * il controllo costa una lettura di un array in memoria.
 */
class PageMap
{
    /** Il nome dell'enum generato, e quindi anche del file: /public/php/Pagine.php. */
    public const CLASSE = 'Pagine';

    /** Cartelle che non contengono pagine e che non ha senso attraversare. */
    private const SALTA = ['Common', 'vendor', 'Model', 'node_modules'];

    /**
     * Assicura che la pagina che si sta aprendo sia nell'elenco, e in caso rigenera.
     *
     * Non solleva mai: un elenco che non si riesce a scrivere - sito in sola lettura, disco
     * pieno - e' un fastidio per chi programma, non un motivo per non servire la pagina.
     */
    public static function Assicura(string $markupFile): void
    {
        try
        {
            $percorso = self::PercorsoUrl($markupFile);

            if ($percorso === '' || self::Contiene($percorso))
                return;

            self::Aggiorna();
        }
        catch (\Throwable)
        {
        }
    }

    /**
     * Riscrive l'enum leggendo l'albero dei sorgenti.
     *
     * @return string il percorso del file scritto
     */
    public static function Aggiorna(): string
    {
        $radice = self::Radice();

        $pagine = [];

        foreach (self::Markup($radice) as $file)
        {
            $relativo = trim(str_replace('\\', '/', substr($file, strlen($radice))), '/');

            $nome = self::NomeCaso(substr($relativo, 0, -strlen('.php')));

            //due percorsi diversi che darebbero lo stesso nome: il secondo si distingue,
            //invece di sovrascrivere il primo senza dirlo
            $unico = $nome;

            for ($n = 2; isset($pagine[$unico]); $n++)
                $unico = $nome . '_' . $n;

            $pagine[$unico] = self::PercorsoUrl($file);
        }

        ksort($pagine, SORT_NATURAL | SORT_FLAG_CASE);

        return self::Scrivi($pagine);
    }

    /** L'elenco contiene gia' questo percorso? */
    private static function Contiene(string $percorso): bool
    {
        $classe = '\\' . self::CLASSE;

        if (!enum_exists($classe))
            return false;

        //ReflectionEnum e non $classe::cases(): il nome della classe qui e' una stringa, e una
        //chiamata statica su una stringa nessuno la puo' verificare - ne' l'IDE ne' chi legge
        return array_any(
            (new \ReflectionEnum($classe))->getCases(),
            static fn(\ReflectionEnumBackedCase $caso): bool => $caso->getBackingValue() === $percorso
        );
    }

    /**
     * I file markup delle pagine, in tutto l'albero.
     *
     * Il riconoscimento e' "chiama Page::Run", che e' la definizione stessa di pagina in
     * questo motore: non un elenco di cartelle da tenere aggiornato a mano, e nemmeno il
     * nome del file. Si leggono i primi byte, non tutto il file: quella riga sta in cima.
     *
     * @return string[]
     */
    private static function Markup(string $radice): array
    {
        $trovati = [];

        $iteratore = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($radice, \FilesystemIterator::SKIP_DOTS),
                static function (\SplFileInfo $voce): bool
                {
                    $nome = $voce->getFilename();

                    if ($voce->isDir())
                        return !in_array($nome, self::SALTA, true)
                            && !str_starts_with($nome, '_')
                            && !str_starts_with($nome, '.');

                    return str_ends_with($nome, '.php')
                        && !str_ends_with($nome, '.code.php')
                        && !str_ends_with($nome, '.designer.php');
                }
            )
        );

        foreach ($iteratore as $voce)
        {
            /** @var \SplFileInfo $voce */
            $testa = (string)@file_get_contents($voce->getPathname(), false, null, 0, 600);

            if (str_contains($testa, 'Page::Run('))
                $trovati[] = $voce->getPathname();
        }

        return $trovati;
    }

    /** "Northwind/Categorie" -> "Northwind_Categorie", e sempre un identificatore valido. */
    private static function NomeCaso(string $relativo): string
    {
        $nome = preg_replace('/[^A-Za-z0-9_]+/', '_', str_replace('/', '_', $relativo)) ?? '';

        $nome = trim($nome, '_');

        //un caso non puo' cominciare per cifra, e non puo' essere vuoto
        if ($nome === '' || ctype_digit($nome[0]))
            $nome = 'P' . $nome;

        return $nome;
    }

    /** Dal percorso su disco all'indirizzo: e' la stessa cosa, cambia solo la radice. */
    private static function PercorsoUrl(string $file): string
    {
        $documenti = rtrim(str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');

        $file = str_replace('\\', '/', $file);

        if ($documenti === '' || !str_starts_with(strtolower($file), strtolower($documenti)))
            return '';

        return substr($file, strlen($documenti));
    }

    /** La radice dei sorgenti php: questa classe sta in Common/WebForms, due sopra c'e' lei. */
    private static function Radice(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 2));
    }

    /** @param array<string,string> $pagine */
    private static function Scrivi(array $pagine): string
    {
        $file = self::Radice() . '/' . self::CLASSE . '.php';

        $casi = '';

        foreach ($pagine as $nome => $percorso)
            $casi .= '    case ' . $nome . " = '" . $percorso . "';\n";

        $testo = "<?php\ndeclare(strict_types=1);\n\n"
            . "/**\n"
            . " * GENERATO AUTOMATICAMENTE dai markup delle pagine - non modificare a mano.\n"
            . " *\n"
            . " * Ci sono solo i file che chiamano Page::Run(), cioe' le pagine vere: si rigenera da\n"
            . " * solo quando se ne apre una che non c'e'.\n"
            . " *\n"
            . " *     \$this->RedirectToPage(Pagine::Northwind_Categorie);\n"
            . " */\n"
            . 'enum ' . self::CLASSE . ": string implements \\Common\\WebForms\\PaginaDelSito\n"
            . "{\n"
            . $casi
            . "\n"
            . "    public function Percorso(): string\n"
            . "    {\n"
            . "        return \$this->value;\n"
            . "    }\n"
            . "}\n";

        //scrittura atomica: due richieste che lo rigenerano insieme non devono poter far
        //leggere a nessuno un enum mezzo scritto, che sarebbe un errore di sintassi
        $tmp = $file . '.' . getmypid() . '.tmp';

        if (@file_put_contents($tmp, $testo) !== false)
            @rename($tmp, $file);

        return $file;
    }
}
