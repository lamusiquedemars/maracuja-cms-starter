<?php

namespace App\Modules\Media\Filament\Forms\Components;

use App\Modules\Media\Models\MediaAsset;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

class MaracujaRichEditor extends RichEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->fileAttachments(false)
            ->tools([
                RichEditorTool::make('insertImage')
                    ->label('Insérer une image')
                    ->action()
                    ->icon(Heroicon::OutlinedPhoto),
                RichEditorTool::make('insertDocument')
                    ->label('Insérer un document')
                    ->action()
                    ->icon(Heroicon::OutlinedDocumentArrowDown),
            ])
            ->registerActions([
                $this->insertImageAction(),
                $this->insertDocumentAction(),
            ]);
    }

    public function getDefaultToolbarButtons(): array
    {
        $buttons = parent::getDefaultToolbarButtons();
        $buttons[] = ['insertImage', 'insertDocument'];

        return $buttons;
    }

    private function insertImageAction(): Action
    {
        return Action::make('insertImage')
            ->label('Insérer une image')
            ->modalHeading('Choisir une image de la médiathèque')
            ->schema([
                MediaIdSelect::make('media_id')
                    ->label('Image')
                    ->imagesOnly()
                    ->required(),
                TextInput::make('alt_text')
                    ->label('Texte alternatif')
                    ->helperText('Laisser vide pour reprendre le texte alternatif du média.'),
            ])
            ->action(function (array $arguments, array $data, MaracujaRichEditor $component): void {
                $media = MediaAsset::query()->images()->findOrFail($data['media_id']);

                $component->runCommands([
                    EditorCommand::make('insertContent', arguments: [[
                        'type' => 'image',
                        'attrs' => [
                            'alt' => filled($data['alt_text'] ?? null) ? $data['alt_text'] : $media->alt_text,
                            'id' => 'media-'.$media->id,
                            'src' => $media->publicPath(),
                        ],
                    ]]),
                ], editorSelection: $arguments['editorSelection'] ?? null);
            });
    }

    private function insertDocumentAction(): Action
    {
        return Action::make('insertDocument')
            ->label('Insérer un document')
            ->modalHeading('Ajouter un document à télécharger')
            ->schema([
                MediaIdSelect::make('media_id')
                    ->label('Document')
                    ->documentsOnly()
                    ->live()
                    ->afterStateUpdated(function (Set $set, int|string|null $state): void {
                        $set('label', MediaAsset::query()->find($state)?->display_name);
                    })
                    ->required(),
                TextInput::make('label')
                    ->label('Texte du lien')
                    ->placeholder('Télécharger le document')
                    ->required(),
            ])
            ->action(function (array $arguments, array $data, MaracujaRichEditor $component): void {
                $media = MediaAsset::query()->documents()->findOrFail($data['media_id']);

                $component->runCommands([
                    EditorCommand::make('insertContent', arguments: [sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        e($media->publicPath()),
                        e($data['label']),
                    )]),
                ], editorSelection: $arguments['editorSelection'] ?? null);
            });
    }
}
