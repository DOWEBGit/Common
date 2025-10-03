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
- Prima di utilizzare librerie esterne, controllare nel file `start.php` se è presente la riga:
  ```php
  require_once __DIR__ . '/vendor/autoload.php';
  ```
  Se presente, significa che è attivo l'autoload di Composer e si possono utilizzare le librerie installate in `vendor/`. Verificare quali pacchetti sono disponibili consultando il file `composer.json`.

**Nota sicurezza output:**
- Non è necessario usare `htmlspecialchars()` quando si stampano i valori letti da database nelle pagine, perché i valori vengono già salvati encodati.
- Negli editor, o comunque quando si deve stampare un valore letto da un Model dentro un input, bisogna usare la funzione `\Common\Convert::ForInputValue($valore)`.

**Nota sui link delle pagine:**
- `$_SERVER['REQUEST_URI']` non esiste nel sistema. Per i link delle pagine utilizzare sempre i metodi della classe `\Code\GetLink` che hanno nomi simili alle View su cui si sta lavorando.
- Se non si trova un metodo appropriato in `\Code\GetLink`, lasciare una stringa vuota con un commento `// TODO: verificare metodo GetLink` e avvisare via chat.

---

## 1bis. Regole generali di formattazione del codice

### Blocchi di controllo (if, for, foreach, while)

Per tutti i blocchi di controllo del flusso, sia in **PHP** che in **JavaScript**, seguire queste regole:

#### Uso delle parentesi graffe
- **Usare parentesi graffe `{}` solo se il blocco contiene 2 o più righe di codice**
- **Non usare parentesi graffe per blocchi di una sola riga**

#### Spaziatura
- **Ogni blocco deve essere preceduto da una riga vuota** (per separarlo visivamente dal codice precedente)
- **Ogni blocco deve essere seguito da una riga vuota** (per separarlo visivamente dal codice successivo)

#### Esempi corretti PHP:
```php
// ❌ SBAGLIATO - parentesi graffe inutili per una riga
if ($tipiConnessioneId > 0) {
    $tipiConnessione = \Model\TipiConnessione::GetItemById($tipiConnessioneId);
}

// ✅ CORRETTO - una riga senza parentesi graffe
if ($tipiConnessioneId > 0)
    $tipiConnessione = \Model\TipiConnessione::GetItemById($tipiConnessioneId);

// ✅ CORRETTO - più righe con parentesi graffe
if ($tipiConnessioneId > 0) {
    $tipiConnessione = \Model\TipiConnessione::GetItemById($tipiConnessioneId);
    $tipoTitolo = $tipiConnessione->Titolo;
}

// ✅ CORRETTO - spaziatura con riga vuota prima e dopo
$codice = "esempio";

if ($condizione)
    $risultato = "singola riga";

$altrocodice = "continua";

// ✅ CORRETTO - foreach con blocco multiplo
foreach ($elementi as $elemento) {
    $elemento->Aggiorna();
    $elemento->Save();
}

// ✅ CORRETTO - foreach con singola riga
foreach ($elementi as $elemento)
    $elemento->Process();
```

#### Esempi corretti JavaScript:
```javascript
// ❌ SBAGLIATO - parentesi graffe inutili per una riga
if (message !== '') {
    alert(message);
}

// ✅ CORRETTO - una riga senza parentesi graffe
if (message !== '')
    alert(message;

// ✅ CORRETTO - più righe con parentesi graffe
if (message !== '') {
    alert(message);
    ReloadViewAll();
}

// ✅ CORRETTO - spaziatura con riga vuota prima e dopo
let data = TempRead("data");

if (data.length > 0)
    processData(data);

let result = "elaborazione completata";

// ✅ CORRETTO - for loop con blocco multiplo
for (let i = 0; i < items.length; i++) {
    items[i].update();
    items[i].save();
}

// ✅ CORRETTO - for loop con singola riga
for (let i = 0; i < items.length; i++)
    items[i].process();
```

#### Applicazione alle strutture principali:
- **if/else**: seguire sempre queste regole
- **for/foreach**: seguire sempre queste regole  
- **while/do-while**: seguire sempre queste regole
- **try/catch**: seguire sempre queste regole (raramente si ha un catch di una sola riga)

Questa formattazione migliora la leggibilità del codice e mantiene coerenza in tutto il progetto.

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

### Principio di responsabilità per salvataggio/eliminazione Model

**REGOLA FONDAMENTALE**: Ogni Model deve essere salvato/eliminato **SOLO** nel proprio Controller corrispondente.

#### Quando creare metodi dedicati nel Controller:
- **SEMPRE** quando ci sono validazioni custom oltre al semplice `$model->Save()` o `$model->Delete()`
- **SEMPRE** quando si salvano/eliminano Model di altre entità insieme al Model principale
- **SEMPRE** quando la logica di salvataggio/eliminazione è più complessa di una singola riga

#### Quando è accettabile NON creare metodi dedicati:
- **SOLO** quando si fa una semplice operazione diretta senza logica aggiuntiva:
  ```php
  // Accettabile in casi molto semplici
  $model = \Model\NomeModel::GetItemById($id);
  $deleteResult = $model->Delete();
  ```

#### Esempi di violazioni da EVITARE:
```php
// ❌ SBAGLIATO - Salvare Model di altre entità nel Controller sbagliato
class SegnalazioniController {
    public function salvaSegnalazione() {
        // ...logica segnalazione...
        
        // VIOLAZIONE: salvataggio Model di altra entità
        $valoreCampo = new \Model\ValoriCampiPersonalizzati();
        $valoreCampo->Save();
        
        // VIOLAZIONE: salvataggio Model di altra entità  
        $allegato = new \Model\Allegati();
        $allegato->Save();
    }
}
```

#### Approccio CORRETTO:
```php
// ✅ CORRETTO - Ogni Model nel proprio Controller
class SegnalazioniController {
    public function salvaSegnalazione() {
        // ...logica segnalazione...
        
        // Delega ai Controller dedicati
        \Controller\ValoriCampiPersonalizzati::SalvaValore($segnalazione, $campoId, $valore);
        \Controller\Allegati::SalvaAllegato($segnalazione, $nomeFile, $bytes);
    }
}

class ValoriCampiPersonalizzatiController {
    public static function SalvaValore($segnalazione, $campoId, $valore): SaveResponse {
        // Validazione + creazione + salvataggio Model proprio
        $valoreCampo = new \Model\ValoriCampiPersonalizzati();
        // ...logica specifica...
        return $valoreCampo->Save();
    }
}

class AllegatiController {
    public static function SalvaAllegato($segnalazione, $nomeFile, $bytes): SaveResponse {
        // Validazione + creazione + salvataggio Model proprio  
        $allegato = new \Model\Allegati();
        // ...logica specifica...
        return $allegato->Save();
    }
}
```

