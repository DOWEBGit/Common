<?php
declare(strict_types=1);

namespace Common\Dati;

class Controlli
{
    /*Crea un controllo da agganciare ad un dato, ritorna l'id del controllo
     *ritorna -1 in caso di errore
     *@return int
     */
    private static function CreaControllo(
        int $id,
        \Common\Dati\Enum\TipoDatoEnum $tipoDato,
        \Common\Dati\Enum\TipoInputEnum $tipoInput,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $fileKbMax = 0,
        string $fileEstensioni = '',
        string $immaginiRidimensionamento = 'Nessuno',
        string $immaginiRidimensionamentoColore = '',
        int $immagineAltezzaMassima = 0,
        int $immagineAltezzaMinima = 0,
        int $immagineLarghezzaMassima = 0,
        int $immagineLarghezzaMinima = 0,
        int $immagineAutoLarghezza = 0,
        int $immagineAutoAltezza = 0,
        string $immagineDisposizioneLogo = 'NessunLogo',
        int $numeriValoreMax = 0,
        int $numeriValoreMin = 0,
        int $testoLunghezzaParole = 0,
        int $testoMaxCaratteri = 0,
        int $testoMaxParole = 0,
        string $testoRegEx = '',
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int
    {
        $obj = PHPDOWEB();

        //guardo se il controllo esiste già nel caso in cui mi abbiano passato un id == 0
        if ($id == 0)
        {
            foreach ($obj->ControlliGetList()->Controlli as $controllo)
            {
                if (strcasecmp($controllo->Nome, $nome) == 0)
                {
                    $result = $controllo;
                    return (int)$result->Id;
                }
            }
        }

        $listaOpzioni = $opzioni == '' ? [] : explode(PHP_EOL, $opzioni);

        $arr = $obj->ControlliSave(
            $id,
            $tipoDato->value,
            $tipoInput->value,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            (string)$fileKbMax,
            $fileEstensioni,
            $immaginiRidimensionamento,
            $immaginiRidimensionamentoColore,
            (string)$immagineAltezzaMassima,
            (string)$immagineAltezzaMinima,
            (string)$immagineLarghezzaMassima,
            (string)$immagineLarghezzaMinima,
            (string)$immagineAutoLarghezza,
            (string)$immagineAutoAltezza,
            $immagineDisposizioneLogo,
            (string)$numeriValoreMax,
            (string)$numeriValoreMin,
            (string)$testoLunghezzaParole,
            (string)$testoMaxCaratteri,
            (string)$testoMaxParole,
            $testoRegEx,
            $decode ? "True" : "False",
            (string)$adminRighe,
            (string)$adminColonne,
            $autoIncrement ? "True" : "False",
            $listaOpzioni
        );

        if (!$arr->Errore)
            return (int)$arr->Id;

        \Common\Log::Error("Impossibile creare il controllo $nome: " . $arr->Avviso);

        return -1;
    }

    /*
     * Ritorna i tipi di input disponibili per il tipo di dato passato
     * @param \Common\Dati\Enum\TipoDatoEnum $tipoDato
     * @return array di \Common\Dati\Enum\TipoInputEnum
     */
    public static function TipiInputDisponibili(\Common\Dati\Enum\TipoDatoEnum $tipoDato): array
    {
        $result = [];

        if ($tipoDato == \Common\Dati\Enum\TipoDatoEnum::Data || $tipoDato == \Common\Dati\Enum\TipoDatoEnum::DataOra)
            $result[] = \Common\Dati\Enum\TipoInputEnum::TextBox;
        else if ($tipoDato == \Common\Dati\Enum\TipoDatoEnum::Numeri)
        {
            $result[] = \Common\Dati\Enum\TipoInputEnum::TextBox;
            $result[] = \Common\Dati\Enum\TipoInputEnum::CheckBox;
        }
        else if ($tipoDato == \Common\Dati\Enum\TipoDatoEnum::Testo)
        {
            $result[] = \Common\Dati\Enum\TipoInputEnum::TextBox;
            $result[] = \Common\Dati\Enum\TipoInputEnum::TextArea;
            $result[] = \Common\Dati\Enum\TipoInputEnum::RichTextBox;
            $result[] = \Common\Dati\Enum\TipoInputEnum::RichTextBoxMini;
            $result[] = \Common\Dati\Enum\TipoInputEnum::DropDownList;
            $result[] = \Common\Dati\Enum\TipoInputEnum::ListBox;
        }
        else if ($tipoDato == \Common\Dati\Enum\TipoDatoEnum::File || $tipoDato == \Common\Dati\Enum\TipoDatoEnum::Immagini)
            $result[] = \Common\Dati\Enum\TipoInputEnum::FileInput;
        else if ($tipoDato == \Common\Dati\Enum\TipoDatoEnum::Dato)
        {
            $result[] = \Common\Dati\Enum\TipoInputEnum::TextBox;
            $result[] = \Common\Dati\Enum\TipoInputEnum::DropDownList;
            $result[] = \Common\Dati\Enum\TipoInputEnum::ListBox;
        }

        return $result;
    }

    public static function CreaControlloTestoTextBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $testoMaxCaratteri,
        int $testoLunghezzaParole = 0,
        int $testoMaxParole = 0,
        string $testoRegEx = '',
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Testo,
            \Common\Dati\Enum\TipoInputEnum::TextBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            testoMaxCaratteri: $testoMaxCaratteri,
            testoLunghezzaParole: $testoLunghezzaParole,
            testoMaxParole: $testoMaxParole,
            testoRegEx: $testoRegEx,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloTestoTextArea(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $testoMaxCaratteri,
        int $adminRighe = 3,
        int $adminColonne = 4,
        int $testoLunghezzaParole = 0,
        int $testoMaxParole = 0,
        string $testoRegEx = '',
        bool $decode = false,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Testo,
            \Common\Dati\Enum\TipoInputEnum::TextArea,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            testoMaxCaratteri: $testoMaxCaratteri,
            testoLunghezzaParole: $testoLunghezzaParole,
            testoMaxParole: $testoMaxParole,
            testoRegEx: $testoRegEx,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloTestoDropDownList(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        string $opzioni, // Le opzioni del dropdown
        bool $decode = false,
        bool $autoIncrement = false
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Testo,
            \Common\Dati\Enum\TipoInputEnum::DropDownList,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            decode: $decode,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    // === NUMERI ===

    public static function CreaControlloNumeriTextBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $numeriValoreMin = 0,
        int $numeriValoreMax = 0,
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Numeri,
            \Common\Dati\Enum\TipoInputEnum::TextBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            numeriValoreMin: $numeriValoreMin,
            numeriValoreMax: $numeriValoreMax,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloNumeriCheckBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        bool $decode = false,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Numeri,
            \Common\Dati\Enum\TipoInputEnum::CheckBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            decode: $decode,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    // === FILE E IMMAGINI ===

    public static function CreaControlloFileInput(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $fileKbMax,
        string $fileEstensioni,
        bool $decode = false,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::File,
            \Common\Dati\Enum\TipoInputEnum::FileInput,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            fileKbMax: $fileKbMax,
            fileEstensioni: $fileEstensioni,
            decode: $decode,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloImmaginiFileInput(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $fileKbMax,
        string $fileEstensioni,
        string $immaginiRidimensionamento = 'Nessuno',
        string $immaginiRidimensionamentoColore = '',
        int $immagineAltezzaMassima = 0,
        int $immagineAltezzaMinima = 0,
        int $immagineLarghezzaMassima = 0,
        int $immagineLarghezzaMinima = 0,
        int $immagineAutoLarghezza = 0,
        int $immagineAutoAltezza = 0,
        string $immagineDisposizioneLogo = 'NessunLogo',
        bool $decode = false,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Immagini,
            \Common\Dati\Enum\TipoInputEnum::FileInput,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            fileKbMax: $fileKbMax,
            fileEstensioni: $fileEstensioni,
            immaginiRidimensionamento: $immaginiRidimensionamento,
            immaginiRidimensionamentoColore: $immaginiRidimensionamentoColore,
            immagineAltezzaMassima: $immagineAltezzaMassima,
            immagineAltezzaMinima: $immagineAltezzaMinima,
            immagineLarghezzaMassima: $immagineLarghezzaMassima,
            immagineLarghezzaMinima: $immagineLarghezzaMinima,
            immagineAutoLarghezza: $immagineAutoLarghezza,
            immagineAutoAltezza: $immagineAutoAltezza,
            immagineDisposizioneLogo: $immagineDisposizioneLogo,
            decode: $decode,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    // === DATA E DATAORA ===

    public static function CreaControlloDataTextBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Data,
            \Common\Dati\Enum\TipoInputEnum::TextBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloDataOraTextBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::DataOra,
            \Common\Dati\Enum\TipoInputEnum::TextBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    // === CONTROLLI PER FOREIGN KEY (TIPO DATO) ===

    public static function CreaControlloDatoDropDownList(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Dato,
            \Common\Dati\Enum\TipoInputEnum::DropDownList,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloDatoListBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Dato,
            \Common\Dati\Enum\TipoInputEnum::ListBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloDatoTextBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        bool $decode = false,
        int $adminRighe = 1,
        int $adminColonne = 1,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Dato,
            \Common\Dati\Enum\TipoInputEnum::TextBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    // === METODI COMPLEMENTARI ===

    public static function CreaControlloTestoRichTextBox(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $testoMaxCaratteri,
        int $adminRighe = 10,
        int $adminColonne = 4,
        int $testoLunghezzaParole = 0,
        int $testoMaxParole = 0,
        string $testoRegEx = '',
        bool $decode = false,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Testo,
            \Common\Dati\Enum\TipoInputEnum::RichTextBox,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            testoMaxCaratteri: $testoMaxCaratteri,
            testoLunghezzaParole: $testoLunghezzaParole,
            testoMaxParole: $testoMaxParole,
            testoRegEx: $testoRegEx,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }

    public static function CreaControlloTestoRichTextBoxMini(
        int $id,
        string $nome,
        string $descrizione,
        string $avvisoCampoNonValido,
        string $avvisoCampoDuplicato,
        string $avvisoCampoMancante,
        int $testoMaxCaratteri,
        int $adminRighe = 5,
        int $adminColonne = 4,
        int $testoLunghezzaParole = 0,
        int $testoMaxParole = 0,
        string $testoRegEx = '',
        bool $decode = false,
        bool $autoIncrement = false,
        string $opzioni = ''
    ): int {
        return self::CreaControllo(
            $id,
            \Common\Dati\Enum\TipoDatoEnum::Testo,
            \Common\Dati\Enum\TipoInputEnum::RichTextBoxMini,
            $nome,
            $descrizione,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoMancante,
            testoMaxCaratteri: $testoMaxCaratteri,
            testoLunghezzaParole: $testoLunghezzaParole,
            testoMaxParole: $testoMaxParole,
            testoRegEx: $testoRegEx,
            decode: $decode,
            adminRighe: $adminRighe,
            adminColonne: $adminColonne,
            autoIncrement: $autoIncrement,
            opzioni: $opzioni
        );
    }
}