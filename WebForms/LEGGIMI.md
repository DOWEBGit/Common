# Motore WebForms

Un motore a postback per PHP, sul modello di ASP.NET WebForms: albero di controlli lato
server, eventi nel codebehind, e il browser che fonde le differenze invece di ricaricare.

Questa cartella e' il motore. Non contiene pagine: le pagine stanno nel sito che lo usa.

L'API e' in inglese e ricalca WebForms; i commenti restano in italiano come il resto del
codice.

---

## Cosa e' cambiato il 14/09/2026

- **`<dw:DatePicker>`**: `Value`, `Min`, `Max` sono `DateTimeImmutable`; `Mode` e' l'enum
  `DateTimeMode` (`Date` / `DateTime`) e sceglie fra `type="date"` e `type="datetime-local"`,
  calendario nativo del browser; `AutoPostBack` e `OnDateChanged`. Quello che arriva dal
  browser si rilegge come data: la spazzatura diventa vuoto, l'ora di troppo cade. (§3)
- **Proprieta' enum nei controlli**: dal markup per nome del caso, nello stato come valore, e
  la prova generica le sonda. D'ora in poi due o tre valori possibili sono un enum. (§3)
- **Ispezioni PhpStorm a zero** sul motore: costanti tipate, eccezioni checked fermate alla
  sorgente come `RuntimeException`, `__DIR__` al posto di `DOCUMENT_ROOT` in `Bootstrap`.
- **`Notify()` porta un oggetto**: terzo argomento, arriva all'handler di `Subscribe()` come
  array, firmato; `data-dw-topics` sulla radice fa partire il postback solo per i topic
  iscritti. Banco: Prima manda un utente con foto, Seconda lo mostra. (§6)
- **`EntityEvents::Broadcast()`** manda un messaggio con dati a tutti i browser del dominio,
  subito; `DW.on()` lo riceve in pagina; `data-dw-client` protegge dal morph quello che uno
  script aggiunge. Gli script del motore sono passati nella testa del documento. (§6)
- Il **designer** si rigenera anche quando un tipo dichiarato non esiste: un plugin vecchio non
  puo' piu' lasciare una pagina rotta con un 200. Plugin **1.21.0**: UserControl per nome nel
  designer, `ViewStateMode` nel completamento. (§11)

## Cosa e' cambiato l'11/09/2026

Per chi conosceva il motore com'era: le cose sono cambiate in profondita', e i nomi con loro.

- **Le variabili di pagina restano tutte**, come i campi di una form. `#[Persist]` non esiste
  piu'; il poco che deve rinascere si marca `#[Transient]`; `#[Portable]` resta per quello che
  attraversa le pagine. (§2)
- **I controlli attaccati dal codice tornano da soli** al postback dopo — in `OnLoad`, in un
  handler, ovunque — con posizione, classe e stato. Anche alla radice della pagina con
  `$this->Add()`. Il markup non si salva mai: si rilegge. (§3, *Panel e PlaceHolder*)
- **Il Repeater salva solo la differenza** dalla fotografia di nascita: quello che il codice
  mette nelle righe resta, comunque ci sia arrivato, e un template a soli segnaposto non paga
  niente. `ClearItems()` e' l'`Items.Clear()` di WebForms. (§3, *Repeater*)
- **`ViewStateMode`** su ogni controllo, ereditato: `Inherit` / `Enabled` / `Disabled`. (§2)
- **Modo WinForms acceso per tutti** (`KeepState`): il browser si tiene lo stato di ogni pagina
  e lo rimanda quando ci si torna, anche con una querystring diversa; tetto 50 MB per ultimo
  accesso; una riga in console dice quanto pesa. Chi non lo vuole lo spegne. (§2)
- **Il ViewState sta fuori da `dw-root`**, e il morph non lo tocca. (§2)
- **`Attributes` e `Style` sono collection** con `Add`/`Remove`/`Clear`, leggibili come array.
  Tutta l'API e' in inglese, con i nomi di WebForms; i commenti restano in italiano. (§3)
- **`runtime.js` e `runtime.css`** sono file veri accanto al motore, non piu' `const` PHP. (§1)
- **`Pages::`** e' il nuovo nome dell'enum delle pagine (`Pagine::`), `SitePage` dell'interfaccia.
- **Prove**: 373, di cui sessantatre per riflessione su tutti i controlli, piu' i banchi a mano in
  `ProveAMano/` — due pagine con un menu, la tabella costruita a mano, lo stato. (§10)

---

# 1. Una pagina

## Tre file

| file | WebForms | ruolo |
|---|---|---|
| `Ordine.php` | `Ordine.aspx` | markup, ed e' l'URL della pagina |
| `Ordine.code.php` | `Ordine.aspx.cs` | codebehind: la classe |
| `Ordine.designer.php` | `Ordine.aspx.designer.cs` | **generato**: i controlli per l'IDE |

Il markup comincia con una riga sola — il percorso a `Bootstrap.php` e' relativo alla pagina:

```php
<?php require __DIR__ . '/../Common/WebForms/Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \WebForms\Ordine::class); ?>

<h1>Righe ordine</h1>
<dw:TextBox id="txtArticolo" Placeholder="Codice articolo" />
<dw:Button id="btnAggiungi" Text="Aggiungi" OnClick="AggiungiClick" />
<dw:Label id="lblStato" />
```

Il codebehind estende `Page` e usa il trait del designer:

```php
class Ordine extends Page
{
    use OrdineDesigner;

    protected function AggiungiClick(Control $sender, string $argomento): void
    {
        $this->lblStato->Text = 'Riga aggiunta.';   // e qualunque altro controllo
    }
}
```

Codebehind e designer **non passano dall'autoloader** — il loro nome non e' un nome di
classe — e vengono inclusi per percorso, designer per primo perche' e' un trait che la
classe usa. Il vantaggio e' che la classe si chiama `\WebForms\Ordine`, non `OrdineCode`.

Il designer si riscrive da solo quando il markup e' piu' recente, e ci mette anche un `@see`
per ogni handler nominato nel markup: se scrivi `OnClick="DeletRow"` con il refuso, l'IDE lo
segna in rosso subito.

## Ciclo di vita

    OnInit               albero costruito dal markup - sempre identico, e' il vincolo che regge tutto
    LoadViewState        proprieta' dei controlli + variabili della pagina
    LoadPostData         i valori del form entrano nei controlli
    OnLoad               con $this->IsPostBack
    RaisePostBackEvent   l'handler nominato dal markup
    OnPreRender
    Render               HTML dell'INTERA pagina
    SaveViewState

Il client fonde le differenze nel DOM (morph). **Nessuna regione da dichiarare**: un handler
tocca qualunque controllo, ovunque nella pagina, e si aggiorna. Niente `UpdatePanel`, niente
`AsyncPostBackTrigger`.

## Perche' il markup non e' un `.html`

Sotto `Public/Php` Kestrel serve i `.html` come **file statici**: un `Pagina.html` tornerebbe
al browser con dentro il `<?php` non eseguito, e chiunque potrebbe leggersi il markup.
Provato, non dedotto. Servirebbe una modifica a Kestrel che vale per tutti i siti, quindi
l'estensione resta `.php` — che tiene anche l'URL pulito.

Nell'editor non si perde niente: PhpStorm evidenzia gia' come HTML tutto quello che sta dopo
il `?>`, con emmet e completamento dei tag.

## Le classi

| classe | ruolo |
|---|---|
| `Page` | ciclo di vita, `IsPostBack`, `FindControl()`, `Add()`, `KeepState`, `Subscribe()`/`Raise()`, `Redirect()`, `RedirectToPage()`, `RedirectToLogin()` |
| `Control` | base: `Id` `Visible` `CssClass` `Attributes` `ViewStateMode` `Parent` `Controls` `Page`, `FindControl()`, `NamingContainer()`, `Attributes->Add()`, `Attributes->Remove()`, `Style->Add()`, `Style->Remove()`, `CanRaiseEvents()`, `RaiseBubbleEvent()`, `Render()` |
| `UserControl` | controllo composto: ciclo di vita proprio, `OnBubbleEvent()`, `RaiseHostEvent()` |
| `MasterPage` | la cornice condivisa: un UserControl che contiene la pagina invece di esserne contenuto |
| `PageParser` | markup → albero di nodi, con cache su `mtime` |
| `ControlBuilder` | nodi → controlli, segnaposto `{{Campo}}`, `<dw:ListItem>` |
| `ViewState` | lo stato: campo nascosto compresso e firmato, niente sessione |
| `Response` | `Document()` `Fragment()` `Redirect()` `Reload()` |
| `Runtime` | i due tag che il motore mette in ogni pagina: lo stile funzionale e gli script |
| `Designer` | genera il trait dei controlli |
| `Upload` | il canale dei file, separato dal postback |
| `Csrf` | protezione a doppio invio su cookie: nessun token da tenere sul server |
| `EntityEvents` | notifiche di dominio: `Notify()` `Flush()` `Suspend()` `Resume()` |
| `Transient` | attributo sui campi che NON devono sopravvivere al postback: tutti gli altri restano |
| `Portable` | attributo sui campi che devono attraversare le pagine |
| `Alert` | la coda degli avvisi: `Success()` `Fail()`, e vivono in un campo `#[Portable]` |
| `PageMap` | genera l'enum `Pagine` leggendo i markup: l'elenco per `RedirectToPage()` |
| `SitePage` | quel poco che il motore chiede all'enum generato: `Percorso()` |

`runtime.js` e `runtime.css`, accanto a quelle classi, **sono il motore lato browser**: morph, postback,
navigazione, upload, notifiche, piu' le regole di stile senza cui un comportamento si rompe.
Li aggancia `Runtime` da solo — il foglio in testa, lo script in coda al body con `defer` —
con la marca temporale nell'indirizzo. Stanno in due file veri e non in un `const` di PHP
perche' li' dentro non sarebbero codice per nessuno: non per l'editor, non per il controllo
di sintassi, non per il debugger del browser, che li chiamerebbe "inline" senza saper dire a
che riga sei.

`FileUploadHandler.php` e' l'unico file del motore che si chiama da URL: riceve i file in
POST e serve le anteprime in GET. `_cache` tiene il markup analizzato, `_segreto.php` la
chiave con cui si firma il ViewState nascosto — entrambi si rigenerano da soli.

---

# 2. Lo stato

## Le variabili della pagina restano, tutte

In PHP l'oggetto pagina muore a fine richiesta. Qui pero' i suoi campi **tornano al giro
dopo da soli**, come i campi di una form di WinForms:

```php
private array $righe = [];   //dopo il click ha ancora le righe di prima
private int $prossimoId = 1; //e il contatore non riparte
```

Nessun attributo. Guardando le pagine vere non ce n'era una che volesse il contrario: un
campo di pagina e' memoria per definizione, e chiedere di marcarlo era solo un modo per
dimenticarsene. Ci vanno **scalari e array**, anche annidati.

Restano fuori **da soli**: le proprieta' del motore (`Title`, `Lang`, `IsPostBack`, …), le
`#[Portable]`, che hanno un canale loro, e le proprieta' **tipizzate con una classe** — i
controlli del designer, la master, un Model. Un oggetto non attraversa la serializzazione,
e non deve nemmeno provarci: se ne metti uno in una proprieta' senza tipo, il salvataggio si
ferma subito con il nome della proprieta', invece di dartelo rotto al click dopo.

Il poco che deve **rinascere** ad ogni richiesta — una cache riempita in `OnLoad`, un valore
grosso che non ha senso far viaggiare — si marca `#[Transient]`. Serve anche a chi legge:
senza, un campo che si azzera sembra un difetto.