#### Vantaggi di questo approccio:
- **Centralizzazione**: Tutta la logica di validazione/salvataggio di un Model è in un solo posto
- **Manutenibilità**: Modifiche alle regole di un Model richiedono modifiche solo nel suo Controller
- **Riusabilità**: I metodi possono essere riutilizzati da altri Controller
- **Testabilità**: Ogni Controller può essere testato indipendentemente
- **Separazione responsabilità**: Ogni Controller gestisce solo il proprio dominio

---
## 3. Gestione input nei form

### Input standard (text, number, ecc.)
Ogni input deve avere un id univoco e la classe `TempData`.
Per salvare il valore:
```javascript
TempWrite("nomeInput", document.getElementById("nomeInput").value);
```

**Esempio di input per i model:**
```html
<label for="Nome" class="form-label">Nome</label>
<input aria-describedby="ErroreNome" type="text" class="form-control TempData" id="Nome" name="Nome" maxlength="200">
<span id="ErroreNome" class="danger" aria-live="assertive"></span>
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
<?php /* @see \Action\Province::NomeFunzione() */ ?>
Action("Province", "NomeFunzione", function() {
    // gestione risposta
});
```

- La funzione NomeFunzione deve esistere nella classe Action\Province.
- L'action legge i dati con TempRead e li passa al controller.
- **OBBLIGATORIO**: Prima di ogni chiamata `Action()` in JavaScript, inserire sempre un commento `@see` per permettere la navigazione diretta con Ctrl+click: `<?php /* @see \Action\NomeClasse::NomeFunzione() */ ?>`

### Esempio completo

**1. View**
```javascript
<?php /* @see \Action\Province::Inserisci() */ ?>
Action("Province", "Inserisci", function() {
    let message = TempRead("message");
    if (message !== '') {
        TempReadAllIdWithSpan(message);
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

### Gestione del risultato di una funzione di inserimento/modifica nel Controller

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
        if (!$saveResponse->Success)
        {
            foreach ($saveResponse->InternalAvvisi as $key => $value)
                \Common\State::TempWrite($key, $value);

            \Common\State::TempWrite("message", $saveResponse->AvvisoDecode(PHP_EOL));
        }
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
- Se id > 0 → tentare di recuperare il record con `GetItemById()`:
  - Se il record esiste → modifica record esistente
  - Se il record non esiste (null) → nuovo inserimento
- Se id = 0 o vuoto → nuovo inserimento.
- Controller e Action gestiscono entrambi i casi.

**Esempio di logica corretta:**
```php
$model = \Model\NomeModel::GetItemById((int)\Common\State::WindowRead("Id", "0"));

// Se $model è null (ID = 0, ID non valido o nuovo inserimento)
if (!$model) {
    $model = new \Model\NomeModel();
    // Logica per nuovo inserimento
} else {
    // Logica per modifica esistente
}
```

---

## 7. Struttura delle View

### Best practice generali per le View

- **Gestione dei generatori**: se un metodo come `GetList()` restituisce un generatore, non usare `empty()` direttamente. 
  - **Per contare gli elementi**: usa `GetCount()` con gli stessi parametri (wherePredicate, whereValues, ecc.) invece di convertire in array solo per contare
  - **Per iterare più volte**: converti in array con `iterator_to_array()` solo se necessario per iterazioni multiple
  ```php
  // ❌ NON FARE - inefficiente
  $richieste = iterator_to_array(\Model\RichiesteInterne::GetList());
  if (empty($richieste)) { ... }
  
  // ✅ CORRETTO - usa GetCount per verificare l'esistenza
  $count = \Model\RichiesteInterne::GetCount();
  if ($count == 0) { 
      echo "Nessun elemento trovato"; 
  } else {
      $richieste = \Model\RichiesteInterne::GetList();
      foreach ($richieste as $item) { ... }
  }
  
  // ✅ CORRETTO - con filtri
  $where = '[Stato] = {0}';
  $whereValues = ['Attivo'];
  $count = \Model\RichiesteInterne::GetCount(wherePredicate: $where, whereValues: $whereValues);
  if ($count > 0) {
      $richieste = \Model\RichiesteInterne::GetList(wherePredicate: $where, whereValues: $whereValues);
      foreach ($richieste as $item) { ... }
  }
  ```

- **Editor**:
  - Usa sempre un campo hidden per l'ID (`<input type="hidden" ...>`).
  - Se l'ID è > 0, tentare di recuperare il record con `GetItemById()`:
    - Se il record esiste → modifica record esistente
    - Se il record non esiste (null) → nuovo inserimento
  - Se l'ID è = 0 o vuoto → nuovo inserimento.
  - Precompila i campi usando `\Common\State::WindowRead()` per mantenere i dati tra reload e validazioni.
  - Organizza i campi in righe e colonne Bootstrap per una migliore UX.
  - Gestisci la selezione dinamica delle dipendenze tra select (es. categoria/corso) tramite `onchange` e reload della view.

- **Elenco**:
  - Mostra i dati in card o tabella responsive.
  - Per le azioni (modifica/elimina):
    - Modifica: link diretto alla view di editing, usando un helper come `\Code\GetLink::...`.
    - Elimina: bottone che chiama una funzione JS con conferma, che invia la richiesta all’action corrispondente.
  - Non duplicare la logica di routing: usa sempre i metodi centralizzati per generare i link.

- **Mai modificare i Model**: tutta la logica di business e di manipolazione dei dati deve essere gestita nei Controller, usando solo i metodi pubblici già esistenti nei Model.

- **Action**:
  - Ricevi sempre i parametri tramite `\Common\State::BodyRead()` o `\Common\State::TempRead()`.
  - Gestisci la risposta scrivendo eventuali messaggi di errore in TempWrite.

- **Controller**:
  - Implementa i metodi di business (es. EliminaRichiestaInterne) usando solo `GetItemById` e `Delete` del Model.
  - Restituisci sempre un oggetto SaveResponse.

- **Gestione degli ID e dei dati**:
  - Se l’ID è > 0, modifica; se 0 o vuoto, nuovo inserimento.
  - Passa sempre l’ID tramite campo hidden e tramite URL per le azioni di modifica.
  - Usa sempre i dati di State per precompilare i form, così da mantenere i valori anche in caso di errore di validazione.

- **JavaScript nelle View**:
  - Usa sempre `TempWriteAllId()` prima di chiamare Action.
  - Gestisci la risposta mostrando eventuali errori o ricaricando la view in caso di successo.
  - Usa `ReloadViewAll()` per aggiornare la pagina dopo operazioni di salvataggio o eliminazione.

- **Sicurezza e validazione**:
  - Usa sempre `htmlspecialchars()` per l’output di dati dinamici nelle view.
  - Gestisci la validazione lato server e mostra i messaggi di errore accanto ai campi tramite span dedicati.

---

## 7bis. Linee guida per la realizzazione degli elenchi (liste)

Quando si sviluppano viste elenco (es. ElencoProvince, ElencoComuni, ecc.), seguire queste indicazioni:

- Utilizzare una struttura HTML semantica:
  - Per dati tabellari, utilizzare l'elemento <code>&lt;table&gt;</code> con intestazione (<code>&lt;thead&gt;</code>) e corpo (<code>&lt;tbody&gt;</code>).
  - Inserire eventuali filtri direttamente all'interno delle celle <code>&lt;th&gt;</code> corrispondenti nel <code>&lt;thead&gt;</code>.
  - Includere sempre una colonna "Azioni" per pulsanti come Modifica/Elimina.
- Gestione filtri:
  - I filtri devono essere visibili e allineati con la colonna che filtrano.
  - Il valore del filtro deve essere letto/scritto tramite \Common\State::WindowRead/WindowWrite.
- Azioni:
  - Per l'eliminazione, chiedere conferma all'utente (es. con <code>confirm</code> JS).
  - Per la modifica, prevedere un redirect all'editor della singola entità.
- Sicurezza:
  - Eseguire sempre l'escape dell'output (es. <code>htmlspecialchars</code>) per i dati mostrati.
- Separazione logica:
  - La funzione <code>Server()</code> deve solo includere la struttura base e gli script JS.
  - La funzione <code>Client()</code> deve occuparsi di leggere i dati, applicare filtri e generare la tabella.
- Riutilizzo:
  - Se possibile, creare funzioni JS riutilizzabili per azioni comuni (es. elimina, filtra, redirect).

Esempio di struttura minima aggiornata:

```php
public function Server(): void
{
    ?>
    <script type="text/javascript">
        function applicaFiltro() {
            WindowWrite("Titolo", document.getElementById("Titolo").value);
            ReloadViewAll();
        }

        function eliminaProvincia(id) {
            if (!confirm("Sei sicuro di voler eliminare questa provincia?")) return;
            TempWrite("Id", id);
            <?php /* @see \\Action\\Province::Elimina() */ ?>
            Action("Province", "Elimina", function() {
                let message = TempRead("message");
                if (message !== "")
                    alert(message);
                ReloadViewAll();
            });
        }

        function redirectToProvinciaEditor(id) {
            window.location.href = "";
        }
    </script>
    
    <main class="main">
        <section class="section">
            <div class="container" <?= self::GetViewId() ?>>
                <?php self::Client(); ?>
            </div>
        </section>
    </main>
    <?php
}

