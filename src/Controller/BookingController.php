<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Stripe\Stripe;
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
        $reservation = $this->prepareReservation($mail, $manager);

        $personsInfosForIndex = [];
        $cpt = 0;

        $personsInfos = $reservation->createPersonInfos();
    
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
            'personsInfos' => $personsInfos;
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
    public function treatment(ObjectManager $manager, \Swift_Mailer $mailer)
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
            $reservation = $this->prepareReservation($mail, $manager);
            $form = $this->createForm(ReservationType::class, $reservation);
            $form->submit($reservationData);

            foreach($reservation->getTemporaryPersonsList() as $person)
            {
                $manager->persist($person);
                $reservation->addPerson($person);
            }

            $manager->persist($reservation);

            $manager->flush();

            $this->sendMail($reservation, $mail, $mailer);
        }

        // return view
        return $this->render("booking/treatment.html.twig", [
            'success' => $success,
            'mail' => $mail
        ]);
    }

    /**
     * send the mail, using a reservation object. No route here because it's just a function used by other functions.
     *
     * @return string
     */
    public function sendMail(Reservation $reservation, $mail, \Swift_Mailer $mailer)
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
        $message[] = 'Chaque visiteur devra présenter son billet, joint à ce présent mail, au format pdf, imprimable.';
        $message[] = 'Une pièce d\'identité, ainsi qu\'un justificatif pour une éventuelle réduction, sera aussi demandée à chaque visiteur.';

        $messageText = '';
        //$messageHtml = '<!doctype html><html><head><title>réservation</title></head><body>';
        //$messageHtml = '<p>';
        foreach($message as $paragraph)
        {
            $messageText .= $paragraph . $newLine;
            //$messageHtml .= $paragraph . '</p><p>';
        }
        //$messageHtml = '</p>';
        //$messageHtml = '</body></html>';

        $finalMail = (new \Swift_Message($mailSubject))
            ->setSubject($mailSubject)
            ->setFrom('travail@MacBook-Pro-de-frederic.local')
            ->setTo($mail)
            ->setCharset('UTF-8')
            //->attach(\Swift_Attachment::fromPath('/image/musee.jpg', 'image/jpeg'))
            ->setBody($messageText, 'text/plain');
            //->embed(\Swift_Image::fromPath('/image/musee.jpg'));
            //->addPart($messageHtml, 'text/html');
            //->addPart($this->renderView(page twig avec le corps du message));
        
        $i = 0;
        foreach ($reservation->getPersons() as $person)
        {
            /*$package = new Package(new EmptyVersionStrategy());
            $picture = $package->getUrl('/image/musee.jpg');*/

            $pdfFile = new Dompdf();
            $pdfFile->load_html($this->render('mail/ticket.html.twig', ['person' => $person, 'reservation' => $reservation]));
            $pdfFile->setPaper('A4', 'portrait');
            $pdfFile->render();

            $output = $pdfFile->output();
            /*$publicDirectory = $this->get('kernel')->getProjectDir() . '/public';
            $pdfFilePath = $publicDirectory . '/mypdf';*/
            
            $pdfName = $i . '_' . $person->getFirstName() . '_' . $person->getName() . '.pdf';
            $i++;

            $attachment = new \Swift_Attachment($output, $pdfName, 'application/pdf');

            $finalMail->attach($attachment);
        }

        $mailer->send($finalMail);
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
