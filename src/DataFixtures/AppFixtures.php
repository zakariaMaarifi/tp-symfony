<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Coach;
use App\Entity\Programme;
use App\Entity\Seance;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $users = [];
        $usersData = [
            ['Zakaria', 'Maarifi', 'zakmaarifi@gmail.com', ['ROLE_ADMIN'], false, true],
            ['Anas', 'Maarifi', 'anas@example.com', ['ROLE_USER'], false, false],
            ['Aymane', 'Chkiri', 'aymane@example.com', ['ROLE_USER'], false, true],
            ['Mohamed', 'Maarifi', 'mohamed@example.com', ['ROLE_BANNED'], true, true],
            ['Yahya', 'Chkiri', 'yahya@example.com', ['ROLE_USER'], false, false],
            ['Niama', 'Fakhri', 'niama@example.com', ['ROLE_USER'], false, false],
        ];

        foreach ($usersData as [$prenom, $nom, $email, $roles, $isBanned, $isCoach]) {
            $user = new User();
            $user->setEmail($email);
            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setRoles($roles);
            $user->setIsBanned($isBanned);
            $user->setIsCoach($isCoach);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $users[$email] = $user;
        }

        $coachs = [];
        foreach ($users as $user) {
            if ($user->isCoach()) {
                $coach = new Coach();
                $coach->setNom($user->getNom());
                $coach->setPrenom($user->getPrenom());
                $coach->setSpecialite("Fitness");
                $coach->setUser($user);
                $manager->persist($coach);
                $coachs[$user->getEmail()] = $coach;
            }
        }

        $programmes = [];
        $programmeData = [
            ['Programme 1', 'Description 1', 'zakmaarifi@gmail.com'],
            ['Programme 2', 'Description 2', 'aymane@example.com'],
            ['Programme 3', 'Description 3', 'mohamed@example.com'],
        ];
        foreach ($programmeData as [$titre, $description, $coachEmail]) {
            $programme = new Programme();
            $programme->setTitre($titre);
            $programme->setDescription($description);
            $programme->setCoach($coachs[$coachEmail]);
            $manager->persist($programme);
            $programmes[] = $programme;
        }

        foreach ($programmes as $programme) {
            for ($i = 1; $i <= 3; $i++) {
                $seance = new Seance();
                $seance->setDate((new \DateTime())->modify("+$i day"));
                $seance->setStatut('disponible');
                $seance->setUser($users['anas@example.com']);
                $seance->setProgramme($programme);
                $manager->persist($seance);
            }
        }

        $manager->flush();
    }
}

