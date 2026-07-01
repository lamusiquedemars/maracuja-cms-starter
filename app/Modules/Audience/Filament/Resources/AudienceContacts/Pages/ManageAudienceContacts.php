<?php

namespace App\Modules\Audience\Filament\Resources\AudienceContacts\Pages;

use App\Modules\Audience\Actions\ImportAudienceContactsFromCsv;
use App\Modules\Audience\Actions\ImportContactsFromInquiries;
use App\Modules\Audience\Filament\Resources\AudienceContacts\AudienceContactResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageAudienceContacts extends ManageRecords
{
    protected static string $resource = AudienceContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importCsv')
                ->label('Importer CSV')
                ->form([
                    FileUpload::make('csv')
                        ->label('Fichier CSV')
                        ->disk('local')
                        ->directory('imports/audience')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                    TextInput::make('default_segment')
                        ->label('Segment à ajouter à tous les contacts')
                        ->helperText('Optionnel. Exemple: Tous les clients. La colonne segments du CSV reste prise en compte.'),
                ])
                ->action(function (array $data): void {
                    $result = ImportAudienceContactsFromCsv::run(
                        path: (string) $data['csv'],
                        defaultSegment: $data['default_segment'] ?? null,
                    );

                    $body = "Créés: {$result['created']} | Mis à jour: {$result['updated']} | Ignorés: {$result['skipped']} | Segments créés: {$result['segments']}";

                    if ($result['errors'] !== []) {
                        $body .= "\n" . implode("\n", $result['errors']);
                    }

                    Notification::make()
                        ->title('Import CSV terminé')
                        ->body($body)
                        ->success()
                        ->send();
                }),
            Action::make('importFromInquiries')
                ->label('Importer depuis Demandes')
                ->action(function (): void {
                    $result = ImportContactsFromInquiries::run();

                    Notification::make()
                        ->title('Import terminé')
                        ->body("Créés: {$result['created']} | Déjà présents: {$result['existing']}")
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Créer un contact'),
        ];
    }
}