public function Client(): void
{
    $filtroTitolo = \Common\State::WindowRead("Titolo", "");
    \Common\State::WindowWrite("Titolo", $filtroTitolo);
    $province = \Controller\Province::GetListFiltered($filtroTitolo);
    ?>
    <h2>Elenco Province</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>
                    <label for="Titolo" class="form-label">Filtro per nome provincia</label><br />
                    <input type="text" class="form-control TempRead" id="Titolo" name="Titolo" value="<?= htmlspecialchars($filtroTitolo) ?>" />
                    <button type="button" class="btn btn-secondary mt-2" onclick="applicaFiltro()">Applica filtro</button>
                </th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($province as $provincia) { ?>
            <tr>
                <td><?= htmlspecialchars($provincia->Titolo) ?></td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm" onclick="redirectToProvinciaEditor(<?= $provincia->Id ?>)">Modifica</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminaProvincia(<?= $provincia->Id ?>)">Elimina</button>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php
}
```

Adattare la struttura, i nomi delle variabili e le colonne alle esigenze specifiche della vista elenco.

---

## 8. Costruzione delle query con parametri: sintassi e best practice

Quando si costruiscono query per i metodi dei model (es. GetList, GetItemBy...), utilizzare sempre la sintassi con i nomi degli attributi racchiusi tra parentesi quadre e i parametri come placeholder numerici tra parentesi graffe.

### Regole:
- **Per le clausole WHERE**: Ogni attributo deve essere scritto come `[NomeAttributo]` (con parentesi quadre).
- **Per le clausole ORDER BY**: Ogni attributo deve essere scritto come `NomeAttributo` (senza parentesi quadre).
- Ogni parametro deve essere rappresentato come `{N}` dove N è l'indice del parametro corrispondente nell'array `$whereValues`.
- L'ordine dei parametri nella query deve corrispondere all'ordine dei valori in `$whereValues`.
- Se ci sono più filtri, incrementare l'indice per ogni parametro.
- Gli unici operatori consentiti per costruire le query sono: `AND`, `OR`, le parentesi tonde, `=`, `<>`, `>`, `<` e `LIKE`.

### Esempio con un solo filtro
```php
$where = '[Titolo] LIKE {0}';
$whereValues = ['%' . $filtroTitolo . '%'];
$listaProvince = \Model\Province::GetList(wherePredicate: $where, whereValues: $whereValues);
```

### Esempio di chiamata semplice
```php
$where = '';
$whereValues = [];
if ($filtroTitolo !== '') {
    $where = '[Titolo] LIKE {0}';
    $whereValues[] = '%' . $filtroTitolo . '%';
}
$listaProvince = \Model\Province::GetList(wherePredicate: $where, whereValues: $whereValues);
```

### Esempio con paginazione
```php
$item4page = 20; // elementi per pagina
$page = 2; // pagina corrente (parte da 1)
$where = '';
$whereValues = [];
if ($filtroTitolo !== '') {
    $where = '[Titolo] LIKE {0}';
    $whereValues[] = '%' . $filtroTitolo . '%';
}
$listaProvince = \Model\Province::GetList(item4page: $item4page, page: $page, wherePredicate: $where, whereValues: $whereValues);
```

### Esempio con ordinamento
```php
$orderBy = '[Titolo] ASC';
$where = '';
$whereValues = [];
if ($filtroTitolo !== '') {
    $where = '[Titolo] LIKE {0}';
    $whereValues[] = '%' . $filtroTitolo . '%';
}
$listaProvince = \Model\Province::GetList(wherePredicate: $where, whereValues: $whereValues, orderPredicate: $orderBy);
```

### Esempio con più filtri
```php
$where = '';
$whereValues = [];
if ($filtroTitolo !== '') {
    $where = '[Titolo] LIKE {0}';
    $whereValues[] = '%' . $filtroTitolo . '%';
}
if ($stato !== '') {
    $where .= ($where ? ' AND ' : '') . '[Stato] = {' . count($whereValues) . '}';
    $whereValues[] = $stato;
}
$orderBy = '[Titolo] ASC';
$item4page = 20;
$page = 1;
$listaProvince = \Model\Province::GetList(item4page: $item4page, page: $page, wherePredicate: $where, whereValues: $whereValues, orderPredicate: $orderBy);
```

### Note aggiuntive
- Non usare mai `?` o altri placeholder diversi da `{N}`.
- Se la query non ha filtri, passare stringa vuota come `$where` e array vuoto come `$whereValues`.
- Questa sintassi è obbligatoria per tutte le query custom nei controller e nelle view che usano i metodi dei model.

---

## Dinamicità delle select e reload della View

- Per popolare dinamicamente una select (es. Corso) in base a un'altra (es. Categoria corso), usa l'evento `onchange` sulla select principale (Categoria corso).
- All'interno dell'evento `onchange`, chiama `WindowWriteAllId()` (o `WindowWriteAllId()` se vuoi scrivere tutti i valori degli input con classe TempData) e poi `ReloadViewAll()` o `ReloadView()`.
- Quando la View viene ricaricata, la funzione `Client()` può leggere i valori passati tramite `\Common\State::WindowRead()`.
- In questo modo, puoi aggiornare il contenuto della select dipendente senza bisogno di chiamate AJAX: la logica di popolamento avviene lato server, e i valori selezionati vengono mantenuti tramite WindowRead/WindowWrite.
- Assicurati che tutti gli input che vuoi "passare" tra reload abbiano la classe `TempData`.

**Esempio pratico:**

```php
// Nella select Categoria corso
<select id="Categorie_corsi" name="Categorie_corsi" class="form-control TempData" onchange="ChangeCategoria();">
    ...
</select>

<script type="text/javascript">
function ChangeCategoria() {
    WindowWriteAllId(); // oppure WindowWriteAllId() per tutti gli input TempData
    ReloadViewAll();
}
</script>
```

```php
// Nella funzione Client della View
$idCategoria = (int)\Common\State::WindowRead("Categorie_corsi", ...);
$categoriaSelected = \Model\Categorie_corsi::GetItemById($idCategoria);
if ($categoriaSelected) {
    $listaCorsi = iterator_to_array($categoriaSelected->CorsiGetList());
}
```

- Questo pattern permette di gestire dipendenze tra campi del form in modo semplice e senza AJAX, sfruttando il ciclo di reload della View e la persistenza temporanea dei dati tramite WindowRead/WindowWrite.

---

## 9. Lettura da Model: ottimizzazione delle query

Per estrarre dati dai Model in modo efficiente, utilizzare i metodi appropriati e il parametro `selectColumns` per rendere le query più leggere.

### Metodi di estrazione dati

- **GetList()**: per estrarre più righe (restituisce un generatore)
- **GetItemById()**: per estrarre una singola riga tramite ID
- **GetItemBy[NomeColonna]()**: per estrarre una singola riga tramite parametri univoci
- **GetCount()**: per contare il numero di righe corrispondenti (restituisce un intero)

### Parametro selectColumns

Sia per le `GetList` che per le varie `GetItem`, è disponibile il parametro `selectColumns`: un array che permette di specificare solo le colonne che interessano, rendendo le query più leggere.

**Regole importanti:**
- Se si vuole solo controllare l'esistenza di righe, usare `GetCount()`.
- È sempre necessario includere `"Id"` nel `selectColumns` se si prevedono operazioni di salvataggio sulle righe estratte.
- Specificare solo le colonne effettivamente utilizzate nel codice.

### Esempi pratici

**Estrazione per visualizzazione (solo titolo):**
```php
$province = \Model\Province::GetList(selectColumns: ["Id", "Titolo"]);
foreach ($province as $provincia) {
    echo $provincia->Titolo; // Solo Titolo viene usato, ma Id è necessario per eventuali operazioni
}
```

**Controllo esistenza:**
```php
$count = \Model\Province::GetCount(wherePredicate: '[Titolo] = {0}', whereValues: [$titolo]);
if ($count > 0) {
    // Esiste almeno una provincia con quel titolo
}
```

**Estrazione completa per operazioni di salvataggio:**
```php
$provincia = \Model\Province::GetItemById($id); // Tutte le colonne
$provincia->Descrizione = "Nuova descrizione";
$provincia->Save();
```

**Estrazione ottimizzata per controlli semplici:**
```php
$richiesta = \Model\Richieste::GetItemById($id, selectColumns: ["Id", "Stato", "DataRichiesta"]);
if ($richiesta && $richiesta->Stato === "Approvata") {
    // Logica specifica
}
```

**Nelle relazioni tra Model:**
```php
$richiesta = \Model\Richieste::GetItemById($idRichiesta);
$dettagli = $richiesta->Dettagli_richiesteGetList(
    orderPredicate: "DataOrder ASC", 
    selectColumns: ["Data", "Ora inizio"]
);
```

### Best practice
- Analizzare sempre quale dato viene effettivamente utilizzato nel codice.
- Usare `GetCount()` per semplici controlli di esistenza invece di `GetList()` o `GetItem()`.
- Includere sempre `"Id"` se si prevedono operazioni sui dati estratti.
- Per elenchi di visualizzazione, limitarsi ai campi mostrati all'utente.
- Per operazioni di salvataggio, estrarre tutti i campi (non usare `selectColumns`).

---

## 10. Gestione della paginazione con \Code\Html\Pager

Per implementare la paginazione negli elenchi, utilizzare la classe `\Code\Html\Pager` che genera automaticamente il markup Bootstrap e gestisce la navigazione tra le pagine.

### Implementazione nelle View

#### 1. Funzione Server()
Nella funzione `Server()`, leggere i parametri di paginazione dalla URL e passarli al Client tramite `WindowWrite`:

```php
public function Server(): void
{
    // Legge i parametri di paginazione dalla URL
    $page = $_GET['page'] ?? '0';           // Pagina corrente (base 0)
    $items = $_GET['items'] ?? '10';        // Elementi per pagina
    
    \Common\State::WindowWrite("page", $page);
    \Common\State::WindowWrite("items", $items);
    ?>
    <div <?= self::GetViewId() ?>><?php self::Client() ?></div>
    
    <script type="text/javascript">
        // Le tue funzioni JavaScript specifiche della view
    </script>
    <?php
}
```

#### 2. Funzione Client()
Nella funzione `Client()`, implementare la logica di paginazione:

```php
public function Client(): void
{
    // Legge i parametri di paginazione passati dal Server
    $page = (int)\Common\State::WindowRead("page", "0");
    $item4page = (int)\Common\State::WindowRead("items", "10");
    
    // Conta il totale degli elementi
    $tot = \Model\NomeModel::GetCount();
    
    // Ottiene gli elementi paginati
    $elementi = iterator_to_array(\Model\NomeModel::GetList(
        item4page: $item4page,
        page: $page
    ));
    
    // Ottiene l'URL corrente per il paginatore
    $urlCorrente = $_SERVER['REQUEST_URI'];
    if (strpos($urlCorrente, '?') !== false) {
        $urlCorrente = substr($urlCorrente, 0, strpos($urlCorrente, '?'));
    }
    ?>
    <div class="container">
        <h2>Titolo Elenco</h2>
        
        <?php if ($tot == 0): ?>
            <div class="alert alert-info">
                Nessun elemento trovato.
            </div>
        <?php else: ?>
            <!-- Paginatore superiore -->
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-12">
                    <?= \Code\Html\Pager::Genera($page, $item4page, $tot, $urlCorrente) ?>
                </div>
            </div>
            
            <!-- Contenuto paginato -->
            <div class="row">
                <?php foreach ($elementi as $elemento): ?>
                    <!-- Il tuo markup per ogni elemento -->
                    <div class="col-md-6">
                        <!-- Contenuto dell'elemento -->
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginatore inferiore -->
            <div class="row" style="margin-top: 20px;">
                <div class="col-md-12">
                    <?= \Code\Html\Pager::Genera($page, $item4page, $tot, $urlCorrente) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
```

### Parametri della classe Pager

La classe `\Code\Html\Pager::Genera()` accetta i seguenti parametri:

```php
\Code\Html\Pager::Genera(
    int $paginaCorrente,        // Pagina corrente (base 0)
    int $elementiPerPagina,     // Numero di elementi per pagina
    int $totaleElementi,        // Numero totale di elementi
    string $urlPagina,          // URL base della pagina
    string $parametroPagina = 'page',     // Nome parametro GET per la pagina
    string $parametroElementi = 'items',  // Nome parametro GET per elementi
    int $maxPagineVisibili = 7             // Numero massimo di pagine visibili
);
```

### Funzionalità automatiche del Pager

- **Navigazione**: frecce precedente/successivo
- **Numeri di pagina**: mostra fino a 7 pagine visibili per default
- **Selettore elementi**: dropdown per 10, 30, 50, 100 elementi per pagina
- **Contatore totale**: mostra il numero totale di elementi
- **URL management**: mantiene automaticamente altri parametri nell'URL
- **Layout responsive**: si adatta a dispositivi mobili con grid Bootstrap

### Best practice per la paginazione

- **Gestione parametri**: utilizzare sempre stringhe per `WindowWrite` e convertire in interi solo quando necessario
- **URL corrente**: estrarre sempre l'URL base senza parametri per evitare duplicazioni
- **Conteggio totale**: utilizzare `GetCount()` per ottenere il numero totale di elementi
- **Doppio paginatore**: includere il paginatore sia sopra che sotto l'elenco per una migliore UX
- **Caso vuoto**: gestire sempre il caso in cui non ci sono elementi da mostrare
- **Generatori**: convertire sempre i generatori in array con `iterator_to_array()` prima dell'utilizzo

### Integrazione con filtri

Se l'elenco include filtri, gestirli insieme alla paginazione:

```php
public function Client(): void
{
    // Legge parametri di paginazione
    $page = (int)\Common\State::WindowRead("page", "0");
    $item4page = (int)\Common\State::WindowRead("items", "10");
    
    // Legge parametri di filtro
    $filtroTitolo = \Common\State::WindowRead("filtroTitolo", "");
    
    // Costruisce where per i filtri
    $where = '';
    $whereValues = [];
    if ($filtroTitolo !== '') {
        $where = '[Titolo] LIKE {0}';
        $whereValues[] = '%' . $filtroTitolo . '%';
    }
    
    // Conta il totale con filtri applicati
    $tot = \Model\NomeModel::GetCount(
        wherePredicate: $where, 
        whereValues: $whereValues
    );
    
    // Ottiene elementi paginati e filtrati
    $elementi = iterator_to_array(\Model\NomeModel::GetList(
        item4page: $item4page,
        page: $page,
        wherePredicate: $where,
        whereValues: $whereValues
    ));
    
    // Il resto dell'implementazione...
}
```

La classe `\Code\Html\Pager` gestirà automaticamente la persistenza dei parametri di filtro nell'URL durante la navigazione tra le pagine.

---

## 11. Creazione di Controlli e Dati per la generazione dei Model

Il sistema utilizza le classi in `\Common\Dati` per creare una struttura di **Controlli** e **Dati** che servirà poi per generare automaticamente i Model utilizzati come strato di comunicazione tra PHP e database.

### ⚠️ REGOLE FONDAMENTALI DA RISPETTARE

**Naming Convention:**
- **Nomi dei Dati**: devono contenere **SOLO lettere maiuscole o minuscole**. Per separare le parole usare il **TitleCase** (es. "CategorieBlog", "ArticoliBlog", "CommentiUtenti")
- **Nomi dei Controlli**: devono contenere **SOLO lettere maiuscole o minuscole**. Mai usare spazi o underscore (es. "Titolo50", "ContenutoRich", "FkDropDown")
- **Nomi dei campi in AgganciaControllo**: seguire la stessa regola del TitleCase (es. "NomeAutore", "EmailAutore", "SitoWeb")

**Parametri tecnici:**
- **adminColonne**: può contenere **SOLO valori da 1 a 4**
  - 1 = col-sm-3 (25% larghezza)
  - 2 = col-sm-6 (50% larghezza) 
  - 3 = col-sm-9 (75% larghezza)
  - 4 = col-sm-12 (100% larghezza)

**Controlli NON necessari:**
- **Controlli per slug/URL**: NON creare mai controlli specifici per slug delle URL. Il sistema non li richiede e non devono essere implementati.

**❌ ESEMPI SBAGLIATI:**
```php
// NOMI SBAGLIATI
nome: "Categorie_blog"        // underscore non ammesso
nome: "Articoli blog"         // spazio non ammesso
nome: "nome_utente"           // underscore non ammesso

// PARAMETRI SBAGLIATI
adminColonne: 80             // deve essere 1-4
adminColonne: 12             // deve essere 1-4
```

**✅ ESEMPI CORRETTI:**
```php
// NOMI CORRETTI
nome: "CategorieBlog"        // TitleCase senza separatori
nome: "ArticoliBlog"         // TitleCase senza separatori
nome: "NomeUtente"           // TitleCase senza separatori

// PARAMETRI CORRETTI
adminColonne: 4              // 100% larghezza (col-sm-12)
adminColonne: 2              // 50% larghezza (col-sm-6)
```

### Concetti fondamentali

- **Controllo**: rappresenta un singolo input di form HTML (es. TextBox, TextArea, DropDownList). Un controllo può essere riutilizzato in più Dati e più volte anche nello stesso Dato se si adatta alle circostanze.
- **Dato**: rappresenta un'entità del dominio (es. Utenti, Blog, Province). Corrisponde concettualmente a come modelleresti un form HTML per salvare/modificare quella entità.

### Tipi di Dato disponibili

I tipi di dato sono definiti in `\Common\Dati\Enum\TipoDatoEnum`:

- **Testo**: per contenuto testuale (stringhe, descrizioni, titoli)
- **Numeri**: per valori numerici (ID, età, prezzi, contatori)
- **Data/DataOra**: per date e orari
- **File**: per allegati generici
- **Immagini**: per file immagine
- **Dato**: per relazioni verso altri Dati (foreign key)

### Tipi di Input disponibili

I tipi di input sono definiti in `\Common\Dati\Enum\TipoInputEnum`:

- **TextBox**: input di testo singola riga
- **TextArea**: input di testo multi-riga
- **RichTextBox/RichTextBoxMini**: editor di testo formattato
- **DropDownList**: select con singola selezione
- **ListBox**: select con selezione multipla
- **CheckBox**: casella di controllo
- **FileInput**: input per file

### Combinazioni valide Tipo Dato → Tipo Input

- **Testo**: TextBox, TextArea, RichTextBox, RichTextBoxMini, DropDownList, ListBox
- **Numeri**: TextBox, CheckBox
- **Data/DataOra**: TextBox
- **File/Immagini**: FileInput
- **Dato**: TextBox, DropDownList, ListBox

### Creazione di Controlli comuni

#### Controllo TextBox generico per testi brevi
```php
$controlloTitoloId = \Common\Dati\Controlli::CreaControlloTestoTextBox(
    id: 0,
    nome: "Titolo50",
    descrizione: "Campo titolo con massimo 50 caratteri",
    avvisoCampoNonValido: "Il titolo non è valido",
    avvisoCampoDuplicato: "Questo titolo è già presente",
    avvisoCampoMancante: "Il titolo è obbligatorio",
    testoMaxCaratteri: 50
);
```

#### Controllo per Email con regex
```php
$controlloEmailId = \Common\Dati\Controlli::CreaControlloTestoTextBox(
    id: 0,
    nome: "Email",
    descrizione: "Campo email con validazione",
    avvisoCampoNonValido: "Formato email non valido",
    avvisoCampoDuplicato: "Questa email è già registrata",
    avvisoCampoMancante: "L'email è obbligatoria",
    testoMaxCaratteri: 255,
    testoRegEx: '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
);
```

#### Controllo per Password complessa
```php
// Usa la regex per password complessa da \Common\StringGenerator::GetComplexPassword
$controlloPasswordId = \Common\Dati\Controlli::CreaControlloTestoTextBox(
    id: 0,
    nome: "PasswordComplessa",
    descrizione: "Password complessa con caratteri speciali",
    avvisoCampoNonValido: "La password deve contenere almeno una maiuscola, una minuscola, un numero e un carattere speciale",
    avvisoCampoDuplicato: "Password già utilizzata",
    avvisoCampoMancante: "La password è obbligatoria",
    testoMaxCaratteri: 255,
    testoRegEx: '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\\|!"£$%&/()=?^\'"@\[\]*;:<>,]).{8,}$'
);
```

#### Controllo TextArea per descrizioni
```php
$controlloDescrizioneId = \Common\Dati\Controlli::CreaControlloTestoTextArea(
    id: 0,
    nome: "Descrizione500",
    descrizione: "Campo descrizione con massimo 500 caratteri",
    avvisoCampoNonValido: "La descrizione non è valida",
    avvisoCampoDuplicato: "Questa descrizione è già presente",
    avvisoCampoMancante: "La descrizione è obbligatoria",
    testoMaxCaratteri: 500,
    adminRighe: 5,
    adminColonne: 80
);
```

### Creazione di un Dato (entità)

#### Esempio: Dato Utenti
```php
$datoUtentiId = \Common\Dati\Dati::CreaDato(
    id: 0,
    nome: "Utenti",
    nomeVisualizzato: "Gestione Utenti",
    descrizione: "Entità per la gestione degli utenti del sistema",
    elementiMax: 10000,
    ordinamentoASC: true,
    parent: 0, // 0 = non ha parent
    onSave: "", // logica custom al salvataggio
    onDelete: "" // logica custom all'eliminazione
);

// Aggancia i controlli al dato
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloTitoloId, // Riutilizzo il controllo Titolo50 per Nome
    idDato: $datoUtentiId,
    nome: "Nome",
    obbligatorio: true,
    univoco: false,
    nascosto: false,
    autoIncrementante: false,
    colonnaTabelle: true,
    valoreDefault: '',
    descrizione: "Nome dell'utente"
);

\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloTitoloId, // Riutilizzo lo stesso controllo per Cognome
    idDato: $datoUtentiId,
    nome: "Cognome",
    obbligatorio: true,
    univoco: false,
    colonnaTabelle: true,
    descrizione: "Cognome dell'utente"
);

