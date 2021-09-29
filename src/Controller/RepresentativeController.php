<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organization;
use App\Entity\Report;
use App\Entity\Representative;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RepresentativeController extends AbstractController
{
    /**
     * @Route("/representative/{id}/{action}", name="representative", defaults={ "id"="", "action"="" })
     */
    public function representative(Request $request, $id, $action)
    {
        $em = $this->container->get('doctrine')->getManager();

        $representative = new Representative();
        if (!empty($id)) {
            $representative = null;
            $representatives = $em->createQueryBuilder()
                ->select('r')
                ->from(Representative::class, 'r')
                ->where('r.id = :id')
                ->setParameter('id', $id)
                ->orderBy('r.alias')
                ->getQuery()
                ->getResult();
            foreach ($representatives as $org) {
                $representative = $org;
            }
        }

        if($action == 'delete' && !empty($id) && $representative != null) {
            $em->remove($representative);
            $em->flush();
            return $this->redirectToRoute('representatives');
        } else {
            $form = $this->createFormBuilder($representative)
                ->add('alias', TextType::class, ['required' => false, 'label' => 'Alias', 'attr' => ['placeholder' => 'Zelfgekozen alias (optioneel)']])
                ->add('name', TextType::class, ['label' => 'Naam'])
                ->add('function', TextType::class, ['required' => false, 'label' => 'Functie', 'attr' => ['placeholder' => 'Bv. restaurateur, koerier, ...']])
                ->add('email', TextType::class, ['required' => false, 'label' => 'E-mail', 'attr' => ['placeholder' => 'contact@voorbeeld.com']])
                ->add('phone', TextType::class, ['required' => false, 'label' => 'Telefoon', 'attr' => ['placeholder' => 'xxx xx.xx.xx']])
                ->add('notes', TextareaType::class, ['required' => false, 'label' => 'Notities', 'attr' => ['placeholder' => 'Eigen notities over deze persoon']])
                ->add('submit', SubmitType::class, ['label' => 'Opslaan'])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                if (empty($formData->getAlias())) {
                    $formData->setAlias($formData->getName());
                }
                $em->persist($formData);
                $em->flush();
                return $this->redirectToRoute('representatives');
            } else {
                return $this->render('representative.html.twig', [
                    'current_page' => 'representatives',
                    'form' => $form->createView()
                ]);
            }
        }
    }
}
