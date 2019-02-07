<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\Reservation;
use App\Form\ReservationType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookingController extends AbstractController
{
    /**
     * @Route("/reservation/nouvelle", name="booking_filling_form")
     */
    public function index()
    {
        $mail = $this->get('session')->get('mail');

        $reservation = new Reservation();
        $reservation->setMail($mail);

        $form = $this->createForm(ReservationType::class, $reservation);

        return $this->render('booking/index.html.twig', [
            'form' => $form->createView(),
            'mail' => $mail
        ]);
    }
}
