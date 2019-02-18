<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Person;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\PersonRepository;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
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
        $reservation = $this->prepareReservation($mail, $manager);

        $personsInfosForIndex = [];
        $cpt = 0;

        foreach($reservation->getPersons() as $person)
        {
            $personsInfosForIndex[] = ['hr' => 'hr_reservation_persons_' . $cpt, 'object' => $person];
            $cpt++;
        }

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
            'mail' => $mail,
            'personsInfos' => $personsInfosForIndex
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
    public function treatment(ObjectManager $manager)
    {
        /*Stripe::setApiKey("sk_test_AssWuckpnHlwx6B4edglOnpj");

        $token = $this->get('session')->get('stripeToken');*/

        $price = $this->get('session')->get('price');

        /*$charge = \Stripe\Charge::create([
            'amount' => $price*100,
            'currency' => 'eur',
            'description' => 'Example charge',
            'source' => $token,
        ]);*/

        $reservationData = $this->get('session')->get('reservation');
        $reservation = $this->prepareReservation($this->get('session')->get('mail'), $manager);
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->submit($reservationData);

        foreach($reservation->getTemporaryPersonsList() as $person)
        {
            $manager->persist($person);
            $reservation->addPerson($person);
        }

        $manager->persist($reservation);

        $manager->flush();

        // createMailContent

        // return view
        return new Response("blabla");
    }

    /**
     * create the content of the mail, using a reservation object. No route here because it's just a function used by other functions.
     *
     * @return string
     */
    public function createMailContent(Reservation $reservation)
    {

    }

    private function prepareReservation(string $mail, ObjectManager $manager) {
        $reservation = new Reservation();
        $reservation->setMail($mail);

        // all persons that have already been involved in a reservation made with the mail used to began this new reservation
        $personsMatching = $this->getDoctrine()->getRepository(Person::class)->getPersonsFromMail($mail);

        foreach($personsMatching as $person)
        {
            $reservation->addPerson($person);
        }

        return $reservation;
    }
}
