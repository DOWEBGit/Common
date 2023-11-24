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

        ReloadView(<?=$id?>);

        <?php


        }

        ?>
    </script>
    <?php
}