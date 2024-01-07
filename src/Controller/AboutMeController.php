<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutMeController extends AbstractController
{
    
      #[Route("/about", name:"about")]
    
    public function index(): Response
    {
        return $this->render('about_me.html.twig');
    }
}




?> 