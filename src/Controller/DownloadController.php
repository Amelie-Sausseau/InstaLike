<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Upload;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\UploadEditType;
use App\Form\UploadType;
use App\Repository\CommentRepository;
use App\Repository\UploadRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class DownloadController extends AbstractController
{
    /**
     * @Route("/publish", name="publish")
     */
    public function addPicture(Request $request, SluggerInterface $slugger): Response
    {
        $upload = new Upload();

        $form = $this->createForm(UploadType::class, $upload);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $upload = $form->getData();
            $publicationFile = $form->get('image')->getData();
            // On associe le user connecté à la question
            $upload->setUser($this->getUser());

            if ($publicationFile) {
                $originalFilename = pathinfo($publicationFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$publicationFile->guessExtension();

                try {
                    $publicationFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
        
                }

                $upload->setImage($newFilename);
            }


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($upload);
            $entityManager->flush();
            $this->addFlash('success', 'Photo ajoutée');

            return $this->redirectToRoute('user_images');
        }

        return $this->render('download/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/picture/{id}", name="picture_details", requirements={"id": "\d+"})
     */
    public function show(UploadRepository $uploadRepo, Upload $upload, Request $request, CommentRepository $commentRepository): Response
    {
        $uploadDetails = $uploadRepo->findOneById($upload);

        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $comment->setUpload($upload);              
                $comment->setUser($this->getUser());

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($comment);
                
                $upload->setUpdatedAt(new \DateTime());
                
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($upload);
                $entityManager->flush();
                
                $this->addFlash('success', 'Commentaire ajouté');
                
                return $this->redirectToRoute('picture_details', ['id' => $upload->getId()]);
            }

            $comments = $commentRepository->findBy(['upload' => $upload], ['created_at' => 'DESC']);


        return $this->render('download/detail.html.twig', [
            'uploadDetails' => $uploadDetails,
            'comments' => $comments,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/picture/{id}/edit", name="picture_edit", requirements={"id": "\d+"})
     */
    public function editPicture(Upload $upload, EntityManagerInterface $entityManager, Request $request): Response
    {
        $author = $upload->getUser();
        if ($author !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UploadEditType::class, $upload);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();

            $upload->setUpdatedAt(new DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('picture_details', ['id' => $upload->getId()]);
        }

        return $this->render('download/edit.html.twig', [
            'form' => $form->createView(),
            'upload' => $upload,
        ]);
    }

    /**
     * @Route("/picture/{id}/delete", name="picture_delete", requirements={"id": "\d+"})
     */
    public function deletePicture(Upload $upload, EntityManagerInterface $entityManager): Response
    {
        $author = $upload->getUser();
        if ($author !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($upload);
        $entityManager->flush();

        return $this->redirectToRoute('user_images');
    }
}

