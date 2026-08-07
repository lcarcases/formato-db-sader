<?php

namespace App\Core\Domain\Events;

class {{DomainEvent}}
{
    private \DateTime $ocurridoEn;
    private int $entidadId;
    private array $datosEvento;

    public function __construct(int $entidadId, array $datosEvento = [])
    {
        $this->ocurridoEn = new \DateTime();
        $this->entidadId = $entidadId;
        $this->datosEvento = $datosEvento;
    }

    public function getOcurridoEn(): \DateTime
    {
        return $this->ocurridoEn;
    }

    public function getEntidadId(): int
    {
        return $this->entidadId;
    }

    public function getDatosEvento(): array
    {
        return $this->datosEvento;
    }
}