\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloEmailId,
    idDato: $datoUtentiId,
    nome: "Email",
    obbligatorio: true,
    univoco: true, // Email deve essere univoca
    colonnaTabelle: true,
    descrizione: "Email dell'utente"
);

\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloPasswordId,
    idDato: $datoUtentiId,
    nome: "Password",
    obbligatorio: true,
    nascosto: true, // Non mostrare nelle tabelle
    colonnaTabelle: false,
    descrizione: "Password dell'utente"
);
```

#### Esempio: Dato Blog
```php
$datoBlogId = \Common\Dati\Dati::CreaDato(
    id: 0,
    nome: "Blog",
    nomeVisualizzato: "Gestione Blog",
    descrizione: "Entità per la gestione degli articoli del blog",
    elementiMax: 50000
);

// Riutilizzo il controllo Titolo50 per il titolo del blog
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloTitoloId,
    idDato: $datoBlogId,
    nome: "Titolo",
    obbligatorio: true,
    colonnaTabelle: true,
    descrizione: "Titolo dell'articolo"
);

// Riutilizzo il controllo Descrizione500 per il contenuto
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloDescrizioneId,
    idDato: $datoBlogId,
    nome: "Contenuto",
    obbligatorio: true,
    colonnaTabelle: false,
    descrizione: "Contenuto dell'articolo"
);
```

### Best Practice per Controlli e Dati

- **Riutilizzo**: creare controlli generici riutilizzabili (es. Titolo50, Email, PasswordComplessa) invece di controlli specifici per ogni campo
- **Naming**: usare nomi descrittivi che includano le caratteristiche principali (es. "Titolo50" invece di "Titolo")
- **Validazione**: sempre specificare regex appropriate per validazioni specifiche (email, password, etc.)
- **Messaggi**: personalizzare sempre i messaggi di errore per migliorare UX
- **Univocità**: marcare come univoci solo i campi che devono essere realmente unici nel database
- **Colonne tabelle**: impostare `colonnaTabelle: true` solo per i campi che devono apparire negli elenchi
- **Ordinamento**: usare `ordinamentoASC: true` per ordinamenti alfabetici crescenti

### Generazione automatica Model

Una volta definiti Controlli e Dati, il sistema genererà automaticamente:
- Model corrispondenti in `\Model\NomeEntità`
- Metodi di accesso ai dati (GetList, GetItemById, Save, Delete)
- Validazioni automatiche basate sui controlli definiti
- Interfacce di amministrazione per CRUD operations

Questa struttura garantisce coerenza tra la definizione logica delle entità e la loro implementazione nel codice, automatizzando la creazione del layer di accesso ai dati.

### Gestione delle relazioni tramite Foreign Key

Per collegare tabelle tramite foreign key, il sistema offre due approcci diversi:

#### 1. Relazioni uno-a-molti con parentId

Per relazioni **uno-a-molti** (es. Blog → Immagini del blog), utilizzare il parametro `$parent` nella funzione `CreaDato`:

```php
// Dato principale (Blog)
$datoBlogId = \Common\Dati\Dati::CreaDato(
    id: 0,
    nome: "Blog",
    nomeVisualizzato: "Gestione Blog",
    descrizione: "Entità per la gestione degli articoli del blog",
    elementiMax: 50000,
    parent: 0 // Nessun parent
);

