<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\nombreUser;
use App\Domain\ValueObjects\EmailUser;
use App\Domain\ValueObjects\passwordUser;

class User{


    private ?int $id;

    private nombreUser $nombre;

    private EmailUser $email;

    private passwordUser $password;

        public function __construct(nombreUser $usuario , EmailUser $email, passwordUser $password ,?int $id = null){

                if($id !== null && $id<=0){
                    throw new \InvalidArgumentException("El ID del usuario debe ser mayor a cero");

                }

                $this->id=$id;
                $this->nombre=$usuario;
                $this->email=$email;
                $this->password=$password;

        }


        //Getters
    
        public function getId(): ?int{
            return $this->id;
        }

        public function getNombre(): nombreUser
        {
            return $this->nombre;
        }

        public function getEmail(): EmailUser
        {
            return $this->email;
        }

        public function getPassword(): passwordUser
        {
            return $this->password;
        }








}