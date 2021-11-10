<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
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
use Symfony\Contracts\Translation\TranslatorInterface;

class RepresentativeController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/{_locale}/representative/{id}/{action}", name="representative", defaults={ "id"="", "action"="" })
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
            foreach ($representatives as $rep) {
                $representative = $rep;
            }
        }

        if($action == 'delete' && !empty($id) && $representative != null) {
            $em->remove($representative);
            $em->flush();
            return $this->redirectToRoute('representatives');
        } else {
            $organisationNames = [
                '' => ''
            ];
            $orgs = $em->createQueryBuilder()
                ->select('o')
                ->from(Organisation::class, 'o')
                ->orderBy('o.alias')
                ->getQuery()
                ->getResult();
            foreach ($orgs as $org) {
                $organisationNames[$org->alias] = $org->id;
            }

            $t = $this->translator;
            $form = $this->createFormBuilder($representative)
                ->add('organisation', ChoiceType::class, ['choices' => $organisationNames, 'label' => $t->trans('Organisatie')])
                ->add('alias', TextType::class, ['required' => false, 'label' => $t->trans('Alias'), 'attr' => ['placeholder' => $t->trans('Alias of your choice (optional)')]])
                ->add('name', TextType::class, ['label' => $t->trans('Name'), 'attr' => ['placeholder' => $t->trans('Name of the representative')]])
                ->add('role', TextType::class, ['required' => false, 'label' => $t->trans('Role'), 'attr' => ['placeholder' => $t->trans('Ex. restorer, courier ...')]])
                ->add('email', TextType::class, ['required' => false, 'label' => $t->trans('E-mail'), 'attr' => ['placeholder' => $t->trans('contact@example.com')]])
                ->add('phone', TextType::class, ['required' => false, 'label' => $t->trans('Telephone'), 'attr' => ['placeholder' => 'xxx xx.xx.xx']])
                ->add('notes', TextareaType::class, ['required' => false, 'label' => $t->trans('Notes'), 'attr' => ['placeholder' => $t->trans('Own notes about this person'), 'oninput' => 'fixTextareaheight()']])
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
                return $this->redirectToRoute('representatives');
            } else {
                $locale = $request->get('_locale');
                $locales = $this->getParameter('locales');
                $translatedRoutes = array();
                foreach($locales as $l) {
                    $translatedRoutes[] = array(
                        'lang' => $l,
                        'url' => $this->generateUrl('representative', array('_locale' => $l, 'id' => $id, 'action' => $action)),
                        'active' => $l === $locale
                    );
                }

                return $this->render('representative.html.twig', [
                    'current_page' => 'representatives',
                    'new' => empty($id),
                    'form' => $form->createView(),
                    'translated_routes' => $translatedRoutes
                ]);
            }
        }
    }
}