// Dato figlio (Immagini del blog) - collegato tramite parentId
$datoImmaginiId = \Common\Dati\Dati::CreaDato(
    id: 0,
    nome: "ImmaginiBlog",
    nomeVisualizzato: "Immagini Blog",
    descrizione: "Immagini associate agli articoli del blog",
    elementiMax: 100000,
    parent: $datoBlogId // Collegamento uno-a-molti tramite parentId
);
```

**Limitazioni del parentId:**
- Un Dato può avere **un solo parent**
- Ideale per relazioni gerarchiche semplici (es. Categoria → Prodotti, Blog → Immagini)

#### 2. Controlli di tipo Dato per altre Foreign Key

Per **altre foreign key** oltre al parent, utilizzare Controlli di tipo `Dato` **generici e riutilizzabili** che fanno riferimento a **controlli specifici** di altri dati.

##### Creazione dei Controlli Foreign Key riutilizzabili

I controlli FK devono essere **generici** e **riutilizzabili**. Il collegamento specifico al controllo target viene definito quando si aggancia il controllo al dato tramite `AgganciaControllo()`.

```php
// Controllo FK generico per selezione singola (DropDownList)
$controlloFkDropDownId = \Common\Dati\Controlli::CreaControlloDatoDropDownList(
    id: 0,
    nome: "FkDropDown",
    descrizione: "Foreign key generica per selezione singola",
    avvisoCampoNonValido: "Selezionare un elemento valido",
    avvisoCampoDuplicato: "",
    avvisoCampoMancante: "La selezione è obbligatoria"
);

