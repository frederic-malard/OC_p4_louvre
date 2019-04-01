<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class NotTuesdaySunday extends Constraints
{
    public $message = "la date de visite choisie tombe un mardi ou un dimanche, jours de fermeture du Louvre.";
}