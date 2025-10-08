<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tenant Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('domain')
                            ->maxLength(255)
                            ->helperText('Full domain (e.g., example.ainstein.com)'),
                        Forms\Components\TextInput::make('subdomain')
                            ->maxLength(255)
                            ->helperText('Subdomain only (e.g., example)'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'cancelled' => 'Cancelled',
                                'trial' => 'Trial',
                            ])
                            ->required()
                            ->default('active'),
                    ])->columns(2),

                Section::make('Plan & Limits')
                    ->schema([
                        Forms\Components\Select::make('plan_type')
                            ->options([
                                'starter' => 'Starter',
                                'professional' => 'Professional',
                                'enterprise' => 'Enterprise',
                            ])
                            ->required()
                            ->default('starter'),
                        Forms\Components\TextInput::make('tokens_monthly_limit')
                            ->numeric()
                            ->required()
                            ->default(10000)
                            ->helperText('Monthly token limit'),
                        Forms\Components\TextInput::make('tokens_used_current')
                            ->numeric()
                            ->default(0)
                            ->helperText('Tokens used this month')
                            ->disabled(),
                        Forms\Components\TextInput::make('pages_limit')
                            ->numeric()
                            ->default(10)
                            ->helperText('Maximum number of pages'),
                        Forms\Components\TextInput::make('api_keys_limit')
                            ->numeric()
                            ->default(3)
                            ->helperText('Maximum API keys'),
                    ])->columns(2),

                Section::make('Billing')
                    ->schema([
                        Forms\Components\DatePicker::make('trial_ends_at')
                            ->label('Trial Ends'),
                        Forms\Components\DatePicker::make('subscription_ends_at')
                            ->label('Subscription Ends'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('subdomain')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-link'),
                Tables\Columns\TextColumn::make('plan_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enterprise' => 'success',
                        'professional' => 'warning',
                        'starter' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tokens_used_current')
                    ->label('Token Usage')
                    ->formatStateUsing(fn (Tenant $record): string => number_format($record->tokens_used_current) . ' / ' . number_format($record->tokens_monthly_limit))
                    ->badge(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_type')
                    ->options([
                        'starter' => 'Starter',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'trial' => 'Trial',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reset_tokens')
                    ->label('Reset Tokens')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => $record->update(['tokens_used_current' => 0]))
                    ->successNotificationTitle('Tokens reset successfully'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['users']);
    }
}
