<?php

namespace App\Filament\Pages;

use App\Models\MetricUsage;
use App\Models\ChatbotInstance;
use App\Models\Module;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

class UserActivity extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.user-activity';
    protected static ?string $title = 'User Activities';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $pollingInterval = null;
    protected static ?int $navigationSort = 2;

    #[Url]
    public string $timeFilter = '90days';

    #[Url]
    public string $moduleFilter = 'all';

    public function mount(): void
    {
        $this->timeFilter = request('timeFilter', '90days');
        $this->moduleFilter = request('moduleFilter', 'all');
    }

    public function updatedTimeFilter(): void
    {
        $this->dispatch('filtersUpdated');
    }

    public function updatedModuleFilter(): void
    {
        $this->dispatch('filtersUpdated');
    }

    protected function getViewData(): array
    {
        // Module basierend auf Rolle (per module_code)
        if (auth()->user()->is_admin) {
            // Admin sieht alle Module
            $modules = Module::orderBy('name')->pluck('name', 'code')->toArray();
        } else {
            // Nicht-Admin: nur eigene Module
            $moduleCodes = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (! empty($moduleCodes)) {
                $modules = Module::whereIn('code', $moduleCodes)
                    ->orderBy('name')
                    ->pluck('name', 'code')
                    ->toArray();
            } else {
                $modules = [];
            }
        }

        return [
            'timeFilter'   => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
            'modules'      => ['all' => 'All Modules'] + $modules,
            'timeOptions'  => [
                '1day'    => 'Last 1 Day',
                '3days'   => 'Last 3 Days',
                '5days'   => 'Last 5 Days',
                '7days'   => 'Last 7 Days',
                '30days'  => 'Last 30 Days',
                '90days'  => 'Last 90 Days',
                '6months' => 'Last 6 Months',
            ],
        ];
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                MetricUsage::query()
                    ->select('student_id_hash')
                    ->groupBy('student_id_hash')
                    ->selectRaw('COUNT(*) as total_requests')
                    ->selectRaw('SUM(prompt_tokens + completion_tokens) as total_tokens')
                    ->selectRaw('AVG(duration_ms) as avg_duration')
                    // MariaDB-kompatible Berechnung der Helpful-Quote (0–100%)
                    ->selectRaw('SUM(CASE WHEN helpful THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0) as helpful_ratio')
                    ->when(! auth()->user()->is_admin, function (Builder $query) {
                        // Modul-Restriktion über module_code
                        $userModuleCodes = \DB::table('module_user')
                            ->where('user_id', auth()->id())
                            ->pluck('module_code')
                            ->toArray();

                        if (! empty($userModuleCodes)) {
                            return $query->whereIn('module_code', $userModuleCodes);
                        }

                        // User ohne Module → leeres Ergebnis erzwingen
                        return $query->whereRaw('1 = 0');
                    })
            )
            ->columns([
                TextColumn::make('student_id_hash')
                    ->label('Student')
                    ->searchable()
                    ->url(fn ($record) => route('filament.pages.user-activity-details', [
                        'student_id_hash' => $record->student_id_hash,
                    ])),
                TextColumn::make('total_requests')
                    ->label('Requests')
                    ->sortable(),
                TextColumn::make('total_tokens')
                    ->label('Total Tokens')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state / 1000, 2) . 'K'),
                TextColumn::make('avg_duration')
                    ->label('Avg Duration (ms)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0)),
                TextColumn::make('helpful_ratio')
                    ->label('Helpful %')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1) . '%'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('timeFilter')
                    ->label('Time Range')
                    ->options([
                        '1day'    => 'Last 1 Day',
                        '3days'   => 'Last 3 Days',
                        '5days'   => 'Last 5 Days',
                        '7days'   => 'Last 7 Days',
                        '30days'  => 'Last 30 Days',
                        '90days'  => 'Last 90 Days',
                        '6months' => 'Last 6 Months',
                    ])
                    ->default('7days')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? '7days';

                        $from = match ($value) {
                            '1day'    => now()->subDay(),
                            '3days'   => now()->subDays(3),
                            '5days'   => now()->subDays(5),
                            '7days'   => now()->subDays(7),
                            '30days'  => now()->subDays(30),
                            '90days'  => now()->subDays(90),
                            '6months' => now()->subMonths(6),
                            default   => now()->subDays(7),
                        };

                        return $query->where('timestamp', '>=', $from);
                    }),

                Tables\Filters\SelectFilter::make('moduleFilter')
                    ->label('Module')
                    ->options(function () {
                        if (auth()->user()->is_admin) {
                            return Module::orderBy('name')->pluck('name', 'code')->toArray();
                        }

                        $moduleCodes = \DB::table('module_user')
                            ->where('user_id', auth()->id())
                            ->pluck('module_code')
                            ->toArray();

                        return ! empty($moduleCodes)
                            ? Module::whereIn('code', $moduleCodes)
                                ->orderBy('name')
                                ->pluck('name', 'code')
                                ->toArray()
                            : [];
                    })
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! empty($value)) {
                            return $query->where('module_code', $value);
                        }

                        return $query;
                    }),
            ])
            ->defaultSort('total_requests', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(fn ($record) => route('filament.pages.user-activity-details', [
                        'student_id_hash' => $record->student_id_hash,
                    ]))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->student_id_hash;
    }
}
