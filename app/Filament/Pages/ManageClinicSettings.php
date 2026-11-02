<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageClinicSettings extends Page
{
    protected string $view = 'filament.pages.manage-clinic-settings';

    protected static ?string $title = 'Configuración de la clínica';

    protected static ?string $navigationLabel = 'Configuración de la clínica';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Configuration;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.view') ?? false;
    }

    public function mount(): void
    {
        Gate::authorize('settings.view');

        $this->form->fill([
            'clinic_name' => Setting::get('clinic_name', config('clinic.name')),
            'clinic_legal_name' => Setting::get('clinic_legal_name', config('clinic.legal_name')),
            'clinic_address' => Setting::get('clinic_address', config('clinic.address')),
            'clinic_phone' => Setting::get('clinic_phone', config('clinic.phone')),
            'clinic_email' => Setting::get('clinic_email', config('clinic.email')),
            'currency' => Setting::get('currency', config('clinic.currency'), 'billing'),
            'currency_symbol' => Setting::get('currency_symbol', config('clinic.currency_symbol'), 'billing'),
            'tax_rate' => Setting::get('tax_rate', config('clinic.tax_rate'), 'billing'),
            'receipt_prefix' => Setting::get('receipt_prefix', config('clinic.receipt_prefix'), 'billing'),
            'default_duration_minutes' => Setting::get('default_duration_minutes', config('clinic.appointment_duration'), 'appointments'),
            'reminder_days_before' => Setting::get('reminder_days_before', config('clinic.reminder_days_before'), 'appointments'),
            'timezone' => Setting::get('timezone', config('clinic.timezone'), 'system'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Identidad de la clínica')
                        ->columns(2)
                        ->schema([
                            TextInput::make('clinic_name')
                                ->label('Nombre comercial')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('clinic_legal_name')
                                ->label('Razón social')
                                ->maxLength(255),
                            TextInput::make('clinic_address')
                                ->label('Dirección')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('clinic_phone')
                                ->label('Teléfono')
                                ->tel()
                                ->maxLength(30),
                            TextInput::make('clinic_email')
                                ->label('Correo electrónico')
                                ->email()
                                ->maxLength(255),
                        ]),
                    Section::make('Facturación')
                        ->columns(2)
                        ->description('Los montos se almacenan en centavos para evitar errores de redondeo.')
                        ->schema([
                            TextInput::make('currency')
                                ->label('Moneda')
                                ->required()
                                ->maxLength(3),
                            TextInput::make('currency_symbol')
                                ->label('Símbolo')
                                ->required()
                                ->maxLength(5),
                            TextInput::make('tax_rate')
                                ->label('IVA')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->suffix('%'),
                            TextInput::make('receipt_prefix')
                                ->label('Prefijo de recibos')
                                ->required()
                                ->maxLength(10),
                        ]),
                    Section::make('Operación')
                        ->columns(2)
                        ->schema([
                            TextInput::make('default_duration_minutes')
                                ->label('Duración de cita por defecto')
                                ->numeric()
                                ->required()
                                ->minValue(5)
                                ->maxValue(480)
                                ->suffix('minutos'),
                            TextInput::make('reminder_days_before')
                                ->label('Días de anticipación para recordatorios')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(60)
                                ->suffix('días'),
                            TextInput::make('timezone')
                                ->label('Zona horaria')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('La zona horaria se define en la configuración del sistema.'),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar cambios')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        Gate::authorize('settings.update');

        $data = $this->form->getState();

        $settings = [
            ['general', 'clinic_name', $data['clinic_name'] ?? null, 'string'],
            ['general', 'clinic_legal_name', $data['clinic_legal_name'] ?? null, 'string'],
            ['general', 'clinic_address', $data['clinic_address'] ?? null, 'string'],
            ['general', 'clinic_phone', $data['clinic_phone'] ?? null, 'string'],
            ['general', 'clinic_email', $data['clinic_email'] ?? null, 'string'],
            ['billing', 'currency', $data['currency'] ?? null, 'string'],
            ['billing', 'currency_symbol', $data['currency_symbol'] ?? null, 'string'],
            ['billing', 'tax_rate', $data['tax_rate'] ?? null, 'decimal'],
            ['billing', 'receipt_prefix', $data['receipt_prefix'] ?? null, 'string'],
            ['appointments', 'default_duration_minutes', $data['default_duration_minutes'] ?? null, 'integer'],
            ['appointments', 'reminder_days_before', $data['reminder_days_before'] ?? null, 'integer'],
        ];

        foreach ($settings as [$group, $key, $value, $type]) {
            Setting::set($key, $value, $group, $type);
        }

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->body('Los cambios se aplicaron correctamente.')
            ->send();
    }
}
