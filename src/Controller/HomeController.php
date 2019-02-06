<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * recieve the mail with POST (from form in home), and redirect to reservation if the mail is unknown, or propose menu that propose to check existing reservations made with the email, or make a new reservation with that email. If the visitor want to change the mail, he can do so using the navbar.
     *
     * @Route("/menu", name="menu")
     */
    public function menu(ReservationRepository $repository)
    {
        $mail = $_POST['mail'];

        $reservations = $repository->findBy(['mail' => $mail]);

        /*if (count($reservations) == 0)
        {
            return $this->redirectToRoute("booking_filling_form", ['mail' => $mail]);
        }
        else
        {*/
            return $this->render("home/menu.html.twig", [
                'reservations' => $reservations,
                'mail' => $mail
            ]);
        //}
    }
}
