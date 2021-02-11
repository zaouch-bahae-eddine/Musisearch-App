<?php

namespace App\Controller;

use App\Entity\Style;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StyleController
 * @package App\Controller
 * @Route("/api")
 */
class StyleController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/style", name="style", methods={"GET"})
     */
    public function index(): Response
    {
        if(!$this->getUser()){
            return $this->json(["fail" => "Un probleme de connexion peut etre la cause"]);
        }
        $styles = $this->em->getRepository(Style::class)->findAll();
        $tabStyles = [];
        $i = 0;
        foreach ($styles as $style) {
            $tabStyles[$i] = [
                "id" => $style->getId(),
                "libelle" => $style->getLibelle()
            ];
            $i++;
        }
        return $this->json(["styles" => $tabStyles]);
    }
}
