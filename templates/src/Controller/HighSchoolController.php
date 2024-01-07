<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HighSchoolController extends AbstractController
{
    
      #[Route("/school", name:"school")]
    
    public function index(): Response
    {
        return $this->render('high-school.html.twig');
    }
}




?> 