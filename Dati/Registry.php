<?php
declare(strict_types=1);
// Common/Registry.php - Registro centralizzato degli ID

namespace Common\Dati;

class Registry
{
    /**
     * Ottiene l'ID di un controllo di un dato specifico
     */
    public static function GetControlloRefId(string $nomeDato, string $nomeControllo): int
    {
        return \Common\Dati\Dati::GetIdControlloRefId($nomeDato, $nomeControllo);
    }

    /**
     * Ottiene l'ID di un dato per nome
     */
    public static function GetDatoId(string $nomeDato): int
    {
        return \Common\Dati\Dati::GetIdDato($nomeDato);
    }

    /**
     * Ottiene l'ID di un controllo per nome
     */
    public static function GetControlloId(string $nomeControllo): int
    {
        return \Common\Dati\Controlli::GetIdControllo($nomeControllo);
    }
}