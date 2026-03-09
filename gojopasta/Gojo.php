<?php

require_once 'Personagem.php';
require_once 'ExcecaoJogo.php';

class Gojo extends Personagem {

    const CUSTO_REVERSE = 60;
    const CUSTO_VAZIO_ROXO = 40;
    const CUSTO_AZUL = 25;
    const REGENERACAO_PROPRIA = 35;

    public function __construct(string $nome) {
        parent::__construct($nome, 200, 15, 5, 1000);
    }

    public static function getDescricao(): string {
        return "Gojo (HP alto, energia muito alta, habilidades: Azul, Vazio Roxo e Reverse Energy)";
    }

    public function vazioRoxo(Personagem $alvo): string {

        if ($this->energiaAtual < self::CUSTO_VAZIO_ROXO) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_VAZIO_ROXO;

        $danoReal = $this->ataque * 5; // ignora defesa
        $vidaAntes = $alvo->getVidaAtual();

        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("Vazio Roxo", $alvo, $vidaAntes, $danoReal);
    }

    public function azul(Personagem $alvo): string {

        if ($this->energiaAtual < self::CUSTO_AZUL) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_AZUL;

        $danoBase = max(0, $this->ataque - $alvo->getDefesaTotal());

        $danoReal = $danoBase * 2;
        $vidaAntes = $alvo->getVidaAtual();

        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("Azul", $alvo, $vidaAntes, $danoReal);
    }

    public function reverseEnergy(): string {

        if ($this->energiaAtual < self::CUSTO_REVERSE) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_REVERSE;

        $cura = 50;

        $this->vidaAtual += $cura;

        if ($this->vidaAtual > $this->vidaMaxima) {
            $this->vidaAtual = $this->vidaMaxima;
        }

        return $this->formatarMensagemAcaoSemAlvo("Reverse Energy");
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        return $this->vazioRoxo($alvo);
    }

    public function getHabilidades(): array {

        return [

            [
                "nome" => "Azul",
                "metodo" => "azul",
                "precisaAlvo" => true
            ],

            [
                "nome" => "Vazio Roxo",
                "metodo" => "vazioRoxo",
                "precisaAlvo" => true
            ],

            [
                "nome" => "Reverse Energy",
                "metodo" => "reverseEnergy",
                "precisaAlvo" => false
            ]

        ];
    }

    protected function getRegeneracaoEnergia(): int {
        return self::REGENERACAO_PROPRIA;
    }
}