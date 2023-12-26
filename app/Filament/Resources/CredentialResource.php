<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CredentialResource\Pages;
use App\Models\Credential;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('api_key'),
                Forms\Components\TextInput::make('access_token'),
                Forms\Components\TextInput::make('refresh_token'),
                Forms\Components\TextInput::make('access_token'),
                Forms\Components\Select::make('type')->options([
                    'ssh',
                    'domain',
                    'registrar',
                    'development',
                ]),
                Forms\Components\Select::make('service')->options([
                    'cloudflare',
                    'namecheap',
                    'forge',
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('service'),
                Tables\Columns\TextColumn::make('enabled_on'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCredentials::route('/'),
            'create' => Pages\CreateCredential::route('/create'),
            'edit' => Pages\EditCredential::route('/{record}/edit'),
        ];
    }
}
