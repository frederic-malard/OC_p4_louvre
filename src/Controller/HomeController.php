<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * clear the session and display the homepage
     * 
     * @Route("/", name="home")
     */
    public function index()
    {
        $this->get('session')->clear();

        return $this->render('home/index.html.twig', [
            'index' => true
        ]);
    }

    /**
     * recieve the mail with POST (from form in home), and redirect to reservation if the mail is unknown, or propose menu that propose to check existing reservations made with the email, or make a new reservation with that email. If the visitor want to change the mail, he can do so using the navbar.
     *
     * @Route("/menu", name="menu")
     */
    public function menu(ReservationRepository $repository)
    {
        // getting the mail of the visitor (if isset) from the homepage's form and saving it. Usefull on first arrival, or when the mail have been reset.
        if (isset($_POST['mail']))
        {
            $mail = $_POST['mail'];
            $this->get('session')->set('mail', $mail);
        }
        // or catching the mail previously saved in the session. Usefull if the visitor already used the website in the past minutes, and want to go to the menu without resetting his mail
        else
            $mail = $this->get('session')->get('mail');

        // getting all the reservations passed using the mail provided in homepage
        $reservations = $repository->findBy(['mail' => $mail]);

        // if there is no one, it's useless to display a menu proposing to consulte passed reservations. So we directly go to the route proposing to make a new reservation.
        if (count($reservations) == 0)
        {
            return $this->redirectToRoute("booking_filling_form");
        }
        // if there is at least one reservation, we propose to consulte it or to create a new one.
        else
        {
            return $this->render("home/menu.html.twig");
        }
    }
}
