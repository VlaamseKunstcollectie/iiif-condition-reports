<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
use App\Entity\Report;
use App\Utils\CustomChoiceType;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrganisationController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/{_locale}/organisation/{id}/{action}", name="organisation", defaults={ "id"="", "action"="" })
     */
    public function organisation(Request $request, $id, $action)
    {
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('organisation', array('_locale' => $locales[0], 'id' => $id, 'action' => $action));
        }
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_USER', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        $em = $this->container->get('doctrine')->getManager();

        $organisation = new Organisation();
        if (!empty($id)) {
            $organisation = null;
            $organisations = $em->createQueryBuilder()
                ->select('o')
                ->from(Organisation::class, 'o')
                ->where('o.id = :id')
                ->setParameter('id', $id)
                ->orderBy('o.alias')
                ->getQuery()
                ->getResult();
            foreach ($organisations as $org) {
                $organisation = $org;
            }
        }
        if($action == 'delete' && !empty($id) && $organisations != null) {
            $em->remove($organisation);
            $em->flush();
            return $this->redirectToRoute('organisations');
        } else {
            $t = $this->translator;
            $roles = $this->getParameter('organisation_roles');
            $organisationRoles = [];
            foreach($roles as $role) {
                $organisationRoles[$role] = $role;
            }
            $customRole = $organisation->getRole() != null && $organisation->getRole() !== '' && !array_key_exists($organisation->getRole(), $organisationRoles) ? $organisation->getRole() : '';
            $form = $this->createFormBuilder($organisation)
                ->add('alias', TextType::class, ['required' => false, 'label' => $t->trans('Alias'), 'attr' => ['placeholder' => $t->trans('Alias of your choice (optional)')]])
                ->add('name', TextType::class, ['label' => $t->trans('Name'), 'attr' => ['placeholder' => $t->trans('Name of the organisation')]])
                ->add('role', TextType::class, ['required' => false, 'label' => $t->trans('Role')])
                ->add('logo', TextType::class, ['required' => false, 'label' => $t->trans('Logo'), 'attr' => ['placeholder' => $t->trans('URL of company logo')]])
                ->add('vat', TextType::class, ['required' => false, 'label' => $t->trans('VAT number'), 'attr' => ['placeholder' => 'BE0xxx.xxx.xxx']])
                ->add('address', TextType::class, ['required' => false, 'label' => $t->trans('Adress'), 'attr' => ['placeholder' => $t->trans('Street + house number')]])
                ->add('postal', TextType::class, ['required' => false, 'label' => $t->trans('Postal code'), 'attr' => ['placeholder' => $t->trans('Ex. 9000')]])
                ->add('city', TextType::class, ['required' => false, 'label' => $t->trans('City'), 'attr' => ['placeholder' => $t->trans('Ex. Ghent')]])
                ->add('country', TextType::class, ['required' => false, 'label' => $t->trans('Country'), 'attr' => ['placeholder' => $t->trans('Ex. Belgium')]])
                ->add('email', TextType::class, ['required' => false, 'label' => $t->trans('E-mail'), 'attr' => ['placeholder' => $t->trans('contact@example.com')]])
                ->add('website', TextType::class, ['required' => false, 'label' => $t->trans('Website'), 'attr' => ['placeholder' => $t->trans('https://www.example.com')]])
                ->add('phone', TextType::class, ['required' => false, 'label' => $t->trans('Telephone'), 'attr' => ['placeholder' => 'xxx xx.xx.xx']])
                ->add('mobile', TextType::class, ['required' => false, 'label' => $t->trans('Cell phone'), 'attr' => ['placeholder' => 'xxxx xx.xx.xx']])
                ->add('notes', TextareaType::class, ['required' => false, 'label' => $t->trans('Notes'), 'attr' => ['placeholder' => $t->trans('Own notes about this organisation'), 'oninput' => 'fixTextareaheight()']])
                ->add('submit', SubmitType::class, ['label' => $t->trans('Save')])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                if (empty($formData->getAlias())) {
                    $formData->setAlias($formData->getName());
                }
                $em->persist($formData);
                $em->flush();
                return $this->redirectToRoute('organisations');
            } else {
                $translatedRoutes = array();
                foreach($locales as $l) {
                    $translatedRoutes[] = array(
                        'lang' => $l,
                        'url' => $this->generateUrl('organisation', array('_locale' => $l, 'id' => $id, 'action' => $action)),
                        'active' => $l === $locale
                    );
                }

                return $this->render('organisation.html.twig', [
                    'current_page' => 'organisations',
                    'new' => empty($id),
                    'form' => $form->createView(),
                    'organisation_roles' => $organisationRoles,
                    'role' => $organisation->getRole(),
                    'custom_role' => $customRole,
                    'translated_routes' => $translatedRoutes
                ]);
            }
        }
    }
}
