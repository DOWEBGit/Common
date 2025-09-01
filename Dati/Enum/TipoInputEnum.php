<?php
declare(strict_types=1);

namespace Common\Dati\Enum;

enum TipoInputEnum : string
{
    case CheckBox = "CheckBox";
    case DropDownList = "DropDownList";
    case ListBox = "ListBox";
    case TextBox = "TextBox";
    case TextArea = "TextArea";
    case RichTextBox = "RichTextBox";
    case RichTextBoxMini = "RichTextBoxMini";
    case FileInput = "FileInput";
}