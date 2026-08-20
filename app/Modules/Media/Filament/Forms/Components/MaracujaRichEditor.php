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
                    ->label(__('admin.media.insert_image'))
                    ->action()
                    ->icon(Heroicon::OutlinedPhoto),
                RichEditorTool::make('insertDocument')
                    ->label(__('admin.media.insert_document'))
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
            ->label(__('admin.media.insert_image'))
            ->modalHeading(__('admin.media.choose_image'))
            ->schema([
                MediaIdSelect::make('media_id')
                    ->label(__('admin.media.image'))
                    ->imagesOnly()
                    ->required(),
                TextInput::make('alt_text')
                    ->label(__('admin.media.alt_text'))
                    ->helperText(__('admin.media.alt_text_help')),
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
            ->label(__('admin.media.insert_document'))
            ->modalHeading(__('admin.media.add_document'))
            ->schema([
                MediaIdSelect::make('media_id')
                    ->label(__('admin.media.document'))
                    ->documentsOnly()
                    ->live()
                    ->afterStateUpdated(function (Set $set, int|string|null $state): void {
                        $set('label', MediaAsset::query()->find($state)?->display_name);
                    })
                    ->required(),
                TextInput::make('label')
                    ->label(__('admin.media.link_text'))
                    ->placeholder(__('admin.media.download_document'))
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
