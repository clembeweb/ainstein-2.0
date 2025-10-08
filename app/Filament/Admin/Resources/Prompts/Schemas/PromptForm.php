<?php

namespace App\Filament\Admin\Resources\Prompts\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Prompt Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Display name for this prompt'),
                        TextInput::make('alias')
                            ->label('Unique Alias')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-z0-9_-]+$/')
                            ->helperText('Unique identifier (lowercase, numbers, - and _ only)'),
                        Select::make('category')
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
                            ])
                            ->required()
                            ->searchable(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Describe what this prompt does and when to use it'),
                    ])->columns(2),

                Section::make('Prompt Configuration')
                    ->schema([
                        Textarea::make('template')
                            ->label('Prompt Template')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull()
                            ->helperText('Use {{variable_name}} for dynamic variables. Example: Generate content for {{topic}} targeting {{audience}}'),
                        TagsInput::make('variables')
                            ->label('Variables')
                            ->helperText('List of variables used in the template (without {{}})')
                            ->placeholder('topic, audience, tone')
                            ->columnSpanFull(),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active prompts are available for use'),
                        Toggle::make('is_system')
                            ->label('System Prompt')
                            ->default(true)
                            ->helperText('System prompts are available to all tenants')
                            ->visible(fn () => auth()->user()?->is_super_admin),
                        Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->nullable()
                            ->helperText('Leave empty for system prompts available to all tenants')
                            ->hidden(fn (callable $get) => $get('is_system')),
                    ])->columns(3),
            ]);
    }
}
