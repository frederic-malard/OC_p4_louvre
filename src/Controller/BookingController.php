<?php

/**
 * controller to manage the case the visitor is in the new booking tunnel
 */

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
     * when you choose to make a new reservation, this route create a reservation form that you can fill, then it recall this same route for the validation of the form, then redirect to route "payment" when the form is valid.
     * 
     * @Route("/reservation/nouvelle", name="booking_filling_form")
     */
    public function index(Request $request, ObjectManager $manager)
    {
        // create the new reservation with the mail used at the homepage
        $mail = $this->get('session')->get('mail');
        $reservation = new Reservation();
        $reservation->setMail($mail);
    
        // create a reservation form, with the homemade reservationtype, using the reservation object we just created
        $form = $this->createForm(ReservationType::class, $reservation);
        
        // usefull the second time we get through this route, when it recall itself, after the form has been filled. Handle the request relative to the reservation form.
        $form->handleRequest($request);
        
        // if the form is submitted and valid, we save the reservation and the price in the session for later. We use the request to get the reservation, because otherwise, the session save just the reservation, without the persons related to it. Then we redirect to the payment route.
        if ($form->isSubmitted() && $form->isValid())
        {
            $this->get('session')->set('reservation', $request->request->get('reservation'));
            $this->get('session')->set('price', $reservation->price());

            return $this->redirectToRoute("payment");
        }

        /**
         * else (if the form isn't submitted yet or if the form isn't valid) we propose to fill the form
         */
        return $this->render('booking/index.html.twig', [
            'form' => $form->createView(),
            'mail' => $mail
        ]);
    }

    /**
     * first we go through the else, and display the payment page, after passing to it the price multiplied by a hundred to get it in cents. This page implement the stripe solution, that display a form to get the payment informations.
     * then, once the stripe's form is submitted, we catch the token returned by stripe and go to the treatment route.
     * 
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
     * treatment of the reservation
     * 
     * @Route("/traitement", name="treatment")
     */
    public function treatment(ObjectManager $manager, \Swift_Mailer $mailer, Mail $mailService)
    {
        // preparing payment
        Stripe::setApiKey("sk_test_AssWuckpnHlwx6B4edglOnpj");
        $token = $this->get('session')->get('stripeToken');
        $price = $this->get('session')->get('price');

        // processing to payment with stripe
        $charge = \Stripe\Charge::create([
            'amount' => $price*100,
            'currency' => 'eur',
            'description' => 'Example charge',
            'source' => $token,
        ]);

        // catching boolean indicating weather the payment processed succesfully or not
        $success = ($charge->status == "succeeded");

        // if the payment succeed
        if ($success)
        {
            $mail = $this->get('session')->get('mail');

            // we catch all the reservation datas (including those relative to the persons) we previously (in booking controller > index function > l49) saved in session using the request
            $reservationData = $this->get('session')->get('reservation');

            // we create a new reservation object that we feed with the request object reservationData we just passed through the session
            $reservation = new Reservation();
            $reservation->setMail($mail);
            $form = $this->createForm(ReservationType::class, $reservation);
            $form->submit($reservationData);

            // now the payment have been proceed successfully, we can persist the reservation
            $manager->persist($reservation);

            $manager->flush();

            // then, we send the tickets for Louvre by mail, with swiftmailer, with a handmade mail service. We don't take care of mails not beeing sent successfully here, because we tell the customer to wait a few minutes, then he can get it later through the consultation path if he never get it otherwise.
            $mailService->sendMail($reservation, $mail, $mailer);
        }

        // then, we display a message telling if the reservation succeed or not.
        return $this->render("booking/treatment.html.twig", [
            'success' => $success,
            'mail' => $mail
        ]);
    }
}
