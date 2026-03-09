<?php

interface AcaoCombate {
    public function executar(Personagem $alvo): string;
}