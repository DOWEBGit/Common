<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Una raccolta nome => valore con l'aria delle collection di WebForms.
 *
 *     $control->Attributes->Add('title', 'Elimina la riga');
 *     $control->Style->Add('color', 'crimson');
 *     $control->Style->Remove('color');
 *     $control->Style->Clear();
 *     $control->Attributes['title']   //si legge anche come un array
 *
 * E' la base di AttributeCollection e CssStyleCollection, che aggiungono solo il controllo
 * sul NOME: un attributo HTML e una proprieta' CSS hanno grammatiche diverse, e un nome
 * sbagliato si ferma quando lo si scrive, non al render, dove l'errore salterebbe fuori
 * lontano da chi l'ha scritto.
 *
 * Nello stato viaggia come array (ToArray / Replace): il pacchetto si riapre senza classi,
 * di proposito, e un oggetto non ci passerebbe.
 */
abstract class NamedCollection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /** @var array<string,string> */
    private array $voci = [];

    /** @param array<string,string> $voci */
    public function __construct(array $voci = [])
    {
        $this->Replace($voci);
    }

    /** Il nome e' accettabile? Chi eredita dice come. */
    abstract protected function CheckName(string $name): void;

    /** Aggiunge o cambia una voce. Torna la raccolta, cosi' se ne concatenano piu' d'una. */
    public function Add(string $name, string $value): static
    {
        $this->CheckName($name);

        $this->voci[$name] = $value;

        return $this;
    }

    public function Remove(string $name): static
    {
        unset($this->voci[$name]);

        return $this;
    }

    public function Clear(): static
    {
        $this->voci = [];

        return $this;
    }

    public function Has(string $name): bool
    {
        return array_key_exists($name, $this->voci);
    }

    /** @return array<string,string> */
    public function ToArray(): array
    {
        return $this->voci;
    }

    /**
     * Sostituisce tutto con quello che arriva dallo stato. I nomi si ricontrollano: nello
     * stato ci si arriva anche scrivendo dritto, e un nome con uno spazio dentro non sarebbe
     * un attributo in piu' - sarebbe markup iniettato.
     */
    public function Replace(array $voci): void
    {
        $this->voci = [];

        foreach ($voci as $name => $value)
            $this->Add((string)$name, (string)$value);
    }

    // ---------------------------------------------------------------- come un array

    public function offsetExists(mixed $offset): bool
    {
        return $this->Has((string)$offset);
    }

    public function offsetGet(mixed $offset): ?string
    {
        return $this->voci[(string)$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->Add((string)$offset, (string)$value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->Remove((string)$offset);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->voci);
    }

    public function count(): int
    {
        return count($this->voci);
    }
}
