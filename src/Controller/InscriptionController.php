<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InscriptionController extends AbstractController
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
     * @param Request $request
     * @return mixed
     */
    private function jsonDecode(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }
        return $data;
    }
    /**
     * @Route("/inscription", name="inscription")
     */
    public function inscription(Request $request, UserPasswordEncoderInterface $encoder, ValidatorInterface $validator ): Response
    {
        if(!$request->isXmlHttpRequest())
            return $this->render('inscription/index.html.twig');

        $data = $this->jsonDecode($request);

        $newUser = new User();
        $newUser
            ->setNom($data["nom"])
            ->setPrenom($data['prenom'])
            ->setRoles(['ROLE_CNX'])
            ->setDateCreation(new \DateTime('now'))
            ->setMail($data["mail"])
            ->setPassword($encoder->encodePassword($newUser, $data["mdp"]));

        $this->em->persist($newUser);
        $this->em->flush();
        $token = new UsernamePasswordToken($newUser, null, 'main', $newUser->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        $this->container->get('session')->set('_security_main', serialize($token));
        return $this->json(["success" => "you are connected"]);
    }

}
