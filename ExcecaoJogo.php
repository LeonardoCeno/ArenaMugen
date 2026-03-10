<?php

class ExcecaoJogo extends Exception {}

class EnergiaInsuficienteException extends ExcecaoJogo {
    public function __construct() {
        parent::__construct("Energia insuficiente para usar a habilidade especial.");
    }
}

class EntradaInvalidaException extends ExcecaoJogo {
    public function __construct() {
        parent::__construct("Entrada inválida. Escolha uma ação válida.");
    }
}

class AtaqueBloqueadoException extends ExcecaoJogo {
    public function __construct() {
        parent::__construct("Ataque bloqueado pelo Infinito. Use outra ação neste turno.");
    }
}