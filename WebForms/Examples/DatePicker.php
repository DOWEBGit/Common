<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\DatePickerExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Il calendario e' quello del browser, nativo: <code>type="date"</code> o
        <code>type="datetime-local"</code> secondo <code>Mode</code>. Niente librerie, niente
        formato locale da indovinare. Il server parla solo in date: <code>Value</code>,
        <code>Min</code> e <code>Max</code> sono <code>DateTimeImmutable</code>, e quello che arriva
        dal browser si rilegge come data — la spazzatura diventa vuoto, il 30 febbraio anche.
    </p>

    <div class="ex-demo">
        <p>
            <dw:DatePicker id="__DatePicker_Day" Mode="Date" AutoPostBack="true" OnDateChanged="DateChanged" />
            <dw:DatePicker id="__DatePicker_When" Mode="DateTime" AutoPostBack="true" OnDateChanged="DateChanged" />
            <dw:Button id="__Button_SwitchMode" Text="Cambia il modo del primo" OnClick="SwitchModeClick" />
            <dw:Button id="__Button_Today" Text="Oggi, dal server" OnClick="TodayClick" />
        </p>

        <p class="ex-note"><dw:Literal id="__Literal_Dates" /></p>

        <p class="ex-note">
            Il primo ha <code>Min</code> a inizio anno: il calendario non lascia scegliere prima. Il
            bottone cambia il <code>Mode</code> del primo al volo e il valore resta, adeguato al tipo
            dell'input — senza, il browser rifiuterebbe <code>2026-09-14T10:30</code> in un
            <code>type="date"</code> e mostrerebbe il campo vuoto senza dire niente.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="DatePicker" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
