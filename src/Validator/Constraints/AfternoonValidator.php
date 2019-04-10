<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AfternoonValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint App\Validator\Afternoon */

        if ($value->getVisitDay() == new \Date() && ! $value->getHalfDay() && (int) date('H') >= 14)
            $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}
