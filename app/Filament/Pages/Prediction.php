<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Phpml\Regression\SVR;
use Tables\Columns\TextColumn;
use Phpml\Dataset\ArrayDataset;
use App\Filament\Tables\PredictionResultsTable;

class Prediction extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.prediction';

    public function getPrediction()
        {
            // Fetch the necessary data from the users table
            $users = User::select('date_of_birth', 'baptized_at', 'active_status')
                ->get()
                ->toArray();

            // Prepare the data for the prediction model
            $dataset = new ArrayDataset(
                array_column($users, 'date_of_birth'),
                array_column($users, 'baptized_at'),
                array_column($users, 'active_status')
            );

            // Create and train the prediction model
            $model = new SVR();
            $model->train($dataset->getX(), $dataset->getY());

            // Make the prediction
            $predictedUsers = $model->predict(['2024-08-21']);

            return $predictedUsers[0];
        }
        
}