// Controllo FK generico per selezione multipla (ListBox)
$controlloFkListBoxId = \Common\Dati\Controlli::CreaControlloDatoListBox(
    id: 0,
    nome: "FkListBox",
    descrizione: "Foreign key generica per selezione multipla",
    avvisoCampoNonValido: "Selezionare elementi validi",
    avvisoCampoDuplicato: "",
    avvisoCampoMancante: ""
);

// Controllo FK generico per input testo (TextBox) - se necessario per ID diretti
$controlloFkTextBoxId = \Common\Dati\Controlli::CreaControlloDatoTextBox(
    id: 0,
    nome: "FkTextBox",
    descrizione: "Foreign key generica per input diretto ID",
    avvisoCampoNonValido: "Inserire un ID valido",
    avvisoCampoDuplicato: "",
    avvisoCampoMancante: "L'ID è obbligatorio"
);
```

##### Esempio completo: Blog con multiple Foreign Key

```php
$datoBlogId = \Common\Dati\Dati::CreaDato(
    nome: "Blog",
    parent: $datoCategorieId // Uno-a-molti: Categoria → Blog
);

// FK verso Utenti - riutilizzo controllo generico DropDown
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkDropDownId, // Controllo FK generico
    idDato: $datoBlogId,
    nome: "Autore", // Il nome del campo che conterrà la FK
    obbligatorio: true,
    univoco: false,
    colonnaTabelle: true,
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Utenti", "Email"), // Riferimento al controllo Email dell'utente
    descrizione: "Autore dell'articolo"
);

