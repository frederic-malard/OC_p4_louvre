<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CheckingController extends AbstractController
{
    /**
     * @Route("/consultation-reservations", name="checking_index")
     */
    public function index()
    {
        return $this->render('checking/index.html.twig');
    }
}
