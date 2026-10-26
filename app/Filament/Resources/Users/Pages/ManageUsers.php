<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\ImportUsers;
use App\Models\Company;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadImportTemplate')->label('Excel template')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $writer = new Writer;
                    $writer->openToFile('php://output');
                    $writer->addRow(Row::fromValues(['name', 'email']));
                    $writer->close();
                }, 'users-import-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])),
            Action::make('importUsers')->label('Import Excel')->modalHeading('Import users from Excel')
                ->modalDescription('Upload an .xlsx file with name and email columns on the first worksheet (up to 100 users). All imported users belong to the selected company. Existing emails are skipped. New users receive an invitation with a generated temporary password.')
                ->modalSubmitActionLabel('Import & send invitations')
                ->schema([
                    Select::make('company_id')->label('Company')->options(fn (): array => Company::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable()->required(),
                    FileUpload::make('file')->label('Excel workbook')->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(5120)->storeFiles(false)->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $result = app(ImportUsers::class)->import($data['file']->getRealPath(), (int) $data['company_id']);
                    } catch (ValidationException $exception) {
                        $errors = [];
                        foreach ($exception->errors() as $field => $messages) {
                            $errors[$this->getMountedActionSchema()->getStatePath().'.'.$field] = $messages;
                        }
                        throw ValidationException::withMessages($errors);
                    }
                    $data['file']->delete();
                    Notification::make()->title('User import complete')
                        ->body("{$result['created']} users created and invitations queued. {$result['skipped']} existing or duplicate emails skipped.")
                        ->success()->send();
                }),
            CreateAction::make()->label('Create & send invitation')->successNotificationTitle('User created and invitation queued')->using(fn (array $data): User => UserResource::saveUser(new User, $data)),
        ];
    }
}
