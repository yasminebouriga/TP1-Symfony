<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CategorieController extends AbstractController
{
    #[Route('/categories', name: 'app_categories')]
    public function index(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();
        return $this->render('categorie/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/nouvelle', name: 'app_categorie_nouvelle')]
    public function nouvelle(Request $request, EntityManagerInterface $em): Response
    {
        $categorie = new Categorie();
        $form = $this->createFormBuilder($categorie)
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => [
                    'placeholder' => 'Ex: Technologie, Sport...',
                    'class' => 'form-control',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                ],
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => '💾 Créer la catégorie',
                'attr' => ['class' => 'btn btn-primary w-100 mt-3'],
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie "' . $categorie->getNom() . '" créée !');
            return $this->redirectToRoute('app_categories');
        }

        return $this->render('categorie/nouvelle.html.twig', [
            'formulaire' => $form,
        ]);
    }

    #[Route('/categories/{id}', name: 'app_categorie_detail')]
    public function detail(Categorie $categorie): Response
    {
        return $this->render('categorie/detail.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    // 2. تعديل فئة
    #[Route('/categories/{id}/modifier', name: 'app_categorie_modifier')]
    public function modifier(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_categorie_detail', ['id' => $categorie->getId()]);
        }

        return $this->render('categorie/modifier.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
        ]);
    }


    #[Route('/categories/{id}/supprimer', name: 'app_categorie_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
            if (!$categorie->getArticles()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle contient des articles.');
                return $this->redirectToRoute('app_categorie_detail', ['id' => $categorie->getId()]);
            }

            $em->remove($categorie);
            $em->flush();
        }

        return $this->redirectToRoute('app_categories');
    }
}
