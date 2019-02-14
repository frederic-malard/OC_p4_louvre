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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookingController extends AbstractController
{
    /**
     * @Route("/reservation/nouvelle", name="booking_filling_form")
     */
    public function index(Request $request, ObjectManager $manager, PersonRepository $repository)
    {
        // je ne sais ou dans cette fonction, prendre toutes les reservations qui ont le mail mémorisé, prendre toutes les personnes incluses dans les précédentes versions (cf fonction du repository de l'ancien projet ?) et les proposer (directement en utilisant les anciennes entités pour pas en créer de nouvelle) et passer prénom et nom en hidden dans un bloc supprimable avec un bouton, et juste afficher prénom et nom pas dans un champs. Problème : je peux pas créer deux champs pour le même attributs "persons" dans "reservationtype". Idée : ajouter un attribut "temporaryPersonsList" a "reservation", qui n'apparaitra pas dans la BDD car onetomany et jamais persisté, et qui contient les personnes ajoutées, qui n'ont jamais été associées à ce mail. Les anciennes personnes seront dans le classique "persons". Puis dans le controller, toutes les reservation du temp sont ajoutées a persons, puis le temp est vidé à coup de removeTempmachin, etc. PS : previousPersontype est supprimé, PersonType est renommé "TemporaryPersonType" et continue à demander tous les champs, et un nouveau "PersonType" est créé et laisse changer tout sauf prénom et nom (qui sont en hidden et juste affichés... Comment les afficher ? Possible d'ajouter un label à un hidden ? Et comment le remplir avec une variable ? Ou alors indiquer direct dans le index avec var dans le bloc supprimable... Vraiment faisable ? Depuis _reservation_persons_entry_row je peux pas connaitre le prénom de la personne en cours ? A moins d'y accéder depuis la variable {{id}} ! Dans le controlleur, préparer la liste des personnes, et leur associer un id incrémenté partant de 0 si possible, et retrouver dans l'index comme ça) et reservationtype a deux collectiontype : un persontype et un temporarypersontype. Note : finalement pas hiddentype, mais carrément le champs absent, c'est dans le controleur que sera de toute manière prérempli l'entité avec les précédentes valeurs, plus besoin d'y toucher ! Juste les rappeler textuellement dans l'index. PS2 : pays automatiquement sur afghanistan, se met pas sur la valeur prévue ! Idée temporaire : passer de coutrytype a texttype. Voir si peut rester dans countrytype et garder la valeur PS3 : il faudra changer la mise en page dans index

        $mail = $this->get('session')->get('mail');

        $reservation = new Reservation();
        $reservation->setMail($mail);

        // all persons that have already been involved in a reservation made with the mail used to began this new reservation
        $personsMatching = $repository->getPersonsFromMail($mail);

        $personsInfosForIndex = [];
        $cpt = 0;

        foreach($personsMatching as $person)
        {
            $reservation->addPerson($person);

            $personsInfosForIndex[] = ['hr' => 'hr_reservation_persons_' . $cpt, 'object' => $person];
            $cpt++;
        }

        $form = $this->createForm(ReservationType::class, $reservation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            foreach($reservation->getPersons() as $person)
            {
                $person->addReservation($reservation);
                $manager->persist($person);
            }

            $manager->persist($reservation);

            $manager->flush();

            $this->get('session')->set('reservation', $reservation);

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
        $reservation = $this->get('session')->get('reservation');
        
        $price = $reservation->price();
        $this->get('session')->set('price', $price);

        if (isset($_POST['stripeToken']))
        {
            $this->get('session')->set('stripeToken', $_POST['stripeToken']);

            return $this->redirectToRoute("treatment");
        }
        else
        {
            return $this->render("booking/payment.html.twig", [
                'price' => $price * 100
            ]);
        }
    }

    /**
     * @Route("/traitement", name="treatment")
     */
    public function treatment()
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

        $reservation = $this->get('session')->get('reservation');
    }

    /**
     * create the content of the mail, using a reservation object. No route here because it's just a function used by other functions.
     *
     * @return string
     */
    public function createMailContent(Reservation $reservation)
    {

    }
}
