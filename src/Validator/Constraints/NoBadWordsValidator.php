<?php
// src/Validator/Constraints/NoBadWordsValidator.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoBadWordsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NoBadWords) {
            throw new UnexpectedTypeException($constraint, NoBadWords::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        foreach ($constraint->badWords as $badWord) {
            if (preg_match("/\b" . preg_quote($badWord, '/') . "\b/i", $value)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ badWord }}', $badWord)
                    ->addViolation();
                return; 
            }
        }
    }
}