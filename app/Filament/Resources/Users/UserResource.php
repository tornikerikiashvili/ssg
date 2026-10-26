<?php

namespace App\Filament\Resources\Users;

use App\AccountOnboarding;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Access management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255)
                ->mutateStateForValidationUsing(fn (string $state): string => Str::lower(trim($state)))
                ->dehydrateStateUsing(fn (string $state): string => Str::lower(trim($state)))
                ->unique(ignoreRecord: true),
            Select::make('company_id')->relationship('company', 'name')->searchable()->preload()
                ->required(fn (?User $record): bool => ! $record?->is_admin)
                ->helperText('Users inherit the regions configured for their company.'),
            Toggle::make('is_active')->default(true)
                ->disabled(fn (?User $record): bool => $record?->id === auth()->id()),
        ]);
    }

    public static function saveUser(User $record, array $data): User
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $data = Arr::only($data, ['name', 'email', 'company_id', 'is_active']);
        if ($record->id === auth()->id() && isset($data['is_active']) && ! $data['is_active']) {
            throw ValidationException::withMessages(['data.is_active' => 'You cannot disable your own account.']);
        }
        DB::transaction(function () use ($record, $data): void {
            $isNew = ! $record->exists;
            if ($isNew) {
                $record->password = Str::random(64);
            }
            $record->forceFill($data)->save();
            if ($isNew) {
                app(AccountOnboarding::class)->invite($record);
            }
        });

        return $record;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('company.name')->placeholder('SmartSoft team'),
            IconColumn::make('is_admin')->label('Administrator')->boolean(),
            IconColumn::make('is_active')->boolean(),
            IconColumn::make('requires_password_setup')->label('Invitation pending')->boolean(),
        ])->recordActions([
            Action::make('resendInvitation')->label('Resend invitation')->requiresConfirmation()
                ->modalDescription('A new temporary password will be emailed to this user. The previous invitation will stop working.')
                ->visible(fn (User $record): bool => $record->requires_password_setup && $record->canAccessClientArea())
                ->action(function (User $record): void {
                    abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
                    abort_unless($record->requires_password_setup && $record->canAccessClientArea(), 403);
                    app(AccountOnboarding::class)->invite($record);
                    Notification::make()->title('Invitation queued for delivery')->success()->send();
                }),
            EditAction::make()->using(fn (User $record, array $data): User => static::saveUser($record, $data)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
