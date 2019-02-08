<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\Reservation;
use App\Form\ReservationType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookingController extends AbstractController
{
    /**
     * @Route("/reservation/nouvelle", name="booking_filling_form")
     */
    public function index(Request $request, ObjectManager $manager)
    {
        $mail = $this->get('session')->get('mail');

        $reservation = new Reservation();
        $reservation->setMail($mail);

        $form = $this->createForm(ReservationType::class, $reservation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            foreach($reservation->getPersons() as $person)
            {
                $person->addReservation($reservation);
                $manager->persist($person);
            }

            $manager->persist($reservation);

            $manager->flush();

            //return $this->redirectToRoute("payment");
        }

        return $this->render('booking/index.html.twig', [
            'form' => $form->createView(),
            'mail' => $mail
        ]);
    }

    /**
     * @Route("/paiement", name="payment")
     */
    public function payment()
    {

    }
}
