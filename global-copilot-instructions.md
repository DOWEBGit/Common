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
- Se id > 0 → modifica record esistente.
- Se id = 0 o vuoto → nuovo inserimento.
- Controller e Action gestiscono entrambi i casi.

---

## 7. Struttura delle View

### Best practice generali per le View

- **Gestione dei generatori**: se un metodo come `GetList()` restituisce un generatore, non usare `empty()` direttamente. Converti sempre il generatore in array con `iterator_to_array()` prima di controllare se è vuoto o di iterare più volte.
  ```php
  $richieste = iterator_to_array(\Model\RichiesteInterne::GetList());
  if (empty($richieste)) { ... }
  foreach ($richieste as $item) { ... }
  ```

- **Editor**:
  - Usa sempre un campo hidden per l’ID (`<input type="hidden" ...>`).
  - Se l’ID è > 0, modifica; se 0 o vuoto, nuovo inserimento.
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
    <main class="main">
        <section class="section">
            <div class="container" <?= self::GetViewId() ?>>
                <?php self::Client(); ?>
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
- Ogni attributo deve essere scritto come `[NomeAttributo]`.
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
