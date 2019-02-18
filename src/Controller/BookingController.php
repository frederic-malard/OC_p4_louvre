<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Stripe\Stripe;
use App\Entity\Person;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\PersonRepository;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
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
        Stripe::setApiKey("sk_test_AssWuckpnHlwx6B4edglOnpj");

        $token = $this->get('session')->get('stripeToken');

        $price = $this->get('session')->get('price');

        $charge = \Stripe\Charge::create([
            'amount' => $price*100,
            'currency' => 'eur',
            'description' => 'Example charge',
            'source' => $token,
        ]);

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
        // note : je devrais transformer ça en une collection de billets en HTML !!!
        createMailContent($reservation);

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
        $mailSubject = 'Votre réservation pour le musée du Louvre';

        if (preg_match("#(hotmail|live|msn)\.[a-zA-Z]{2,6}$#", $mail))
            $newLine = "\r\n";
        else
            $newLine = "\n";

        $message = array();
        $message[] = 'Merci pour votre réservation au musée du Louvre.';
        $message[] = 'Vous avez réservé pour la date du ' . $reservation->stringVisitDay();
        if ($reservation->getHalfDay())
            $message[] = 'Votre arrivée est prévue après 14h.';
        else
            $message[] = 'Vous pouvez venir à l\'heure que vous désirez.';
        $message[] = 'Votre numéro de réservation est le' . $reservation->getBookingCode();
        $message[] = 'coût total de la réservation : ' . $reservation->price() . '€';

        $messageText = '';
        $messageHtml = '<p>';
        foreach($message as $paragraph)
        {
            $messageText .= $paragraph . $newLine;
            $messageHtml .= $paragraph . '</p><p>';
        }
        $messageHtml = '</p>';

        $finalMail = (new \Swift_Message($mailSubject))
            ->setSubject($mailSubject)
            ->setFrom('travail@MacBook-Pro-de-frederic.local')
            ->setTo($mail)
            ->setCharset('UTF-8')
            ->setBody($messageText)
            ->addPart($messageHtml, 'text/html');
        
        $i = 0;
        foreach ($reservation->getPersons() as $person)
        {
            $pdfFile = new Dompdf();
            $pdfFile->loadHtml($this->templating->render('mail/ticket.html.twig', ['person' => $person, 'reservation' => $reservation]))
                    ->setPaper('A4', 'portrait')
                    ->render();
            
            $pdfName = $i . '_' . $person.getFirstName() . '_' . $person.getName() . '.pdf';
            $i++;

            $attachment = new Swift_Attachment($pdfFile, $pdfName, 'application/pdf');

            $finalMail->attach($attachment);
        }

        $this->get('mailer')->send($mailFinal);


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
