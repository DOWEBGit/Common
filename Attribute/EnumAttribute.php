<?php
declare(strict_types=1);

namespace Common\Attribute;

use Attribute;

//la uso per mettere il valore originale in stringa sopra all'enum

#[Attribute]
class EnumAttribute
{
    public string $pagina = "";
    public string $identificativo = "";
    public string $tipoInput = "";
    public bool $decode = false;

    public function __construct(string $pagina, string $identificativo, string $tipoInput, bool $decode)
    {
        $this->pagina = $pagina;
        $this->identificativo = $identificativo;
        $this->tipoInput = $tipoInput;
        $this->decode = $decode;
    }
}
