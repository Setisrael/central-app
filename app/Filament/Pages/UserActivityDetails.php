<?php

namespace App\Filament\Pages;

use App\Models\MetricUsage;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class UserActivityDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.user-activity-details';
    protected static ?string $title = 'Student Activity Details';
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static bool $isDiscovered=false;
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
                ->when(!auth()->user()->is_admin, fn ($query) => $query->whereHas('chatbotInstance.modules.users', fn ($q) => $q->where('users.id', auth()->id())))
            )
            ->columns([
                TextColumn::make('timestamp')
                    ->label('Time')
                    ->dateTime(),
                TextColumn::make('module_id')
                    ->label('module'),
                TextColumn::make('document_id')
                    ->label('Document'),
                TextColumn::make('helpful')
                    ->label('Helpful')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                TextColumn::make('duration_ms')
                    ->label('Duration (ms)'),
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
                    ->default('7days')
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->where('timestamp', '>=', match ($data['value']) {
                        '1day' => now()->subDay(),
                        '3days' => now()->subDays(3),
                        '5days' => now()->subDays(5),
                        '7days' => now()->subDays(7),
                        '30days' => now()->subDays(30),
                        '90days' => now()->subDays(90),
                        '6months' => now()->subMonths(6),
                        default => now()->subDays(7),
                    }) : $query),
            ])
            ->defaultSort('timestamp', 'desc');
    }
}
