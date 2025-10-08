<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.admin.pages.settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'openai_api_key' => config('services.openai.api_key'),
            'openai_model' => config('services.openai.model', 'gpt-4'),
            'openai_max_tokens' => config('services.openai.max_tokens', 4000),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('OpenAI API Configuration')
                    ->description('Configure the global OpenAI API key used by all tenants for content generation')
                    ->schema([
                        Forms\Components\TextInput::make('openai_api_key')
                            ->label('OpenAI API Key')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('Your OpenAI API key (sk-...)')
                            ->placeholder('sk-...')
                            ->rules(['string', 'min:10']),

                        Forms\Components\Select::make('openai_model')
                            ->label('Default Model')
                            ->options([
                                'gpt-4' => 'GPT-4 (Most capable)',
                                'gpt-4-turbo' => 'GPT-4 Turbo (Fast and capable)',
                                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast and efficient)',
                            ])
                            ->required()
                            ->helperText('Default AI model for content generation'),

                        Forms\Components\TextInput::make('openai_max_tokens')
                            ->label('Max Tokens')
                            ->numeric()
                            ->required()
                            ->minValue(100)
                            ->maxValue(16000)
                            ->default(4000)
                            ->helperText('Maximum tokens per request (100-16000)'),
                    ])->columns(1),

                Section::make('Platform Settings')
                    ->description('General platform configuration')
                    ->schema([
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('Maintenance Mode')
                            ->helperText('Enable maintenance mode to prevent tenant access'),

                        Forms\Components\Toggle::make('allow_new_registrations')
                            ->label('Allow New Registrations')
                            ->default(true)
                            ->helperText('Allow new tenants to register'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            // Update .env file with new API key
            $this->updateEnvFile('OPENAI_API_KEY', $data['openai_api_key']);
            $this->updateEnvFile('OPENAI_MODEL', $data['openai_model']);
            $this->updateEnvFile('OPENAI_MAX_TOKENS', $data['openai_max_tokens']);

            // Clear config cache
            Cache::flush();

            Notification::make()
                ->title('Settings saved successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving settings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function updateEnvFile(string $key, string $value): void
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);

        // Check if key exists
        if (preg_match("/^{$key}=/m", $content)) {
            // Update existing key
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        } else {
            // Add new key
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }
}
