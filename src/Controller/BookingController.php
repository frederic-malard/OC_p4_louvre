<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    /**
     * @Route("/reservation/nouvelle", name="booking_filling_form")
     */
    public function index()
    {
        return $this->render('booking/index.html.twig');
    }
}
