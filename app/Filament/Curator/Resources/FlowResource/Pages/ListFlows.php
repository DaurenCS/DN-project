<?php

namespace App\Filament\Curator\Resources\FlowResource\Pages;

use App\Filament\Curator\Resources\FlowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlows extends ListRecords
{
    protected static string $resource = FlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
