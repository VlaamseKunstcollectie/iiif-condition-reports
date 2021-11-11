<?php

namespace App\Controller;

use App\Entity\Image;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DownloadController extends AbstractController
{
    /**
     * @Route("/{_locale}/download", name="download")
     */
    public function download(Request $request)
    {
        $image = $request->get('image');
        $type = exif_imagetype($image);
        $mimes  = array(
            IMAGETYPE_JPEG => "jpg",
            IMAGETYPE_PNG => "png",
            IMAGETYPE_PSD => "psd",
            IMAGETYPE_BMP => "bmp",
            IMAGETYPE_TIFF_II => "tif",
            IMAGETYPE_TIFF_MM => "tif",
            IMAGETYPE_JPC => "jpc",
            IMAGETYPE_JP2 => "jp2",
            IMAGETYPE_JPX => "jpx",
            IMAGETYPE_JB2 => "jb2",
            IMAGETYPE_SWC => "swc",
            IMAGETYPE_IFF => "iff",
            IMAGETYPE_WBMP => "wbmp",
            IMAGETYPE_XBM => "xbm",
            IMAGETYPE_ICO => "ico"
        );

        if(array_key_exists($type, $mimes)) {
            $extension = $mimes[$type];
            $folder = 'annotation_images';
            $filenameNoExt = round(microtime(true) * 1000);
            $filename = $folder . '/' . $filenameNoExt . '.' . $extension;
            $thumbnail = $folder . '/' . $filenameNoExt . '_thm.jpg';
            copy($image, $filename);

            $image = new Image();
            $image->setImage('/' . $filename);
            $image->setThumbnail('/' . $thumbnail);
            $em = $this->container->get('doctrine')->getManager();
            $em->getConnection()->getConfiguration()->setSQLLogger(null);
            $em->persist($image);
            $em->flush();

            IIIFUtil::generateThumbnail($filename, $thumbnail);
            $response = new Response(json_encode(array('hash' => $image->getHash(), 'image' => '/' . $filename, 'thumbnail' => '/' . $thumbnail)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            $response = new Response(json_encode(array('hash' => null)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
}
