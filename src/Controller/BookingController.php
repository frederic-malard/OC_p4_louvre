<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Service\Mail;
use App\Entity\Person;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\PersonRepository;
use Symfony\Component\Asset\Package;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class BookingController extends AbstractController
{
    /**
     * @Route("/reservation/nouvelle", name="booking_filling_form")
     */
    public function index(Request $request, ObjectManager $manager)
    {
        $mail = $this->get('session')->get('mail');

        // reservation doit être initialiser différement selon que le form ait déjà été rempli ou non ?
        /* $reservation = $request->request->get('reservation');
        if ($reservation == null)*/
        $reservation = new Reservation();
        $reservation->setMail($mail);
    
        $form = $this->createForm(ReservationType::class, $reservation);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $this->get('session')->set('reservation', $request->request->get('reservation'));
            $this->get('session')->set('price', $reservation->price());

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
        if (isset($_POST['stripeToken']))
        {
            $this->get('session')->set('stripeToken', $_POST['stripeToken']);

            return $this->redirectToRoute("treatment");
        }
        else
        {
            return $this->render("booking/payment.html.twig", [
                'price' => $this->get('session')->get('price') * 100
            ]);
        }
    }

    /**
     * @Route("/traitement", name="treatment")
     */
    public function treatment(ObjectManager $manager, \Swift_Mailer $mailer, Mail $mailService)
    {
        Stripe::setApiKey("sk_test_AssWuckpnHlwx6B4edglOnpj");

        $token = $this->get('session')->get('stripeToken');

        $price = $this->get('session')->get('price');

        $charge = \Stripe\Charge::create([
            'amount' => $price*100,
            'currency' => 'eur',
            'description' => 'Example charge',
            'source' => $token,
        ]);

        $success = ($charge->status == "succeeded");

        if ($success)
        {
            $mail = $this->get('session')->get('mail');

            $reservationData = $this->get('session')->get('reservation');
            $reservation = new Reservation();
            $reservation->setMail($mail);
            $form = $this->createForm(ReservationType::class, $reservation);
            $form->submit($reservationData);

            foreach($reservation->getTemporaryPersonsList() as $person)
            {
                $manager->persist($person);
                $reservation->addPerson($person);
            }

            $manager->persist($reservation);

            $manager->flush();

            $mailService->sendMail($reservation, $mail, $mailer);
        }

        // return view
        return $this->render("booking/treatment.html.twig", [
            'success' => $success,
            'mail' => $mail
        ]);
    }
}
