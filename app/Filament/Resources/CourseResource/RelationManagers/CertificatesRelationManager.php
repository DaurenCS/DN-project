<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Сертификаты';
    protected static ?string $modelLabel = 'Сертификат';
    protected static ?string $pluralModelLabel = 'Сертификаты';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Название шаблона')
                ->required()
                ->maxLength(255),

            Forms\Components\FileUpload::make('template_path')
                ->label('Файл шаблона (.docx)')
                ->disk('local')
                ->directory('certificate_templates')
                ->acceptedFileTypes([
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->required()
                ->preserveFilenames()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('validity_months')
                ->label('Срок действия (мес.)')
                ->numeric()
                ->default(12)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),

                Tables\Columns\TextColumn::make('template_path')
                    ->label('Файл')
                    ->limit(30),

                Tables\Columns\TextColumn::make('validity_months')
                    ->label('Срок действия (мес.)'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Добавлен')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить сертификат')
                    ->using(function (array $data, Tables\Actions\CreateAction $action) {
                        $certificate = \App\Models\Certificate::create([
                            'name' => $data['name'],
                            'template_path' => $data['template_path'],
                            'validity_months' => $data['validity_months'],

                        ]);

                        $this->getOwnerRecord()->certificates()->attach($certificate->id);

                        return $certificate;
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
