<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
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
                ->required(fn (?User $record): bool => ! $record?->is_admin),
            Select::make('regions')->relationship('regions', 'name')->multiple()->searchable()->preload()
                ->helperText('Leave empty to inherit company regions. Selected regions narrow access to those also assigned to the company.'),
            TextInput::make('password')->password()->revealable()->minLength(12)->maxLength(255)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->afterStateHydrated(fn (TextInput $component) => $component->state(''))
                ->helperText('Leave blank when editing to keep the existing password.'),
            Toggle::make('is_active')->default(true)
                ->disabled(fn (?User $record): bool => $record?->id === auth()->id()),
        ]);
    }

    public static function saveUser(User $record, array $data): User
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        $data = Arr::only($data, ['name', 'email', 'password', 'company_id', 'is_active']);
        if ($record->id === auth()->id() && isset($data['is_active']) && ! $data['is_active']) {
            throw ValidationException::withMessages(['data.is_active' => 'You cannot disable your own account.']);
        }
        $record->forceFill($data)->save();

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
        ])->recordActions([
            EditAction::make()->using(fn (User $record, array $data): User => static::saveUser($record, $data)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
