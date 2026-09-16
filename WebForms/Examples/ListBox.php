<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\ListBoxExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Tutto quello della <a href="DropDownList.php">DropDownList</a>, piu' la scelta multipla:
        <code>SelectionMode="Multiple"</code> e <code>SelectedValues</code>, un array. Nella
        selezione multipla il campo si chiama <code>id[]</code> — senza parentesi PHP terrebbe
        solo l'ultimo — e il runtime manda <b>tutte</b> le opzioni scelte, perche'
        <code>select.value</code> su un elenco multiplo ne restituisce una sola.
    </p>

    <div class="ex-demo">
        <p>
            <dw:ListBox id="__ListBox_Days" SelectionMode="Multiple" Rows="7">
                <dw:ListItem Value="1" Text="Lunedi'" Selected="true" />
                <dw:ListItem Value="2" Text="Martedi'" />
                <dw:ListItem Value="3" Text="Mercoledi'" Selected="true" />
                <dw:ListItem Value="4" Text="Giovedi'" />
                <dw:ListItem Value="5" Text="Venerdi'" />
                <dw:ListItem Value="6" Text="Sabato" />
                <dw:ListItem Value="7" Text="Domenica" />
            </dw:ListBox>
            <dw:Button id="__Button_Read" Text="Leggi le scelte" OnClick="ReadClick" />
            <dw:Button id="__Button_Weekdays" Text="Seleziona i feriali dal server" OnClick="WeekdaysClick" />
        </p>

        <p class="ex-note">Tieni premuto Ctrl per sceglierne piu' di uno. <dw:Literal id="__Literal_Read" /></p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="ListBox" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
