<?php

namespace App\Controller;

use App\Entity\Upload;
use App\Repository\UploadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function list(UploadRepository $uploadRepo): Response
    {
        $allUploads = $uploadRepo->findBy([], ['created_at' => 'DESC']);

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'allUploads' => $allUploads,
        ]);
    }
}
