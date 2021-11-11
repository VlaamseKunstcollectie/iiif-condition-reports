<?php

namespace App\Controller;

use App\Entity\Image;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    /**
     * @Route("/{_locale}/upload", name="upload")
     */
    public function upload(Request $request)
    {
        $file = $request->files->get('annotate-file');
        if($file == null) {
            $response = new Response(json_encode(array('hash' => null)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            $extension = $file->guessClientExtension();
            if ($extension == null) {
                $extension = 'jpg';
            }
            $folder = 'annotation_images';
            $filenameNoExt = round(microtime(true) * 1000);
            $filename = $folder . '/' . $filenameNoExt . '.' . $extension;

            $image = new Image();
            $image->setImage('/' . $filename);
            $thumbnail = $folder . '/' . $filenameNoExt . '_thm.jpg';
            $file->move($folder, $filenameNoExt . '.' . $extension);
            IIIFUtil::generateThumbnail($filename, $thumbnail);
            $image->setThumbnail('/' . $thumbnail);

            $em = $this->container->get('doctrine')->getManager();
            $em->getConnection()->getConfiguration()->setSQLLogger(null);
            $em->persist($image);
            $em->flush();

            $response = new Response(json_encode(array('hash' => $image->getHash(), 'image' => $image->getImage(), 'thumbnail' => $image->getThumbnail())));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
}
