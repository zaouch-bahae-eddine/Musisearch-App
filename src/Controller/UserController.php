<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


/**
 * @Route("/api")
 */
class UserController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(EntityManagerInterface $em, Filesystem $fs)
    {
        $this->em = $em;
        $this->fs = $fs;
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

    private function slugger($string)
    {
        return preg_replace(
            '/[^a-z0-9]/', '-', strtolower(trim(strip_tags($string)))
        );
    }

    private function stockFile($file, $type){
        $dirParameter = "";
        if($type == 'son'){
            $dirParameter = "music_directory";
        } else if ($type == 'img') {
            $dirParameter = "img_directory";
        }
        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            try {
                $file->move(
                    $this->getParameter($dirParameter),
                    $newFilename
                );
            } catch (FileException $e) {
                return $this->json(["fail" => "Probleme consernant le fichier envoyé"], 500);
            }

            return $newFilename;
        }
    }
    private function remplacerFile($file, $fileName, $type){
        $dirParameter = "";
        if($type == 'son'){
            $dirParameter = "music_directory";
        } else if ($type == 'img') {
            $dirParameter = "img_directory";
        }
        if ($file) {

            try {
                $this->fs->remove($this->getParameter($dirParameter).'/'.$fileName);
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter($dirParameter),
                    $newFilename
                );
            } catch (FileException $e) {
                return $this->json(["fail" => "Probleme consernant le fichier envoyé"], 500);
            }
        }
        return $newFilename;
    }
    //Proposer des amies :)
    /**
     * @Route("/users/sugestion", name="propser_user", methods={"GET"})
     */
    public function getAllUser(Request $request): Response
    {
        if(!$request->isXmlHttpRequest())
            return $this->render('artist/index.html.twig');

        $users = $em = $this->getDoctrine()->getManager()->getRepository(User::class)->findAll();
        $tabUser = [];
        $i = 0;

        foreach ($users as $user) {
            // $user != $this->getUser() && $user->getRole() != "ADMIN_ROLE"
            if ($this->getUser()->getId() != $user->getId()) {
                $tabUser[$i] = [
                    "id" => $user->getId(),
                    "nom" => $user->getNom(),
                    "prenom" => $user->getPrenom(),
                    "img" => $user->getImg()
                ];
                $i++;
            }
        }
        return $this->json(["users" => $tabUser],200);
    }
    //S'inscrire
    /**
     * @Route("/users/enregister", name="ajouter_users", methods={"POST"})
     */
    public function enregistrerUser(Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder) {
        //img refer to the name of input in front-end
        $file = $request->files->get("img");
        $data = $this->jsonDecode($request);
        $user = new User();
        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            try {
                $file->move(
                    $this->getParameter('img_directory'),
                    $newFilename
                );
            } catch (FileException $e) {

            }

            $user->setImg($newFilename);
        }
        $user
            ->setNom($data["nom"])
            ->setPrenom($data["prenom"])
            ->setBio($data["bio"])
            ->setMail($data["mail"])
            ->setRoles(['ROLE_CNX'])
            ->setDateCreation(new \DateTime('now'))
            ->setMdp($encoder->encodePassword($user, $data["mdp"]))
        ;
        $this->em->persist($user);
        $this->em->flush();
        return $this->json(["success" => $user->getId()]);
    }
    //Ajouter Amies :)
    /**
     * @Route("/users/abonner/{id}", name="user_abonnement", methods={"GET"})
     */
    public function abonnement(User $user): Response
    {
        if($user != null) {
            $abonnement = new Abonnement();
            $abonnement->setUser2($user);
            $this->getUser()->addAbonnement($abonnement);
            $this->em->persist($abonnement);
            $this->em->flush();
            return $this->json(["success" => [
                "newAbonnementNom" => $user->getNom(),
                "newAbonnementPrenom" => $user->getPrenom(),
            ]], 200);
        }
        return $this->json(["fail" => "Utilisateur n'existe pas !"], 404);
    }
    //Deabonnement
    /**
     * @Route("/users/deabonner/{id}", name="user_deabonnement", methods={"GET"})
     */
    public function deabonnement(User $user): Response
    {
        if($user != null) {
            $abonnement = $this->em->getRepository(Abonnement::class)->findOneBy([
                'user' => $this->getUser(),
                'user2' => $user->getId(),
                ]);
            if($abonnement != null) {
                $this->getUser()->removeAbonnement($abonnement);
                $this->em->persist($this->getUser());
                $this->em->flush();
            }
            return $this->json(["success" => [
                "newAbonnementNom" => $user->getNom(),
                "newAbonnementPrenom" => $user->getPrenom(),
            ]], 200);
        }
        return $this->json(["fail" => "Utilisateur n'existe pas !"], 404);
    }
    //Lister les abonnements
    /**
     * @Route("/users/abonnement/list", name="list_user_abonnement", methods={"GET"})
     */
    public function userAbonnementList() {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        if(!$user){
            return $this->json(['fail' => "Probleme de connexion peut etre la cause"], 500);
        }
        $abonnementList = $user->getAbonnements();
        $tabAbonnement = [];
        $i = 0;
        foreach ($abonnementList as $abonnement){
            $amie = $abonnement->getUser2();
            $tabAbonnement[$i] = [
                "id" => $amie->getId(),
                "img" => $amie->getImg(),
                "nom" => $amie->getNom(),
                "prenom" => $amie->getPrenom(),
            ];
            $i++;
        }
        return $this->json(["listAbonnement" => $tabAbonnement], 200);
    }
    /**
     * @Route("/users/profil/{id}", name="profil", methods={"GET"})
     */
    public function profilUsers(Request $request, User $user = null) {
        $mine = false;
        if(!$request->isXmlHttpRequest()){
            return $this->render('profil/index.html.twig',[
                'idUser' => $user->getId()]);
        }
        if(!$user){
            return $this->json(["fail" => "L'utilisateur que vous essayer de trouver n'existe pas !"], 400);
        }
        if ($user->getId() == $this->getUser()->getId()){
            $mine = true;
        }
        $musics = $user->getMusic();
        $tabMusic = [];
        foreach ($musics as $music){
            $favorieState = false;
            if($music->getFavoris() != null)
                $favorieState = true;
            $tabMusic[$music->getId()] = [
                "id" => $music->getId(),
                "img" => $request->getUriForPath($this->getParameter('absolut_path_img') . $music->getImg()),
                "son" => $request->getUriForPath($this->getParameter('absolut_path_son') . $music->getSon()),
                "titre" => $music->getTitre(),
                "style" => $music->getStyle()->getLibelle(),
                "styleId" => $music->getStyle()->getId(),
                "favorit" => $favorieState ,
            ];
        }
        return $this->json([
            "mine" => $mine,
            "artiste" => [
                "id" => $user->getId(),
                "img" => $request->getUriForPath($this->getParameter('absolut_path_img') . $user->getImg()),
                "nom" => $user->getNom(),
                "prenom" => $user->getPrenom(),
                "bio" => $user->getBio(),
                "mail" => $user->getMail(),
            ],
            "listMusic" =>$tabMusic
        ], 200);
    }
    /**
     * @Route("/users/seting", name="seting", methods={"GET"})
     */
    public function setingUsers() {
        return $this->render('seting/index.html.twig');
    }
    /**
     * @Route("/users/set/seting", name="set_seting", methods={"POST"})
     */
    public function setSetingUsers(Request $request, UserPasswordEncoderInterface $encoder) {
        $data = $this->jsonDecode($request);
        if(($data["mdp2"] == $data["mdp3"])
            && $encoder->isPasswordValid($this->getUser(), $data['mdp'])){
            $this->getUser()->setMdp($encoder->encodePassword($this->getUser(), $data['mdp2']));
        }
        $this->getUser()->setNom($data["nom"])
            ->setPrenom($data['prenom'])
            ->setMail($data['mail']);
        if($this->getUser()->getImg() == null){
            $this->stockFile($request->files->get('img'), 'img');
        } else {
            $this->stockFile($request->files->get('img'),$this->getUser()->getImg(), 'img');
        }
        return $this->render('seting/index.html.twig');
    }
}
