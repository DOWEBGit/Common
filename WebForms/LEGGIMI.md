# Motore WebForms

Un motore a postback per PHP, sul modello di ASP.NET WebForms: albero di controlli lato
server, eventi nel codebehind, e il browser che fonde le differenze invece di ricaricare.

Questa cartella e' il motore. Non contiene pagine del sito: le pagine stanno nel sito che lo
usa. Le uniche pagine qui dentro sono gli esempi di `Examples/`, che sono del motore: una
pagina per controllo, sotto una master con il menu.

**La lingua.** L'API del motore e' in inglese e ricalca WebForms. Lo stesso vale per il codice
che si scrive con il motore: **nomi di variabili, metodi, pagine, tabelle e campi in inglese;
commenti, testi mostrati all'utente e messaggi in italiano.** Un identificatore in inglese si
legge in qualunque codice del mondo e non fa a pugni con le parole chiave e con le librerie;
un commento in italiano lo legge chi lo deve leggere. Northwind e' nato in italiano e resta
cosi': e' un esempio, non un modello di stile.

Il documento e' diviso cosi':

    1. Una pagina            i tre file, il ciclo di vita, le classi
    2. Lo stato              le variabili che restano, #[Portable], il ViewState, KeepState, ViewStateMode
    3. I controlli           uno per uno, con le regole che il motore garantisce
    4. UserControl           i controlli composti e la master page
    5. Upload                il canale dei file, separato dal postback
    6. Eventi                fra controlli, fra schede, fra utenti; la master che ascolta
    7. Navigazione e accesso link senza ricarico, redirect, CSRF, e dove finiscono gli errori
    8. Regole da non disfare
    9. Cosa manca di proposito
    10. Le prove             unitarie, del JavaScript, e gli esempi da toccare
    11. Il plugin per PhpStorm

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
<dw:TextBox id="__TextBox_Articolo" Placeholder="Codice articolo" />
<dw:Button id="__Button_Aggiungi" Text="Aggiungi" OnClick="AggiungiClick" />
<dw:Label id="__Label_Stato" />
```

Il codebehind estende `Page` e usa il trait del designer:

```php
class Ordine extends Page
{
    use OrdineDesigner;

    protected function AggiungiClick(Control $sender, string $argomento): void
    {
        $this->__Label_Stato->Text = 'Riga aggiunta.';   // e qualunque altro controllo
    }
}
```

Codebehind e designer **non passano dall'autoloader** — il loro nome non e' un nome di
classe — e vengono inclusi per percorso, designer per primo perche' e' un trait che la
classe usa. Il vantaggio e' che la classe si chiama `\WebForms\Ordine`, non `OrdineCode`.

Il designer si riscrive da solo quando il markup e' piu' recente del file, o quando lo e' il
generatore, o quando dichiara un tipo del motore che non esiste. Ci mette anche un `@see` per
ogni handler nominato nel markup: se scrivi `OnClick="DeletRow"` con il refuso, l'IDE lo
segna in rosso subito.

## Ciclo di vita

    OnInit               albero costruito dal markup - sempre identico, e' il vincolo che regge tutto;
                         prima quello degli UserControl e della master, poi quello della pagina
    LoadPortable         il pacchetto #[Portable] che attraversa le pagine
    LoadViewState        proprieta' dei controlli, controlli attaccati dal codice, variabili della pagina
    LoadPostData         i valori del form entrano nei controlli
    OnLoad               con $this->IsPostBack; pagina prima, UserControl dopo
    RaisePostBackEvent   l'handler nominato dal markup, o gli iscritti a un evento arrivato dal hub
    OnPreRender          pagina prima, UserControl dopo
    SaveViewState        lo stato torna nel campo nascosto, firmato
    Render               HTML dell'INTERA pagina

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
| `Page` | ciclo di vita, `IsPostBack`, `FindControl()`, `Add()`, `KeepState`, `Title` `Lang` `Head`, `Alert`, `Subscribe()`/`Raise()`, `Redirect()`, `RedirectToPage()`, `RedirectToLogin()` |
| `Control` | base: `Id` `Visible` `CssClass` `Attributes` `Style` `ViewStateMode` `Parent` `Controls` `Page`, `FindControl()`, `NamingContainer()`, `IsViewStateEnabled()`, `CanRaiseEvents()`, `RaiseBubbleEvent()`, `Render()` |
| `AttributeCollection` / `CssStyleCollection` | `Attributes` e `Style`: `Add` `Remove` `Clear` `Has`, leggibili come array |
| `UserControl` | controllo composto: ciclo di vita proprio, `OnBubbleEvent()`, `RaiseHostEvent()` |
| `MasterPage` | la cornice condivisa: un UserControl che contiene la pagina invece di esserne contenuto |
| `PageParser` | markup → albero di nodi, con cache su `mtime` |
| `ControlBuilder` | nodi → controlli, segnaposto `{{Campo}}`, `<dw:ListItem>`, master e `<dw:Content>` |
| `ViewState` | lo stato: campo nascosto compresso e firmato, niente sessione |
| `ViewStateMode` | `Inherit` / `Enabled` / `Disabled`, ereditato lungo l'albero |
| `Response` | `Document()` `Fragment()` `Redirect()` `Reload()` `StateField()` |
| `Runtime` | i tag che il motore mette in ogni pagina: `runtime.css` in testa, `runtime.js` e SignalR in testa con `defer` |
| `Designer` | genera il trait dei controlli, e lo rigenera quando non torna |
| `Upload` | il canale dei file, separato dal postback |
| `Csrf` | protezione a doppio invio su cookie: nessun token da tenere sul server |
| `EntityEvents` | notifiche di dominio: `Notify()` `Broadcast()` `Flush()` `Suspend()` `Resume()` |
| `Transient` | attributo sui campi che NON devono sopravvivere al postback: tutti gli altri restano |
| `Portable` | attributo sui campi che devono attraversare le pagine |
| `Alert` | la coda degli avvisi: `Success()` `Fail()`, e vivono in un campo `#[Portable]` |
| `PageMap` | genera l'enum `Pages` leggendo i markup: l'elenco per `RedirectToPage()` |
| `SitePage` | quel poco che il motore chiede all'enum generato: `Path()` |
| `DateTimeMode` | l'enum del `DatePicker`: `Date` / `DateTime` |

`runtime.js` e `runtime.css`, accanto a quelle classi, **sono il motore lato browser**: morph,
postback, navigazione, upload, notifiche, piu' le regole di stile senza cui un comportamento
si rompe. Li aggancia `Runtime` da solo — tutti nella testa del documento, gli script con
`defer` — con la marca temporale nell'indirizzo. Stanno in due file veri e non in un `const`
di PHP perche' li' dentro non sarebbero codice per nessuno: non per l'editor, non per il
controllo di sintassi, non per il debugger del browser, che li chiamerebbe "inline" senza
saper dire a che riga sei.

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

Nessun attributo. Un campo di pagina e' memoria per definizione, e chiedere di marcarlo
sarebbe solo un modo per dimenticarsene. Ci vanno **scalari e array**, anche annidati.

Restano fuori **da soli**: le proprieta' del motore (`Title`, `Lang`, `IsPostBack`, …), le
`#[Portable]`, che hanno un canale loro, e le proprieta' **tipizzate con una classe** — i
controlli del designer, la master, un Model. Un oggetto non attraversa la serializzazione,
e non deve nemmeno provarci: se ne metti uno in una proprieta' senza tipo, il salvataggio si
ferma subito con il nome della proprieta', invece di dartelo rotto al click dopo.

Il poco che deve **rinascere** ad ogni richiesta — una cache riempita in `OnLoad`, un valore
grosso che non ha senso far viaggiare — si marca `#[Transient]`. Serve anche a chi legge:
senza, un campo che si azzera sembra un difetto.

