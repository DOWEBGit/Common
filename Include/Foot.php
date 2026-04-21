<?php
declare(strict_types=1);

\Common\State::WriteToHtml();

global $asyncArray;

if ($asyncArray && count($asyncArray) > 0)
{
    ?>
    <script>
        <?php

        foreach ($asyncArray as $id)
        {
        ?>

        ReloadViewInternal(<?=$id?>);

        <?php


        }

        ?>
    </script>
    <?php
}

if (\Common\Client::$enabled)
{
    // Determina se siamo in ambiente localhost
    $isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])
            || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');

    if ($isLocalhost)
    {
        ?>

        <!-- Carica la libreria SignalR Core dal CDN -->
        <script src="https://cdn.jsdelivr.net/npm/@microsoft/signalr@latest/dist/browser/signalr.min.js"></script>

        <!-- Carica il client SignalR multi-tenant -->
        <script src="https://static.doweb.site/liveserver/signalr-client.js"></script>

        <?php
    }
    else
    {
        ?>

        <script src="https://static.doweb.site/liveserver/signalRJs.js"></script>

        <script type="text/javascript">

            var connection = hubConnection("/client", {useDefaultPath: false});

            connection.connectionSlow(function ()
            {
                console.log('DOWEB Client: We are currently experiencing difficulties with the connection.')
            });

            //connection.start({ transport: ['webSockets', 'longPolling'] });

            connection.url = "<?= \Common\SiteVars::Value(\Common\VarsEnum::webpath) ?>/client";

            var proxy = connection.createHubProxy('Client');

            proxy.on('Push', function (name, value)
            {
                if (typeof Push === 'function')
                {
                    Push(name, value);
                }
            });

            //connection.logging = true;
            connection.start({pingInterval: 10000})
                .done(function ()
                {
                    console.log("DOWEB Client connected");
                })
                .fail(function (e)
                {
                    console.log("DOWEB Client failed: " + e);
                });

        </script>

        <?php
    }
}