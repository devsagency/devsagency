<?php

namespace App\Controller;

use Pam\Controller\MainController;
use Pam\Model\Factory\ModelFactory;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class MembersController
 * @package App\Controller
 */
class MembersController extends MainController
{
    /**
     * @var array
     */
    private $member = [];

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function defaultMethod()
    {
        $allMembers = ModelFactory::getModel("Members")->listData();

        return $this->render("front/members.twig", ["allMembers" => $allMembers]);
    }

    private function setMemberData()
    {
        $this->member["name"]       = (string) trim($this->getPost()->getPostVar("name"));
        $this->member["email"]      = (string) trim($this->getPost()->getPostVar("email"));
        $this->member["linkedin"]   = (string) trim($this->getPost()->getPostVar("linkedin"));
        $this->member["github"]     = (string) trim($this->getPost()->getPostVar("github"));

        $this->member["website"]    = (string) trim($this->getPost()->getPostVar("website"));
        $this->member["website"]    = str_replace("https://", "", $this->member["website"]);

        $this->member["position"]       = (string) trim($this->getPost()->getPostVar("position"));
        $this->member["city"]           = (string) trim($this->getPost()->getPostVar("city"));
        $this->member["presentation"]   = (string) trim($this->getPost()->getPostVar("presentation"));
    }

    private function setMemberImage()
    {
        $this->member["image"] = $this->getString()->cleanString($this->member["name"]) . $this->getFiles()->setFileExtension();

        $this->getFiles()->uploadFile("img/members/", $this->getString()->cleanString($this->member["name"]));
        $this->getImage()->makeThumbnail("img/members/" . $this->member["image"], 200);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function createMethod()
    {
        if ($this->getSecurity()->checkIsAdmin() !== true) {
            $this->redirect("home");
        }

        if (!empty($this->getPost()->getPostArray())) {
            $this->setMemberData();
            $this->setMemberImage();

            if ($this->getPost()->getPostVar("pass") !== $this->getPost()->getPostVar("conf-pass")) {
                $this->getSession()->createAlert("Les Mots de Passe ne Correspondent pas !", "red");

                $this->redirect("members!create");
            }

            $this->member["pass"] = password_hash($this->getPost()->getPostVar("pass"), PASSWORD_DEFAULT);

            ModelFactory::getModel("Members")->createData($this->member);
            $this->getSession()->createAlert("Nouveau Membre Créé avec Succès !", "green");

            $this->redirect("admin");
        }

        return $this->render("back/members/createMember.twig");
    }

    private function setUpdatePassword()
    {
        $member = ModelFactory::getModel("Members")->readData($this->getGet()->getGetVar("id"));

        if (!password_verify($this->getPost()->getPostVar("old-pass"), $member["pass"])) {
            $this->getSession()->createAlert("L'Ancien Mot de Passe est Incorrect !", "red");

            $this->redirect("admin");
        }

        if ($this->getPost()->getPostVar("new-pass") !== $this->getPost()->getPostVar("conf-pass")) {
            $this->getSession()->createAlert("Les Nouveaux Mots de Passe ne Correspondent pas !", "red");

            $this->redirect("admin");
        }

        $this->member["pass"] = password_hash($this->getPost()->getPostVar("new-pass"), PASSWORD_DEFAULT);
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function updateMethod()
    {
        if ($this->getSecurity()->checkIsAdmin() !== true) {
            $this->redirect("home");
        }

        if (!empty($this->getPost()->getPostArray())) {
            $this->setMemberData();

            if (!empty($this->getFiles()->getFileVar("name"))) {
                $this->setMemberImage();
            }

            if (!empty($this->getPost()->getPostVar("old-pass"))) {
                $this->setUpdatePassword();
            }

            ModelFactory::getModel("Members")->updateData($this->getGet()->getGetVar("id"), $this->member);
            $this->getSession()->createAlert("Modification du Membre Sélectionné Effectuée !", "blue");

            $this->redirect("admin");
        }

        $member = ModelFactory::getModel("Members")->readData($this->getGet()->getGetVar("id"));

        return $this->render("back/members/updateMember.twig", ["member" => $member]);
    }

    public function deleteMethod()
    {
        if ($this->getSecurity()->checkIsAdmin() !== true) {
            $this->redirect("home");
        }

        ModelFactory::getModel("Members")->deleteData($this->getGet()->getGetVar("id"));
        $this->getSession()->createAlert("Membre Supprimé !", "red");

        $this->redirect("admin");
    }
}
