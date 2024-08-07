<?php
declare(strict_types=1);

namespace Common\Collection;

/*
 *  esempio type hint
 *  @var $servizi \Common\Collection\IntStringValue[]
    foreach ($servizi as $servizio)
    {
        $servizio->value;
    }
 * */

/**
 * @property-read int $key
 * @property-read string $value
 */
class DictionaryIntString implements \IteratorAggregate
{
    /**
     * @var IntStringValue[]
     */
    private array $dictionary;

    /**
     * DictionaryIntString constructor.
     */
    public function __construct()
    {
        $this->dictionary = array();
    }

    /**
     * Aggiunge un elemento al dizionario.
     *
     * @param int $key La chiave dell'elemento da aggiungere.
     * @param string $value Il valore dell'elemento da aggiungere.
     * @return bool True se l'elemento è stato aggiunto con successo, altrimenti false.
     */
    public function Add(int $key, string $value): bool
    {
        if ($this->ContainsKey($key)) {
            return false;
        }

        $this->dictionary[$key] = new IntStringValue($key, $value);
        return true;
    }

    /**
     * Rimuove un elemento dal dizionario.
     *
     * @param int $key La chiave dell'elemento da rimuovere.
     * @return bool True se l'elemento è stato rimosso con successo, altrimenti false.
     */
    public function Remove(int $key): bool
    {
        if ($this->ContainsKey($key)) {
            unset($this->dictionary[$key]);
            return true;
        }

        return false;
    }

    /**
     * Tenta di ottenere il valore associato a una chiave specifica.
     *
     * @param int $key La chiave dell'elemento da cercare.
     * @param string|null $value La stringa in cui memorizzare il valore, se trovato.
     * @return bool True se l'elemento è stato trovato, altrimenti false.
     */
    public function TryGet(int $key, ?string &$value): bool
    {
        if ($this->ContainsKey($key)) {
            $value = $this->dictionary[$key]->value;
            return true;
        }

        $value = null;
        return false;
    }

    /**
     * Restituisce un iteratore per il dizionario.
     *
     * @return \ArrayIterator
     */

    /** @var $servizi \Common\Collection\IntStringValue[] */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->dictionary);
    }

    /**
     * Restituisce il numero di elementi nel dizionario.
     *
     * @return int
     */
    public function Count(): int
    {
        return count($this->dictionary);
    }

    /**
     * Ordina gli elementi del dizionario in ordine ascendente basato sul valore.
     */
    public function SortValueAsc(): void
    {
        usort($this->dictionary, function ($a, $b) {
            return strcmp($a->value, $b->value);
        });
    }

    /**
     * Ordina gli elementi del dizionario in ordine discendente basato sul valore.
     */
    public function SortValueDesc(): void
    {
        usort($this->dictionary, function ($a, $b) {
            return strcmp($b->value, $a->value);
        });
    }

    /**
     * Controlla se una chiave è presente nel dizionario.
     *
     * @param int $key La chiave da controllare.
     * @return bool True se la chiave è presente, altrimenti false.
     */
    private function ContainsKey(int $key): bool
    {
        return array_key_exists($key, $this->dictionary);
    }
}
?>
