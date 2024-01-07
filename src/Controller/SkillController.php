<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Form\SkillType;
use App\Service\FileUploader;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
class SkillController extends AbstractController
{
    #[Route("skills", name: "skills")]
    public function readAll2(ManagerRegistry $doctrine): Response
    {
        $skillRepository = $doctrine->getRepository(Skill::class);
        $skills = $skillRepository->findAll();
        return $this->render("user/skills.html.twig", ["skills" =>  $skills]);
    }
    #[Route("/read2", name: "read2")]
    public function read2(skill $skill): Response
    {
        if (!$skill) {
            throw $this->createNotFoundException();
        }
        return $this->render("skill/read.html.twig", ["skill" => $skill]);
    }
    #[Route("/create2", name: "create2")]
    public function create(Request $request, ManagerRegistry $doctrine, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $skill = new skill();
        $form = $this->createForm(skillType::class, $skill);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $imageFileName = $fileUploader->upload($imageFile);
                    $skill->setImage($imageFileName);
                }
            }


            $em = $doctrine->getManager();
            $em->persist($skill);
            $em->flush();
            return $this->redirectToRoute('home');
        }
        return $this->render("skill/form.html.twig", [
            "form" => $form->createView(),
            "type" => "create2",
        ]);
    }
    #[Route("/delete2/{id}", name: "delete2")]
    public function delete(Skill $skill, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->addFlash("danger", "Le project '{$skill->getName()}' a été supprimée !");
        $em = $doctrine->getManager();
        $em->remove($skill);
        $em->flush();
        return $this->redirectToRoute("skills");
    }
   
 

    #[Route("/update2/{id}", name: "update2")]
    public function update2(skill $skill, Request $request, ManagerRegistry $doctrine, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_EDIT')) {
            throw $this->createAccessDeniedException('Accès refusé. Vous devez avoir le rôle ROLE_ADMIN ou ROLE_EDIT pour effectuer cette action.');
        }
    
        


        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) { 
                $imageFileName = $fileUploader->upload($imageFile);
                $skill->setImage($imageFileName);
                $doctrine->getManager()->flush();
                $this->addFlash("warning", "Le project '{$skill->getName()}' a été modifiée !");
                return $this->redirectToRoute("skills");
            }
        }
        
        return $this->render("skill/form.html.twig", [
            "form" => $form->createView(),
            "skill" => $skill,
            "type" => "update",
        ]);
    }
}