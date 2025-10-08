<?php

namespace App\Filament\Admin\Resources\Prompts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Prompt Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('alias')
                    ->label('Alias')
                    ->searchable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->colors([
                        'primary' => 'content_generation',
                        'success' => 'seo_optimization',
                        'warning' => 'social_media',
                        'danger' => 'email_marketing',
                        'info' => 'analysis',
                    ]),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap(),
                IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->trueIcon('heroicon-o-cog-6-tooth')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->placeholder('All Tenants')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'content_generation' => 'Content Generation',
                        'seo_optimization' => 'SEO Optimization',
                        'social_media' => 'Social Media',
                        'email_marketing' => 'Email Marketing',
                        'product_description' => 'Product Description',
                        'blog_writing' => 'Blog Writing',
                        'ad_copy' => 'Ad Copy',
                        'analysis' => 'Content Analysis',
                        'translation' => 'Translation',
                        'other' => 'Other'
                    ]),
                TernaryFilter::make('is_system')
                    ->label('System Prompts')
                    ->placeholder('All prompts')
                    ->trueLabel('System prompts only')
                    ->falseLabel('Tenant prompts only'),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All prompts')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
