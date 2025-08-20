# Linee guida di sviluppo PHP/JS (Progetti)

## 1. Regole generali per i file PHP
- Ogni file **PHP** deve iniziare con:
  ```php
  <?php
  declare(strict_types=1);
  ```
- Il server supporta **PHP 8.4** → privilegiare codice moderno e performante.
- Per recuperare lo **stream di input**:
    - Non usare:
      ```php
      $input = file_get_contents('php://input');
      ```
    - Usare invece:
      ```php
      $input = $_POST["INPUTSTREAM"];
      ```

---

## 2. Struttura di progetto

Ogni progetto contiene sempre le seguenti cartelle:

### \Model
- Contiene le entità del dominio.
- Classi 1:1 con le tabelle DB.
- Generati automaticamente da
  \Common\CodeGenerator\CodeGenerator.php.
- Non modificare manualmente.
- Ogni model estende \Common\BaseModel.

### \Controller
- Contiene i controller.
- Uno per ogni entità del dominio.
- Ogni file contiene una sola classe che estende \Common\Base\BaseController.
- Sempre presenti i metodi:
    - OnSave
    - OnDelete

**Esempio Controller/About.php:**
```php
<?php
declare(strict_types=1);

namespace Controller;
use \Common\Base\BaseModel;
use \Common\Response\SaveResponseModel;
use \Common\Response\SaveResponse;

class About extends \Common\Base\BaseController
{
    /**
    * @var \Model\About | null $about
    */
    public static function OnSave(?\Common\Base\BaseModel $about = null): string
    {
        return "";
    }

    public static function OnDelete(?\Common\Base\BaseModel $baseModel = null): string
    {
        return "";
    }
}
```

---

## 3. Gestione input nei form

### Input standard (text, number, ecc.)
Ogni input deve avere un id univoco.
Per salvare il valore:
```javascript
TempWrite("nomeInput", document.getElementById("nomeInput").value);
```

### Input file (type="file")
Esempio:
```javascript
const fileInput = document.getElementById("inputFile");
const file = fileInput.files[0];
const reader = new FileReader();
reader.onload = function(e) {
    TempWrite("inputFile", btoa(e.target.result));
};
reader.readAsBinaryString(file);
```

### Generico
- Qualsiasi input:
  ```javascript
  TempWrite("input", document.getElementById("input"));
  ```
- Se non ci sono file:
  ```javascript
  TempWriteAllId();
  ```

---

## 4. Flusso View → Action → Controller

### Chiamata AJAX
```javascript
Action("Province", "NomeFunzione", function() {
    // gestione risposta
});
```

- La funzione NomeFunzione deve esistere nella classe Action\Province.
- L’action legge i dati con TempRead e li passa al controller.

### Esempio completo

**1. View**
```javascript
Action("Province", "Inserisci", function() {
    let message = TempRead("message");
    if (message !== '') {
        TempReadAllId(message);
    } else {
        alert("Provincia inserita con successo!");
        ReloadViewAll();
    }
});
```

**2. Action/Province.php**
```php
public function Inserisci(): void
{
    $Titolo = \Common\State::TempRead("Titolo");
    $saveResponse = \Controller\Province::InserisciProvincia($Titolo);
    // gestione risposta
}
```

**3. Controller/Province.php**
```php
public static function InserisciProvincia(string $Titolo): \Common\Response\SaveResponse
{
    $provincia = new \Model\Province();
    $provincia->Titolo = $Titolo;
    return $provincia->Save();
}
```

### Gestione del risultato di una funzione di inserimento/modific

a nel Controller

- Se NON serve restituire il model appena salvato (es. per semplici inserimenti/modifiche), la funzione può restituire direttamente un oggetto SaveResponse.
- Se invece serve restituire anche il model appena salvato (es. per catene di salvataggio o logiche che richiedono l’oggetto aggiornato), la funzione deve restituire un oggetto SaveResponseModel.
    - In questo caso, se il salvataggio ha successo ($saveResponse->Success), valorizzare la proprietà Model con il model appena salvato: `$saveResponse->Model = $model;`
- Scegliere il tipo di risposta in base alle necessità della logica applicativa.

**Esempio:**
```php
// Caso semplice
public static function InserisciProvincia(string $Titolo): \Common\Response\SaveResponse
{
    $provincia = new \Model\Province();
    $provincia->Titolo = $Titolo;
    return $provincia->Save();
}

// Caso in cui serve il model risultante
public static function Salva(string $Titolo): \Common\Response\SaveResponseModel
{
    $response = new \Common\Response\SaveResponseModel();
    $provincia = \Model\Province::GetItemByTitolo($Titolo);
    if (!$provincia) {
        $provincia = new \Model\Province();
        $provincia->Titolo = $Titolo;
        $save = $provincia->Save();
        $response->Success = $save->Success;
        $response->InternalAvviso = $save->InternalAvviso;
        if ($save->Success) $response->Model = $provincia;
        return $response;
    }
    $response->Success = true;
    $response->Model = $provincia;
    return $response;
}
```

---

## 4bis. Creazione della classe Action e delle funzioni da chiamare

Quando usi la funzione `Action("NomeClasse", "NomeFunzione", ...)` in JavaScript, assicurati che:
- Esista la classe `Action\NomeClasse` (es. `Action\Province`) nel file corrispondente (es. `Action/Province.php`).
- All'interno di questa classe esista la funzione pubblica `NomeFunzione` (es. `Inserisci`).
- Se la classe o la funzione non esistono, vanno create.
- La funzione Action deve leggere i dati tramite `TempRead` e passarli al controller corrispondente (es. `Controller\Province`).
- Nel controller, assicurati che esista una funzione dedicata per l’operazione richiesta (es. `InserisciProvincia`).
- Se la funzione nel controller non esiste, va creata.
- Non usare OnSave e OnDelete per operazioni specifiche: crea sempre funzioni dedicate con nomi chiari.

