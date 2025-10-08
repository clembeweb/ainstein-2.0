<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromptResource\Pages;
use App\Models\Prompt;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromptResource extends Resource
{
    protected static ?string $model = Prompt::class;


    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Prompt name'),
                        Forms\Components\TextInput::make('alias')
                            ->maxLength(100)
                            ->placeholder('Short alias or code'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Description of what this prompt does'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'seo' => 'SEO',
                                'content' => 'Content Generation',
                                'marketing' => 'Marketing',
                                'product' => 'Product Description',
                                'blog' => 'Blog Writing',
                                'social' => 'Social Media',
                                'email' => 'Email Marketing',
                                'custom' => 'Custom',
                            ])
                            ->placeholder('Select category'),
                    ])->columns(2),

                Forms\Components\Section::make('Prompt Template')
                    ->schema([
                        Forms\Components\Textarea::make('template')
                            ->label('Prompt Template')
                            ->required()
                            ->rows(8)
                            ->maxLength(65535)
                            ->placeholder('Enter your prompt template here. Use {{variable_name}} for dynamic variables.')
                            ->helperText('Use double curly braces {{variable_name}} to define variables that can be replaced dynamically.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Variables Configuration')
                    ->schema([
                        Forms\Components\Repeater::make('variables')
                            ->label('Template Variables')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder('variable_name'),
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->placeholder('Display Label'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'text' => 'Text',
                                        'textarea' => 'Textarea',
                                        'number' => 'Number',
                                        'select' => 'Select',
                                        'checkbox' => 'Checkbox',
                                    ])
                                    ->default('text')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->placeholder('Variable description')
                                    ->rows(2),
                                Forms\Components\TextInput::make('default_value')
                                    ->placeholder('Default value'),
                                Forms\Components\Toggle::make('required')
                                    ->label('Required')
                                    ->default(false),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->defaultItems(0)
                            ->addActionLabel('Add Variable')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Settings & Access')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_system')
                            ->label('System Prompt')
                            ->default(false)
                            ->helperText('System prompts are available to all tenants'),
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->hidden(fn (Forms\Get $get): bool => $get('is_system'))
                            ->required(fn (Forms\Get $get): bool => !$get('is_system')),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alias')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Prompt $record): ?string {
                        return $record->description;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'seo' => 'success',
                        'content' => 'info',
                        'marketing' => 'warning',
                        'product' => 'secondary',
                        'blog' => 'primary',
                        'social' => 'danger',
                        'email' => 'gray',
                        'custom' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variables')
                    ->label('Variables Count')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? count($state) : '0')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System')
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'seo' => 'SEO',
                        'content' => 'Content Generation',
                        'marketing' => 'Marketing',
                        'product' => 'Product Description',
                        'blog' => 'Blog Writing',
                        'social' => 'Social Media',
                        'email' => 'Email Marketing',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('System Prompt'),
                Tables\Filters\SelectFilter::make('tenant')
                    ->relationship('tenant', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Prompt $record) {
                        $newRecord = $record->replicate();
                        $newRecord->name = $record->name . ' (Copy)';
                        $newRecord->alias = null;
                        $newRecord->save();
                    })
                    ->requiresConfirmation(),
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
            'index' => Pages\ListPrompts::route('/'),
            'create' => Pages\CreatePrompt::route('/create'),
            'view' => Pages\ViewPrompt::route('/{record}'),
            'edit' => Pages\EditPrompt::route('/{record}/edit'),
        ];
    }
}