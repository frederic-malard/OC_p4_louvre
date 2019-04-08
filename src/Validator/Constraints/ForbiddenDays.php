<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class ForbiddenDays extends Constraint
{
    public $message = "la date de visite choisie tombe un mardi, un dimanche (jours de fermeture du Louvre) ou un jour férié.";
}