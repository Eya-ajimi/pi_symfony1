<?php
// src/Validator/Constraints/NoBadWords.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use voku\helper\AntiXSS;

/**
 * @Annotation
 */
class NoBadWords extends Constraint
{
    public $message = 'Your input contains inappropriate language: "{{ badWord }}"';
    public $badWords = [
          'damn', 'crap', 'piss', 'retard' , 'bhim'
    ]; 
}