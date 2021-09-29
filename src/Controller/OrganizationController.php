<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organization;
use App\Entity\Report;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationController extends AbstractController
{
    /**
     * @Route("/organization/{id}/{action}", name="organization", defaults={ "id"="", "action"="" })
     */
    public function organization(Request $request, $id, $action)
    {
        $em = $this->container->get('doctrine')->getManager();

        $organization = new Organization();
        if (!empty($id)) {
            $organization = null;
            $organizations = $em->createQueryBuilder()
                ->select('o')
                ->from(Organization::class, 'o')
                ->where('o.id = :id')
                ->setParameter('id', $id)
                ->orderBy('o.alias')
                ->getQuery()
                ->getResult();
            foreach ($organizations as $org) {
                $organization = $org;
            }
        }
        if($action == 'delete' && !empty($id) && $organizations != null) {
            $em->remove($organization);
            $em->flush();
            return $this->redirectToRoute('organizations');
        } else {
            $form = $this->createFormBuilder($organization)
                ->add('alias', TextType::class, ['required' => false, 'label' => 'Alias', 'attr' => ['placeholder' => 'Zelfgekozen alias (optioneel)']])
                ->add('name', TextType::class, ['label' => 'Naam', 'attr' => ['placeholder' => 'Naam van de organisatie']])
                ->add('function', TextType::class, ['required' => false, 'label' => 'Functie', 'attr' => ['placeholder' => 'Bv. eigen organisatie, bruikleennemer, koerier, ...']])
                ->add('logo', TextType::class, ['required' => false, 'label' => 'Logo', 'attr' => ['placeholder' => 'URL van bedrijfslogo']])
                ->add('vat', TextType::class, ['required' => false, 'label' => 'BTW-nummer', 'attr' => ['placeholder' => 'BE0xxx.xxx.xxx']])
                ->add('address', TextType::class, ['required' => false, 'label' => 'Adres', 'attr' => ['placeholder' => 'Straat + huisnummer']])
                ->add('postal', TextType::class, ['required' => false, 'label' => 'Postcode', 'attr' => ['placeholder' => 'Bv. 9000']])
                ->add('city', TextType::class, ['required' => false, 'label' => 'Gemeente', 'attr' => ['placeholder' => 'Bv. Gent']])
                ->add('country', TextType::class, ['required' => false, 'label' => 'Land', 'attr' => ['placeholder' => 'Bv. België']])
                ->add('email', TextType::class, ['required' => false, 'label' => 'E-mail', 'attr' => ['placeholder' => 'contact@voorbeeld.com']])
                ->add('website', TextType::class, ['required' => false, 'label' => 'Website', 'attr' => ['placeholder' => 'https://www.example.com']])
                ->add('phone', TextType::class, ['required' => false, 'label' => 'Telefoon', 'attr' => ['placeholder' => 'xxx xx.xx.xx']])
                ->add('mobile', TextType::class, ['required' => false, 'label' => 'GSM', 'attr' => ['placeholder' => 'xxxx xx.xx.xx']])
                ->add('notes', TextareaType::class, ['required' => false, 'label' => 'Notities', 'attr' => ['placeholder' => 'Eigen notities over deze organisatie']])
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
                return $this->redirectToRoute('organizations');
            } else {
                return $this->render('organization.html.twig', [
                    'current_page' => 'organizations',
                    'form' => $form->createView()
                ]);
            }
        }
    }
}
