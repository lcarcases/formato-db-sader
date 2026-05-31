<?php

namespace App\Core\Shared\Application\Converters;


interface IConverter
{
    public function fromDto($dto);
    public function fromEntity($entity);
    public function toDtoList(array $entities);
    public function toEntitiesList(array $dtos);
}