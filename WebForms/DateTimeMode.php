<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Cosa chiede un <dw:DatePicker>: solo il giorno, o giorno e ora.
 *
 *     <dw:DatePicker id="dtDal" Mode="Date" />
 *     <dw:DatePicker id="dtAppuntamento" Mode="DateTime" AutoPostBack="true" OnDateChanged="..." />
 *
 * Il valore dietro ogni caso e' il "type" dell'<input> HTML che lo realizza: il selettore e'
 * quello NATIVO del browser - niente librerie, niente calendario disegnato a mano - e cosi'
 * il controllo non deve sapere niente della lingua, del formato locale o del touch: lo sa il
 * browser, ed e' lui a mostrare il calendario giusto per l'utente.
 *
 * E' un enum e non due stringhe costanti perche' i valori possibili sono ESATTAMENTE due, e
 * un enum lo dice al compilatore, all'IDE e a chi legge: un Mode="Datetime" sbagliato di una
 * lettera si ferma al markup con l'elenco dei nomi buoni, invece di rendere un <input> che il
 * browser non riconosce e mostra come casella di testo.
 */
enum DateTimeMode: string
{
    /** Solo la data: <input type="date">, valore "2026-09-14". */
    case Date = 'date';

    /** Data e ora al minuto: <input type="datetime-local">, valore "2026-09-14T10:30". */
    case DateTime = 'datetime-local';

    /** Il formato con cui il browser scrive e legge il valore di questo tipo di input. */
    public function Format(): string
    {
        return match ($this) {
            self::Date     => 'Y-m-d',
            self::DateTime => 'Y-m-d\TH:i',
        };
    }
}
