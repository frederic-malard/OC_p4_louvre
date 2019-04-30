<?php

/**
 * when the visitor go through the consultation part of the website
 */

namespace App\Controller;

use App\Service\Mail;
use App\Service\Prices;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckingController extends AbstractController
{
    /**
     * provide the list of all the reservations previously passed using the mail given at the homepage
     * 
     * @Route("/consultation-reservations", name="checking_index")
     */
    public function index(ReservationRepository $repository)
    {
        $mail = $this->get('session')->get('mail');

        $reservations = $repository->getReservationsFromMail($mail);

        return $this->render('checking/index.html.twig', [
            'reservations' => $reservations
        ]);
    }

    /**
     * show a reservation using its slug
     *
     * @Route("/reservation_{slug}", name="checking_show")
     */
    public function show(Reservation $reservation)
    {
        $priceService = new Prices();

        $mail = $this->get('session')->get('mail');

        return $this->render("checking/show.html.twig", [
            'reservation' => $reservation,
            'mail' => $mail,
            'priceService' => $priceService
        ]);
    }

    /**
     * resend mail with tickets relatives to the reservation, using the handmade mail service, that use itslef swiftmailer
     *
     * @Route("/resend_mail/{slug}", name="resend")
     */
    public function resendMail(Reservation $reservation, Mail $mailService, \Swift_Mailer $mailer)
    {
        $mail = $this->get('session')->get('mail');

        $mailService->sendMail($reservation, $mail, $mailer);

        return $this->render("checking/resend.html.twig", [
            'mail' => $mail
        ]);
    }
}