// FK verso Tag (relazione molti-a-molti) - riferimento al controllo Nome del tag
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkListBoxId, // Controllo FK per selezione multipla
    idDato: $datoBlogId,
    nome: "Tag", // Il nome del campo che conterrà la FK
    obbligatorio: false,
    univoco: false,
    colonnaTabelle: false,
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Tag", "Nome"), // Riferimento al controllo Nome del marchio
    descrizione: "Tag associati all'articolo"
);
```

##### Scenario 2: Sistema di ticketing
```php
// Ticket con parent Progetti + FK verso Utenti e Priorità
$dataTicketId = \Common\Dati\Dati::CreaDato(
    nome: "Ticket",
    parent: $datoProgettiId // Uno-a-molti: Progetto → Ticket
);

// FK verso Utenti - riutilizzo controllo generico DropDown
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkDropDownId, // Controllo FK generico
    idDato: $dataTicketId,
    nome: "AssegnatoA", // Nome campo FK
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Utenti", "Email") // Riferimento all'email dell'utente
);

// FK verso Priorità - riutilizzo lo stesso controllo generico DropDown
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkDropDownId, // Stesso controllo FK riutilizzato
    idDato: $dataTicketId,
    nome: "Priorita", // Nome campo FK
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Priorita", "Livello") // Riferimento al livello di priorità
);
```

#### Requisiti per i controlli referenziabili come FK

Per poter utilizzare un controllo di un dato come target di una foreign key, il controllo deve essere configurato con **una** delle seguenti combinazioni:

1. **univoco: true** + **obbligatorio: true** 
2. **univoco: true** + **nascosto: true**

**Esempi di controlli validi come target FK:**

```php
// Controllo Email negli Utenti - univoco e obbligatorio
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloEmailId,
    idDato: $datoUtentiId,
    nome: "Email",
    obbligatorio: true,  // ✅ Obbligatorio
    univoco: true,       // ✅ Univoco
    colonnaTabelle: true,
    descrizione: "Email dell'utente"
);

