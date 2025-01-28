<?php
declare(strict_types=1);

namespace Common;

class Lock
{
    public static function Lock(string $key, callable $callback): void
    {
        $lockFile = 'c:' . DIRECTORY_SEPARATOR . 'windows' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . md5($key) . '.lock';

        while (true)
        {
            $fileHandle = @fopen($lockFile, 'c'); // Sopprimi temporaneamente gli errori con @

            if ($fileHandle !== false && flock($fileHandle, LOCK_EX))
            {
                // Prova a prendere il lock
                try
                {
                    // Esegui il callback
                    call_user_func($callback);
                }
                finally
                {
                    // Rilascia il lock e chiudi il file
                    flock($fileHandle, LOCK_UN);
                    fclose($fileHandle);

                    // Rimuovi il file di lock
                    if (file_exists($lockFile))
                    {
                        unlink($lockFile);
                    }
                }

                return; // Esci se il lock è stato gestito correttamente
            }

            if ($fileHandle !== false)
                fclose($fileHandle); // Chiudi il file se aperto ma il lock è fallito

            usleep(1000); // Aspetta il ritardo specificato (1 millisecondo)
        }
    }

    public static function TryEnter(string $key, callable $callback): bool
    {
        $lockFile = 'c:' . DIRECTORY_SEPARATOR . 'windows' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . md5($key) . '.lock';

        $fileHandle = @fopen($lockFile, 'c'); // Sopprimi temporaneamente gli errori

        if ($fileHandle !== false && flock($fileHandle, LOCK_EX | LOCK_NB))
        {
            // NB evita attese per il lock
            try
            {
                call_user_func($callback);
            }
            finally
            {
                flock($fileHandle, LOCK_UN); // Rilascia il lock
                fclose($fileHandle); // Chiudi il file

                if (file_exists($lockFile))
                    unlink($lockFile); // Rimuovi il file di lock
            }
            return true; // Lock acquisito con successo
        }

        if ($fileHandle !== false)
            fclose($fileHandle); // Chiudi il file se non riesci a prendere il lock

        return false; // Lock non acquisito
    }
}
