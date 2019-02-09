<?php

namespace App\Controller;

use Stripe\Stripe;
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

            $this->get('session')->set('reservation', $reservation);

            return $this->redirectToRoute("payment");
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
        $reservation = $this->get('session')->get('reservation');
        
        $price = $reservation->price();
        $this->get('session')->set('price', $price);

        if (isset($_POST))
            return $this->redirectToRoute("treatment");
        else
        {
            return $this->render("payment.html.twig", [
                'price' => $price * 100
            ]);
        }
    }

    /**
     * @Route("/traitement", name="treatment")
     */
    public function treatment()
    {
        Stripe::setApiKey("sk_test_AssWuckpnHlwx6B4edglOnpj");

        $token = $_POST['stripeToken'];

        $price = $this->get('session')->get('price');

        $charge = \Stripe\Charge::create([
            'amount' => $prix*100,
            'currency' => 'eur',
            'description' => 'Example charge',
            'source' => $token,
        ]);

        $reservation = $this->get('session')->get('reservation');
    }
}