**Esempio di implementazione Action/Province.php:**
```php
<?php
declare(strict_types=1);

namespace Action;

class Province extends \Common\Base\BodyToState
{
    public function Inserisci(): void
    {
        $Titolo = \Common\State::TempRead("Titolo");
        $saveResponse = \Controller\Province::InserisciProvincia($Titolo);
        // gestione risposta
    }
}
```

**Esempio di implementazione Controller/Province.php:**
```php
public static function InserisciProvincia(string $Titolo): \Common\Response\SaveResponse
{
    $provincia = new \Model\Province();
    $provincia->Titolo = $Titolo;
    return $provincia->Save();
}
```

**Nota:**
- Se la classe Action o la funzione non esistono, vanno create.
- Se la funzione nel controller non esiste, va creata.
- Non usare OnSave e OnDelete per operazioni specifiche: crea sempre funzioni dedicate con nomi chiari.

---

## 5. Coerenza nomi input ↔ proprietà

L’attributo id e name degli input deve corrispondere al nome della proprietà del model/controller.

Tutti gli input (inclusi select, text, number, ecc.) che devono essere letti tramite TempRead o WindowRead devono avere sempre anche la classe `TempRead`.

**Esempio:**
Model → proprietà Titolo

```html
<input type="text" id="Titolo" name="Titolo" class="TempRead" />
```

---

## 5bis. Gestione delle select popolate da un Model (GetList)

- Quando una select HTML viene popolata tramite un model (es. \Model\TipiConnessione::GetList()), l’attributo value delle option deve essere l’id della riga (intero).
- L’attributo id e name della select deve corrispondere al nome del model (es. TipiConnessione).
- La select deve avere sempre anche la classe `TempRead` (oltre ad eventuali altre classi), per essere letta da TempRead o WindowRead.
- In fase di invio (JS), passa l’id selezionato tramite TempWrite usando il nome del model (es. TempWrite("TipiConnessione", ...)).
- In Action, recupera l’id con TempRead usando il nome del model, poi usa GetItemById per ottenere il model corrispondente.
- Passa l’oggetto model (o null se non trovato) al controller, non solo l’id.
- Nel controller, accetta come parametro il model (o null) e gestisci il caso in cui sia null.
- Questo garantisce coerenza e permette di accedere a tutte le proprietà del model direttamente nel controller.

**Esempio:**

**View (PHP):**
```php
$tipiConnessione = \Model\TipiConnessione::GetList();
<select id="TipiConnessione" name="TipiConnessione" class="form-control TempRead">
    <?php foreach ($tipiConnessione as $tipo) { ?>
        <option value="<?= $tipo->Id ?>"><?= htmlspecialchars($tipo->Titolo) ?></option>
    <?php } ?>
</select>
```

**JS:**
```javascript
TempWrite("TipiConnessione", document.getElementById("TipiConnessione").value);
```

**Action:**
```php
$tipiConnessioneId = (int)\Common\State::TempRead("TipiConnessione");
$tipiConnessione = null;
if ($tipiConnessioneId > 0) {
    $tipiConnessione = \Model\TipiConnessione::GetItemById($tipiConnessioneId);
}
\Controller\Province::ImportaDaExcel($fileBytes, $tipiConnessione);
```

**Controller:**
```php
public static function ImportaDaExcel(string $fileBytes, ?\Model\TipiConnessione $tipiConnessione): SaveResponse
{
    if ($tipiConnessione) {
        // Usa le proprietà del model
    } else {
        // Gestisci il caso null
    }
}
```

---

## 6. Editor riutilizzabili (Inserimento/Modifica)

- Ogni editor deve avere un campo nascosto id.
- Se id > 0 → modifica record esistente.
- Se id = 0 o vuoto → nuovo inserimento.
- Controller e Action gestiscono entrambi i casi.

---

## 7. Struttura delle View

**Esempio di View vuota:**
```php
<?php
declare(strict_types=1);

namespace View;

use Common\Base\BaseView;
use Common\Base\IView;

class InserisciProvincia extends BaseView implements IView
{
    public function Server(): void
    {

    }

    public function Client(): void
    {
        
    }
}
```

### Parte Server (PHP + JS di supporto)
```php
$provincia = \Code\ObjectFromQuery::GetProvince();
if ($provincia)
    \Common\State::WindowWrite("Id", (string)$provincia->Id);
```

JS di invio:
```javascript
function inviaProvincia()
{
    TempWriteAllId();
    <?php /* @see \Action\Province::Inserisci() */ ?>
    Action("Province", "Inserisci", function() {
        let message = TempRead("message");
        if (message !== '')
            TempReadAllId(message);
        else {
            alert("Provincia inserita con successo!");
            ReloadViewAll();
        }
    });
}
```

### Parte Client (HTML form)
```php
$provincia = \Model\Province::GetItemById((int)\Common\State::WindowRead("Id"));
?>
<form id="formProvincia" onsubmit="return false;">
    <input type="hidden" name="id" id="id" value="<?= $provincia->Id ?>" />
    <div class="mb-3">
        <label for="Titolo" class="form-label">Nome Provincia</label>
        <input type="text" class="form-control" id="Titolo" name="Titolo"
               value="<?= \Common\State::WindowRead("Titolo", $provincia ? $provincia->Titolo : "") ?>" />
    </div>
    <button type="button" class="btn btn-primary" onclick="inviaProvincia()">Salva</button>
</form>
<?php
```
