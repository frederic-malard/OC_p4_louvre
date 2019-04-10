<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Afternoon extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'Vous avez indiqué la date du jour, et avez réservé pour une journée entière (vous avez laissé la case "arrivée après 14h" décochée) alors qu\'il est déjà 14h passée. Veuillez cocher la case (vous profiterez par la même occasion d\'un demi-tarif.';
}
