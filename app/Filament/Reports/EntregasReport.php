<?php

namespace App\Filament\Reports;

use App\Models\Entrega;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class EntregasReport extends Report
{
    protected static ?string $model = Entrega::class;
    public ?string $heading = "Reporte de Entregas";

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Text::make("Reporte de Entregas")
                    ->title()
                    ->primary(),
            ]);
    }

    public function body(Body $body): Body
    {
        return $body
            ->schema([
                Body\Layout\BodyColumn::make()
                    ->schema([
                        Text::make("Registered Users")
                            ->fontXl()
                            ->fontBold()
                            ->primary(),
                        Text::make("This is a list of registered users from the specified date range")
                            ->fontSm()
                            ->secondary(),
                        Body\Table::make()
                            ->columns([
                                Body\TextColumn::make("tipo_cafe")
                                    ->label("Tipo de cafe"),
                                Body\TextColumn::make("quintalaje_liquidable")
                                    ->label("Quintalaje liquidable"),
                            ])
                            ->data(
                                function (?array $filters) {
                                    [$from, $to] = getCarbonInstancesFromDateString(
                                        $filters['created_at'] ?? null
                                    );
                                    return Entrega::query()
                                        ->when($from, function ($query, $date) {
                                            return $query->whereDate('created_at', '>=', $date);
                                        })
                                        ->when($to, function ($query, $date) {
                                            return $query->whereDate('created_at', '<=', $date);
                                        })
                                        ->select("tipo_cafe", "quintalaje_liquidable", "created_at")
                                        ->take(10)
                                        ->get();
                                }
                            ),
                    ]),
            ]);
    }

    public function footer(Footer $footer): Footer
    {
        return $footer
            ->schema([
            ]);
    }

    public function filterForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->nullable(),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha Inicio')
                    ->nullable(),
                DatePicker::make('fecha_fin')
                    ->label('Fecha Fin')
                    ->nullable(),
            ]);
    }
}
