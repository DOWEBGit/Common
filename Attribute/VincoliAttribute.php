<?php
declare(strict_types=1);

namespace Common\Attribute;

use Attribute;

/**
 * I vincoli di un campo, come sono dichiarati nel pannello.
 *
 * Li scrive il generatore leggendo il controllo da Kestrel: obbligatorieta', lunghezze,
 * espressione regolare, minimi e massimi, estensioni e misure - piu' i due avvisi con cui il
 * pannello li spiega.
 *
 * NON E' UNA SECONDA REGOLA. E' lo specchio di quella: la regola resta dichiarata in un
 * posto solo, nel pannello, e qui ci arriva rigenerando i Model. Serve a fermare un
 * salvataggio sbagliato PRIMA di chiamare il pipe - e a dirlo con la stessa frase che
 * direbbe Kestrel, invece di inventarne una.
 *
 * L'ultima parola resta comunque a Kestrel, che ricontrolla al salvataggio: questo taglia il
 * viaggio, non la guardia.
 *
 * Zero e stringa vuota vogliono dire "nessun limite"; Min e Max a -1 pure, che e' la
 * convenzione del pannello per i numeri.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class VincoliAttribute
{
    public function __construct(
        public bool $Obbligatorio = false,
        public int $MaxCaratteri = 0,
        public int $MaxParole = 0,
        public int $LunghezzaParola = 0,
        public string $RegEx = '',
        public int $Min = -1,
        public int $Max = -1,
        public string $Estensioni = '',
        public int $KBytesMax = 0,
        public int $LarghezzaMin = 0,
        public int $LarghezzaMax = 0,
        public int $AltezzaMin = 0,
        public int $AltezzaMax = 0,
        public string $AvvisoMancante = '',
        public string $AvvisoNonValido = ''
    )
    {
    }

    /**
     * I vincoli di un campo, dal riferimento "Model\AllegatiOrdine::Documento".
     *
     * E' il modo in cui un controllo del markup dice a quale campo appartiene il file che
     * sta per ricevere: da li' legge le regole del pannello invece di tenersene di proprie.
     */
    public static function Di(string $riferimento): ?self
    {
        //il riferimento viene dal markup ma passa anche dal client: si accetta solo la forma
        //"Classe::Campo", mai qualcosa che possa diventare altro
        if (preg_match('/^\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*::[A-Za-z_][A-Za-z0-9_]*$/', $riferimento) !== 1)
            return null;

        [$classe, $campo] = explode('::', $riferimento);

        if (!class_exists($classe) || !property_exists($classe, $campo))
            return null;

        $attributi = (new \ReflectionProperty($classe, $campo))->getAttributes(self::class);

        if ($attributi === [])
            return null;

        $istanza = $attributi[0]->newInstance();

        //newInstance() promette solo "object": il controllo costa niente e toglie di mezzo
        //il caso in cui qualcuno cambi il filtro di getAttributes() e non se ne accorga
        return $istanza instanceof self ? $istanza : null;
    }

    /** Le estensioni ammesse, gia' divise, senza punto e in minuscolo. Vuoto = nessun elenco. */
    public function EstensioniAmmesse(): array
    {
        if ($this->Estensioni === '')
            return [];

        $ammesse = [];

        foreach (explode('|', strtolower($this->Estensioni)) as $voce)
        {
            $voce = trim($voce, " .");

            if ($voce !== '')
                $ammesse[] = $voce;
        }

        return $ammesse;
    }
}
