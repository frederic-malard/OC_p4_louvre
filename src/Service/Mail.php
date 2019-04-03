<?php

namespace App\Service;

use Dompdf\Dompdf;
use App\Entity\Reservation;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class Mail
{
    private $manager;
    private $mailer;
    private $templating;
    private $message;
    private $messageText;
    //private $messageHtml;
    private $finalMail;
    private $mailSubject;
    private $reservation;
    private $mail;

    // EngineInterface $templating marche pas ? Bug, stackoverflow conseille de le remplacer
    public function __construct(ObjectManager $manager, \Twig_Environment $templating)
    {
        $this->manager = $manager;
        $this->templating = $templating;
    }

    private function createContent()
    {
        $this->mailSubject = 'Votre réservation pour le musée du Louvre';

        if (preg_match("#(hotmail|live|msn)\.[a-zA-Z]{2,6}$#", $this->mail))
            $newLine = "\r\n";
        else
            $newLine = "\n";

        $message = array();
        $message[] = 'Merci pour votre réservation au musée du Louvre.';
        $message[] = 'Vous avez réservé pour la date du ' . $this->reservation->stringVisitDay();
        if ($this->reservation->getHalfDay())
            $message[] = 'Votre arrivée est prévue après 14h.';
        else
            $message[] = 'Vous pouvez venir à l\'heure que vous désirez.';
        $message[] = 'Votre numéro de réservation est le' . $this->reservation->getBookingCode();
        $message[] = 'coût total de la réservation : ' . $this->reservation->price() . '€';
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

        $this->message = $message;
        $this->messageText = $messageText;
        //$this->messageHtml = $messageHtml;
    }

    private function createMail()
    {
        $this->finalMail = (new \Swift_Message($this->mailSubject))
            ->setSubject($this->mailSubject)
            ->setFrom('travail@MacBook-Pro-de-frederic.local')
            ->setTo($this->mail)
            ->setCharset('UTF-8')
            //->attach(\Swift_Attachment::fromPath('/image/musee.jpg', 'image/jpeg'))
            ->setBody($this->messageText, 'text/plain');
            //->embed(\Swift_Image::fromPath('/image/musee.jpg'));
            //->addPart($messageHtml, 'text/html');
            //->addPart($this->renderView(page twig avec le corps du message));
    }

    private function addTickets()
    {
        $i = 0;
        foreach ($this->reservation->getPersons() as $person)
        {
            /*$package = new Package(new EmptyVersionStrategy());
            $picture = $package->getUrl('/image/musee.jpg');*/

            $pdfFile = new Dompdf();
            $pdfFile->load_html($this->templating->render('mail/ticket.html.twig', [
                'person' => $person,
                'reservation' => $this->reservation,
                'imagePath' => getenv('PUBLIC_HOST')
            ]));
            $pdfFile->setPaper('A4', 'portrait');
            $pdfFile->render();

            $output = $pdfFile->output();
            /*$publicDirectory = $this->get('kernel')->getProjectDir() . '/public';
            $pdfFilePath = $publicDirectory . '/mypdf';*/
            
            $pdfName = $i . '_' . $person->getFirstName() . '_' . $person->getName() . '.pdf';
            $i++;

            $attachment = new \Swift_Attachment($output, $pdfName, 'application/pdf');

            $this->finalMail->attach($attachment);
        }
    }

    public function sendMail(Reservation $reservation, $mail, \Swift_Mailer $mailer)
    {
        $this->reservation = $reservation;
        $this->mail = $mail;
        $this->mailer = $mailer;

        $this->createContent();
        $this->createMail();
        $this->addTickets();

        $mailer->send($this->finalMail);
    }
}