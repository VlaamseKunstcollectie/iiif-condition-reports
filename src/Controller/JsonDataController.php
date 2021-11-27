<?php

namespace App\Controller;

use App\Entity\Image;
use App\Utils\IIIFUtil;
use App\Utils\ReportTemplateData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class JsonDataController extends AbstractController
{
    /**
     * @Route("/{_locale}/data/{id}.json", name="json_data")
     */
    public function jsonData(Request $request, $id)
    {
        $em = $this->get('doctrine')->getManager();
        $jsonData = ReportTemplateData::getJsonData($em, $id);

        $headers = array('Content-Type' => 'application/json');
        return new Response(json_encode($jsonData, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE), 200, $headers);
    }
}
