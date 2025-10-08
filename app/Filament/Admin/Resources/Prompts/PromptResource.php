<?php

namespace App\Filament\Admin\Resources\Prompts;

use App\Filament\Admin\Resources\Prompts\Pages\CreatePrompt;
use App\Filament\Admin\Resources\Prompts\Pages\EditPrompt;
use App\Filament\Admin\Resources\Prompts\Pages\ListPrompts;
use App\Filament\Admin\Resources\Prompts\Schemas\PromptForm;
use App\Filament\Admin\Resources\Prompts\Tables\PromptsTable;
use App\Models\Prompt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PromptResource extends Resource
{
    protected static ?string $model = Prompt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $navigationLabel = 'AI Prompts';

    protected static ?string $modelLabel = 'AI Prompt';

    protected static ?string $pluralModelLabel = 'AI Prompts';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PromptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromptsTable::configure($table);
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
            'index' => ListPrompts::route('/'),
            'create' => CreatePrompt::route('/create'),
            'edit' => EditPrompt::route('/{record}/edit'),
        ];
    }
}
