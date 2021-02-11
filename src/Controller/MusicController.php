<?php

namespace App\Controller;

use App\Entity\Favoris;
use App\Entity\Music;
use App\Entity\Style;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class MusicController
 * @package App\Controller
 * @Route("/api")
 */
class MusicController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    private $fs;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->fs = new Filesystem();
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

    /**
     * @Route("/accueil", name="accueil", methods="GET")
     */
    public function listMusic(Request $request): Response
    {
        if($request->isXmlHttpRequest()) {
            if (!$this->getUser()) {
                return $this->json(["fail" => "Un probleme de connexion peut etre la cause"]);
            }
            $tabMusic = [];
            $myMusics = $this->getUser()->getMusic();
            /**
             * @var User $user
             */
            $user = $this->getUser();
            foreach ($myMusics as $myMusic) {
                /**
                 * @var Music $myMusic
                 */
                $favorieState = false;
                if($myMusic->getFavoris() != null)
                    $favorieState = true;
                $tabMusic[$myMusic->getId()] = [
                    "id" => $myMusic->getId(),
                    "artiste" => [
                        'id' => $this->getUser()->getId(),
                        'nom' => $this->getUser()->getNom(),
                        'prenom' => $this->getUser()->getPrenom(),
                        'img' => $request->getUriForPath($this->getParameter('absolut_path_img') . $this->getUser()->getImg()),
                    ],
                    'titre' => $myMusic->getTitre(),
                    "img" => $request->getUriForPath($this->getParameter('absolut_path_img') . $myMusic->getImg()),
                    "son" => $request->getUriForPath($this->getParameter('absolut_path_son') . $myMusic->getSon()),
                    "dateCreation" => $myMusic->getDateCreation()->format('d-m-Y H:i:s'),
                    "style" => $myMusic->getStyle()->getLibelle(),
                    "favorit" => $favorieState ,
                ];
            }
            $abonemments = $this->getUser()->getAbonnements();
            foreach ($abonemments as $abonemment) {
                $user2 = $abonemment->getUser2();
                $musics = $user2->getMusic();
                foreach ($musics as $music) {
                    $favorieState = false;
                    if($music->getFavoris() != null)
                        $favorieState = true;
                    $tabMusic [$music->getId()] = [
                        "id" => $music->getId(),
                        "artiste" => [
                            'id' => $music->getUser()->getId(),
                            'nom' => $music->getUser()->getNom(),
                            'prenom' => $music->getUser()->getPrenom(),
                            'img' => $request->getUriForPath($this->getParameter('absolut_path_img') . $music->getUser()->getImg()),
                        ],
                        'titre' => $music->getTitre(),
                        "img" => $request->getUriForPath($this->getParameter('absolut_path_img') . $music->getImg()),
                        "son" => $request->getUriForPath($this->getParameter('absolut_path_son') . $music->getSon()),
                        "dateCreation" => $music->getDateCreation()->format('d-m-Y H:i:s'),
                        "style" => $music->getStyle()->getLibelle(),
                        "styleId" => $music->getStyle()->getId(),
                        "favorit" => $favorieState ,
                    ];
                }
            }
            return $this->json(["listMusicAccueil" => $tabMusic], 200);
        }
        return $this->render('accueil/index.html.twig');
    }

    /**
     * @Route("/music/ajouter", name="add_music", methods={"POST"})
     */
    public function ajouterMusic(Request $request): Response
    {
        $data = $request->request->all();
        if(!$this->getUser()){
            return $this->json(["fail" => "Un probleme de connexion peut etre la cause"]);
        }
        //son refer to the name of input in front-end
        $son = $request->files->get('son');
        //img refer to the name of input in front-end
        $img = $request->files->get('img');

        $music = new Music();
        $style = $this->em->getRepository(Style::class)->find($data["style"]);
        if($son != null){
            $sonNom = $this->stockFile($son, 'son');
        } else{
            return $this->json(['success' => 'fail notFound son']);
        }
        if($img != null){
            $imgNom = $this->stockFile($img, 'img');
        } else {

            return $this->json(['success' => 'fail notFound ilg']);
        }
        $newStyle = $this->em->getRepository(Style::class)->findOneBy(["libelle" => $data["style"]]);
        if($newStyle == null){
            $newStyle = new Style();
            $newStyle->setLibelle($data['style']);
            $this->em->persist($newStyle);
        }
        $music
            ->setUser($this->getUser())
            ->setTitre($data["titre"])
            ->setDescription($data["description"])
            ->setDateCreation(new \DateTime('now'))
            ->setStyle($newStyle)
            ->setSon($sonNom)
            ->setImg($imgNom)
        ;
        $this->em->persist($music);
        $this->em->flush();
        /* Json Resultat
             {
                success: {
                    "idMusicAjout": id
                }
             }
         */
        $json =  $this->json(["success" => ["idMusicAjouter" => $music->getId()]]);
        return $this->render('accueil/index.html.twig');
    }
    /**
     * @Route("/music/supprimer/{id}", name="delete_music", methods={"GET"})
     */
    public function supprimerMusic(Music $music): Response
    {
        dd($music);
        $this->fs->remove($this->getParameter('music_directory').'/'.$music->getSon());
        $this->fs->remove($this->getParameter('img_directory').'/'.$music->getImg());
        $music->setFavori(null);
        $this->em->remove($music);
        /*$user = $this->getUser();
        $user->removeMusic($music);
        $this->em->persist($user);*/
        try {
            $this->em->flush();
        }catch (\Exception $e){
            return $this->json(['fail' => 'probléme de conexion peut etre la cause']) ;
        }

        return $this->json(['success' => 'musique supprimée'], 200);;
    }
    /**
     * @Route("/music/modifier/{id}", name="edit_music")
     */
    public function modifierMusic(Request $request, Music $music)
    {
        $data = $request->request->all();
        $img = $request->files->get("img");
        $son = $request->files->get("son");
        $style = $this->em->getRepository(Style::class)->find($data["style"]);
        $music
            ->setTitre($data["titre"])
            ->setDescription($data["description"])
            ->setStyle($style);
        if($music->getImg() != null) {
            $this->remplacerFile($img, $music->getImg(), 'img');
        } else {
            $imgName = $this->stockFile($img, 'img');
            $music->setImg($imgName);
        }
        if($music->getSon() != null) {
            $this->remplacerFile($son, $music->getSon(), 'son');
        } else {
            $sonName = $this->stockFile($son, 'son');
            $music->setSon($sonName);
        }
        try{
            $this->em->flush();
        } catch (\Exception $e) {
            return $this->json(['fail' => 'Probleme dans de connexion peut etre la cause !']);
        }
        $this->json(["success"=> ["idMusicModifier" => $music->getId()]]);
        return $this->render('profil/index.html.twig',['id' => $this->getUser()->getId()]);
    }

    /**
     * @Route("/music/favoris", name="favoris", methods={"GET"})
     */
    public function favoris(){
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $favoris = $user->getFavoris();
        $tabFav = [];
        $i = 0;
        foreach ($favoris as $f){
            $tabFav[$i] = [
                "userFavoris" => [
                    "id" => $f->getUser()->getId(),
                    "nom" => $f->getUser()->getNom(),
                    "prenom" => $f->getUser()->getPrenom(),
                ],
                "musicFavoris" => [
                    "id" => $f->getMusic()->getId(),
                    "titre" => $f->getMusic()->getTitre(),
                    "img" => $f->getMusic()->getImg(),
                    "son" => $f->getMusic()->getSon()
                ]
            ];
            $i++;
        }
        return $this->json(["listFavoris" => $tabFav], 200);
    }
    /**
     * @Route("/music/favoris/add/{id}", name="add_favoris", methods={"POST"})
     */
    public function addFavoris(Music $music) {
        $favoris = new Favoris();
        $favoris
            ->setUser($this->getUser())
            ->setMusic($music)
            ->setDateCreation(new \DateTime('now'));
        $this->em->persist($favoris);
        $this->em->flush();

        return $this->json(["success" => "Music ajouté au favoris"], 200);
    }
    /**
     * @Route("/music/favoris/delete/{id}", name="delete_favoris", methods={"DELETE"})
     */
    public function deleteFavoris(Music $music) {

        $favoris = $music->getFavoris();
        $this->em->remove($favoris);
        try{
            $this->em->flush();
        } catch (\Exception $e){
            return $this->json(["fail" => "Un probleme de connecion peut etre la cause"]);
        }

        return $this->json(["success" => "Music supprimer des favoris"], 200);
    }
}
