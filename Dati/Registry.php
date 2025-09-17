<?php
declare(strict_types=1);
// Common/Registry.php - Registro centralizzato degli ID

namespace Common\Dati;

class Registry
{
    private static array $controlli = [];
    private static array $dati = [];

    /**
     * Registra un controllo nel Registry
     */
    public static function RegistraControllo(string $nome, int $id): void
    {
        self::$controlli[$nome] = $id;
    }

    /**
     * Registra un dato nel Registry
     */
    public static function RegistraDato(string $nome, int $id): void
    {
        self::$dati[$nome] = $id;
    }

    /**
     * Ottiene l'ID di un controllo per nome dal Registry
     */
    public static function GetIdControllo(string $nomeControllo): int
    {
        return self::$controlli[$nomeControllo] ?? \Common\Dati\Controlli::GetIdControllo($nomeControllo);
    }

    /**
     * Ottiene l'ID di un dato per nome dal Registry
     */
    public static function GetIdDato(string $nomeDato): int
    {
        return self::$dati[$nomeDato] ?? \Common\Dati\Dati::GetIdDato($nomeDato);
    }

    /**
     * Ottiene l'ID di un controllo di un dato specifico
     */
    public static function GetControlloRefId(string $nomeDato, string $nomeControllo): int
    {
        return \Common\Dati\Dati::GetIdControlloRefId($nomeDato, $nomeControllo);
    }
}