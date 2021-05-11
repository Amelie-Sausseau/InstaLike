<?php

namespace App\Controller;

use App\Entity\Upload;
use App\Entity\User;
use App\Form\UserEditType;
use App\Repository\UploadRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function userIndex(): Response
    {
        $user = $this->getUser();

        $comments = $user->getComments();

        return $this->render('user/user.html.twig', [
            'controller_name' => 'UserController',
            'user' => $user,
            'comments' => $comments,
        ]);
    }

    /**
     * @Route("/user/edit", name="user_edit")
     */
    public function edit(Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();

        $form = $this->createForm(UserEditType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();

            $avatar = $form->get('avatar')->getData();

            if ($avatar) {
                $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$avatar->guessExtension();

                try {
                    $avatar->move(
                        $this->getParameter('avatars_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
        
                }

                $user->setAvatar($newFilename);
            }

            $user->setUpdatedAt(new DateTime());
            $entityManager->flush();
            $this->addFlash('success', 'Profil modifié.');

            return $this->redirectToRoute('user');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/mypics", name="user_images")
     */
    public function userImagesList(UploadRepository $uploadRepository): Response
    {
        $user = $this->getUser();

        $userUploads = $uploadRepository->findBy(['user' => $user], ['created_at' => 'DESC']);

        return $this->render('user/mypics.html.twig', [
            'controller_name' => 'UserController',
            'userUploads' => $userUploads,
            'user' => $user,
        ]);
    }

}