Tutto questo vive quanto la pagina: cambiando pagina si riparte da zero (salvo il modo
WinForms, piu' sotto). Per un valore che deve attraversare le pagine c'e' `#[Portable]`.
## `#[Portable]`: lo stato che attraversa le pagine

```php
#[Portable]
private int $categoria = 0;      // il NOME del campo e' la chiave, condivisa fra le pagine
```

Dove sta: non in sessione, non in un cookie, non nel querystring. Sta in un pacchetto
firmato che il browser tiene **in memoria** e rimanda ad ogni postback e ad ogni
navigazione. Puo' farlo perche' qui la pagina non si ricarica mai — un click e' una fetch e
poi un morph — quindi quella memoria non si azzera in mezzo al lavoro.

E' lo stesso pacchetto del ViewState: firmato, quindi il client non se lo riscrive. Ma
arriva pur sempre dal browser, quindi e' **stato, non autorizzazione**: un id portato di qua
dice "stavo guardando questo", non "posso vedere questo".

Due cose da sapere:

- **il nome della proprieta' e' una chiave globale.** Due pagine che dichiarano
  `#[Portable] private int $categoria` condividono il valore — ed e' il punto. Due pagine che
  intendono cose diverse non devono chiamarle uguale, come per le chiavi di sessione.
- **un caricamento vero azzera tutto.** F5, indirizzo scritto a mano, link aperto in una
  scheda nuova: li' il server rende la pagina prima che il JavaScript esista, quindi i campi
  partono dai loro valori iniziali. Blazor si comporta uguale: F5 e' un circuito nuovo.

Oltre 4 kB il motore si ferma con un errore invece di far viaggiare un'intestazione che
qualche proxy taglierebbe per conto suo: li' vanno le chiavi, non i dati.

## Dove vive lo stato

In un campo nascosto, e in nessun altro posto. **Il motore non usa la sessione**: niente
`$_SESSION`, niente lock del file di sessione, niente stato sul server fra una richiesta e
l'altra. Quindi lo stato non scade finche' la pagina resta aperta, sopravvive a un riavvio
del server, e due postback dello stesso browser non si mettono in fila per un lock.

Si paga in banda: ~1 kB piu' ~25 byte per riga, ad ogni postback, in andata e ritorno.

C'era anche un modo "in sessione", con un anello di dieci slot: **tolto**. Faceva scadere le
pagine aperte da un po', ne teneva vive solo dieci, e serializzava le richieste dello stesso
browser proprio quando i postback si accavallano.

Il campo nascosto e' il ViewState vero: `serialize` → `gzdeflate` → `base64` → **HMAC-SHA256**.
La compressione prima del base64 (dopo non comprimerebbe piu' niente), la firma per ultima,
cosi' si firma esattamente cio' che viaggia. Il campo va in **fondo** alla pagina, per non
ritardare il contenuto.

La firma non e' un dettaglio: senza, il client si riscrive lo stato e le proprieta' dei
controlli diventano quello che decide lui. Il segreto nasce da solo in `_segreto.php` — un
`.php` e non un `.txt`, altrimenti sotto `Public/Php` verrebbe servito come file statico e
la chiave sarebbe pubblica.

Uno stato manomesso o scaduto non e' un errore: `OnViewStateExpired()` ricarica pulito.

### Vale per una pagina e per una visita

Cambiare pagina **azzera il ViewState**, ed e' voluto: lo stato descrive quella pagina
com'era, e tornarci dopo essere stati altrove vuol dire ricominciare — su un elenco e' anche
l'unica cosa giusta, perche' i dati intanto possono essere cambiati. Quello che attraversa
le pagine e' `#[Portable]`, che e' un'altra cosa e viaggia in un pacchetto suo.

### Il modo WinForms: `KeepState`, acceso per tutti

Il **browser** si conserva lo stato di ogni pagina quando la si lascia e glielo rimanda
quando ci si torna: contatori, filtri, pannelli aperti, controlli attaccati dal codice,
tutto dov'era — come una form di WinForms che resta in memoria mentre ne guardi un'altra.
E' il comportamento predefinito. Chi non lo vuole lo spegne:

```php
protected function OnInit(): void
{
    $this->KeepState = false;   //ogni arrivo e' una pagina nuova, com'e' il web
}
```

Come funziona: la radice esce con `data-dw-tieni`, il client tiene una mappa
*indirizzo → stato* (fino a **50 MB**, poi butta quelle con l'ultimo accesso piu' vecchio), e quando si torna su un
indirizzo che ha in serbo fa una **POST** invece della solita GET, con l'intestazione
`X-DW-Ripristina`. Il server la tratta come un postback senza evento — `IsPostBack` e'
vero, quindi l'inizializzazione di `OnLoad` non ricomincia da capo — e risponde con un
documento intero perche' resta una navigazione.

Cosa sapere prima di accenderlo:

- lo stato tenuto vive **nella memoria di quella scheda**: F5 lo butta, un'altra scheda non
  lo vede, chiudere il browser lo perde. Non e' un salvataggio, e non va usato come tale;
- la chiave e' il **percorso senza querystring**: `Calendario.php?mese=2026-10` e' la stessa
  pagina di `?mese=2026-09`, e lo stato rientra lo stesso. Il server vede la `$_GET` nuova e
  `IsPostBack` e' vero; cosa ricaricare lo decide la pagina in `OnLoad` — confronta il
  parametro con quello che si ricorda, e se e' cambiato fa `$rpt->ClearItems()` e rilega.
  `UnitTest/ProveQuerystring.php` e' esattamente questo, con i giorni di un mese;
- **niente resta appeso**: svuotare un elenco con `ClearItems()` o rimpiazzare un pannello
  riporta il pacchetto al peso di prima — misurato: 329 B vuota, 12 KB con 500 righe, 329 B
  dopo `ClearItems()`, e dieci postback a vuoto non lo muovono di un byte. Sul server non c'e'
  niente da liberare per costruzione: la pagina nasce e muore in una richiesta, senza
  sessione. Nel browser la copia tenuta si aggiorna quando si lascia la pagina, e il conto
  dei byte scende con lei;
- in console, alla fine di ogni postback e di ogni navigazione, una riga dice quanto pesa:
  `DW stato: pagina 1.1 KB · tenute 3 pagine, 12.4 KB su 50.00 MB`. Si vede crescere il
  pacchetto mentre si lavora, invece di scoprirlo quando e' gia' grosso;
- resta **firmato**: il server lo verifica come qualunque altro stato, e se non torna buono
  apre la pagina pulita invece di protestare;
- se la pagina smette di volersi tenere — una casella spenta, una condizione cambiata — il
  client **butta** quello che aveva in serbo, altrimenti al ritorno rimetterebbe in piedi
  uno stato che la pagina ha appena rinnegato;
- **su un elenco di dati che cambiano sotto le mani di altri** puo' mostrare righe che
  qualcuno ha gia' cambiato o cancellato. Li' o lo si spegne, o si rilegge in `OnLoad`
  quando serve: al ritorno `IsPostBack` e' vero e la pagina sa di essere stata
  ripristinata.

### Sta FUORI da `dw-root`

Il campo nascosto e' un fratello della radice, non un suo figlio:

```html
<div id="dw-root"> … tutta la pagina … </div>
<input type="hidden" id="__dw_state" value="v1.…">
```

Dentro `dw-root` ci sta il contenuto, e ad ogni postback quel contenuto viene riconciliato
nodo per nodo dal morph. Finche' il campo stava li' in mezzo, **il pacchetto piu' importante
della pagina dipendeva dal fatto che il morph lo riconoscesse** e ne aggiornasse il valore:
una riconciliazione che va storta, e il click dopo parte da uno stato vecchio. Fuori non
dipende piu' da niente — il client se lo scrive da se', con quello che il server gli manda
accanto all'HTML — e si trova sempre allo stesso posto, come una volta si trovava la
sessione. Se manca, il client se lo ricrea invece di perdere il postback.

Il postback lo aggiorna **prima** di toccare il DOM: se il morph solleva a meta' strada, il
campo e' gia' quello nuovo e il click successivo resta allineato col server. Una navigazione
senza ricarico lo prende dal documento appena scaricato.

## La terza via: non tenerlo — `ViewStateMode`

Un elenco puo' non persistere le righe: persiste filtro, ordinamento e pagina, e rilegge dal
database ad ogni postback. Immune alle scadenze per costruzione, costa una query per click.
Per gli elenchi e' quasi sempre la scelta giusta, e si dice nel markup, come in WebForms:

```html
<dw:Repeater id="rpt" ViewStateMode="Disabled" DataKeyField="Id">
```

Tre valori. `Inherit` (predefinito) fa quello che fa il padre, e la pagina e' `Enabled`:
senza scrivere niente si salva tutto. `Disabled` spegne il controllo e, per eredita', tutto
il ramo sotto; un figlio puo' riaccendersi con `Enabled`. Vale su **qualunque** controllo — un
`Panel` spento spegne tutto quello che contiene.

Cosa succede sotto uno spento:

- i controlli **del markup** ci sono ancora, coi valori del markup: quello che il codice ci
  aveva scritto e' perso;
- i controlli **attaccati dal codice** non tornano: chi li vuole li ricrea in `OnInit`, alla
  maniera vecchia;
- un **Repeater** spento non porta le righe: la pagina lo ridatabinda in `OnLoad` — cioe'
  **prima** dell'evento, cosi' il bottone dentro la riga esiste quando il click arriva.

Non e' un'ottimizzazione da fare a occhi chiusi: le pagine Northwind tengono le righe nello
stato di proposito, e le rileggono solo quando cambia qualcosa. Spegnere quel Repeater le
romperebbe — aprire una scheda svuoterebbe l'elenco. Lo si spegne su un elenco che gia'
rilegge ad ogni richiesta: li' le righe nello stato erano solo peso.

---

# 3. I controlli

**I nomi sono quelli di WebForms**, in inglese: `Attributes->Add()`, `Style->Remove()`,
`ViewStateMode`, `IsViewStateEnabled()`, `KeepState`, `ClearItems()`, `CanRaiseEvents()`. I
commenti restano in italiano. Quello che in WebForms era una collection qui e' una
collection: `Attributes` e `Style` sono oggetti con `Add`, `Remove`, `Clear`, `Has`, e si
leggono anche come array — `$btn->Attributes['title']`.

Scelti contando l'uso reale nelle pagine WebForms del gestionale WK
(`Presentation/**/*.aspx`), che e' il corpus piu' grande che abbiamo:

| controllo | usi in WK | qui |
|---|---:|---|
| `Literal` | 983 | si' |
| `LinkButton` | 788 | si' |
| `TextBox` | 725 | si' |
| `DropDownList` | 422 | si' |
| `UpdatePanel` | 341 | non serve: render totale + morph |
| `ListItem` | 268 | si' |
| `AsyncPostBackTrigger` | 237 | non serve: dipendeva dall'UpdatePanel |
| `CheckBox` | 181 | si' |
| `PlaceHolder` | 178 | si' |
| `HiddenField` | 151 | si' |
| `Repeater` | 134 | si' |
| `FileUpload` | 38 | si', con trascinamento facoltativo |
| `PostBackTrigger` | 32 | non serve |
| `Button` | 21 | si' |
| `Panel` | 16 | si' |
| `DatePicker` | — | si': non c'era in WebForms, in WK erano TextBox con un calendario JavaScript |
| `ListBox` | 6 | si' |
| `TreeView` | 4 | manca, e con quattro usi non vale il prezzo |

Piu' `Content` (280), che e' delle master page: qui quel posto lo prendono gli UserControl.

Tutti stanno in `Controls` e hanno `Id`, `Visible`, `CssClass`.

### Attributi HTML dal codice

Quello che il motore non prevede si aggiunge a mano, come l'`Attributes` di WebForms:

```php
$elimina->Attributes->Add('title', 'Elimina "' . $categoria->Nome . '"')
        ->Add('data-stato', $ordine->Stato);

$elimina->Attributes->Remove('data-stato');
```

Ci si mette quello che dipende dalla riga o dallo stato e non merita una proprieta' del
controllo: un `title`, un `data-` che serve a un pezzo di JavaScript del sito, un `aria-` per
un caso particolare. Nel markup invece gli attributi si scrivono e basta.

Tre regole, e tutte e tre hanno una prova:

- **il valore esce escapato**, sempre. Ci finiscono nomi di record, che hanno apostrofi e
  virgolette: senza, il primo `L'Oreal` spezzerebbe il tag;
- **il nome dev'essere un nome di attributo** (`[A-Za-z][A-Za-z0-9_.:-]*`), controllato quando
  lo si scrive e di nuovo al render. Un nome con uno spazio dentro non aggiungerebbe un
  attributo: inietterebbe markup;
- **`id`, `class`, `hidden`, `name` e `data-dw-*` sono riservati**: i primi quattro li scrive
  gia' il controllo (si usano `Id`, `CssClass`, `Visible`), l'ultimo e' il canale fra server e
  runtime. Scriverli solleva, invece di produrre un HTML con l'attributo doppio in cui il
  browser tiene il primo — cioe' non quello appena messo.

Gli attributi stanno nel ViewState, quindi uno messo in un handler c'e' ancora al click dopo;
dentro un `Repeater` con `OnItemDataBound` viaggiano per riga come tutto il resto.

### Lo stile in linea

E' lo `Style` di WebForms, che li' e' una `CssStyleCollection`:

```php
$barra->Style->Add('width', $percentuale . '%')
      ->Add('background-color', $colore);

$riga->Style->Remove('background-color');
$riga->Style->Clear();
```

Esce in un attributo solo — `style="width:72%;background-color:#1a7f37"` — nell'ordine in cui
lo si e' scritto, e vive nel ViewState come gli attributi.

**Quando usarlo, e quando no.** Lo stile in linea serve quando il valore E' il dato: una
barra lunga quanto una percentuale, una riga colorata secondo lo stato, una miniatura alta
quanto dice il record. Tutto il resto — com'e' fatto un bottone, che aspetto ha una scheda —
e' vestito, e il vestito sta nel foglio di stile: una regola scritta li' si cambia per tutti
insieme, uno stile in linea si cambia riga per riga e vince su qualunque foglio.

Il nome dev'essere una proprieta' CSS (`[A-Za-z][A-Za-z0-9-]*`, con le due iniziali per le
proprieta' personalizzate: `--dw-mio`), controllato quando lo si scrive e di nuovo al render.
Il valore invece puo' contenere qualunque cosa: l'attributo esce escapato tutto insieme,
quindi una virgoletta diventa un'entita' e non puo' chiudere niente.

`style` e' fra i nomi riservati di `Attributes->Add()`: due sorgenti per lo stesso
attributo darebbero un HTML con `style` scritto due volte, e il browser terrebbe il primo.

### `Literal`

| proprieta' | |
|---|---|
| `Text` | il testo |
| `Mode` | `Encode` (predefinito) oppure `PassThrough` |

Testo senza un elemento attorno. E' il piu' usato di tutti e si capisce: serve dove un
`<span>` darebbe fastidio — dentro una cella gia' stilizzata, in mezzo a una frase.
`PassThrough` per l'HTML costruito dal server, come un'anteprima immagine.

### `Label`

| proprieta' | |
|---|---|
| `Text` | il testo |
| `Html` | se vero il testo esce non escapato |

Rende uno `<span>`.

### `TextBox`

| proprieta' | |
|---|---|
| `Text` | il valore |
| `Placeholder` | |
| `TextMode` | `SingleLine` (predefinito), `MultiLine` (textarea), `Password` |
| `Type` | il `type` HTML, quando serve `email`, `number`, `date`… |
| `Rows` | righe della textarea |
| `Enabled` | |
| `AutoPostBack` | posta appena il valore cambia |
| `AutoPostBackDelay` | millisecondi di pausa prima di postare |
| `OnTextChanged` | handler |

**Ricerca mentre si scrive**, dichiarata e basta:

```html
<dw:TextBox id="txtFiltro" AutoPostBack="true" AutoPostBackDelay="350"
            OnTextChanged="FiltroCambiato" />
```

`AutoPostBackDelay` a 0 aspetta il blur, come WebForms; sopra 0 il runtime ascolta ogni tasto
e trattiene il postback per quella pausa. Digitando "color" parte **una** richiesta, non
cinque. Il codebehind non distingue i due casi.

### `DatePicker`

```html
<dw:DatePicker id="dtDal" Mode="Date" AutoPostBack="true" OnDateChanged="DalCambiato" />
<dw:DatePicker id="dtQuando" Mode="DateTime" />
```

```php
$this->dtDal->Value = new \DateTimeImmutable('2026-09-14');
$quando = $this->dtQuando->Value;   // ?DateTimeImmutable, null se vuoto
$this->dtDal->Min = new \DateTimeImmutable('2026-01-01');
```

| proprieta' | |
|---|---|
| `Mode` | `DateTimeMode::Date` (predefinito) o `DateTimeMode::DateTime`: `<input type="date">` o `type="datetime-local"`. Nel markup `Mode="Date"` / `Mode="DateTime"`, senza badare alle maiuscole; un nome sbagliato si ferma al markup |
| `Value` `Min` `Max` | **date**, `?DateTimeImmutable`. Nello stato viaggia il testo nel formato dell'input (`Text`, `MinText`, `MaxText`): il pacchetto si riapre senza classi |
| `AutoPostBack` | scegliere una data fa partire il postback |
| `OnDateChanged` | handler di pagina, `(DatePicker $sender)` |
| `Enabled` | |

**Il calendario e' quello del browser**, nativo: niente librerie, niente formato locale da
indovinare, niente touch da gestire — lo sa il browser, e mostra il calendario giusto per
l'utente. Il server parla solo in date.

Quello che arriva dal browser si **rilegge come data e si riscrive**: un valore che non e'
una data (dalla console, da uno script) diventa vuoto; il 30 febbraio diventa vuoto, non il 2
marzo; un'ora mandata a un `Mode="Date"` cade. Si accettano anche i formati di un database
— con lo spazio, con i secondi — e si normalizzano. Cambiando `Mode` a runtime il valore resta
e si adegua al tipo nuovo: senza, il browser rifiuterebbe `2026-09-14T10:30` in un
`type="date"` e mostrerebbe il campo vuoto senza dire niente.

`Mode` e' il primo caso di **proprieta' enum** in un controllo, e il motore ora le tratta
da solo: dal markup si sceglie il caso per nome, nello stato viaggia il valore e torna
come enum, e la prova generica su tutti i controlli sonda anche quelle. Il prossimo controllo
con due o tre valori possibili li dichiari come enum, non come costanti stringa.

### `Button` e `LinkButton`

| proprieta' | |
|---|---|
| `Text` | |
| `Enabled` | |
| `OnClick` | handler |
| `CommandArgument` | arriva all'handler come secondo argomento |
| `Confirm` | domanda prima del postback; vuoto = nessuna domanda |
| `CommandName` | *(solo `LinkButton`)* comando che sale verso il contenitore |

`LinkButton`, se disabilitato, degrada a `<span>` invece che a link morto.

`Confirm` e' cortesia verso l'utente, non una difesa: si toglie dalla console, quindi
l'handler deve comportarsi come se il click fosse arrivato senza.

**Chi ha cliccato resta spento finche' non torna la risposta.** Fra il click e il render c'e'
una fetch, e in quel tempo il bottone e' li' che sembra pronto: due click su "Salva" salvano
due volte, e la seconda l'utente non l'aveva chiesta. Il runtime spegne il controllo appena
parte il postback — `disabled` su un `Button`, `aria-disabled` piu' `pointer-events:none` su
un `LinkButton`, e la classe `js-dw-occupato` per lo stile — e lo riaccende quando la
richiesta finisce, comunque sia andata.

Si spegne **solo quello**: il resto della pagina continua a rispondere. E' una difesa contro
il doppio click, non un blocco della pagina; per l'attesa lunga c'e' `js-dw-attesa` sul
`<body>`, che c'e' da prima.

Non c'e' niente da riaccendere a mano e niente da scrivere nella pagina: il render che torna
e' quello che comanda, e nel suo HTML il controllo e' acceso. Vale per i click, non per gli
`AutoPostBack` di testo — spegnere una casella mentre ci si scrive porterebbe via il fuoco e
le lettere digitate nel frattempo.

### `CheckBox`

| proprieta' | |
|---|---|
| `Checked` | |
| `Text` | etichetta accanto |
| `Enabled` | |
| `AutoPostBack` | |
| `OnCheckedChanged` | handler |

Rende anche un campo nascosto `<id>__presente`: una casella non spuntata non compare fra i
campi inviati, e l'assenza va distinta dal "controllo non era in pagina".

### `DropDownList`

| proprieta' | |
|---|---|
| `Items` | array valore ⇒ testo |
| `SelectedValue` | |
| `Enabled` | |
| `AutoPostBack` | |
| `OnSelectedIndexChanged` | handler |
| `CommandName` | comando che sale verso il contenitore |

### `ListBox`

Tutto quello di `DropDownList`, piu':

| proprieta' | |
|---|---|
| `SelectionMode` | `Single` (predefinito) o `Multiple` |
| `SelectedValues` | array dei valori scelti |
| `Rows` | righe visibili |

Nella selezione multipla il campo si chiama `id[]` — senza parentesi PHP terrebbe solo
l'ultimo valore — e il raccoglitore lato client manda tutte le opzioni scelte, perche'
`select.value` su un elenco multiplo ne restituisce **una sola**.

Il valore che torna dal client si accetta solo se e' una delle voci che il server ha davvero
reso: altrimenti la selezione diventa un campo di testo libero.

### `ListItem`

| proprieta' | |
|---|---|
| `Value` | |
| `Text` | se manca vale `Value` |
| `Selected` | |

Dichiara le voci nel markup invece di assegnarle dal codebehind:

```html
<dw:DropDownList id="ddlColore" AutoPostBack="true" OnSelectedIndexChanged="SceltaCambiata">
    <dw:ListItem Value="rosso" Text="Rosso" Selected="true" />
    <dw:ListItem Value="verde" Text="Verde" />
</dw:DropDownList>
```

Al montaggio dell'albero le voci finiscono in `Items` e spariscono dai figli: da li' in poi
markup e codebehind sono indistinguibili.

### `HiddenField`

| proprieta' | |
|---|---|
| `Value` | |

Dentro una riga di `Repeater` porta l'identificativo del record, come il `__Hidden_Id` delle
pagine WK. Il valore torna dal client, quindi **non e' un'autorizzazione**: serve a ritrovare
il record, i controlli di accesso restano nel Controller.

### `Panel` e `PlaceHolder`

`Panel` ha `Tag` (predefinito `div`) e rende un contenitore. `PlaceHolder` non rende niente
di suo: e' il punto dove il codebehind attacca i controlli che crea a runtime.

**Un controllo creato dal codice si scrive dove viene comodo, e resta.** In `OnInit`, in
`OnLoad`, dentro un handler: il contenitore si salva i figli che **non vengono dal markup**
— posizione, classe e stato — e li rimette al loro posto in `LoadViewState`, cioe' prima
che si legga il form. Quindi funziona tutto e non solo il render: un `TextBox` attaccato
dal codice riceve quello che l'utente ci ha scritto come uno qualunque.

E' il vecchio «me lo tengo in sessione», senza sessione — e senza l'obbligo di ricrearlo a
ogni richiesta che WebForms imponeva.

```php
$etichetta = new Label();

$etichetta->Id   = 'lblEsito';   //stabile: e' la chiave con cui torna indietro
$etichetta->Text = 'Fatto.';

$this->phEsiti->Add($etichetta);
```

Tre cose da sapere:

- **l'id dev'essere stabile.** Un id che dipende dall'ordine di creazione o da un contatore
  che riparte e' un controllo diverso ogni volta, e lo stato del precedente resta orfano;
- **chi lo ricrea in `OnInit` non si ritrova doppioni.** Se al momento di rimetterlo esiste
  gia' un figlio con quell'id, il motore gli posa sopra lo stato invece di aggiungerne un
  secondo: le pagine scritte alla maniera vecchia continuano a funzionare come sempre;
- **solo i controlli del motore si sanno ricostruire da un nome di classe.** Un UserControl
  e' markup piu' designer piu' classe, e un `new` nudo darebbe un guscio vuoto: attaccarne
  uno a runtime solleva **subito**, dicendo di ricrearlo in `OnInit`, invece di lasciarlo
  sparire al primo click.

**Anche la radice della pagina e' un contenitore.** Una pagina puo' avere un markup senza
nemmeno un controllo e montare tutto dal codice con `$this->Add($pannello)`: quello che si
attacca alla radice torna al postback dopo come da un `PlaceHolder` qualunque. Un `Repeater`
costruito dal codice si porta dietro anche il template, che non ha un markup da cui
rileggerlo — e solo lui: per quelli del markup sarebbe la stessa informazione due volte.
`UnitTest/ProvePaginaVuota.php` e' l'esempio completo: pannello, casella, bottone, elenco e
un CRUD intero a postback, una volta alla radice e una dentro un Panel del markup.

Chi si ricostruisce i figli da se' lo dichiara con `RebuildsChildren()`, e allora non
finiscono nello stato: lo fanno il `Repeater` con le sue righe e `PageNavigator` con i suoi
numeri di pagina, che li rifa' da `CurrentPage`, `PageSize` e `TotalItems`.

`Common/WebForms/ProveAMano/Stato.php` fa vedere le tre cose una accanto all'altra.

### `Stylesheet` e `Script`

```html
<dw:Stylesheet src="Layouts/Sito.css" />
<dw:Script src="Layouts/Sito.js" />
```

| proprieta' | |
|---|---|
| `Src` | percorso dalla **radice dei sorgenti php**: `Layouts/Sito.css`. La barra iniziale si accetta e si ignora |
| `Media` | *(Stylesheet)* `print` per un foglio che vale solo alla stampa; vuoto = tutti |
| `Defer` | *(Script)* **acceso**: non blocca l'analisi, esegue a documento pronto e in ordine |
| `Async` | *(Script)* esegue appena arriva, senza ordine garantito |
| `Module` | *(Script)* `type="module"`, e il defer ce l'ha per definizione |

Rendono il loro tag con in coda `?v=<marca temporale del file>`. Il perche' sta in *"Il CSS:
cosa porta il motore e cosa no"*, piu' avanti: senza quella marca una modifica non arriva a
chi ha gia' visitato il sito, per sessanta giorni.

Un file che non c'e' **ferma la pagina** dicendo quale manca: e' un refuso nel markup, e un
sito senza vestito o senza script si diagnostica peggio di un errore esplicito.

**Uno `<dw:Script>` viene eseguito una volta sola**, al caricamento vero. Qui le pagine non si
ricaricano - un click e' una fetch e poi un morph - e il runtime riesegue solo gli script
INLINE dopo una navigazione, non quelli con `src`, che sono gia' in memoria. Quindi ci va
codice che si aggancia al documento (delega sugli eventi, `DW.onLeave`, `dw:pagina`), non
codice che cerca i suoi elementi all'avvio e se li tiene: al primo morph quelli diventano nodi
che non stanno piu' in pagina.

### `Alert`

Gli avvisi: "salvato", "non salvato e il perche'". Si mette **una volta nella master page**,
come l'UpdateProgress:

```html
<dw:Alert id="avvisi" Duration="5000" />
```

e da qualunque handler di qualunque pagina:

```php
$this->Alert->Success('Categoria salvata.');
$this->Alert->Fail('Non salvata: ' . $esito->Avviso(' '));
$this->Alert->Fail('Il file supera gli 8 MB.', true);   // modale: si deve cliccare OK
```

| proprieta' | |
|---|---|
| `Duration` | millisecondi prima che un riquadro se ne vada da solo (`5000`); `0` = resta finche' non lo si chiude |
| `CssClass` | si **aggiunge** a `dw-avvisi` |

**Due forme, e la differenza non e' l'importanza.** Il riquadro in alto a destra se ne va da
solo e non ferma niente: dice com'e' andata. Il modale oscura la pagina e vuole un OK: e' per
quando il messaggio va letto **per forza**, perche' quello che l'utente stava facendo non e'
successo. Usarlo per un "salvato" e' come mettere un semaforo in mezzo a un corridoio.

Cosa fa da solo, senza una riga di JavaScript da scrivere:

- **al massimo cinque** riquadri insieme, uno sotto l'altro, il piu' recente in basso;
- **se ne va dopo `Duration`**, con una dissolvenza verso destra;
- **col mouse sopra resta**: si sta leggendo, ed e' l'unico momento in cui si e' sicuri che
  quel messaggio interessa a qualcuno. Tolto il mouse, il tempo riparte;
- **la x lo chiude subito**; il modale si chiude con OK o con Esc, non cliccando lo sfondo -
  troppo facile perderlo per sbaglio, ed e' proprio quello che si voleva far leggere.

**Sopravvivono al cambio di pagina.** La coda vive in un campo `#[Portable]`, quindi viaggia
nel pacchetto firmato che il browser tiene in memoria: un handler puo' dire "salvato" e subito
dopo `RedirectToPage(...)`, e il messaggio compare **sulla pagina di arrivo**. Si puo' fare
perche' qui una navigazione e' una fetch e un morph, non un ricarico - non c'e' nessun F5 che
azzeri quella memoria. Un ricarico vero invece li perde, ed e' giusto: un avviso e' la
risposta a un gesto, non un dato della pagina.

**Chi li disegna li consuma**: appena resi la coda si svuota, e il pacchetto che parte con
quella stessa risposta non li porta piu'. Senza, ricomparirebbero ad ogni click.

Si stilano da fuori: `dw-avvisi`, `dw-avviso`, `dw-avviso-successo`, `dw-avviso-fallito`,
`dw-avviso-icona`, `dw-avviso-testo`, `dw-avviso-chiudi`, `dw-modale`, `dw-modale-scatola`,
`dw-modale-ok`. Il motore ne porta il minimo perche' si vedano bene appena montati, e il
foglio di stile del sito, che esce dopo, li sovrascrive senza toccare Common.

### `UpdateProgress`

"Attendere..." mentre il postback e' in viaggio. Si mette **una volta nella master page** e
vale per tutte le pagine, come in WK:

```html
<dw:UpdateProgress id="prgAttesa" DisplayAfter="200">
    <div class="dw-attesa-scatola">Attendere ...</div>
</dw:UpdateProgress>
```

| proprieta' | |
|---|---|
| `DisplayAfter` | millisecondi prima di mostrarsi (predefinito `200`) |
| `Text` | il testo del contenuto predefinito, usato solo se dentro il tag non c'e' niente |
| `CssClass` | si **aggiunge** a `dw-attesa`, non la sostituisce |

Il contenuto va dentro il tag, **senza il `<ProgressTemplate>`** di WebForms: qui l'unico
template che il compilatore riconosce e' l'`ItemTemplate` del Repeater, e un tag inventato
finirebbe nell'HTML come elemento sconosciuto. Senza contenuto ne rende uno suo.

Non ha handler da scrivere, e non c'e' niente da accendere o spegnere. Il runtime mette
`js-dw-attesa` sul `<body>` per tutta la durata della richiesta — postback **e** navigazione —
e questo controllo e' un pezzo di CSS che reagisce a quella classe. Quando la richiesta
finisce la classe sparisce comunque sia andata, anche se la fetch e' fallita.

`DisplayAfter` non e' un vezzo: un postback che dura 40 ms con l'overlay mostrato subito e' un
lampo bianco ad ogni click, che si vede peggio di non averlo. L'attesa la fa il **ritardo di
un'animazione CSS**, non un `setTimeout`, quindi quando la risposta arriva prima non c'e'
niente da annullare — l'animazione semplicemente non parte.

### `Repeater`

| proprieta' | |
|---|---|
| `DataSource` | l'array di righe |
| `DataKeyField` | campo che identifica la riga: e' la **chiave** del morph |
| `Tag` | elemento contenitore (`div` predefinito) |
| `ItemTag` | elemento di ogni riga |
| `ItemTemplate` | il modello, dal markup |
| `OnItemDataBound` | handler chiamato per ogni riga durante il `DataBind()` |

| metodo | |
|---|---|
| `DataBind()` | fotografa `DataSource` nelle righe e le costruisce |
| `Items()` | le righe rese, come `RepeaterItem` |

```html
<table>
    <dw:Repeater id="rptRighe" Tag="tbody" ItemTag="tr" DataKeyField="Id">
        <ItemTemplate>
            <td>{{Nome}}</td>
            <td>
                <dw:HiddenField id="hidId" Value="{{Id}}" />
                <dw:LinkButton id="lnkElimina" Text="Elimina" OnClick="DeleteRow"
                               Confirm="Eliminare {{Nome}}?" />
            </td>
        </ItemTemplate>
    </dw:Repeater>
</table>
```

I segnaposto `{{Campo}}` sono sostituzione testuale, non un motore di espressioni: qui ci va
la presentazione di un campo, non logica. Nel markup letterale escono escapati; in un
attributo escono grezzi, perche' li escapa il controllo al render — escaparli due volte
darebbe `L&#039;Oreal` sotto gli occhi dell'utente.

`Tag` e `ItemTag` servono perche' un `<div>` dentro `<table>` il browser lo sposta fuori
dalla tabella.

Nell'handler si risale alla riga dal controllo che ha scatenato l'evento:

```php
protected function DeleteRow(Control $sender, string $argomento): void
{
    $id = (int)$sender->NamingContainer()->FindControl('hidId')->Value;
    ...
}
```

E' l'equivalente di `linkButton.Parent.FindControl("__Hidden_Id")` delle pagine WK.

#### Riempire le righe in codice: `OnItemDataBound`

I segnaposto vanno bene finche' la cella e' un campo del dato. Quando invece si calcola — un
conteggio, un colore, un link che dipende dai permessi — la riga si riempie in codice, ed e'
la forma delle pagine WK: nel template ci sono `<dw:Literal>` **vuoti**, e a scriverli e'
l'handler.

```html
<dw:Repeater id="rptCategorie" Tag="tbody" ItemTag="tr" DataKeyField="Id"
             OnItemDataBound="OnRowDataBound">
    <ItemTemplate>
        <td><dw:Literal id="litNome" /></td>
        <td><dw:HiddenField id="hidId" Value="{{Id}}" /></td>
    </ItemTemplate>
</dw:Repeater>
```

```php
protected function OnRowDataBound(Repeater $sender, RepeaterItem $riga): void
{
    if ($riga->ItemType !== RepeaterItem::ITEM && $riga->ItemType !== RepeaterItem::ALTERNATING_ITEM)
        return;

    $id = (int)$riga->FindControl('hidId')->Value;

    $categoria = \Model\Categorie::GetItemById($id, 'IT');

    $riga->FindControl('litNome')->Text = $categoria->Nome;
}
```

`RepeaterItem` porta `ItemIndex`, `ItemType` (`ITEM` / `ALTERNATING_ITEM`) e `DataItem`, che vale
**solo dentro l'handler**: fuori dal `DataBind()` la riga non tiene piu' i dati, e chi li
vuole se li rilegge dall'id — come fa WK.

Due regole che il motore garantisce e su cui ci sono le prove:

- l'evento scatta **al `DataBind()` e solo li'**. Ricostruendo le righe dallo stato non
  scatta: altrimenti ogni postback rifarebbe tutte le letture;
- quello che il codice ha messo nelle righe **sopravvive a un postback che non ridatabinda**
  — si apre una scheda, si annulla, si preme un bottone fuori dall'elenco.

**Vale per tutto lo stato dei controlli di riga, non solo per il testo**: `CssClass`, gli
attributi di `Attributes->Add()`, lo stile di `Style->Add()`, il `Visible` di un
bottone dentro la riga. E vale **comunque il codice ci sia arrivato**: da `OnItemDataBound`,
o vestendo le righe dopo il `DataBind()` con `Items()`. Non c'e' un interruttore da
ricordarsi di accendere.

**Come fa, senza pagare due volte lo stesso stato.** Il Repeater fotografa ogni riga appena
costruita — prima che l'handler la tocchi — e a fine richiesta salva **solo i controlli che
non combaciano piu'** con la loro fotografia. Un template a soli segnaposto ricostruisce le
celle dai dati della riga, che sono gia' in `Items`: niente e' cambiato, e nello stato non
finisce niente. Chi tocca tre celle su quaranta paga tre celle.

`Common/WebForms/ProveAMano/Stato.php` e' la prova vista da fuori: un Repeater, un bottone
che fa postback senza ridatabindare, e righe vestite dal codice dopo il `DataBind()`.

Il prezzo del modo WK e' una lettura per riga. Si paga volentieri con dieci o venti righe per
pagina, ed e' quello che permette di far fare al **database** filtro, ordinamento e
impaginazione invece di leggere tutto e tagliare in memoria:

```php
$totale = \Model\Categorie::GetCount(wherePredicate: $predicato, whereValues: $valori);

$this->pgSotto->TotalItems = $totale;

$this->rpt->DataSource = ...GetList(
    item4page: $this->pgSotto->PageSize,
    page: $this->pgSotto->CurrentPage,
    wherePredicate: $predicato,
    whereValues: $valori,
    orderPredicate: $this->ordine . ($this->ascendente ? ' ASC' : ' DESC'),
    selectColumns: ['Id']);

$this->rpt->DataBind();
$this->rpt->Visible = $this->rpt->Items() !== [];
```

I due modi convivono: `Northwind/Categorie` e' fatta cosi', le altre pagine leggono e
lavorano in memoria. Poche decine di righe, o un ordinamento su una colonna che il database
non ha, e conviene il secondo.

### `FileUpload`

| proprieta' | |
|---|---|
| `Accept` | filtro del selettore (`image/*` predefinito): e' del browser, si aggira trascinando |
| `Vincoli` | il campo del Model a cui il file appartiene: `Model\AllegatiOrdine::Documento`. Da li' arrivano le regole |
| `Error` | perche' il file e' stato rifiutato; vuoto se non c'e' niente da dire |
| `AllowDrop` | area di trascinamento — **predefinito `false`** |
| `Text` | testo dell'area di trascinamento; ignorato se `AllowDrop` e' falso |
| `Enabled` | |
| `OnFileUploaded` | handler, chiamato appena il file e' stato messo da parte |
| `Token` | il token del file: e' l'unica cosa che attraversa il postback |

| metodo | |
|---|---|
| `HasFile()` | c'e' un file caricato e non ancora consumato |
| `FileName()` | nome ripulito |
| `Size()` | byte |
| `Bytes()` | il contenuto, letto dal temporaneo solo quando serve; `null` se non c'e' |
| `Clear()` | butta il temporaneo e dimentica il token |

```html
<!-- solo l'input, come in WebForms: nessun elemento in piu' -->
<dw:FileUpload id="fuSemplice" OnFileUploaded="FileCaricato" />

<!-- con l'area di trascinamento -->
<dw:FileUpload id="fuImmagine" AllowDrop="true" OnFileUploaded="ImmagineCaricata"
               Text="Trascina qui l'immagine, o clicca per sceglierla" />
```

**`AllowDrop` e' spento di default.** La zona di trascinamento ha bisogno di un `<div>`
attorno su cui appoggiare eventi e stile, e un elemento in piu' nel markup non si aggiunge a
chi non l'ha chiesto — chi mette un `FileUpload` dentro una cella o una riga di form vuole
l'input e basta.

Cosa cambia davvero nel DOM:

    AllowDrop="false"   <input type="hidden" name="id__token"> <input type="file" id="id" …>
    AllowDrop="true"    <div id="id" class="dw-drop" data-dw-drop="1"> hidden + input + testo </div>

Nel primo caso id e marcatori stanno sull'`<input>`, nel secondo sul `<div>`, e l'input
diventa una superficie trasparente sopra. Il resto — token, anteprima, handler — e'
identico: il codebehind non distingue i due casi. Il capitolo 5 racconta il canale.

---

# 4. UserControl

Un controllo composto ha markup, codebehind e designer suoi, come una pagina — e' il `.ascx`:

    PageNavigator.php           markup                       (.ascx)
    PageNavigator.code.php      la classe                    (.ascx.cs)
    PageNavigator.designer.php  generato                     (.ascx.designer.cs)

Vive in una cartella del sito, non qui dentro: gli UserControl sono dell'applicazione, il
motore sa solo montarli.

**Si scrive col suo nome**, come in WK:

```html
<dw:PageNavigator id="pgSotto" OnPageChanged="PaginaCambiata" PageSize="10" />
```

La regola: un tag `dw:` che **non e' un controllo del motore** si cerca in `UserControls/`,
e se il markup c'e' quello e'. **La cartella e' la registrazione** - niente `<%@ Register %>`
in cima al file come in WebForms, niente elenco da tenere aggiornato da qualche parte.

I controlli del motore vincono sempre: un UserControl chiamato `Panel` resta raggiungibile
solo con `src=`, e va bene cosi' - il markup non deve poter cambiare significato a un tag che
tutti danno per scontato.

**Per un controllo che sta altrove** resta la forma lunga, con `src` senza estensione e
relativo alla radice di `Public/Php`:

```html
<dw:UserControl id="pgSopra" src="Layouts/Parti/PageNavigator"
                OnPageChanged="PaginaCambiata" PageSize="10" />
```

Le due forme fanno esattamente la stessa cosa: la prima e' la seconda con il `src` dedotto.
Anche il designer lo sa, e dichiara `\UserControls\PageNavigator $pgSotto` in tutti e due i
casi.

Tre proprieta' lo rendono utile:

**E' un contenitore di denominazione.** Gli id dei figli vengono qualificati con quello del
controllo (`lnkNext__pgSopra`), cosi' due istanze nella stessa pagina — un paginatore sopra e
uno sotto — non si pestano gli id. Dentro si continua a scrivere `$this->lnkNext`.

**Ha un ciclo di vita suo**: `OnInit` dal basso (i controlli composti si preparano prima
della pagina), `OnLoad` e `OnPreRender` dall'alto — un paginatore deve sapere quanti elementi
ci sono, e lo sa solo dopo che la pagina ha letto i dati. E' l'ordine di WebForms.

**Parla con la pagina per eventi, non per nome.** I bottoni interni non chiamano metodi:
mandano un comando verso l'alto (`CommandName`, il `RaiseBubbleEvent` di WebForms), il
controllo lo raccoglie in `OnBubbleEvent`, aggiorna se stesso e chiama
`RaiseHostEvent('OnPageChanged')`. Chi lo ospita e come si chiami il metodo, il controllo non
lo sa: per questo si riusa.

Dalla pagina ci si arriva con un cast:

```php
/** @var \UserControls\PageNavigator $pg */
$pg = $this->FindControl('pgSopra');
$pg->TotalItems = $totale;
```

Il markup di un UserControl comincia con `<?php http_response_code(404); exit; ?>` — e' un
frammento, non una pagina, e chiesto direttamente non deve rispondere.

**Lo stato che due istanze devono condividere appartiene alla pagina, non al controllo.**
Un paginatore sopra e uno sotto restano allineati solo se nessuno dei due possiede il dato:
lo tiene il codebehind in campi suoi e lo assegna a entrambi ad ogni rilettura.

## La master page

Una master e' la cornice che le pagine condividono - testata, menu, piede - ed e' l'unica
cosa che cambia il modo in cui una pagina si dichiara:

```php
<?php require __DIR__ . '/../Common/WebForms/Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Northwind\Categorie::class, 'Layouts/Sito'); ?>

<dw:Content placeholder="corpo">
    ... la pagina, e nient'altro ...
</dw:Content>
```

Il terzo argomento sta nel markup e non nel codebehind per lo stesso motivo per cui in
WebForms sta nella direttiva `<%@ Page MasterPageFile="..." %>`: e' una scelta di
impaginazione, e si vede aprendo la pagina invece di andarla a cercare.

La master e' fatta come un UserControl - `Layouts/Sito.php`, `.code.php`, `.designer.php` -
e dichiara dove va il contenuto:

```html
<header>…</header>
<dw:UserControl id="menu" src="UserControls/Menu" Tag="nav" />

<dw:ContentPlaceHolder id="corpo">
    <p class="dw-vuoto">Questa pagina non ha ancora contenuto.</p>
</dw:ContentPlaceHolder>

<footer>…</footer>
```

Quello scritto dentro il segnaposto e' il contenuto **predefinito**: resta li' se la pagina
non dichiara nessun `<dw:Content>` per quell'id.

**Dalla pagina la master si tocca per nome, tipizzata.** Il designer dichiara
`public \Layouts\Sito $Master;` quando il markup ne nomina una, quindi
`$this->Master->SetTitle('Categorie', '…')` si completa da solo e un refuso si vede in rosso.

Tre scelte, e sono il punto:

**La master NON avvolge il contenuto in un elemento.** Un UserControl rende un `<div>`
attorno a se'; la master invece *e'* il corpo della pagina, e un `<div>` in piu' attorno a
tutto cambierebbe il CSS di ogni sito che la adotta.

**La master NON e' un contenitore di denominazione.** Di master ce n'e' una sola per pagina,
quindi non c'e' niente da disambiguare, e `txtFiltro` resta `txtFiltro`: e' la regola su cui
si regge il morph, ed e' proprio quella che in WebForms la master rompeva trasformandolo in
`ctl00$corpo$txtFiltro`.

**Un `<dw:Content>` che punta a un segnaposto inesistente e' un errore, non un silenzio.**
Sparirebbe dalla pagina senza che niente lo segnali, ed e' il tipo di difetto che si scopre
guardando una pagina vuota e chiedendosi perche'. Stessa cosa per il markup scritto fuori
dai `<dw:Content>` di una pagina con master: non avrebbe un posto dove finire.

## La testa del documento

`Response::Document` non e' piu' l'unico posto da cui si tocca l'`<head>`:

```php
protected function OnPreRender(): void
{
    $this->Head[] = '<meta name="description" content="' . Control::HtmlEncode($testo) . '">';
    $this->Head[] = '<link rel="canonical" href="' . $url . '">';
}
```

`Page::$Head` e' HTML gia' pronto, quindi ci va solo roba decisa dal server; esce **dopo**
gli stili del motore, cosi' un foglio di stile di pagina o di master li sovrascrive senza
dover alzare la specificita'. `Page::$Lang` riempie `<html lang="…">`, che su un sito
multilingua cambia per richiesta.

## Il CSS: cosa porta il motore e cosa no

Il motore porta **solo il minimo senza cui qualcosa non funziona**: l'attesa che compare dopo
un ritardo, il controllo spento mentre il postback viaggia, il campo file invisibile steso
sopra la zona di trascinamento, il banner degli errori, `[hidden]`. Toglierne una non rende
una pagina brutta: la rompe. I colori li prende da `var(--dw-…)` con un ripiego scritto
accanto, quindi si vede qualcosa anche senza tavolozza.

Sta in `Common/WebForms/runtime.css`, ed entra in testa da solo — con la marca temporale,
come qualunque altro foglio. Non c'e' niente da dichiarare nel markup: e' il motore, non un
pezzo del sito.

**Il vestito lo mette il sito, e non sta in una costante PHP.** Nell'esempio e' un foglio di
stile vero, `Layouts/Sito.css`, agganciato con una riga nel markup della master:

```html
<dw:Stylesheet src="Layouts/Sito.css" />
```

che rende

```html
<link rel="stylesheet" href="/public/php/Layouts/Sito.css?v=1789041657">
```

Il `src` e' relativo alla **radice dei sorgenti php**, la stessa da cui parte l'autoloader:
cosi' la stessa riga funziona su un sito servito da una cartella diversa. `Media="print"` per
un foglio che vale solo alla stampa. Un file che non c'e' ferma la pagina invece di lasciarla
senza vestito: e' un refuso nel markup, e un sito tutto storto e' piu' difficile da capire di
un errore che dice quale file manca.

**La marca temporale non e' un vezzo.** Sotto `Public/Php` i file statici escono con
`Cache-Control` di **sessanta giorni**: senza qualcosa che cambi nell'indirizzo, una modifica
al CSS la vedrebbe solo chi non e' mai passato di li'. Scrivendo il `<link>` a mano ci si
ricorda di alzare il `?v=` le prime due volte e poi mai piu', e si finisce a premere Ctrl+F5
senza capire perche' il sito non cambia; qui l'indirizzo cambia da solo quando cambia il file.

Un `<style>` scritto nel markup va bene uguale, e per poche righe e' anche meglio: una
richiesta in meno.

---

# 5. Upload e trascinamento

Un file **non passa dal postback**: quello e' una POST di campi che risponde JSON. Il file va
per conto suo su `FileUploadHandler.php`, che lo mette da parte e restituisce un **token**;
nel form entra solo il token. Cosi' lo stato resta leggero anche con immagini da megabyte, e
il codebehind vede solo `HasFile()`, `FileName()`, `Bytes()`.

Lo stesso file, chiamato in GET con `?token=…`, serve l'anteprima.

```php
protected function SalvaClick(Control $sender, string $argomento): void
{
    $immagine = $this->fuImmagine->Bytes();      // null se non e' stato caricato niente

    // ... si consegna al Controller: nome vuoto significa "non toccare quella che c'e' gia'"

    $this->fuImmagine->Clear();   // butta il temporaneo
}
```

## Le regole che non sono dettagli

**Il temporaneo sta fuori dalla radice del sito**, in `sys_get_temp_dir()` col nome
`dwup_<32 esadecimali>`: sotto `Public/Php` sarebbe raggiungibile da URL, e un upload
diventerebbe pubblicazione. E' la temp dell'**utente del processo**, quindi condivisa fra i
siti serviti dallo stesso account.

**Il tipo si legge dal contenuto** con `getimagesize()`, non dall'intestazione che scrive il
client — quella vale quanto una promessa. Un `.txt` rinominato `.png` viene rifiutato
(provato). `getimagesize` e non `mime_content_type` perche' l'estensione fileinfo non c'e',
e comunque e' piu' severa: il file dev'essere davvero un'immagine leggibile, non solo
cominciare con i byte giusti.

**Il `drop` va annullato su tutto il documento**, non solo sulla zona: altrimenti un file
lasciato cadere accanto al riquadro lo apre e porta via dalla pagina. Vale anche quando
`AllowDrop` e' spento ovunque.

**Il formato lo decide il server, non l'attributo `accept`.** Quello e' il filtro del
selettore di file: lo applica il browser e si aggira trascinando. Il controllo scarta il file
appena il token arriva, prima che la pagina lo veda, mettendo il perche' in `Error`. Chi
scrive la pagina lo mostra, altrimenti il file sembra sparito nel nulla.

**Le regole non le tiene il controllo: le legge dal campo.** `Vincoli` dice a quale proprieta'
del Model il file e' destinato, e da li' arrivano estensioni ammesse e peso massimo — che
sono quelle dichiarate nel **pannello** e copiate sul Model dal generatore
(`#[VincoliAttribute]`). Non c'e' un secondo elenco da tenere allineato, e l'ultima parola
resta comunque a Kestrel, che ricontrolla al salvataggio: questo taglia il viaggio, non la
guardia.

    <dw:FileUpload id="fuImmagine" Vincoli="Model\Prodotti::Immagine" />
    <dw:FileUpload id="fuAllegato" Vincoli="Model\AllegatiOrdine::Documento" />

Senza `Vincoli` il campo accetta tutto quello che il canale sa riconoscere: va bene per una
vetrina, non per un campo che finisce in un database.

**I documenti si riconoscono dai byte, non dal nome.** Un `.pdf` che non comincia con `%PDF-`
non e' un pdf comunque si chiami; un `.docx` e' uno zip e comincia con `PK`. Solo per i file
inerti - txt e csv - non c'e' niente da riconoscere e si accetta l'estensione. Le immagini
passano da `getimagesize`, che e' piu' severa di un confronto sui primi byte perche' il file
deve essere davvero un'immagine leggibile.

**Un documento si scarica, un'immagine si guarda**: l'anteprima manda
`Content-Disposition: attachment` per i documenti e niente per le immagini.

**Le immagini che non vanno bene si rifiutano, non si convertono.** Quali vadano bene lo dice
il campo, non il controllo: l'elenco delle estensioni e' quello del pannello. Il posto giusto
per dire di no e' qui, all'ingresso, con il formato scritto nel messaggio - non al
salvataggio, dove la piattaforma risponderebbe *"Dimensioni minime 10 pixel"* e sembrerebbe
un problema di misure.

Nessuna conversione di nascosto, mai: chi carica deve sapere cosa e' finito nel sito, e un
file che entra diverso da come e' stato scelto e' una bugia detta a fin di bene - il nome
cambia, i byte cambiano, e chi lo ritrova fra un anno non sa piu' cosa aveva caricato.

**Il token e' il dato, firmato**: dentro ci sono nome, percorso, tipo, scadenza e il browser
a cui appartiene. Non c'e' nessuna tabella da nessuna parte - ne' in sessione ne' altrove - e
il legame col browser e' il cookie del motore. Provato: lo stesso token, valido e firmato,
chiesto senza quel cookie riceve **404**.

Ammessi JPG, PNG e GIF, fino a 8 MB — e comunque entro il limite di PHP, che vince
sempre. Il temporaneo si butta con `Clear()` dopo il salvataggio, e in ogni caso dopo un'ora:
la potatura guarda la **cartella**, non un elenco, quindi passa da tutti i `dwup_` scaduti di
chiunque fossero. Con l'elenco in sessione si vedevano solo i propri, e gli orfani restavano
li' per sempre — misurati, dieci alla volta.

---

# 6. Eventi

## Fra controlli della stessa pagina

`Subscribe` in `OnInit`, `Raise` dagli handler:

```php
protected function OnInit(): void
{
    $this->Subscribe('RigheCambiate', fn() => $this->AggiornaVista());
}
```

Le iscrizioni si dichiarano **in `OnInit`**, mai dentro un handler: devono esistere prima che
la fase eventi cominci ed essere identiche ad ogni ricostruzione dell'albero, altrimenti il
comportamento dipende da cosa l'utente ha cliccato prima. C'e' un tetto di profondita' contro
gli anelli.

## Fra schede e fra utenti

Non c'e' niente da chiamare: il gancio sta in `BaseModel::Save` e `BaseModel::Delete`, quindi
**ogni** salvataggio annuncia la propria entita' — da una pagina WebForms, dal pannello, da un
cron, da un'importazione. Il topic e' il nome della classe senza namespace, cosi' chi ascolta
si iscrive a `Categorie` e non a `Model\Categorie`.

A mano si chiama solo per annunciare qualcosa che non passa da un salvataggio:

```php
EntityEvents::Notify('Categorie');   // es. dopo un riordino fatto con una query sola
```

Il runtime si collega da solo al hub `/signalrhub`. Chi riceve rilegge con un postback
normale.

**E' backend a backend: la pagina non scrive JavaScript.** Il giro completo, con un bottone
al posto di un salvataggio:

```php
//chi manda: un click qualunque
protected function SalutaClick(): void
{
    EntityEvents::Notify('Saluti');
}

//chi riceve, in QUALUNQUE pagina aperta del dominio: si iscrive in OnInit
protected function OnInit(): void
{
    $this->Subscribe('Saluti', function (): void
    {
        $this->SalutiRicevuti++;                    //stato della pagina, sul server
        $this->Alert->Success('Qualcuno ha salutato.');
        $this->Rilega();                            //o un DataBind, per riallineare una griglia
    });
}
```

Cosa succede in mezzo: il nome del topic arriva al browser dal hub, il runtime fa un
postback **vuoto** con quel nome (`__push`), e il motore chiama l'handler iscritto — sul
server, con lo stato di quella pagina in mano, dentro il normale giro degli eventi. Da li'
si fa quello che si farebbe in un click: un avviso, una rilettura, un `DataBind()`. E' cosi'
che due browser sulla stessa griglia restano allineati senza che nessuno abbia scritto una
riga di JavaScript. `ProveAMano/Prima.php` e `Seconda.php` aperte in due schede lo fanno
vedere: si preme nella prima, la seconda mostra l'avviso e sale il contatore.

**E l'evento puo' portare un oggetto.** Terzo argomento di `Notify()`:

```php
$utente = new Utente('Anna', 'Bianchi', 'anna@esempio.it', $immagine);

EntityEvents::Notify('Utente', dati: $utente);

//chi riceve: l'oggetto arriva come array, con le proprieta' pubbliche
$this->Subscribe('Utente', function (array $dati): void
{
    $this->litNome->Text = $dati['Nome'] . ' ' . $dati['Cognome'];
    $this->Alert->Success('E\' arrivato ' . $dati['Nome']);
});
```

Un oggetto qualunque passa da `json_encode` — escono le proprieta' pubbliche, non le
private — e arriva come array con le stesse chiavi; un array arriva com'e', uno scalare
sotto la chiave `valore`. Il pacchetto viaggia **firmato** con il segreto del ViewState: il
browser lo riporta al server com'e', e se lo tocca, o lo attacca a un altro topic, la firma
non regge e l'handler gira senza dati. Un secondo `Notify` dello stesso topic senza dati
non cancella quelli del primo.

La firma protegge l'**integrita'**, non la riservatezza: quello che si passa esce dal
contesto di chi salva ed entra in ogni browser del dominio. Ci va cio' che tutti gli utenti
del sito possono vedere; per il resto il nome e basta, e ognuno rilegge il suo.

La radice della pagina porta `data-dw-topics` con i topic a cui e' iscritta: il runtime fa
il postback di notifica **solo** se ne arriva uno di quelli, invece di svegliare il server
a ogni salvataggio del sito. Prima lo faceva sempre, e il server scartava.

La libreria arriva da **`static.doweb.site`**, non da un CDN pubblico: un sito che per
funzionare dipende da un dominio di qualcun altro smette di funzionare quando quel dominio
ha una brutta giornata, e intanto racconta a lui chi visita le nostre pagine. L'indirizzo
porta il numero di versione (`/LiveServer/signalr/8.0.29/signalr.min.js`), quindi non cambia
mai contenuto e la cache lunga degli statici non e' un problema: la versione nuova e' un
indirizzo nuovo. Se il nostro static non risponde si ripiega su jsdelivr - senza libreria le
notifiche sparirebbero in silenzio, e una griglia che non si aggiorna piu' non ha nessun
sintomo da cui risalire.

Il tag e' in `defer`: se la libreria non arriva restano i postback e si perdono solo le
notifiche.

**L'evento porta solo il nome del topic, mai i dati.** Il salvataggio e' avvenuto nel
contesto di sicurezza di qualcun altro: spedire i valori li farebbe attraversare un confine
di autorizzazione. Chi riceve rilegge con i **propri** permessi, e se quel record non puo'
vederlo non gli torna nulla — nessun controllo da scrivere a mano. In piu' l'aggiornamento
diventa idempotente: eventi doppi o fuori ordine non fanno danno.

Due livelli: `Categorie` per gli elenchi, `Categorie/1042` per i dettagli.

Chi scrive firma l'evento con il proprio `PushId`, cosi' la scheda che ha agito scarta la
propria notifica invece di rileggersi due volte.

Gli eventi si accumulano e partono **una volta sola a fine richiesta**: un salvataggio con
venti righe costa un giro sul pipe, non ventuno. `Suspend()` / `Resume()` per le importazioni.

Il salvataggio a blocchi resta una richiesta sola, quindi anche un'importazione che scrive
mille righe manda un evento solo per entita'. `Suspend()` se nemmeno quello serve.

## Un messaggio con dati a tutti: `Broadcast()` — solo quando serve davvero il JavaScript

Quasi sempre basta `Notify()` + `Subscribe()`: e' backend a backend e non chiede niente al
browser. `Broadcast()` e' per il caso in cui il browser deve reagire **senza un postback** —
un contatore che pulsa, «qualcuno sta scrivendo», un prezzo in vetrina che cambia sotto gli
occhi — e li' un pezzo di JavaScript e' il prezzo:

```php
protected function SalutaClick(): void
{
    EntityEvents::Broadcast('Saluto', ['testo' => $this->txtSaluto->Text, 'ora' => date('H:i:s')]);
}
```

e in pagina, in un `<dw:Script>`:

```js
DW.on('Saluto', dati => {
    const riga = document.createElement('div');
    riga.dataset.dwClient = '1';         // e' roba del client: il morph non la tocca
    riga.textContent = dati.testo + ' alle ' + dati.ora;
    document.getElementById('ricevuti').prepend(riga);
});
```

Parte **subito**, non a fine richiesta, e arriva a **ogni browser** collegato al dominio —
anche a chi ha premuto il bottone, salvo `Broadcast($nome, $dati, ancheAlMittente: false)`.
I dati passano da `json_encode`: array, `JsonSerializable`, oggetti con proprieta' pubbliche.

**Chiunque abbia una pagina aperta lo vede, con qualunque permesso.** Non e' un canale per
il record di un cliente: per quello c'e' `Notify()`, che manda il nome e fa rileggere a
ognuno il suo. Il nome `DWEventi` e' del motore e non si puo' usare.

`DW.on()` vive quanto la pagina: cambiandola gli ascoltatori si tolgono da soli. Torna una
funzione che toglie quell'ascolto; `{ sempre: true }` per uno che deve durare quanto la
scheda. `DW.deliver(nome, json)` simula un messaggio senza hub, per provare.

**`data-dw-client`.** Quello che uno script aggiunge dentro `dw-root` il server non lo
conosce, e al postback dopo il morph lo toglierebbe come figlio in piu'. Il marcatore dice
«questo e' mio»: il morph non lo confronta e non lo toglie. E' il patto delle classi `js-`
portato ai nodi, e solo il client puo' scriverlo — dal server `data-dw-*` e' del motore.

Gli script del motore stanno nella **testa** del documento, tutti in `defer`: i differiti
girano nell'ordine in cui stanno scritti, e cosi' un `<dw:Script>` di pagina, nel body, trova
`DW` gia' pronto. In fondo al body, il motore girava *dopo* lo script della pagina.

`ProveAMano/Prima.php` aperta in due schede fa vedere tutto: si scrive in una, arriva in
tutte e due.

---

# 7. Navigazione e accesso

## Senza ricarico

I link interni non ricaricano: il documento viene chiesto al server e fuso nel DOM, con
`history` e scroll. Restano fuori i link esterni, quelli con `target`, i download, e i click
con ctrl/cmd/shift o tasto centrale — sbagliarlo romperebbe "apri in nuova scheda".

Gli `<script>` inline della pagina vengono **rieseguiti**: uno `<script>` inserito nel DOM da
codice non parte da solo, ed e' la sorpresa classica di chi aggiorna una pagina senza
ricaricarla. Solo in navigazione, mai nel postback.

Il codice di pagina che apre qualcosa (timer, editor, osservatori) lo chiude con
`DW.onLeave(fn)`, altrimenti dopo venti navigazioni se ne trascinano venti copie vive.

## Redirect e login

```php
if (!/* il controllo di accesso dell'applicazione */)
    $this->RedirectToLogin();
```

`Page::Redirect()` si chiama allo stesso modo dal primo caricamento e da dentro un handler:
nel primo caso e' un `302`, nel secondo un'istruzione JSON che il runtime traduce in
navigazione. Durante un postback un `302` non funzionerebbe: la fetch lo seguirebbe e l'HTML
della destinazione finirebbe fuso nel DOM della pagina di partenza.

**Verso un'altra pagina si usa `RedirectToPage()`, che la nomina invece di scriverne
l'indirizzo:**

```php
$this->RedirectToPage(\Pages::Northwind_Categorie);
```

`Pagine` e' un enum **generato dai markup**: ci finiscono solo i file che chiamano
`Page::Run()`, cioe' le pagine vere — non i codebehind, non i designer, non gli UserControl e
non le master, che chiesti da un indirizzo rispondono 404. Il nome del caso e' il percorso
con gli underscore al posto delle barre: `Northwind/Categorie.php` → `Northwind_Categorie`.

Si rigenera da solo quando si apre una pagina che non c'e' dentro, quindi una pagina nuova
entra nell'elenco la prima volta che la si visita; a mano lo rifa' `PageMap::Refresh()`.

Il perche' non e' l'eleganza: una stringa la si sbaglia a scrivere e non se ne accorge nessuno
finche' un utente non ci clicca sopra, e quando una pagina si sposta i rimandi rotti non
compaiono in nessuna ricerca perche' sono pezzi di testo. Un caso dell'enum l'IDE lo completa,
lo rinomina e lo trova.

**`Redirect()` accetta solo destinazioni interne.** Percorsi, e indirizzi assoluti soltanto
verso l'host della richiesta; `//altrosito.it/x`, `javascript:`, `data:` e un a capo
nell'indirizzo sollevano. Un redirect che accetta qualunque cosa e' un trampolino: basta che
un giorno una pagina ci passi un valore che viene dall'utente, e il nostro dominio manda la
gente dove vuole chi ha scritto il link.

E' cortesia verso l'utente, non la difesa. Il controllo di accesso vive nel Controller ed e'
la ragione per cui esiste: una pagina che lo scavalca per "far funzionare la demo" e' il modo
in cui i controlli spariscono davvero. Un handler che elimina chiama il Controller, non il
metodo di cancellazione del modello.

Il canale di postback ha il CSRF, e non passa dalla sessione: il motore mette un valore
casuale in un cookie e lo scrive nella pagina, il runtime lo rimanda nell'intestazione, il
server confronta i due. Un sito estraneo puo' far partire la richiesta col cookie allegato -
e' il cuore del CSRF - ma non puo' leggere ne' il cookie ne' la nostra pagina, quindi non sa
cosa scrivere nell'intestazione. **Un postback senza cookie viene rifiutato**, non lasciato
passare: il cookie e' `SameSite=Lax` e in una POST cross-site non viene mandato, quindi
"cookie assente" e' proprio la situazione da fermare. `Common\Csrf`, che invece tiene il token
in sessione, resta al suo posto per il dispatcher del sito: qui non si tocca. Gli errori
JavaScript sono sempre visibili in pagina, mai silenziosi.

**Un controllo nascosto non scatena eventi.** Qui `Visible = false` rende il controllo lo
stesso, con l'attributo `hidden`, perche' il morph lo ritrovi quando torna visibile invece di
ricostruire il ramo: il rovescio e' che il suo id resta nella pagina, e dalla console si
premerebbe il bottone di una scheda chiusa. L'evento si ferma sul server se il controllo o un
suo antenato e' invisibile, quindi un pannello chiuso e' davvero inerte. `Enabled` vale allo
stesso modo: il `disabled` dell'HTML si toglie dalla console, e la condizione si ricontrolla
dove conta.

---

# 8. Regole da non disfare

**Gli id escono verbatim.** Nessun prefisso di contenitore, nessun `ctl00$`. E' l'errore che
rendeva inservibile `getElementById` in WebForms, ed e' anche cio' che permette al morph di
riconoscere i nodi invece di ricrearli.

**Le righe del Repeater si chiavano sul dato** (`DataKeyField`), mai sulla posizione. Con id
posizionali, inserire una riga in testa riscrive l'intera griglia: sparisce il focus, muoiono
i listener, si vede il lampo. E' la `key` di React, per lo stesso motivo.

**L'handler lo nomina il markup, non il client.** Il browser manda l'id del controllo. Non
esiste una richiesta capace di far eseguire un metodo che il markup non abbia autorizzato.

**L'albero costruito in `OnInit` dev'essere deterministico.** Se dipendesse dai dati, lo stato
non si riaggancerebbe al postback successivo. I controlli creati a runtime — le righe di un
Repeater, i numeri di un paginatore — si ricreano prima che si scatenino gli eventi.

**Il morph ha delle regole sue.** Le classi con prefisso `js-` sono del client e sopravvivono
al postback; un sottoalbero con `dw-preserve` non viene toccato (editor, datepicker); il
campo con il focus non viene mai calpestato.

**Gli eventi di dominio non portano dati.** Chi riceve rilegge con i propri permessi.

---

# 9. Cosa manca di proposito

- **Render parziale dei soli controlli sporchi.** Il render totale + morph e' corretto per
  costruzione. E' un'ottimizzazione interna: si aggiunge senza toccare markup ne' codebehind.
- **Binding bidirezionale lato client.** Gli agganci ci sono; il modello JS locale si aggiunge
  quando una pagina lo merita davvero.
- **Master annidate.** Una master dentro un'altra master: WebForms le ha, qui una pagina ne
  ha una sola. Il giorno che servisse, il posto e' `ControlBuilder::BuildMaster`.
- **`TreeView`**, quattro usi in WK.
- **`idiomorph`.** Se lo si carica in pagina, `DW.morph` lo usa da solo. Il morph incluso e'
  la scorta autosufficiente.

---

# 10. Le prove

    UnitTest/prove.cmd             TUTTE le prove: quelle PHP e quelle del JavaScript
    UnitTest/Esegui.php            le prove PHP: da URL risponde 200 se e' tutto verde e 500 se no
    UnitTest/prove.mjs             le prove del JavaScript, con node: il cassetto, la navigazione, DW.on
    UnitTest/Prova.php             confronto, conto, e "deve sollevare"
    UnitTest/ProveControlli.php    cosa rendono i controlli, e cosa diventano col POST
    UnitTest/ProveDatePicker.php   date vere, i due Mode, cosa entra dal browser, l'evento, l'enum nello stato
    UnitTest/ProveEventiConDati.php Notify con un oggetto: il messaggio firmato che parte e il postback che torna
    UnitTest/ProveDinamici.php     i controlli attaccati dal codice, e come tornano indietro
    UnitTest/ProvePaginaVuota.php  markup vuoto, tutto dal codice: un CRUD intero a postback
    UnitTest/ProveQuerystring.php  la querystring cambia, lo stato resta, la pagina rilega
    UnitTest/ProveMemoria.php      lo stato cala con i dati: ClearItems e rimpiazzi non lasciano niente
    UnitTest/ProveVariabili.php    le variabili di pagina restano tutte; #[Transient] e #[Portable]
    UnitTest/ProveViewStateMode.php Inherit/Enabled/Disabled, e cosa resta sotto uno spento
    UnitTest/ProveOgniControllo.php le tre domande fatte a TUTTI i controlli, per riflessione
    UnitTest/ProveMarkup.php       il compilatore, i segnaposto {{Campo}}, i <dw:Content>
    UnitTest/ProveRepeater.php     OnItemDataBound: quando scatta, e cosa sopravvive al postback
    UnitTest/ProveSicurezza.php    controlli nascosti, redirect fuori sito, CSRF
    UnitTest/ProveStato.php        il pacchetto firmato: soprattutto cosa RIFIUTA
    ProveAMano/Stato.php           il banco di prova da aprire nel browser (vedi sotto)
    ProveAMano/Tabella.php         righe di tabella costruite a mano, e nient'altro
    ProveAMano/Prima.php           due pagine con un menu: cosa attraversa la navigazione
    ProveAMano/Seconda.php         e cosa invece resta di la'
    ProveAMano/Cornice.php         la master dei banchi (menu, titolo, piede)

Si lanciano nei due modi, e l'esito e' un numero: **uscita 1** da riga di comando, **500**
sull'HTTP, cosi' le puo' guardare uno script senza leggerle a occhio.

    "C:\Program Files\PHP\php.exe" Esegui.php
    http://localhost:8081/public/php/Common/WebForms/UnitTest/Esegui.php

Da riga di comando **non si passa da `Start.php`**: quello vuole un sito attorno e il pipe
verso Kestrel. Si registra il minimo per trovare le classi e le prove girano su PHP e basta -
che e' esattamente quello che devono provare.

**Il JavaScript si prova ritagliandolo.** `runtime.js` e' scritto per il DOM, ma i pezzi con una
logica propria — il cassetto delle pagine tenute, la navigazione e il tasto indietro, la
consegna dei messaggi — si tagliano fra due marcatori e girano in node con un DOM finto grande
quanto basta. Non e' il morph, che si guarda nel browser: e' quello che si puo' sbagliare
senza che il browser lo dica — la chiave sbagliata sul tasto indietro, il postback in volo
durante la navigazione. `prove.mjs` va lanciato con node; `Esegui.php` non puo' farlo, perche'
`exec` e compagni sono spenti in `php.ini` — com'e' giusto su un server web — e le prove
girano con lo stesso `php.ini` del sito.

**Si prova quello che e' PHP puro**: i controlli sono oggetti, si valorizzano e si guarda
l'HTML, oppure si passa loro l'array del POST e si guarda cosa diventano. Niente HTTP,
niente database, niente pipe. Una prova che ha bisogno di un sito acceso non e' una prova
unitaria, e' un collaudo.

## Il banco di prova a mano

    http://<sito>/public/php/Common/WebForms/ProveAMano/Stato.php

Le prove unitarie dicono che lo stato torna indietro; questa pagina lo fa vedere
cliccando — che e' l'unico modo di provare anche il pezzo che gira nel browser, cioe' il
morph che rimpiazza i nodi senza ricaricare. Risponde a tre domande, una per riquadro:
quello che il codice mette nelle righe di un `Repeater` resta; un controllo costruito in
`OnInit` resta, con dentro anche quello che l'utente ci ha digitato, e senza diventare un
doppione; e restano anche quelli costruiti in `OnLoad` o dentro un handler.

`Tabella.php` e' il caso limite, ed e' il piu' severo che si possa fare allo stato: in
`OnLoad` **non succede niente**, e ogni `<tr>` — con dentro un `Literal` e un `LinkButton`
«elimina» — nasce da un click e non viene mai ricostruito. Mette insieme le quattro cose
che di solito si rompono una alla volta: un albero dinamico **annidato** (`tr` dentro
`tbody`, `td` dentro `tr`), un controllo che **scatena eventi** creato dal codice, la
**rimozione** di una riga che deve restare rimossa senza spostare le altre, e il **tag**
giusto — un `<div>` dentro una `<table>` il browser lo butta fuori dalla tabella, e il
morph poi non ritrova piu' niente al suo posto.

L'orario stampato in ogni riga e' li' per questo: se dopo cinque postback e' ancora quello
del click che l'ha creata, quella riga non e' stata ricostruita da nessuno.

**Stanno in `Common` di proposito.** Niente Model, niente database, nessun foglio di stile
del sito: e' una prova **del motore**, quindi viaggia con il motore e si apre uguale su un
sito appena creato che non ha ancora niente dentro. La master page c'e', ma e' `Cornice.php`
li' accanto: il menu che lega i banchi fra loro e' la prova a mano del "modo WinForms" (§4),
e una master dentro `Common` e' anche la prova che una master page puo' stare fuori dal sito.
Per lo stesso motivo non entrano nell'enum `Pagine`: `PageMap` non attraversa `Common`.

Sotto una master gli `id` dei controlli della pagina e quelli della master vivono nello stesso
elenco: un `<dw:Panel id="corpo">` in una pagina il cui segnaposto si chiama `corpo` e' un
errore di tipo al primo caricamento, non un avviso.

## Le prove che non si scrivono

`ProveOgniControllo` non nomina nessun controllo: li pesca da `Controls/` e fa a ognuno le
stesse tre domande. Il controllo che qualcuno aggiungera' fra sei mesi e' gia' sotto prova
senza che nessuno scriva una riga.

1. **Ogni proprieta' pubblica sta in `ViewStateProperties()`?** Una che non c'e' si azzera
   al click dopo, e non e' un errore che si vede: la pagina funziona, ma un valore sparisce
   quando l'utente fa un'altra cosa. Le eccezioni vere - `Id`, `Parent`, `Page`, il
   `DataItem` che vale solo dentro l'handler - stanno in un elenco **con scritto il
   perche'**: cosi' aggiungerne una e' una decisione, non una dimenticanza.
2. **Lo stato torna indietro intero?** Si riempie ogni proprieta' con un valore del tipo
   giusto, si salva, si ricarica su un controllo nuovo e si confronta.
3. **Il testo cattivo esce escapato?** Ogni proprieta' di testo, piu' un attributo e uno
   stile aggiunti dal codice, si riempiono di `<script>"x"&'y'</script>`, e nell'HTML non
   deve comparire com'era. E' la regola che tiene su tutto il resto, e vale per i controlli
   di domani come per quelli di oggi.

**Non si prova** il morph, la coda dei postback e la navigazione: sono JavaScript e vanno
guardati nel browser. Ne' il layer dati, che passa dal pipe.

Ogni prova corrisponde a un difetto vero, gia' capitato: il doppio escape negli attributi,
la casella non spuntata che non si distingueva da "controllo assente", il `select multiple`
che mandava un valore solo, il ViewState manomesso. Il nome di una prova dice **cosa deve
succedere**, non cosa fa il codice: quando diventa rossa, la riga che si legge spiega gia'
cosa si e' rotto.

---

# 11. Il plugin per PhpStorm

In `Z:\Rete\_Programmi\DOWEB\PHPSTORM` — jar, sorgenti, `compila.cmd`, `installa.cmd`.

**Navigazione.** Ctrl+click sul nome di un tag apre la classe del controllo - e per un
UserControl chiamato per nome, `<dw:PageNavigator>`, il suo codebehind. Sul valore di un
evento apre il metodo nel `.code.php`; sul nome di un evento di UserControl, il punto in cui
il controllo lo solleva; su `src` il markup, il foglio di stile o lo script. Su
`placeholder="corpo"`, il `<dw:ContentPlaceHolder>` della master.

**Completamento.** I nomi degli attributi dentro un tag `dw:`: scrivendo `Con` dentro un
`<dw:LinkButton>` esce `Confirm`. L'elenco non e' una tabella scritta a mano, viene da
`ViewStateProperties()` della classe: un controllo nuovo o una proprieta' nuova entrano nel
completamento senza toccare il plugin.

**Generazione.** `X.designer.php` si riscrive ad ogni salvataggio di `X.php`, quindi il
controllo appena aggiunto al markup si completa subito nel codebehind. Un UserControl
chiamato per nome — `<dw:Menu>` — si dichiara con la SUA classe, `\UserControls\Menu`, con
la stessa regola di `ControlBuilder::TagSrc()` (dalla **1.21.0**: prima scriveva
`Controls\Menu`, e la pagina moriva al primo caricamento). Il motore comunque non si fida
della data: se un designer dichiara un tipo del motore che non esiste, lo rigenera lui. **New
&rarr; WebForms** chiede il nome e crea i tre file.

Il completamento degli attributi propone anche `ViewStateMode`, che non sta in
`ViewStateProperties()` per forza — e' lui a decidere se lo stato esiste.

**Tasti.** Mappa `DOWEB WebForms (Visual Studio)` da Settings > Keymap: i tasti del debugger
di Visual Studio e **F7** per saltare fra markup e codebehind, nei due versi.

**Apri in Chrome**, primo nel menu del tasto destro: apre la pagina sul Kestrel locale
leggendo la porta da `SitiLocali.xml`, invece che sul server interno di PhpStorm dove la
pagina non gira.

Segna in rosso gli handler che non esistono, e toglie l'avviso *"Class name doesn't match
the containing file name"* sui `.code.php`, che per costruzione non puo' coincidere. Annida
`X.code.php` e `X.designer.php` sotto `X.php`:

    Ordine.php  (+)
       Ordine.code.php
       Ordine.designer.php

Il plugin di terze parti **File Nesting** va tenuto disattivato: appiattisce l'annidamento.
E in `ui.lnf.xml` non devono esserci regole di File Nesting scritte a mano — mandano in
eccezione il servizio nativo ad ogni ricostruzione dell'albero, e l'albero dei file smette di
funzionare senza dire perche'.
