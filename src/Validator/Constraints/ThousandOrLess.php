<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class ThousandOrLess extends Constraint
{
    public $message = "La capacité d'accueil du Louvre pour la date sélectionnée est dépassée. Merci de choisir une autre date.";
}