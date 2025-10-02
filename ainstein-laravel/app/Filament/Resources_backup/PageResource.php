<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;


    protected static ?string $recordTitleAttribute = 'url_path';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Page Information')
                    ->schema([
                        Forms\Components\TextInput::make('url_path')
                            ->label('URL Path')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('/example-page'),
                        Forms\Components\TextInput::make('keyword')
                            ->maxLength(255)
                            ->placeholder('target keyword'),
                        Forms\Components\TextInput::make('category')
                            ->maxLength(100)
                            ->placeholder('category'),
                        Forms\Components\Select::make('language')
                            ->options([
                                'en' => 'English',
                                'es' => 'Spanish',
                                'fr' => 'French',
                                'de' => 'German',
                                'it' => 'Italian',
                                'pt' => 'Portuguese',
                            ])
                            ->default('en')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('CMS Integration')
                    ->schema([
                        Forms\Components\Select::make('cms_type')
                            ->label('CMS Type')
                            ->options([
                                'wordpress' => 'WordPress',
                                'joomla' => 'Joomla',
                                'drupal' => 'Drupal',
                                'magento' => 'Magento',
                                'shopify' => 'Shopify',
                                'other' => 'Other',
                            ])
                            ->placeholder('Select CMS type'),
                        Forms\Components\TextInput::make('cms_page_id')
                            ->label('CMS Page ID')
                            ->maxLength(255)
                            ->placeholder('Page ID in CMS'),
                    ])->columns(2)->collapsed(),

                Forms\Components\Section::make('Status & Priority')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'archived' => 'Archived',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100),
                    ])->columns(2),

                Forms\Components\Section::make('Tenant Assignment')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Page Metadata'),
                        Forms\Components\DateTimePicker::make('last_synced')
                            ->label('Last Synced')
                            ->disabled(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('url_path')
                    ->label('URL Path')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('keyword')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->badge(),
                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'inactive' => 'warning',
                        'archived' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cms_type')
                    ->label('CMS')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('generations_count')
                    ->label('Generations')
                    ->counts('generations')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_synced')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('language')
                    ->options([
                        'en' => 'English',
                        'es' => 'Spanish',
                        'fr' => 'French',
                        'de' => 'German',
                        'it' => 'Italian',
                        'pt' => 'Portuguese',
                    ]),
                Tables\Filters\SelectFilter::make('tenant')
                    ->relationship('tenant', 'name'),
                Tables\Filters\SelectFilter::make('cms_type')
                    ->label('CMS Type')
                    ->options([
                        'wordpress' => 'WordPress',
                        'joomla' => 'Joomla',
                        'drupal' => 'Drupal',
                        'magento' => 'Magento',
                        'shopify' => 'Shopify',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}