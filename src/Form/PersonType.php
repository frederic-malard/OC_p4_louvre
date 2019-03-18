<?php

namespace App\Form;

use App\Entity\Person;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PersonType extends AbstractType
{
    private function configuration($label, $placeholder)
    {
        return [
            'label' => $label,
            'attr' => [
                'placeholder' => $placeholder
            ]
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, $this->configuration("prénom", "prénom du visiteur"))
            ->add('name', TextType::class, $this->configuration("nom", "nom du visiteur"))
            ->add('country', CountryType::class, ['label' => 'pays'])
            ->add('birthDate', DateType::class, [
                'label' => "date de naissance",
                'years' => range(date('Y'), date('Y') - 125)
            ])
            ->add('discount', CheckboxType::class, [
                'label' => "mon statut me permet d'obtenit une réduction (10€ la journée, 5€ après 14h. Réservé aux étudiants, aux militaires, et aux employés du musée ou du service du ministère de la culture. Un justificatif vous sera demandé à l'entrée.)",
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