Tutto questo vive quanto la pagina; e con il modo WinForms (piu' sotto), che e' il
predefinito, la pagina vive finche' la scheda e' aperta, anche mentre se ne guarda un'altra.
Per un valore che deve passare **da una pagina all'altra** c'e' `#[Portable]`.

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
del server, e due postback dello stesso browser non si mettono in fila per un lock — che e'
esattamente il momento in cui un modo "in sessione" serializzerebbe le richieste, quando i
postback si accavallano.

Si paga in banda: ~1 kB piu' ~25 byte per riga, ad ogni postback, in andata e ritorno.

Il campo nascosto e' il ViewState vero: `serialize` → `gzdeflate` → `base64` → **HMAC-SHA256**.
La compressione prima del base64 (dopo non comprimerebbe piu' niente), la firma per ultima,
cosi' si firma esattamente cio' che viaggia. Il campo va in **fondo** alla pagina, per non
ritardare il contenuto.

La firma non e' un dettaglio: senza, il client si riscrive lo stato e le proprieta' dei
controlli diventano quello che decide lui. Il segreto nasce da solo in `_segreto.php` — un
`.php` e non un `.txt`, altrimenti sotto `Public/Php` verrebbe servito come file statico e
la chiave sarebbe pubblica.

Uno stato manomesso o scaduto non e' un errore: `OnViewStateExpired()` ricarica pulito.

### Sta FUORI da `dw-root`

Il campo nascosto e' un fratello della radice, non un suo figlio:

```html
<div id="dw-root"> … tutta la pagina … </div>
<input type="hidden" id="__dw_state" value="v1.…">
```

Dentro `dw-root` ci sta il contenuto, e ad ogni postback quel contenuto viene riconciliato
nodo per nodo dal morph. Se il campo stesse li' in mezzo, **il pacchetto piu' importante
della pagina dipenderebbe dal fatto che il morph lo riconosca** e ne aggiorni il valore: una
riconciliazione che va storta, e il click dopo parte da uno stato vecchio. Fuori non dipende
da niente — il client se lo scrive da se', con quello che il server gli manda accanto
all'HTML — e si trova sempre allo stesso posto, come una volta si trovava la sessione. Se
manca, il client se lo ricrea invece di perdere il postback.

Il postback lo aggiorna **prima** di toccare il DOM: se il morph solleva a meta' strada, il
campo e' gia' quello nuovo e il click successivo resta allineato col server. Una navigazione
senza ricarico lo prende dal documento appena scaricato.

## Il modo WinForms: `KeepState`, acceso per tutti

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

Spento, cambiare pagina **azzera il ViewState**: lo stato descrive quella pagina com'era, e
tornarci dopo essere stati altrove vuol dire ricominciare — su un elenco di dati che cambiano
sotto le mani di altri e' anche l'unica cosa giusta. Quello che attraversa le pagine resta
`#[Portable]`, in tutti e due i modi.

Come funziona: la radice esce con `data-dw-tieni`, il client tiene una mappa
*indirizzo → stato* (fino a **50 MB**, poi butta quelle con l'ultimo accesso piu' vecchio), e
quando si torna su un indirizzo che ha in serbo fa una **POST** invece della solita GET, con
l'intestazione `X-DW-Ripristina`. Il server la tratta come un postback senza evento —
`IsPostBack` e' vero, quindi l'inizializzazione di `OnLoad` non ricomincia da capo — e
risponde con un documento intero perche' resta una navigazione.

Cosa sapere:

- lo stato tenuto vive **nella memoria di quella scheda**: F5 lo butta, un'altra scheda non
  lo vede, chiudere il browser lo perde. Non e' un salvataggio, e non va usato come tale;
- la chiave e' il **percorso senza querystring**: `Calendario.php?mese=2026-10` e' la stessa
  pagina di `?mese=2026-09`, e lo stato rientra lo stesso. Il server vede la `$_GET` nuova e
  `IsPostBack` e' vero; cosa ricaricare lo decide la pagina in `OnLoad` — confronta il
  parametro con quello che si ricorda, e se e' cambiato fa `$rpt->ClearItems()` e rilega.
  `UnitTest/ProveQuerystring.php` e' esattamente questo, con i giorni di un mese;
- **il tasto indietro e avanti del browser** ripristinano come un link: lo stato che si tiene
  e' quello di **dopo** l'ultimo postback, anche se il click sul link e' arrivato mentre
  quel postback era ancora in volo — la navigazione aspetta che finisca;
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

`Examples/State.php` ha la casella per accenderlo e spegnerlo, e il menu a sinistra per
andare altrove e tornare.

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
`Panel` spento spegne tutto quello che contiene. `IsViewStateEnabled()` dice com'e' finita
la risalita.

Cosa succede sotto uno spento:

- i controlli **del markup** ci sono ancora, coi valori del markup: quello che il codice ci
  aveva scritto e' perso;
- i controlli **attaccati dal codice** non tornano: chi li vuole li ricrea in `OnInit`, alla
  maniera di WebForms;
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
| `Content` | 280 | si', con la master page (§4) |
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
| `RichTextBox` | — | si': testo formattato come in Word, ogni funzione si spegne da sola |
| `ListBox` | 6 | si' |
| `ModalPopupExtender` | 0 | si', come `<dw:ModalPopup>`: WK non lo usa, ma una scheda sopra l'elenco e' il caso piu' comune |
| `TreeView` | 4 | manca, e con quattro usi non vale il prezzo |

Piu' quelli che una pagina non scrive ma il sito si': `Alert`, `UpdateProgress`, `Stylesheet`,
`Script`, `ContentPlaceHolder`.

Tutti stanno in `Controls` e hanno `Id`, `Visible`, `CssClass`, `Attributes`, `Style`,
`ViewStateMode`.

### Come si chiamano gli id

Il motore accetta qualunque id. La **convenzione** e' quella di WK, dove la seguono 3.400
controlli su 3.900: due underscore, il **nome del tipo**, un underscore, e un nome in
PascalCase che dice **cosa contiene**, non com'e' fatto.

    __Literal_RagioneSociale        __TextBox_Iban              __DropDownList_Tipo
    __LinkButton_Elimina            __Button_Salva              __CheckBox_InvioAutomatico
    __PlaceHolder_Allegati          __Panel_Nascosto            __Repeater_Elenco
    __Hidden_Id                     __FileUpload_Allegato       __DatePicker_Dal
    __Label_Stato                   __ListBox_Regioni           __Alert  __UpdateProgress  __PageNavigator

| tipo | prefisso | note |
|---|---|---|
| `Literal` | `__Literal_` | |
| `Label` | `__Label_` | |
| `TextBox` | `__TextBox_` | |
| `DatePicker` | `__DatePicker_` | |
| `RichTextBox` | `__RichTextBox_` | |
| `Button` / `LinkButton` | `__Button_` / `__LinkButton_` | `__Button_Salva`, `__Button_Chiudi`, `__Button_Nuovo` sono i nomi che WK usa ovunque |
| `CheckBox` | `__CheckBox_` | |
| `DropDownList` | `__DropDownList_` | in WK compare anche `__DDL_`: si preferisce il nome intero |
| `ListBox` | `__ListBox_` | |
| `HiddenField` | `__Hidden_` | e dentro una riga di Repeater e' sempre `__Hidden_Id` |
| `Repeater` | `__Repeater_` | `__Repeater_Elenco` quando in pagina ce n'e' uno solo |
| `Panel` / `PlaceHolder` | `__Panel_` / `__PlaceHolder_` | |
| `ModalPopup` | `__ModalPopup_` | |
| `FileUpload` | `__FileUpload_` | |
| UserControl | `__NomeDelControllo` | `__Menu`, `__PageNavigator`; con due istanze `__PageNavigator_Sopra`, `__PageNavigator_Sotto` |
| `Alert`, `UpdateProgress` | `__Alert`, `__UpdateProgress` | uno per master, senza nome |

Perche' conviene: nel codebehind `$this->__TextBox_Iban->Text` dice il tipo senza aprire il
markup; scrivendo `$this->__Te` il completamento elenca tutte le caselle di testo della
pagina; una ricerca di `__Literal_` trova tutti i Literal del sito; e un id che comincia con
`__` non si confonde mai con un `id="menu"` scritto a mano nell'HTML, con una classe CSS o con
una variabile JavaScript. Chi arriva da WK legge le pagine PHP come leggeva le `.aspx`.

Due cose da sapere. Il motore aggiunge un suffisso `__` agli id dentro un UserControl
(`__Literal_Nome__PageNavigator`): la convenzione ci convive, il nome resta leggibile. E i
nomi `__dw_*` sono del runtime — sono i campi del postback — quindi non si usano come id.

La parte dopo il prefisso segue la regola della lingua: in codice nuovo `__Button_Save`,
`__Literal_CompanyName`, `__Repeater_Orders`; gli esempi qui sopra sono di WK, che e' in
italiano. Le pagine Northwind, la master del sito, gli UserControl e gli esempi la seguono
tutti. Resta un suggerimento: il motore accetta qualunque id.

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
- **`id`, `class`, `style`, `hidden`, `name` e `data-dw-*` sono riservati**: i primi cinque li
  scrive gia' il controllo (si usano `Id`, `CssClass`, `Style`, `Visible`), l'ultimo e' il
  canale fra server e runtime. Scriverli solleva, invece di produrre un HTML con l'attributo
  doppio in cui il browser tiene il primo — cioe' non quello appena messo.

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

### Le proprieta' enum

Una proprieta' con due o tre valori possibili e' un **enum**, non una costante stringa: il
`Mode` del `DatePicker` e' `DateTimeMode::Date` o `DateTimeMode::DateTime`. Il motore le
tratta da solo: dal markup si sceglie il caso **per nome**, senza badare alle maiuscole
(`Mode="datetime"` va bene, `Mode="Ora"` si ferma al markup con il nome del controllo); nello
stato viaggia il valore e torna come enum; la prova generica su tutti i controlli le sonda
come le altre. Il prossimo controllo con un'alternativa chiusa la dichiara cosi'.

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
| `Type` | il `type` HTML, quando serve `email`, `number`… Per le date c'e' il `DatePicker` |
| `Rows` | righe della textarea |
| `Enabled` | |
| `AutoPostBack` | posta appena il valore cambia |
| `AutoPostBackDelay` | millisecondi di pausa prima di postare |
| `OnTextChanged` | handler |

**Ricerca mentre si scrive**, dichiarata e basta:

```html
<dw:TextBox id="__TextBox_Filtro" AutoPostBack="true" AutoPostBackDelay="350"
            OnTextChanged="FiltroCambiato" />
```

`AutoPostBackDelay` a 0 aspetta il blur, come WebForms; sopra 0 il runtime ascolta ogni tasto
e trattiene il postback per quella pausa. Digitando "color" parte **una** richiesta, non
cinque. Il codebehind non distingue i due casi.

### `DatePicker`

```html
<dw:DatePicker id="__DatePicker_Dal" Mode="Date" AutoPostBack="true" OnDateChanged="DalCambiato" />
<dw:DatePicker id="__DatePicker_Quando" Mode="DateTime" />
```

```php
$this->__DatePicker_Dal->Value = new \DateTimeImmutable('2026-09-14');
$quando = $this->__DatePicker_Quando->Value;   // ?DateTimeImmutable, null se vuoto
$this->__DatePicker_Dal->Min = new \DateTimeImmutable('2026-01-01');
```

| proprieta' | |
|---|---|
| `Mode` | `DateTimeMode::Date` (predefinito) o `DateTimeMode::DateTime`: `<input type="date">` o `type="datetime-local"`. Nel markup `Mode="Date"` / `Mode="DateTime"` |
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

### `RichTextBox`

Un editor di testo formattato, alla maniera di Word.

```html
<dw:RichTextBox id="__RichTextBox_Descrizione" MinHeight="220" MaxHeight="600"
                Placeholder="Scrivi qui..." ShowCounter="true" MaxLength="2000"
                EnableTable="false" EnableFontName="false" />
```

```php
$this->__RichTextBox_Descrizione->Text = $articolo->Descrizione;   // HTML
$articolo->Descrizione = $this->__RichTextBox_Descrizione->Text;   // HTML gia' ripulito
$anteprima = $this->__RichTextBox_Descrizione->PlainText();        // solo il testo, con gli a capo

$this->__RichTextBox_Nota->EnableOnly('Bold', 'Italic', 'Link');   // solo queste
$this->__RichTextBox_Nota->SetAll(false);                          // nessun bottone
```

| proprieta' | |
|---|---|
| `Text` | il contenuto, in **HTML**. Quello che arriva dal browser e' gia' ripulito secondo le funzioni accese |
| `Placeholder` | il suggerimento nell'editor vuoto |
| `MinHeight` `MaxHeight` | in pixel; `MaxHeight` 0 = cresce col testo, altrimenti l'area scorre |
| `MaxLength` | caratteri di **testo** (la formattazione non conta). Il browser non lascia scrivere oltre, il server lo ricontrolla con `LengthExceeded()` |
| `ShowCounter` | parole e caratteri sotto l'editor (con `MaxLength` c'e' comunque) |
| `Enabled` | spento si legge e non si scrive; il server ignora quello che arriva |
| `AutoPostBack` `AutoPostBackDelay` `OnTextChanged` | come il `TextBox`: all'uscita dall'editor, o dopo la pausa mentre si scrive. Handler `(RichTextBox $sender)` |
| `FontNames` | i caratteri del menu, con `;`. **Sono anche gli unici che passano**: il Calibri incollato da Word cade |
| `FontSizes` | le dimensioni del menu, in pixel, con `,` |
| `Colors` | la tavolozza dei due menu colore, con `,`; vuota = quella predefinita (40 colori, otto per riga) |

**Le funzioni, una per una.** Ognuna ha la sua proprieta', tutte `true` tranne `EnableSourceView`:

| proprieta' | cosa | tastiera |
|---|---|---|
| `EnableUndoRedo` | Annulla, Ripeti | Ctrl+Z, Ctrl+Y / Ctrl+Maiusc+Z |
| `EnableHeadings` | Paragrafo, Titolo 1-4 | |
| `EnableFontName` `EnableFontSize` | carattere e dimensione, dai due elenchi sopra | |
| `EnableBold` `EnableItalic` `EnableUnderline` | grassetto, corsivo, sottolineato | Ctrl+B, Ctrl+I, Ctrl+U |
| `EnableStrikethrough` `EnableSuperscript` `EnableSubscript` | barrato, apice, pedice | |
| `EnableForeColor` `EnableBackColor` | colore del testo, evidenziatore; la tavolozza piu' "Altro colore…" | |
| `EnableAlign` | sinistra, centro, destra, giustificato | Ctrl+L, E, R, J |
| `EnableLineHeight` | interlinea 1 / 1,15 / 1,5 / 2 / 2,5 / 3 | |
| `EnableBulletedList` `EnableNumberedList` | elenchi | Ctrl+Maiusc+8, Ctrl+Maiusc+7 |
| `EnableIndent` | rientro: negli elenchi annida, fuori sposta il paragrafo di 40 px | Tab / Maiusc+Tab negli elenchi |
| `EnableQuote` `EnableHorizontalRule` | citazione, linea orizzontale | |
| `EnableLink` | collegamento: indirizzo, testo, nuova scheda. "doweb.it" diventa https, una mail mailto:, un numero tel: | Ctrl+K |
| `EnableTable` | tabella dalla griglia 8×8, poi righe e colonne da aggiungere e togliere | |
| `EnableClearFormatting` | toglie la formattazione dalla selezione | |
| `EnableSourceView` | mostra e fa modificare l'HTML — ripulito comunque, come il resto | |
| `EnableFullScreen` | l'editor a tutto lo schermo: sul telefono e' quello che serve | Esc per uscire |

In codice: `EnableOnly('Bold', 'Link')`, `SetAll(bool)`, `IsEnabled('Table')`, e l'elenco
`RichTextBox::FEATURES`. Un nome che non esiste si ferma con l'elenco di quelli buoni.

**Spenta vuol dire spenta anche sul server.** Il bottone sparisce E quello che la funzione
farebbe non passa: con `EnableTable="false"` una tabella incollata da Word diventa paragrafi,
con `EnableLink="false"` un link resta testo, con `EnableForeColor="false"` il colore cade. La
regola vale per quello che scrive l'utente (`LoadPostData`); quello che mette il **codice** in
`Text` esce con tutte le funzioni — spegnere un bottone non deve far sparire un contenuto messo
apposta — ma sempre ripulito.

**La pulizia e' un elenco, non una lista nera.** L'HTML che arriva si ricostruisce da zero con il
parser HTML5 di PHP 8.4 (`Dom\HTMLDocument`, lo stesso algoritmo dei browser) tenendo solo: `p`
`br` `span` `strong` `em` `u` `s` `sup` `sub` `h1-h4` `ul` `ol` `li` `blockquote` `hr` `a`
`table` `thead` `tbody` `tr` `th` `td`; un solo attributo, `style`, con `color`,
`background-color`, `font-size`, `font-family`, `font-weight`, `font-style`, `text-decoration`,
`vertical-align`, `text-align`, `line-height`, `margin-left` — ognuna col valore controllato;
e sui link `href` solo `http` `https` `mailto` `tel` o un percorso del sito, con `target="_blank"`
che si porta sempre dietro `rel="noopener noreferrer"`. Quello che non si conosce si scioglie e
ne resta il testo; `script`, `style`, `iframe`, `img`, `svg`, i campi di una form se ne vanno col
contenuto. `b` `i` `strike` `del` `div` `font` diventano i loro equivalenti, `h5` `h6` diventano
`h4`. Un editor svuotato (`<p><br></p>`) e' vuoto: `Text` vale `''`.

Serve anche fuori da una pagina, per un testo arrivato da un'API o da un import:

```php
$pulito = RichTextBox::Sanitize($html, (new RichTextBox())->Rules());
```

**Come e' fatto.** L'area e' un `contenteditable` con `dw-preserve`: il morph non la tocca mai. Il
valore viaggia in un `<input type="hidden">` col nome del controllo, e dopo ogni render il
runtime dell'editor decide chi comanda: se l'utente e' dentro l'editor vince quello che sta
scrivendo, altrimenti vince il server. Senza, un `AutoPostBackDelay` che torna mentre si scrive
riporterebbe indietro le ultime lettere e il cursore. I comandi di base sono quelli del browser
(`execCommand`: l'unico modo che funzioni uguale su Chrome, Safari, Firefox, e con la tastiera
del telefono, la dettatura, il correttore); interlinea, rientri, tabelle, dimensioni in pixel si
fanno sul DOM. **Annulla e Ripeti sono dell'editor**, non del browser: tornano indietro anche su
quelle. **L'incolla** — da Word, da una pagina, da un'altra app — si ripulisce PRIMA di entrare con
le stesse regole del server, che gliele manda nella configurazione: quello che si vede e' quello
che restera'.

**Il telefono.** Bottoni da 40 px sui touch, la barra su una riga che scorre di lato sotto i 640 px,
i menu come un foglio che sale dal basso, lo schermo intero; la barra resta in vista mentre si
scorre un testo lungo. Toccare un bottone non chiude la tastiera e non perde la selezione.

**I file.** `RichTextBox/RichTextBox.js` lo carica il motore (`armaEditor` in `runtime.js`) la prima
volta che in pagina c'e' un editor — all'apertura, dopo un postback che lo mostra, dopo una
navigazione — **una volta sola** anche con dieci editor. `RichTextBox/RichTextBox.css` e' un
`<link>` dentro il controllo, cosi' c'e' anche per un editor comparso a un postback. **Le icone
stanno nel CSS**, come maschere SVG (`mask-image` con l'SVG in un `data:`): il colore e' quello del
testo del bottone, un bottone in pagina e' uno `<span>` vuoto, e il file e' in cache come ogni
statico. Sono di [Lucide](https://lucide.dev) 1.47, licenza ISC (`RichTextBox/LICENSE-lucide.txt`);
per aggiungerne una si prende il suo `.svg` e la si mette in fondo al CSS con lo stesso schema.

**Piu' editor nella stessa pagina** vanno da se': ognuno ha il suo campo, la sua area
(`<id>__area`), la sua configurazione e il suo stato nel browser. In un `Repeater` gli id
prendono il suffisso della riga come ogni controllo, e il Salva di una riga trova il suo editor
con `$sender->NamingContainer()->FindControl('__RichTextBox_Testo')`. L'esempio fa un elenco di
note con Salva per riga, Aggiungi e Salva tutto.

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
`<body>`, e l'`UpdateProgress` che ci reagisce.

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
<dw:DropDownList id="__DropDownList_Colore" AutoPostBack="true" OnSelectedIndexChanged="SceltaCambiata">
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

Dentro una riga di `Repeater` porta l'identificativo del record, e si chiama `__Hidden_Id` come
nelle pagine WK. Il valore torna dal client, quindi **non e' un'autorizzazione**: serve a ritrovare
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

$etichetta->Id   = '__Label_Esito';   //stabile: e' la chiave con cui torna indietro
$etichetta->Text = 'Fatto.';

$this->__PlaceHolder_Esiti->Add($etichetta);
```

Tre cose da sapere:

- **l'id dev'essere stabile.** Un id che dipende dall'ordine di creazione o da un contatore
  che riparte e' un controllo diverso ogni volta, e lo stato del precedente resta orfano;
- **chi lo ricrea in `OnInit` non si ritrova doppioni.** Se al momento di rimetterlo esiste
  gia' un figlio con quell'id, il motore gli posa sopra lo stato invece di aggiungerne un
  secondo: le pagine scritte alla maniera di WebForms funzionano come sempre;
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
finiscono nello stato: lo fanno il `Repeater` con le sue righe, `PageNavigator` con i suoi
numeri di pagina, che li rifa' da `CurrentPage`, `PageSize` e `TotalItems`, e la master con
i suoi segnaposto, che vengono dal markup.

`Examples/DynamicControls.php` fa vedere le tre cose una sotto l'altra, e in fondo il caso
piu' severo: righe di tabella che nascono solo da click.

### `ModalPopup`

Un pezzo di pagina che compare **sopra** il resto e, finche' e' aperto, e' l'unica cosa che si
tocca: il `ModalPopupExtender` dell'AjaxControlToolkit, senza l'extender — qui il popup **e'**
il contenitore, un `Panel` che sa aprirsi.

```html
<dw:Button id="__Button_Apri" Text="Modifica" />

<dw:ModalPopup id="__ModalPopup_Scheda" TargetControlID="__Button_Apri"
               OkControlID="__Button_Ok" CancelControlID="__Button_Annulla"
               DragHandleControlID="__Panel_Testata" DropShadow="true">
    <dw:Panel id="__Panel_Testata" CssClass="testata">Scheda</dw:Panel>
    <dw:TextBox id="__TextBox_Nome" />
    <dw:Button id="__Button_Salva" Text="Salva" OnClick="SalvaClick" />
    <button type="button" id="__Button_Ok">Ok</button>
    <button type="button" id="__Button_Annulla">Annulla</button>
</dw:ModalPopup>
```

| proprieta' | |
|---|---|
| `TargetControlID` | l'elemento che, cliccato, lo apre **nel browser**, senza postback. Vuoto: si apre solo da `Show()` |
| `OkControlID` / `CancelControlID` | gli elementi che lo chiudono nel browser, **senza postback**; il secondo vale anche per Esc |
| `OnOkScript` / `OnCancelScript` | JavaScript che gira alla chiusura; il popup solleva anche gli eventi DOM `dw:ok` e `dw:cancel` |
| `DragHandleControlID` | l'elemento dentro il popup — la testata — che si afferra per trascinarlo |
| `BackgroundCssClass` | classe in piu' sullo sfondo che copre la pagina |
| `DropShadow` | ombra sulla scatola |
| `X` `Y` | angolo in alto a sinistra, in pixel; `-1` (predefinito) = centrato su quell'asse. Solo `X`: fisso in orizzontale e centrato in verticale, e viceversa |

| metodo | |
|---|---|
| `Show()` | apri, da un handler: il browser lo mostra con la risposta di questo postback |
| `Hide()` | chiudi, da un handler: quando il salvataggio e' andato bene |

Il codebehind e' quello di una scheda qualunque — il popup non cambia niente di come si
leggono e si scrivono i controlli che contiene:

```php
protected function ModificaClick(Control $sender, string $argomento): void
{
    //aprire dal server, con la scheda gia' riempita: il click sul TargetControlID non
    //basterebbe, perche' il browser non sa cosa metterci dentro
    $categoria = \Model\Categorie::GetItemById((int)$argomento, 'IT');

    $this->__TextBox_Nome->Text = $categoria->Nome;
    $this->__ModalPopup_Scheda->Show();
}

protected function SalvaClick(): void
{
    if (trim($this->__TextBox_Nome->Text) === '')
    {
        $this->Alert->Fail('Il nome e\' obbligatorio.');   //il popup resta aperto, l'avviso sopra

        return;
    }

    // ... si salva tramite il Controller ...

    $this->__ModalPopup_Scheda->Hide();
    $this->Alert->Success('Salvato.');
}
```

**Non e' l'`Alert` modale, e non lo sostituisce.** L'avviso modale (`Alert->Fail($testo, true)`)
e' un messaggio che va letto per forza: un testo e un OK. Il `ModalPopup` e' un pezzo di
pagina con dentro dei controlli veri — una scheda, un filtro, una conferma con una casella
da compilare — che fanno postback e hanno stato. Se quello che serve e' dire una cosa, e'
un avviso; se serve chiedere qualcosa, e' un popup.

**Chi lo apre e chi lo chiude, e dove.** Il click sul `TargetControlID` lo apre nel browser;
OK e Annulla lo chiudono nel browser; nessuno dei tre fa postback — come nel toolkit, dove
l'extender annulla il click di quei controlli. Un controllo qualunque **dentro** il popup fa
il suo postback normale, e il popup **resta aperto**: e' il server a decidere se chiuderlo con
`Hide()`, tipicamente quando il salvataggio e' andato bene — e a lasciarlo aperto con un
`Alert->Fail()` quando non passa la validazione. L'avviso compare sopra il popup, che sta
sotto gli avvisi di proposito.

**Lo stato aperto/chiuso vive nel browser**, in una classe `js-` che il morph rispetta, non
nel ViewState: `Show()` e `Hide()` sono ordini per **questa** risposta, non uno stato che si
porta dietro. Se fosse nello stato, un Annulla fatto nel browser lascerebbe il server
convinto che il popup e' aperto, e al postback dopo lo riaprirebbe. Il rovescio: un ritorno
sulla pagina con il modo WinForms lo trova chiuso, e va bene cosi' — un popup e' un lavoro in
corso, non un dato.

Cliccare sullo sfondo **non fa niente**, di proposito: e' un lavoro da finire o da annullare,
non un avviso da far sparire. Il fuoco entra nel primo campo del popup e torna dov'era alla
chiusura. Il trascinamento lascia la posizione in uno stile in linea sulla scatola, quindi il
postback dopo la rimette dov'era il server. `RepositionMode` del toolkit non serve: la
posizione e' `fixed`, e resiste a scroll e ridimensionamento da sola.

Dentro un UserControl i riferimenti (`TargetControlID` e gli altri) prendono il suffisso del
contenitore come gli id, quindi si scrivono nudi, come in `FindControl()`. `DW.showModal(id)`
e `DW.hideModal(id)` per aprirlo e chiuderlo da uno script di pagina.

Cosa esce nell'HTML, per chi lo stila da fuori:

```html
<div id="__ModalPopup_Scheda" class="dw-popup [CssClass]" role="dialog" aria-modal="true" data-dw-modal="1" …>
    <div class="dw-popup-fondo [BackgroundCssClass]"></div>
    <div class="dw-popup-scatola [dw-popup-ombra]"> … i figli … </div>
</div>
```

`dw-popup` e' `display:none` finche' il runtime non aggiunge `js-dw-aperto`; la scatola e'
centrata, scorre se e' piu' alta della finestra, e ha `padding` e `border-radius` suoi che il
foglio del sito sovrascrive quando vuole. Il `CssClass` del controllo va sul contenitore
esterno, quindi `.scheda .dw-popup-scatola { width: 640px }` e' il modo di darle una misura.

`Examples/ModalPopup.php` e' la prova: apertura dal browser e dal server, Salva che chiude solo
se c'e' del testo, Ok e Annulla che scrivono in pagina cosa e' successo, testata trascinabile.

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

**Uno `<dw:Script>` viene eseguito una volta sola per scheda.** Qui le pagine non si
ricaricano - un click e' una fetch e poi un morph - e dopo una navigazione il runtime
riesegue gli script INLINE, mentre quelli con `src` li **carica se non li ha mai visti** in
questa scheda (per indirizzo, senza la marca temporale) e li lascia stare se sono gia' in
memoria: uno script della pagina raggiunta navigando parte lo stesso, e non gira due volte.
Quindi ci va codice che si aggancia al documento (delega sugli eventi, `DW.onLeave`,
`dw:pagina`, `DW.on`), non codice che cerca i suoi elementi all'avvio e se li tiene: al primo
morph quelli diventano nodi che non stanno piu' in pagina. `Examples/Resources.php` lo fa
vedere. Trova `DW` gia' pronto: gli script del motore
stanno in testa, con `defer`, e i differiti girano nell'ordine in cui sono scritti.

### `Alert`

Gli avvisi: "salvato", "non salvato e il perche'". Si mette **una volta nella master page**,
come l'UpdateProgress:

```html
<dw:Alert id="__Alert" Duration="5000" />
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
<dw:UpdateProgress id="__UpdateProgress" DisplayAfter="200">
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
| `ClearItems()` | via tutte le righe, e dallo stato: e' l'`Items.Clear()` di WebForms |

```html
<table>
    <dw:Repeater id="__Repeater_Righe" Tag="tbody" ItemTag="tr" DataKeyField="Id">
        <ItemTemplate>
            <td>{{Nome}}</td>
            <td>
                <dw:HiddenField id="__Hidden_Id" Value="{{Id}}" />
                <dw:LinkButton id="__LinkButton_Elimina" Text="Elimina" OnClick="DeleteRow"
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
    $id = (int)$sender->NamingContainer()->FindControl('__Hidden_Id')->Value;
    ...
}
```

E' il `linkButton.Parent.FindControl("__Hidden_Id")` delle pagine WK, con lo stesso nome.

#### Riempire le righe in codice: `OnItemDataBound`

I segnaposto vanno bene finche' la cella e' un campo del dato. Quando invece si calcola — un
conteggio, un colore, un link che dipende dai permessi — la riga si riempie in codice, ed e'
la forma delle pagine WK: nel template ci sono `<dw:Literal>` **vuoti**, e a scriverli e'
l'handler.

```html
<dw:Repeater id="__Repeater_Categorie" Tag="tbody" ItemTag="tr" DataKeyField="Id"
             OnItemDataBound="OnRowDataBound">
    <ItemTemplate>
        <td><dw:Literal id="__Literal_Nome" /></td>
        <td><dw:HiddenField id="__Hidden_Id" Value="{{Id}}" /></td>
    </ItemTemplate>
</dw:Repeater>
```

```php
protected function OnRowDataBound(Repeater $sender, RepeaterItem $riga): void
{
    if ($riga->ItemType !== RepeaterItem::ITEM && $riga->ItemType !== RepeaterItem::ALTERNATING_ITEM)
        return;

    $id = (int)$riga->FindControl('__Hidden_Id')->Value;

    $categoria = \Model\Categorie::GetItemById($id, 'IT');

    $riga->FindControl('__Literal_Nome')->Text = $categoria->Nome;
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

`Examples/Repeater.php` e' la prova vista da fuori: un Repeater con `OnItemDataBound`, righe
vestite dal codice dopo il `DataBind()`, un bottone che fa postback senza ridatabindare, e
`ClearItems()`.

Il prezzo del modo WK e' una lettura per riga. Si paga volentieri con dieci o venti righe per
pagina, ed e' quello che permette di far fare al **database** filtro, ordinamento e
impaginazione invece di leggere tutto e tagliare in memoria:

```php
$totale = \Model\Categorie::GetCount(wherePredicate: $predicato, whereValues: $valori);

$this->__PageNavigator_Sotto->TotalItems = $totale;

$this->__Repeater_Categorie->DataSource = ...GetList(
    item4page: $this->__PageNavigator_Sotto->PageSize,
    page: $this->__PageNavigator_Sotto->CurrentPage,
    wherePredicate: $predicato,
    whereValues: $valori,
    orderPredicate: $this->ordine . ($this->ascendente ? ' ASC' : ' DESC'),
    selectColumns: ['Id']);

$this->__Repeater_Categorie->DataBind();
$this->__Repeater_Categorie->Visible = $this->__Repeater_Categorie->Items() !== [];
```

I due modi convivono: `Northwind/Categorie` e' fatta cosi', le altre pagine leggono e
lavorano in memoria. Poche decine di righe, o un ordinamento su una colonna che il database
non ha, e conviene il secondo.

### `FileUpload`

| proprieta' | |
|---|---|
| `Accept` | filtro del selettore (`image/*` predefinito): e' del browser, si aggira trascinando |
| `Constraints` | il campo del Model a cui il file appartiene: `Model\AllegatiOrdine::Documento`. Da li' arrivano le regole |
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
<dw:FileUpload id="__FileUpload_Semplice" OnFileUploaded="FileCaricato" />

<!-- con l'area di trascinamento -->
<dw:FileUpload id="__FileUpload_Immagine" AllowDrop="true" OnFileUploaded="ImmagineCaricata"
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

Vive in una cartella del sito: gli UserControl sono dell'applicazione, il motore sa solo
montarli. I due che stanno dentro `Common` — `Examples/PropertyTable` e `Examples/SourceView`
— sono degli esempi, non del motore, e si chiamano con `src=` come qualunque controllo che
sta fuori da `UserControls/`.

**Si scrive col suo nome**, come in WK:

```html
<dw:PageNavigator id="__PageNavigator_Sotto" OnPageChanged="PaginaCambiata" PageSize="10" />
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
<dw:UserControl id="__PageNavigator_Sopra" src="Layouts/Parti/PageNavigator"
                OnPageChanged="PaginaCambiata" PageSize="10" />
```

Le due forme fanno esattamente la stessa cosa: la prima e' la seconda con il `src` dedotto.
Anche il designer lo sa, e dichiara `\UserControls\PageNavigator $__PageNavigator_Sotto` in tutti e due i
casi.

Tre proprieta' lo rendono utile:

**E' un contenitore di denominazione.** Gli id dei figli vengono qualificati con quello del
controllo (`__LinkButton_Next____PageNavigator_Sopra`), cosi' due istanze nella stessa pagina — un paginatore sopra e
uno sotto — non si pestano gli id. Dentro si continua a scrivere `$this->__LinkButton_Next`.

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
$pg = $this->FindControl('__PageNavigator_Sopra');
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
<dw:Menu id="__Menu" Tag="nav" />

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
La master ha `OnInit`, `OnLoad` e `OnPreRender` come un UserControl, e da li' arriva alla
pagina con `$this->Page`: e' cosi' che si iscrive a un evento per tutte le pagine (§6).

Quattro scelte, e sono il punto:

**La master NON avvolge il contenuto in un elemento.** Un UserControl rende un `<div>`
attorno a se'; la master invece *e'* il corpo della pagina, e un `<div>` in piu' attorno a
tutto cambierebbe il CSS di ogni sito che la adotta.

**La master NON e' un contenitore di denominazione.** Di master ce n'e' una sola per pagina,
quindi non c'e' niente da disambiguare, e `__TextBox_Filtro` resta `__TextBox_Filtro`: e' la regola su cui
si regge il morph, ed e' proprio quella che in WebForms la master rompeva trasformandolo in
`ctl00$corpo$__TextBox_Filtro`. Il rovescio: **gli id della master e quelli della pagina vivono
nello stesso elenco**. Un `<dw:Panel id="corpo">` in una pagina il cui segnaposto si chiama
`corpo` e' un errore di tipo al primo caricamento, con il nome della proprieta' nel messaggio,
non un avviso; i controlli della master si chiamano in modo da non incrociarsi con quelli
delle pagine.

**Un `<dw:Content>` che punta a un segnaposto inesistente e' un errore, non un silenzio.**
Sparirebbe dalla pagina senza che niente lo segnali, ed e' il tipo di difetto che si scopre
guardando una pagina vuota e chiedendosi perche'. Stessa cosa per il markup scritto fuori
dai `<dw:Content>` di una pagina con master: non avrebbe un posto dove finire.

**La master puo' stare ovunque**, anche fuori dal sito: il terzo argomento e' un percorso dalla
radice dei sorgenti php, e `Common/WebForms/Examples/Site` e' una master che vive nel
motore. I suoi controlli vengono dal markup, quindi non finiscono nello stato dei figli
dinamici: e' la stessa regola dei segnaposto e dei `<dw:Content>`.

## La testa del documento

L'`<head>` si tocca dalla pagina, in `OnPreRender`, quando sa gia' cosa sta mostrando:

```php
protected function OnPreRender(): void
{
    $this->Head[] = '<meta name="description" content="' . Control::HtmlEncode($testo) . '">';
    $this->Head[] = '<link rel="canonical" href="' . $url . '">';
}
```

`Page::$Head` e' HTML gia' pronto, quindi ci va solo roba decisa dal server; esce **dopo**
gli stili del motore, cosi' un foglio di stile di pagina o di master li sovrascrive senza
dover alzare la specificita'. `Page::$Title` e' il `<title>`, `Page::$Lang` riempie
`<html lang="…">`, che su un sito multilingua cambia per richiesta. Tutti e due viaggiano
nello stato e tornano con la pagina quando la si ripristina.

## Il CSS: cosa porta il motore e cosa no

Il motore porta **solo il minimo senza cui qualcosa non funziona**: l'attesa che compare dopo
un ritardo, il controllo spento mentre il postback viaggia, il campo file invisibile steso
sopra la zona di trascinamento, il riquadro degli errori, gli avvisi, `[hidden]`. Toglierne
una non rende una pagina brutta: la rompe. I colori li prende da `var(--dw-…)` con un ripiego
scritto accanto, quindi si vede qualcosa anche senza tavolozza.

Sta in `Common/WebForms/runtime.css`, ed entra in testa da solo — con la marca temporale,
come qualunque altro foglio. Non c'e' niente da dichiarare nel markup: e' il motore, non un
pezzo del sito.

**Il vestito lo mette il sito.** Nell'esempio e' un foglio di stile vero, `Layouts/Sito.css`,
agganciato con una riga nel markup della master:

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
richiesta in meno. `Examples/Site.php` e' fatta cosi'.

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
    $immagine = $this->__FileUpload_Immagine->Bytes();      // null se non e' stato caricato niente

    // ... si consegna al Controller: nome vuoto significa "non toccare quella che c'e' gia'"

    $this->__FileUpload_Immagine->Clear();   // butta il temporaneo
}
```

## Le regole che non sono dettagli

**Il temporaneo sta fuori dalla radice del sito**, in `sys_get_temp_dir()` col nome
`dwup_<32 esadecimali>`: sotto `Public/Php` sarebbe raggiungibile da URL, e un upload
diventerebbe pubblicazione. E' la temp dell'**utente del processo**, quindi condivisa fra i
siti serviti dallo stesso account.

**Il tipo si legge dal contenuto**, non dall'intestazione che scrive il client — quella vale
quanto una promessa. Le immagini passano da `getimagesize()`: un `.txt` rinominato `.png`
viene rifiutato (provato). `getimagesize` e non `mime_content_type` perche' l'estensione
fileinfo non c'e', e comunque e' piu' severa: il file dev'essere davvero un'immagine
leggibile, non solo cominciare con i byte giusti.

**I documenti si riconoscono dai byte, non dal nome.** Un `.pdf` che non comincia con `%PDF-`
non e' un pdf comunque si chiami; `.docx`, `.xlsx` e `.zip` cominciano con `PK`, `.doc` e
`.xls` con la firma dei vecchi Office. Solo per i file inerti - txt e csv - non c'e' niente da
riconoscere e si accetta l'estensione.

**Un documento si scarica, un'immagine si guarda**: l'anteprima manda
`Content-Disposition: attachment` per i documenti e niente per le immagini.

**Il `drop` va annullato su tutto il documento**, non solo sulla zona: altrimenti un file
lasciato cadere accanto al riquadro lo apre e porta via dalla pagina. Vale anche quando
`AllowDrop` e' spento ovunque.

**Il formato lo decide il server, non l'attributo `accept`.** Quello e' il filtro del
selettore di file: lo applica il browser e si aggira trascinando. Il controllo scarta il file
appena il token arriva, prima che la pagina lo veda, mettendo il perche' in `Error`. Chi
scrive la pagina lo mostra, altrimenti il file sembra sparito nel nulla.

**Le regole non le tiene il controllo: le legge dal campo.** `Constraints` dice a quale proprieta'
del Model il file e' destinato, e da li' arrivano estensioni ammesse e peso massimo — che
sono quelle dichiarate nel **pannello** e copiate sul Model dal generatore
(`#[VincoliAttribute]`). Non c'e' un secondo elenco da tenere allineato, e l'ultima parola
resta comunque a Kestrel, che ricontrolla al salvataggio: questo taglia il viaggio, non la
guardia.

    <dw:FileUpload id="__FileUpload_Immagine" Constraints="Model\Prodotti::Immagine" />
    <dw:FileUpload id="__FileUpload_Allegato" Constraints="Model\AllegatiOrdine::Documento" />

Senza `Constraints` il campo accetta tutto quello che il canale sa riconoscere — JPG, PNG, GIF,
pdf, zip, docx, xlsx, doc, xls, txt, csv — fino a 8 MB, e comunque entro il limite di PHP,
che vince sempre. Va bene per una vetrina, non per un campo che finisce in un database.

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

Il temporaneo si butta con `Clear()` dopo il salvataggio, e in ogni caso dopo un'ora: la
potatura guarda la **cartella**, non un elenco, quindi passa da tutti i `dwup_` scaduti di
chiunque fossero, e non lascia orfani.

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
si iscrive a `Categorie` e non a `Model\Categorie`. Due livelli: `Categorie` per gli elenchi,
`Categorie/1042` per i dettagli.

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
riga di JavaScript.

La radice della pagina porta `data-dw-topics` con i topic a cui e' iscritta: il runtime fa il
postback di notifica **solo** se ne arriva uno di quelli, invece di svegliare il server a ogni
salvataggio del sito. Chi scrive firma l'evento con il proprio `PushId`, cosi' la scheda che
ha agito scarta la propria notifica invece di rileggersi due volte.

`Examples/Events.php` aperta in due schede lo fa vedere: si preme in una, l'altra mostra
l'avviso e sale il contatore — chi ha premuto scarta la propria notifica.

**Un evento che deve arrivare su ogni pagina si ascolta nella master**, una volta:

```php
class Site extends MasterPage
{
    public function OnInit(): void
    {
        $this->Page->Subscribe('Saluti', function (): void
        {
            $this->__Literal_Greetings->Text = (string)((int)$this->__Literal_Greetings->Text + 1);
            $this->Page->Alert->Success('Qualcuno ha salutato.');
        });
    }
}
```

La master ha `OnInit` come la pagina e `$this->Page->Subscribe()` e' la stessa iscrizione:
cosi' ogni pagina degli esempi, anche quelle che di «Saluti» non sanno niente, mostra l'avviso
e il contatore in fondo al menu. Una pagina puo' iscriversi allo stesso evento per conto suo -
lo fa `Events.php`, per tenere un conto suo - e girano tutti e due gli handler.

Il conteggio sta in un `Literal` della master e non in una proprieta' della classe: le
variabili che restano da sole sono quelle della **pagina** (§2), la master e' un controllo e
il suo stato e' quello dei suoi controlli.

**Gli eventi dei salvataggi portano solo il nome del topic, mai i dati.** Il salvataggio e'
avvenuto nel contesto di sicurezza di qualcun altro: spedire i valori li farebbe attraversare
un confine di autorizzazione. Chi riceve rilegge con i **propri** permessi, e se quel record
non puo' vederlo non gli torna nulla — nessun controllo da scrivere a mano. In piu'
l'aggiornamento diventa idempotente: eventi doppi o fuori ordine non fanno danno.

**Un `Notify()` scritto a mano puo' invece portare un oggetto.** Terzo argomento:

```php
$user = new User('Anna', 'Bianchi', 'anna@esempio.it', $image);

EntityEvents::Notify('User', dati: $user);

//chi riceve: l'oggetto arriva come array, con le proprieta' pubbliche
$this->Subscribe('User', function (array $data): void
{
    $this->__Literal_UserName->Text = $data['FirstName'] . ' ' . $data['LastName'];
    $this->Alert->Success('E\' arrivato ' . $data['FirstName']);
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
del sito possono vedere — e' la ragione per cui i salvataggi non lo fanno; per il resto il
nome e basta, e ognuno rilegge il suo. `Examples/Events.php` manda un utente con nome,
cognome, email e foto, e l'altra scheda lo mostra.

Gli eventi si accumulano e partono **una volta sola a fine richiesta**: un salvataggio con
venti righe costa un giro sul pipe, non ventuno, e un'importazione che scrive mille righe
manda un evento solo per entita'. `Suspend()` / `Resume()` se nemmeno quello serve.

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

`Examples/Events.php` aperta in due schede fa vedere anche questo: si scrive in una, arriva in
tutte e due.

---

# 7. Navigazione e accesso

## Senza ricarico

I link interni non ricaricano: il documento viene chiesto al server e fuso nel DOM, con
`history` e scroll. Restano fuori i link esterni, quelli con `target`, i download, e i click
con ctrl/cmd/shift o tasto centrale — sbagliarlo romperebbe "apri in nuova scheda".

Gli `<script>` inline della pagina vengono **rieseguiti**: uno `<script>` inserito nel DOM da
codice non parte da solo, ed e' la sorpresa classica di chi aggiorna una pagina senza
ricaricarla. Quelli con `src` si **caricano la prima volta** che si vedono in questa scheda e
mai piu' dopo, cosi' lo script di una pagina raggiunta navigando parte e non gira due volte.
Solo in navigazione, mai nel postback.

Il codice di pagina che apre qualcosa (timer, editor, osservatori) lo chiude con
`DW.onLeave(fn)`, altrimenti dopo venti navigazioni se ne trascinano venti copie vive.

I postback **si accodano**: due richieste sovrapposte sullo stesso stato lo lascerebbero in
una via di mezzo fra i due esiti. Anche una navigazione aspetta il postback in volo, cosi' lo
stato che si tiene per il ritorno e' quello di dopo.

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

`Pages` e' un enum **generato dai markup**, in `Pages.php` alla radice dei sorgenti: ci
finiscono solo i file che chiamano `Page::Run()`, cioe' le pagine vere — non i codebehind, non
i designer, non gli UserControl e non le master, che chiesti da un indirizzo rispondono 404.
Il nome del caso e' il percorso con gli underscore al posto delle barre:
`Northwind/Categorie.php` → `Northwind_Categorie`. Ogni caso risponde a `Path()`.

Si rigenera da solo quando si apre una pagina che non c'e' dentro, quindi una pagina nuova
entra nell'elenco la prima volta che la si visita; a mano lo rifa' `PageMap::Refresh()`, da
una richiesta HTTP — da riga di comando non c'e' un `DOCUMENT_ROOT` da cui ricavare i
percorsi, e si ferma dicendolo. `Common` non viene attraversato: gli esempi non entrano
nell'enum.

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

## CSRF, e i controlli che non si vedono

Il canale di postback ha il CSRF, e non passa dalla sessione: il motore mette un valore
casuale in un cookie e lo scrive nella pagina, il runtime lo rimanda nell'intestazione, il
server confronta i due. Un sito estraneo puo' far partire la richiesta col cookie allegato -
e' il cuore del CSRF - ma non puo' leggere ne' il cookie ne' la nostra pagina, quindi non sa
cosa scrivere nell'intestazione. **Un postback senza cookie viene rifiutato**, non lasciato
passare: il cookie e' `SameSite=Lax` e in una POST cross-site non viene mandato, quindi
"cookie assente" e' proprio la situazione da fermare. `Common\Csrf`, che invece tiene il token
in sessione, resta al suo posto per il dispatcher del sito: qui non si tocca.

**Un controllo nascosto non scatena eventi.** Qui `Visible = false` rende il controllo lo
stesso, con l'attributo `hidden`, perche' il morph lo ritrovi quando torna visibile invece di
ricostruire il ramo: il rovescio e' che il suo id resta nella pagina, e dalla console si
premerebbe il bottone di una scheda chiusa. L'evento si ferma sul server se il controllo o un
suo antenato e' invisibile, quindi un pannello chiuso e' davvero inerte. `Enabled` vale allo
stesso modo: il `disabled` dell'HTML si toglie dalla console, e la condizione si ricontrolla
dove conta.

## Gli errori si leggono nel log, non solo sullo schermo

`Page::Run` e' l'unico punto da cui passa ogni richiesta, e quello che ne scappa e' un errore
del codice della pagina: l'eccezione di un handler, il `TypeError` di un designer che dichiara
un `Panel` dove il markup ha messo un `ContentPlaceHolder`, la memoria finita a meta' render.
Prima di rilanciarlo a PHP lo scrive in **`\Common\Log::Error`**, il log del sito che si
legge dal pannello:

    WebForms Errors.php [POST /public/php/.../Errors.php]: TypeError: Cannot assign
    Common\WebForms\Controls\ContentPlaceHolder to property ...::$corpo of type
    Common\WebForms\Controls\Panel in C:\...\Page.php:589
    #0 C:\...\Page.php(310): Common\WebForms\Page->BindControls()
    ...

Pagina, metodo, URL, tipo, messaggio, file con la riga e la pila. Una volta sola per
richiesta: gli errori che un `catch` non prende - `E_ERROR`, `E_PARSE`, la memoria - li
raccoglie una funzione di chiusura, che tace se il `catch` ha gia' scritto.

Non si inghiotte niente: l'eccezione riparte com'era, PHP la scrive anche nel suo log e la
mostra se `display_errors` e' acceso. Ma la risposta e' un **500**, messo a mano: il SAPI
del pipe lascia il 200 anche su un fatal, e con un 200 il runtime leggerebbe la pagina
d'errore come se fosse il frammento della pagina. Con il 500 il runtime la mette nel
riquadro degli errori (`#dw-errore`, lo stesso degli errori JavaScript, che non sono mai
silenziosi), com'e' arrivata e senza tag; i 403 e gli stati scaduti ricaricano pulito.

Dal cli - le prove - il pipe non c'e' e `Log::Error` lancia: il motore lo prende e ripiega
su `error_log`, perche' un errore nel loggare non deve coprire quello vero.

---

# 8. Regole da non disfare

**Gli id escono verbatim.** Nessun prefisso di contenitore, nessun `ctl00$`. E' l'errore che
rendeva inservibile `getElementById` in WebForms, ed e' anche cio' che permette al morph di
riconoscere i nodi invece di ricrearli. Gli UserControl sono l'eccezione dichiarata, con il
suffisso `__id`; la master no.

**Le righe del Repeater si chiavano sul dato** (`DataKeyField`), mai sulla posizione. Con id
posizionali, inserire una riga in testa riscrive l'intera griglia: sparisce il focus, muoiono
i listener, si vede il lampo. E' la `key` di React, per lo stesso motivo.

**L'handler lo nomina il markup, non il client.** Il browser manda l'id del controllo. Non
esiste una richiesta capace di far eseguire un metodo che il markup non abbia autorizzato.

**L'albero costruito dal markup dev'essere deterministico.** Se dipendesse dai dati, lo stato
non si riaggancerebbe al postback successivo. Quello che il codice aggiunge sopra torna dallo
stato, con un id stabile; chi si ricostruisce da se' — le righe di un Repeater, i numeri di
un paginatore — lo fa prima che si scatenino gli eventi.

**Il morph ha delle regole sue.** Le classi con prefisso `js-` sono del client e sopravvivono
al postback; un sottoalbero con `dw-preserve` non viene toccato (editor, datepicker); un nodo
con `data-dw-client` e' del client e non viene ne' confrontato ne' tolto; il campo con il
focus non viene mai calpestato.

**Gli eventi dei salvataggi non portano dati.** Chi riceve rilegge con i propri permessi. I
dati viaggiano solo se una pagina li mette a mano in `Notify()`, e allora sono per tutti.

**Niente sessione, niente querystring per lo stato.** Lo stato e' nel campo firmato e nel
pacchetto portatile; la querystring e' l'indirizzo della pagina, e un cambio di querystring
non e' un cambio di pagina.

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
- **Un calendario JavaScript.** Il `DatePicker` usa quello del browser; il giorno in cui un
  sito ne vuole uno suo, lo mette sopra con `dw-preserve`.

---

# 10. Le prove

    UnitTest/prove.cmd             TUTTE le prove: quelle PHP e quelle del JavaScript
    UnitTest/Esegui.php            le prove PHP: da URL risponde 200 se e' tutto verde e 500 se no
    UnitTest/prove.mjs             le prove del JavaScript, con node: il cassetto, la navigazione, gli script, DW.on, il 500,
                                   l'editor caricato una volta sola, piu' editor insieme, le regole uguali al server
    UnitTest/Prova.php             confronto, conto, e "deve sollevare"
    UnitTest/ProveControlli.php    cosa rendono i controlli, e cosa diventano col POST
    UnitTest/ProveDatePicker.php   date vere, i due Mode, cosa entra dal browser, l'evento, l'enum nello stato
    UnitTest/ProveRichTextBox.php  la pulizia coi trucchi veri, ogni funzione spenta anche sul server, piu' editor,
                                   un editor per riga di Repeater con Salva, Aggiungi e Salva tutto
    UnitTest/ProveOgniControllo.php le tre domande fatte a TUTTI i controlli, per riflessione
    UnitTest/ProveDinamici.php     i controlli attaccati dal codice, e come tornano indietro
    UnitTest/ProvePaginaVuota.php  markup vuoto, tutto dal codice: un CRUD intero a postback
    UnitTest/ProveVariabili.php    le variabili di pagina restano tutte; #[Transient] e #[Portable]
    UnitTest/ProveViewStateMode.php Inherit/Enabled/Disabled, e cosa resta sotto uno spento
    UnitTest/ProveQuerystring.php  la querystring cambia, lo stato resta, la pagina rilega
    UnitTest/ProveMemoria.php      lo stato cala con i dati: ClearItems e rimpiazzi non lasciano niente
    UnitTest/ProveModalPopup.php   i marcatori che il server scrive per il runtime, Show/Hide, X e Y, i riferimenti in un UserControl
    UnitTest/ProveEventiConDati.php Notify con un oggetto: il messaggio firmato che parte e il postback che torna
    UnitTest/ProveErrori.php       l'eccezione di una pagina nel log del sito, una volta, e poi fuori com'era
    UnitTest/ProveMarkup.php       il compilatore, i segnaposto {{Campo}}, i <dw:Content>, gli id __Tipo_Nome
    UnitTest/ProveRepeater.php     OnItemDataBound: quando scatta, e cosa sopravvive al postback
    UnitTest/ProveSicurezza.php    controlli nascosti, redirect fuori sito, CSRF
    UnitTest/ProveStato.php        il pacchetto firmato: soprattutto cosa RIFIUTA
    Examples/Site.php              la master degli esempi: menu a sinistra, titolo, e l'iscrizione a «Saluti»
    Examples/Index.php             la panoramica, con le schede di tutte le pagine
    Examples/<Controllo>.php       una pagina per controllo: la prova, le proprieta', il sorgente
    Examples/State.php             le variabili restano, #[Portable], il modo WinForms
    Examples/DynamicControls.php   controlli creati in OnLoad e negli handler, righe di tabella a mano
    Examples/Events.php            Notify e Subscribe, l'oggetto User in viaggio, Broadcast con DW.on
    Examples/Errors.php            un'eccezione nell'handler: nel log e nel riquadro rosso
    Examples/PropertyTable.php     UserControl: le proprieta' di un controllo lette dalla classe, con i docblock
    Examples/SourceView.php        UserControl: markup e codebehind della pagina che lo ospita, dal disco

Sono 600 prove PHP e 61 JavaScript. Si lanciano nei due modi, e l'esito e' un numero:
**uscita 1** da riga di comando, **500** sull'HTTP, cosi' le puo' guardare uno script senza
leggerle a occhio.

    UnitTest\prove.cmd
    "C:\Program Files\PHP\php.exe" Esegui.php
    http://localhost:8081/public/php/Common/WebForms/UnitTest/Esegui.php

Da riga di comando **non si passa da `Start.php`**: quello vuole un sito attorno e il pipe
verso Kestrel. Si registra il minimo per trovare le classi e le prove girano su PHP e basta -
che e' esattamente quello che devono provare.

**Il JavaScript si prova ritagliandolo.** `runtime.js` e' scritto per il DOM, ma i pezzi con una
logica propria — il cassetto delle pagine tenute, la navigazione e il tasto indietro, gli
script da caricare dopo una navigazione, la consegna dei messaggi, il testo di un 500 — si tagliano fra due marcatori e girano in node con
un DOM finto grande quanto basta. Non e' il morph, che si guarda nel browser: e' quello che si
puo' sbagliare senza che il browser lo dica — la chiave sbagliata sul tasto indietro, il
postback in volo durante la navigazione. `prove.mjs` va lanciato con node; `Esegui.php` non
puo' farlo, perche' `exec` e compagni sono spenti in `php.ini` — com'e' giusto su un server
web — e le prove girano con lo stesso `php.ini` del sito. `prove.cmd` lancia tutti e due.

**Si prova quello che e' PHP puro**: i controlli sono oggetti, si valorizzano e si guarda
l'HTML, oppure si passa loro l'array del POST e si guarda cosa diventano. Niente HTTP,
niente database, niente pipe. Una prova che ha bisogno di un sito acceso non e' una prova
unitaria, e' un collaudo.

## Gli esempi da toccare

    http://<sito>/public/php/Common/WebForms/Examples/Index.php

Una pagina per controllo, sotto una master con il menu a sinistra: il modello e' il sito
dell'AjaxControlToolkit. Le prove unitarie dicono che lo stato torna indietro; queste lo
fanno vedere cliccando — che e' l'unico modo di provare anche il pezzo che gira nel browser:
il morph, il cassetto delle pagine tenute, il hub.

Ogni pagina ha tre parti. **La prova**: si clicca, si scrive, si guarda cosa resta. **Le
proprieta'**: `PropertyTable` e' un UserControl che legge la classe del controllo per
riflessione — nome, tipo, valore predefinito e il **docblock** scritto sopra la proprieta' —
quindi non c'e' una tabella da tenere allineata: chi aggiunge una proprieta' e la commenta la
vede comparire, e chi non la commenta vede la casella vuota. **Il sorgente**: `SourceView`
legge dal disco il markup e il codebehind della pagina che lo ospita, quindi quello che si
legge e' esattamente quello che ha reso la pagina.

Le quattro pagine **Motore** sono le prove del motore piu' che di un controllo:

- `State.php` — le variabili che restano, `#[Portable]` che attraversa le pagine (la pagina
  degli eventi dichiara la stessa chiave e lo legge), e la casella del modo WinForms;
- `DynamicControls.php` — controlli costruiti in `OnLoad` e negli handler, e in fondo il caso
  piu' severo: in `OnLoad` **non succede niente**, ogni `<tr>` nasce da un click — un albero
  dinamico **annidato**, un `LinkButton` «elimina» creato dal codice che funziona ancora al
  postback dopo, la **rimozione** che resta rimossa, e il **tag** giusto. L'orario in ogni
  riga dice se qualcuno l'ha ricostruita;
- `Events.php` — aperta in due schede: «Saluta tutti» arriva all'altra, e a tutte le pagine
  tramite la cornice; «Manda un utente» porta un oggetto con la foto; `Broadcast()` arriva a
  `DW.on()` senza postback;
- `Errors.php` — un handler che lancia: la riga nel log del sito, il 500 nel riquadro rosso,
  e il postback dopo che continua da dov'era.

**Stanno in `Common` di proposito.** Niente Model, niente database, nessun foglio di stile
del sito: sono una prova **del motore**, quindi viaggiano con il motore e si aprono uguali su
un sito appena creato che non ha ancora niente dentro. La master e' `Site.php` li' accanto:
il menu che lega le pagine e' la prova a mano del modo WinForms (§2), e una master dentro
`Common` e' anche la prova che una master puo' stare fuori dal sito. Per lo stesso motivo non
entrano nell'enum `Pages`: `PageMap` non attraversa `Common`. I nomi dei file e degli id sono
in inglese, come vuole la regola della lingua; i testi in italiano.

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
   giusto — anche gli enum, un caso alla volta — si salva, si ricarica su un controllo nuovo
   e si confronta.
3. **Il testo cattivo esce escapato?** Ogni proprieta' di testo, piu' un attributo e uno
   stile aggiunti dal codice, si riempiono di `<script>"x"&'y'</script>`, e nell'HTML non
   deve comparire com'era. E' la regola che tiene su tutto il resto, e vale per i controlli
   di domani come per quelli di oggi.

**Non si prova** il morph, che va guardato nel browser. Ne' il layer dati, che passa dal pipe.

Ogni prova corrisponde a un difetto vero, gia' capitato: il doppio escape negli attributi,
la casella non spuntata che non si distingueva da "controllo assente", il `select multiple`
che mandava un valore solo, il ViewState manomesso, il tasto indietro che salvava lo stato
sotto la chiave sbagliata, l'errore del server che spariva in un ricarico. Il nome di una
prova dice **cosa deve succedere**, non cosa fa il codice: quando diventa rossa, la riga che
si legge spiega gia' cosa si e' rotto.

---

# 11. Il plugin per PhpStorm

In `Z:\Rete\_Programmi\DOWEB\PHPSTORM` — jar, sorgenti, `compila.cmd`, `installa.cmd`
(PhpStorm chiuso).

**Navigazione.** Ctrl+click sul nome di un tag apre la classe del controllo - e per un
UserControl chiamato per nome, `<dw:PageNavigator>`, il suo codebehind. Sul valore di un
evento apre il metodo nel `.code.php`; sul nome di un evento di UserControl, il punto in cui
il controllo lo solleva; su `src` il markup, il foglio di stile o lo script. Su
`placeholder="corpo"`, il `<dw:ContentPlaceHolder>` della master.

**Completamento.** I nomi degli attributi dentro un tag `dw:`: scrivendo `Con` dentro un
`<dw:LinkButton>` esce `Confirm`. L'elenco non e' una tabella scritta a mano, viene da
`ViewStateProperties()` della classe: un controllo nuovo o una proprieta' nuova entrano nel
completamento senza toccare il plugin. Propone anche `ViewStateMode`, che non sta in
`ViewStateProperties()` per forza — e' lui a decidere se lo stato esiste.

**Generazione.** `X.designer.php` si riscrive ad ogni salvataggio di `X.php`, quindi il
controllo appena aggiunto al markup si completa subito nel codebehind. Un UserControl
chiamato per nome — `<dw:Menu>` — si dichiara con la SUA classe, `\UserControls\Menu`, con
la stessa regola di `ControlBuilder::TagSrc()`; la master con la sua, `$Master`. Il motore
comunque non si fida della data: se un designer dichiara un tipo del motore che non esiste,
lo rigenera lui — un plugin vecchio non puo' lasciare una pagina rotta. **New &rarr; WebForms**
chiede il nome e crea i tre file.

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
