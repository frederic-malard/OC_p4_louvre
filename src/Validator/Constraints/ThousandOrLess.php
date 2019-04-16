<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class ThousandOrLess extends Constraint
{
    public $message = "La capacité d'accueil du Louvre pour la date sélectionnée est dépassée. Merci de choisir une autre date.";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}