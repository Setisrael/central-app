<?php

namespace App\Filament\Pages;

use App\Models\MetricUsage;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Module;

class UserActivityDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.user-activity-details';
    protected static ?string $title = 'Student Activity Details';
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static bool $isDiscovered = false;
    public $student_id_hash;

    public function mount($student_id_hash)
    {
        $this->student_id_hash = $student_id_hash;
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(MetricUsage::query()
                ->where('student_id_hash', $this->student_id_hash)
                ->when(!auth()->user()->is_admin, function ($query) {
                    // Restrict by modules for non-admin
                    $userModuleCodes = \DB::table('module_user')
                        ->where('user_id', auth()->id())
                        ->pluck('module_code')
                        ->toArray();

                    if (!empty($userModuleCodes)) {
                        return $query->whereIn('module_code', $userModuleCodes);
                    } else {
                        return $query->whereRaw('1 = 0'); // No modules => empty
                    }
                })
            )
            ->columns([
                TextColumn::make('timestamp')->label('Time')->dateTime(),
                TextColumn::make('module_code')
                    ->label('Module')
                    ->formatStateUsing(fn ($state) => $state
                        ? (\App\Models\Module::where('code', $state)->first()?->name ?? $state)
                        : '-'
                    ),
                TextColumn::make('document_id')->label('Document'),
                TextColumn::make('user_message')->label('User Message')->wrap(),
                TextColumn::make('agent_message')->label('Agent Message')->wrap(),
                TextColumn::make('helpful')->label('Helpful')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                TextColumn::make('duration_ms')->label('Duration (ms)'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('timeFilter')
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
                    ->default('7days') // default time filter
                    ->query(fn (Builder $query, array $data) => $query->where('timestamp', '>=', match ($data['value']) {
                        '1day' => now()->subDay(),
                        '3days' => now()->subDays(3),
                        '5days' => now()->subDays(5),
                        '7days' => now()->subDays(7),
                        '30days' => now()->subDays(30),
                        '90days' => now()->subDays(90),
                        '6months' => now()->subMonths(6),
                        default => now()->subDays(7),
                    })),

                Tables\Filters\SelectFilter::make('moduleFilter')
                    ->label('Module')
                    ->options(function () {
                        if (auth()->user()->is_admin) {
                            return Module::orderBy('name')->pluck('name', 'code')->toArray();
                        } else {
                            $moduleCodes = \DB::table('module_user')
                                ->where('user_id', auth()->id())
                                ->pluck('module_code')
                                ->toArray();

                            return !empty($moduleCodes)
                                ? Module::whereIn('code', $moduleCodes)->orderBy('name')->pluck('name', 'code')->toArray()
                                : [];
                        }
                    })
                    ->query(fn (Builder $query, array $data) =>
                    !empty($data['value']) ? $query->where('module_code', $data['value']) : $query
                    ),
            ])

            ->defaultSort('timestamp', 'desc');
    }

}