// Controllo Nome nei Tag - univoco e obbligatorio
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloTitolo50Id,
    idDato: $datoTagId,
    nome: "Nome",
    obbligatorio: true,  // ✅ Obbligatorio
    univoco: true,       // ✅ Univoco
    colonnaTabelle: true,
    descrizione: "Nome del tag"
);

// Controllo Codice nascosto ma univoco (es. per chiavi alternative)
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloTitolo50Id,
    idDato: $datoUtentiId,
    nome: "CodiceUtente",
    obbligatorio: false,
    univoco: true,       // ✅ Univoco
    nascosto: true,      // ✅ Nascosto
    colonnaTabelle: false,
    descrizione: "Codice interno univoco dell'utente"
);
```

#### Convenzioni per le Foreign Key

- **Naming Controlli FK**: usare nomi generici che descrivono il tipo di input:
  - `"FkDropDown"` per selezioni singole
  - `"FkListBox"` per selezioni multiple
  - `"FkTextBox"` per input diretti di ID
- **Riutilizzo totale**: gli stessi controlli FK generici vengono riutilizzati per **tutte** le relazioni che necessitano dello stesso tipo di input
- **Parametro controlloRefId**: **OBBLIGATORIO** per tutte le FK - deve utilizzare `\Common\Dati\Dati::GetIdControlloRefId(nomeDato, nomeControllo)`
- **Controlli target**: il controllo referenziato deve essere **univoco** e (**obbligatorio** oppure **nascosto**)
- **Tipo Input**:
  - `DropDownList` per relazioni uno-a-uno e molti-a-uno
  - `ListBox` per relazioni molti-a-molti
  - `TextBox` per casi particolari dove serve inserimento diretto di ID

#### Esempi di scenari con controlli riutilizzabili

##### Scenario 1: E-commerce
```php
// Prodotti con parent Categorie + FK verso Fornitori e Marchi
$datoProdottiId = \Common\Dati\Dati::CreaDato(
    nome: "Prodotti",
    parent: $datoCategorieId // Uno-a-molti: Categoria → Prodotti
);

// FK verso Fornitori - riutilizzo controllo generico DropDown
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkDropDownId, // Controllo FK generico
    idDato: $datoProdottiId,
    nome: "Fornitore", // Nome campo FK
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Fornitori", "RagioneSociale") // Riferimento al controllo RagioneSociale del fornitore
);

// FK verso Marchi - riutilizzo lo stesso controllo generico DropDown
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkDropDownId, // Stesso controllo FK riutilizzato
    idDato: $datoProdottiId,
    nome: "Marchio", // Nome campo FK
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Marchi", "Nome") // Riferimento al controllo Nome del marchio
);
```

##### Scenario 2: Sistema di ticketing
```php
// Ticket con parent Progetti + FK verso Utenti e Priorità
$dataTicketId = \Common\Dati\Dati::CreaDato(
    nome: "Ticket",
    parent: $datoProgettiId // Uno-a-molti: Progetto → Ticket
);

// FK verso Utenti - riutilizzo controllo generico DropDown
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkDropDownId, // Controllo FK generico
    idDato: $dataTicketId,
    nome: "AssegnatoA", // Nome campo FK
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Utenti", "Email") // Riferimento all'email dell'utente
);

// FK verso Priorità - riutilizzo lo stesso controllo generico DropDown
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkDropDownId, // Stesso controllo FK riutilizzato
    idDato: $dataTicketId,
    nome: "Priorita", // Nome campo FK
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("Priorita", "Livello") // Riferimento al livello di priorità
);
```

#### Sintassi completa per AgganciaControllo con Foreign Key

Quando si aggancia un controllo FK a un dato, la sintassi completa include sempre il parametro `controlloRefId` con `GetIdControlloRefId`:

```php
\Common\Dati\Dati::AgganciaControllo(
    idControllo: $controlloFkId,      // ID del controllo FK generico
    idDato: $datoCorrente,            // ID del dato che contiene la FK
    nome: "NomeCampoFK",              // Nome del campo FK nel database
    obbligatorio: true|false,         // Se la FK è obbligatoria
    univoco: true|false,              // Se la FK deve essere univoca
    nascosto: true|false,             // Se nascondere il campo negli editor
    autoIncrementante: false,         // Sempre false per le FK
    colonnaTabelle: true|false,       // Se mostrare nelle tabelle
    valoreDefault: '',                // Valore di default (opzionale)
    controlloRefId: \Common\Dati\Dati::GetIdControlloRefId("NomeDatoTarget", "NomeControlloTarget"), // *** OBBLIGATORIO: ID del controllo target ***
    descrizione: "Descrizione campo" // Descrizione del campo
);
```

**Note importanti:**
- Il parametro `controlloRefId` è **sempre obbligatorio** quando si usa un controllo di tipo FK
- Utilizzare sempre `\Common\Dati\Dati::GetIdControlloRefId(nomeDato, nomeControllo)` per ottenere l'ID del controllo target
- Il controllo target deve essere **univoco** e (**obbligatorio** oppure **nascosto**)
- Il collegamento FK viene stabilito tramite riferimento a un controllo specifico, non al dato nel suo complesso
