<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Marriage;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MarriageResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MarriageResource\RelationManagers;

class MarriageResource extends Resource
{
    protected static ?string $model = Marriage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Services';

    public static function form(Form $form): Form
    {
        
        return $form
            ->schema([
                Forms\Components\Select::make('spouse1_id')
                    ->label('Spouse 1')
                    ->options(function () {
                        return User::where('marital_status', 'single')
                            ->where('baptized', true)
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->firstname . ' ' . $user->lastname];
                            });
                    })
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        if ($get('spouse1_id') === $get('spouse2_id')) {
                            $set('spouse2_id', null);
                        }
                    }),
                Forms\Components\Select::make('spouse2_id')
                    ->label('Spouse 2')
                    ->options(function () {
                        return User::where('marital_status', 'single')
                            ->where('baptized', true)
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->firstname . ' ' . $user->lastname];
                            });
                    })
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        if ($get('spouse1_id') === $get('spouse2_id')) {
                            $set('spouse2_id', null);
                        }
                    }),
                Forms\Components\Hidden::make('marriage_date')
                    ->default(now()),
                Forms\Components\Hidden::make('officiated_by')
                    ->default(fn () => Auth::user()->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('spouse1FullName')
                ->label('Spouse 1')
                ->searchable(['firstname', 'lastname']),
            Tables\Columns\TextColumn::make('spouse2FullName')
                ->label('Spouse 2')
                ->searchable(['firstname', 'lastname']),
            Tables\Columns\TextColumn::make('officiatedByFullName')
                ->label('Officiated By')
                ->searchable(['firstname', 'lastname']),
                Tables\Columns\TextColumn::make('marriage_date')
                    ->label('Married On')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageMarriages::route('/'),
        ];
    }

}
