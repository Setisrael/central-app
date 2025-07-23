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
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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
        // Get modules based on user role
        if (auth()->user()->is_admin) {
            // Admin sees all modules
            $modules = Module::orderBy('name')->pluck('name', 'id')->toArray();
        } else {
            // Non-admin sees only their assigned modules
            $moduleIds = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_id')
                ->toArray();

            if (!empty($moduleIds)) {
                $modules = Module::whereIn('id', $moduleIds)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            } else {
                $modules = [];
            }
        }

        return [
            'timeFilter' => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
            'modules' => ['all' => 'All Modules'] + $modules,
            'timeOptions' => [
                '1day' => 'Last 1 Day',
                '3days' => 'Last 3 Days',
                '5days' => 'Last 5 Days',
                '7days' => 'Last 7 Days',
                '30days' => 'Last 30 Days',
                '90days' => 'Last 90 Days',
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
                    ->selectRaw('SUM(CASE WHEN helpful THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) * 100 as helpful_ratio')
                    ->when(!auth()->user()->is_admin, function ($query) {
                        // Apply user role restrictions for non-admin users
                        $userModuleIds = \DB::table('module_user')
                            ->where('user_id', auth()->id())
                            ->pluck('module_id')
                            ->toArray();

                        if (!empty($userModuleIds)) {
                            return $query->whereIn('module_id', $userModuleIds);
                        } else {
                            // If user has no modules, return empty result
                            return $query->whereRaw('1 = 0');
                        }
                    })
                    ->when(MetricUsage::count() === 0, fn ($query) =>
                    $query->selectRaw('NULL as student_id_hash, 0 as total_requests, 0 as total_tokens, 0 as avg_duration, 0 as helpful_ratio')
                    )
            )
            ->columns([
                TextColumn::make('student_id_hash')
                    ->label('Student')
                    ->searchable()
                    ->url(fn ($record) => route('filament.pages.user-activity-details', ['student_id_hash' => $record->student_id_hash])),
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
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%'),
            ])
            ->filters([
                SelectFilter::make('timeFilter')
                    ->label('Time Range')
                    ->options([
                        '1day' => 'Last 1 Day',
                        '3days' => 'Last 3 Days',
                        '5days' => 'Last 5 Days',
                        '7days' => 'Last 7 Days',
                        '30days' => 'Last 30 Days',
                        '90days' => 'Last 90 Days',
                        '6months' => 'Last 6 Months',
                    ])
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->where('timestamp', '>=', match ($data['value']) {
                            '1day' => now()->subDay(),
                            '3days' => now()->subDays(3),
                            '5days' => now()->subDays(5),
                            '7days' => now()->subDays(7),
                            '30days' => now()->subDays(30),
                            '90days' => now()->subDays(90),
                            '6months' => now()->subMonths(6),
                            default => now()->subDays(7),
                        })
                        : $query
                    ),
                SelectFilter::make('moduleFilter')
                    ->label('Module')
                    ->options(function () {
                        // Get modules based on user role
                        if (auth()->user()->is_admin) {
                            // Admin sees all modules
                            $modules = Module::orderBy('name')->pluck('name', 'id')->toArray();
                        } else {
                            // Non-admin sees only their assigned modules
                            $moduleIds = \DB::table('module_user')
                                ->where('user_id', auth()->id())
                                ->pluck('module_id')
                                ->toArray();

                            if (!empty($moduleIds)) {
                                $modules = Module::whereIn('id', $moduleIds)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            } else {
                                $modules = [];
                            }
                        }

                        return ['all' => 'All Modules'] + $modules;
                    })
                    ->query(fn (Builder $query, array $data) =>
                    $data['value'] !== 'all'
                        ? $query->where('module_id', $data['value'])
                        : $query
                    ),
            ])
            ->defaultSort('total_requests', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(fn ($record) => route('filament.pages.user-activity-details', ['student_id_hash' => $record->student_id_hash]))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->student_id_hash;
    }
}
/*namespace App\Filament\Pages;

use App\Models\MetricUsage;
use App\Models\ChatbotInstance;
use App\Models\Module;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;

class UserActivity extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.user-activity';
    protected static ?string $title = 'User Activities';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $pollingInterval = null;

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
        // Get modules based on user role
        if (auth()->user()->is_admin) {
            // Admin sees all modules
            $modules = Module::orderBy('name')->pluck('name', 'id')->toArray();
        } else {
            // Non-admin sees only their assigned modules
            $moduleIds = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_id')
                ->toArray();

            if (!empty($moduleIds)) {
                $modules = Module::whereIn('id', $moduleIds)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            } else {
                $modules = [];
            }
        }

        return [
            'timeFilter' => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
            'modules' => ['all' => 'All Modules'] + $modules,
            'timeOptions' => [
                '1day' => 'Last 1 Day',
                '3days' => 'Last 3 Days',
                '5days' => 'Last 5 Days',
                '7days' => 'Last 7 Days',
                '30days' => 'Last 30 Days',
                '90days' => 'Last 90 Days',
                '6months' => 'Last 6 Months',
            ],
        ];
    }

    public function table(Tables\Table $table): Tables\Table
    {

        return $table
            ->query(
                MetricUsage::query()->select('student_id_hash')
                    ->groupBy('student_id_hash')
                    ->selectRaw('COUNT(*) as total_requests')
                    ->selectRaw('SUM(prompt_tokens + completion_tokens) as total_tokens')
                    ->selectRaw('AVG(duration_ms) as avg_duration')
                    ->selectRaw('SUM(CASE WHEN helpful THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) * 100 as helpful_ratio')
                    ->when(!auth()->user()->is_admin, fn ($query) =>
                    $query->whereHas('chatbotInstance.modules.users', fn ($q) =>
                    $q->where('users.id', auth()->id())
                    )
                    )
                    ->when(MetricUsage::count() === 0, fn ($query) =>
                    $query->selectRaw('NULL as student_id_hash, 0 as total_requests, 0 as total_tokens, 0 as avg_duration, 0 as helpful_ratio')
                    )
            )
            ->columns([
                TextColumn::make('student_id_hash')
                    ->label('Student')
                    ->searchable()
                    ->url(fn ($record) => route('filament.pages.user-activity-details', ['student_id_hash' => $record->student_id_hash])),
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
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%'),
            ])
            ->filters([
                SelectFilter::make('timeFilter')
                    ->label('Time Range')
                    ->options([
                        '1day' => 'Last 1 Day',
                        '3days' => 'Last 3 Days',
                        '5days' => 'Last 5 Days',
                        '7days' => 'Last 7 Days',
                        '30days' => 'Last 30 Days',
                        '90days' => 'Last 90 Days',
                        '6months' => 'Last 6 Months',
                    ])
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->where('timestamp', '>=', match ($data['value']) {
                            '1day' => now()->subDay(),
                            '3days' => now()->subDays(3),
                            '5days' => now()->subDays(5),
                            '7days' => now()->subDays(7),
                            '30days' => now()->subDays(30),
                            '90days' => now()->subDays(90),
                            '6months' => now()->subMonths(6),
                            default => now()->subDays(7),
                        })
                        : $query
                    ),
                SelectFilter::make('instanceFilter')
                    ->label('Chatbot Instance')
                    ->options(['all' => 'All'] + ChatbotInstance::pluck('name', 'id')->toArray())
                    //->visible(fn () => auth()->user()->is_admin)
                    ->query(fn (Builder $query, array $data) =>
                    $data['value'] !== 'all'
                        ? $query->where('chatbot_instance_id', $data['value'])
                        : $query
                    ),
            ])
            ->defaultSort('total_requests', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(fn ($record) => route('filament.pages.user-activity-details', ['student_id_hash' => $record->student_id_hash]))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->student_id_hash;
    }
}*/
