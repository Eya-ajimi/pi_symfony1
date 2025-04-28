<?php

// src/Twig/Components/AtmStatus.php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AtmStatus')]
class AtmStatus
{
    public string $status; // Will receive 'active' or 'inactive'
    
    public function getIcon(): string
    {
        return $this->status === 'active' ? 'fa-circle' : 'fa-circle-notch';
    }
    
    public function getColor(): string
    {
        return $this->status === 'active' ? 'green' : 'gray';
    }
}

