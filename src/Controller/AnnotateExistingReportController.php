<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\Search;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AnnotateExistingReportController extends AbstractController
{
    /**
     * @Route("/annotate/existing", name="annotate_existing")
     */
    public function annotateExisting(Request $request)
    {
        return $this->render('annotate_existing.html.twig');
    }
}
