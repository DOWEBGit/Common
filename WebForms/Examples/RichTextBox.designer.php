<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

/**
 * GENERATO AUTOMATICAMENTE da RichTextBox.php - non modificare a mano.
 *
 * Handler richiamati dal markup:
 * @see \Common\WebForms\Examples\RichTextBoxExample::ShowClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::SampleClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::DirtyClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::ApplyClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::AllClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::NoteBound()
 * @see \Common\WebForms\Examples\RichTextBoxExample::SaveNoteClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::RemoveNoteClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::AddNoteClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::SaveAllClick()
 * @see \Common\WebForms\Examples\RichTextBoxExample::LiveChanged()
 */
trait RichTextBoxDesigner
{
    public \Common\WebForms\Examples\Site $Master;
    public \Common\WebForms\Controls\RichTextBox $__RichTextBox_Full;
    public \Common\WebForms\Controls\Button $__Button_Show;
    public \Common\WebForms\Controls\Button $__Button_Sample;
    public \Common\WebForms\Controls\Button $__Button_Dirty;
    public \Common\WebForms\Controls\Literal $__Literal_Html;
    public \Common\WebForms\Controls\ListBox $__ListBox_Features;
    public \Common\WebForms\Controls\Button $__Button_Apply;
    public \Common\WebForms\Controls\Button $__Button_All;
    public \Common\WebForms\Controls\RichTextBox $__RichTextBox_Partial;
    public \Common\WebForms\Controls\Repeater $__Repeater_Notes;
    public \Common\WebForms\Controls\Button $__Button_Add;
    public \Common\WebForms\Controls\Button $__Button_SaveAll;
    public \Common\WebForms\Controls\Literal $__Literal_Database;
    public \Common\WebForms\Controls\RichTextBox $__RichTextBox_Live;
    public \Common\WebForms\Controls\Literal $__Literal_Live;
    public \Common\WebForms\Examples\PropertyTable $__PropertyTable;
    public \Common\WebForms\Examples\SourceView $__SourceView;
}
