<?php

require_once 'Personagem.php';
require_once 'ExcecaoJogo.php';

class Guerreiro extends Personagem {
    const CUSTO_HABILIDADE = 30;
    const REGENERACAO_PROPRIA = 8;

    public function __construct(string $nome) {
        parent::__construct($nome, 120, 25, 10, 80);
    }
       public static function getDescricao(): string {
        return "Guerreiro (Alto HP, ataque médio, habilidade: Golpe Poderoso - dano dobrado)";
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        if ($this->energiaAtual < self::CUSTO_HABILIDADE) {
            throw new EnergiaInsuficienteException();
        }
        $this->energiaAtual -= self::CUSTO_HABILIDADE;
        $vidaAntes = $alvo->getVidaAtual();
        $danoBaseNormal = max(0, $this->ataque - $alvo->getDefesaTotal());
        $danoReal = 2 * $danoBaseNormal;
        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("Golpe Poderoso", $alvo, $vidaAntes, $danoReal);
    }

    protected function getRegeneracaoEnergia(): int {
        return self::REGENERACAO_PROPRIA;
    }
}