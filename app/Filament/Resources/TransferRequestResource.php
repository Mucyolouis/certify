<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Church;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TransferRequest;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Services\TransferPredictionService;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransferRequestResource\Pages;
use App\Filament\Resources\TransferRequestResource\RelationManagers;

class TransferRequestResource extends Resource
{
    protected static ?string $model = TransferRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Services';
    protected static ?int $navigationSort = -1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('christian_id')
                    ->default(fn () => Auth::id()),
                
                Forms\Components\Hidden::make('from_church_id')
                    ->default(fn () => Auth::user()->church_id),

                    Forms\Components\Select::make('to_church_id')
                    ->label('To Church')
                    ->options(Church::where('id', '!=', Auth::user()->church_id)->pluck('name', 'id'))
                    ->searchable()  
                    ->required(),
                Forms\Components\TextArea::make('description')
                    ->label('Description')
                    ->required(),
                
                Forms\Components\Hidden::make('approval_status')
                    ->default('pending'),
                // Forms\Components\Select::make('predicted_approval_status')
                //     ->options(['Pending', 'Approved', 'Rejected'])
                //     ->disabled()
                //     ->dehydrated(false)
                //     ->afterStateHydrated(function ($component, $state, $record, TransferPredictionService $predictionService) {
                //         if ($record) {
                //             try {
                //                 $prediction = $predictionService->predict($record->toArray());
                //                 $component->state($prediction);
                //             } catch (\Exception $e) {
                //                 // Log the error and set a default value
                //                 Log::error("Prediction failed: " . $e->getMessage());
                //                 $component->state('Prediction failed');
                //             }
                //         }
                //     }),
            ]);
    }

    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('christian.name')
                        ->label('Christian')
                        ->formatStateUsing(fn ($record) => $record->christian->firstname . ' ' . $record->christian->lastname)
                        ->searchable(['firstname', 'lastname'])
                        ->sortable(),
                    Tables\Columns\TextColumn::make('fromChurch.name')
                        ->label('From Church')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('toChurch.name')
                        ->label('To Church')
                        ->searchable()
                        ->sortable(),
                        Tables\Columns\BadgeColumn::make('approval_status')
                        ->colors([
                            'primary' => 'Pending',
                            'success' => 'Approved',
                            'danger' => 'Rejected',
                        ]),
                    Tables\Columns\TextColumn::make('approvedBy')
                        ->label('Approved By')
                        ->sortable()
                        ->hidden(fn ($record) => !$record || $record->approved_by === null),        
                    Tables\Columns\TextColumn::make('created_at')
                        ->date()
                        ->sortable()
                        ->label('Requested On'),
                    // Tables\Columns\TextColumn::make('predicted_status')
                    //     ->getStateUsing(function (TransferRequest $record) {
                    //         $predictionService = new TransferPredictionService();
                    //         $input = [
                    //             $record->christian_id,
                    //             $record->from_church_id,
                    //             $record->to_church_id,
                    //             $record->description,
                    //         ];
                    //         return $predictionService->predict($input)[0];
                    //     }),
                    Tables\Columns\TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('deleted_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('approval_status')
                        ->options([
                            'Pending' => 'Pending',
                            'Approved' => 'Approved',
                            'Rejected' => 'Rejected',
                        ])
                        ->label('Status')
                        ->placeholder('All Statuses'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->color('success')
                        ->icon('heroicon-s-check-circle')
                        ->action(fn (TransferRequest $record) => $record->approve())
                        ->visible(function (TransferRequest $record): bool {
                            $user = auth()->user();
                            return $user->hasRole('pastor') && 
                                   $user->church_id === $record->to_church_id &&
                                   $record->approval_status !== 'Approved';
                        }),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);

    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransferRequests::route('/'),
        ];
    }

    //this function is used to check id the logged in user can not request or apporve himself, only allowing other users
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $query->where(function (Builder $query) use ($user) {
            if ($user->hasRole('christian')) {
                $query->where('christian_id', $user->id);
            } elseif ($user->hasRole('pastor')) {
                $query->where('to_church_id', $user->church_id)
                      ->orWhere('from_church_id', $user->church_id);
            }
        });
    }
}
