<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use App\Models\Tenant;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;

class Subscriptions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected string $view = 'filament.admin.pages.subscriptions';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Tenant::query()->with(['users']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('plan_type')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enterprise' => 'success',
                        'professional' => 'warning',
                        'starter' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('tokens_usage')
                    ->label('Token Usage')
                    ->state(function (Tenant $record): string {
                        $percent = $record->tokens_monthly_limit > 0
                            ? round(($record->tokens_used_current / $record->tokens_monthly_limit) * 100, 1)
                            : 0;
                        return number_format($record->tokens_used_current) . ' / ' . number_format($record->tokens_monthly_limit) . " ({$percent}%)";
                    })
                    ->badge()
                    ->color(function (Tenant $record): string {
                        $percent = $record->tokens_monthly_limit > 0
                            ? ($record->tokens_used_current / $record->tokens_monthly_limit) * 100
                            : 0;

                        if ($percent >= 90) return 'danger';
                        if ($percent >= 75) return 'warning';
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('subscription_ends_at')
                    ->label('Subscription Ends')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Member Since')
                    ->date()
                    ->sortable(),
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
                Tables\Actions\Action::make('change_plan')
                    ->label('Change Plan')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('info')
                    ->form([
                        Tables\Components\Select::make('plan_type')
                            ->label('New Plan')
                            ->options([
                                'starter' => 'Starter (10K tokens/month)',
                                'professional' => 'Professional (50K tokens/month)',
                                'enterprise' => 'Enterprise (200K tokens/month)',
                            ])
                            ->required(),
                    ])
                    ->action(function (Tenant $record, array $data): void {
                        $limits = [
                            'starter' => 10000,
                            'professional' => 50000,
                            'enterprise' => 200000,
                        ];

                        $record->update([
                            'plan_type' => $data['plan_type'],
                            'tokens_monthly_limit' => $limits[$data['plan_type']],
                        ]);
                    })
                    ->successNotificationTitle('Plan updated successfully'),

                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record) => $record->status !== 'suspended')
                    ->action(fn (Tenant $record) => $record->update(['status' => 'suspended']))
                    ->successNotificationTitle('Tenant suspended'),

                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Tenant $record) => $record->status === 'suspended')
                    ->action(fn (Tenant $record) => $record->update(['status' => 'active']))
                    ->successNotificationTitle('Tenant activated'),

                Tables\Actions\Action::make('reset_tokens')
                    ->label('Reset Tokens')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => $record->update(['tokens_used_current' => 0]))
                    ->successNotificationTitle('Tokens reset successfully'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('suspend')
                    ->label('Suspend Selected')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => 'suspended'])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
