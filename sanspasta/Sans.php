<?php

require_once 'Personagem.php';
require_once 'ExcecaoJogo.php';

class Sans extends Personagem {

    const CUSTO_BLASTER = 50;
    const CUSTO_PAREDE_OSSOS = 40;
    const REGENERACAO_PROPRIA = 0;

    public function __construct(string $nome) {
        parent::__construct($nome, 1, 30, 1, 200);
    }

    public static function getDescricao(): string {

        return "Sans (HP baixíssimo, ataque alto, defesa baixa, passiva: esquiva usando energia, habilidades: Blaster e Parede de Ossos)";
    }

    public function receberDano(int $danoReal): void {
        if ($this->energiaAtual > 0) {
            $this->energiaAtual -= $danoReal;

            if ($this->energiaAtual < 0) {
                $this->energiaAtual = 0;
            }

            return;
        }

        parent::receberDano($danoReal);
    }

    public function blaster(Personagem $alvo): string {

        if ($this->energiaAtual < self::CUSTO_BLASTER) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_BLASTER;
        $vidaAntes = $alvo->getVidaAtual();

        $danoBase = max(0, $this->ataque - $alvo->getDefesaTotal());

        $danoReal = $danoBase * 3;

        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("Blaster", $alvo, $vidaAntes, $danoReal);
    }

    public function paredeDeOssos(Personagem $alvo): string {

        if ($this->energiaAtual < self::CUSTO_PAREDE_OSSOS) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_PAREDE_OSSOS;
        $vidaAntes = $alvo->getVidaAtual();

        $danoBase = max(0, $this->ataque - $alvo->getDefesaTotal());

        $danoReal = $danoBase * 2;

        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("Parede de Ossos", $alvo, $vidaAntes, $danoReal);
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        return $this->blaster($alvo);
    }

    public function getHabilidades(): array {

        return [
            [
                "nome" => "Blaster",
                "metodo" => "blaster",
                "precisaAlvo" => true
            ],
            [
                "nome" => "Parede de Ossos",
                "metodo" => "paredeDeOssos",
                "precisaAlvo" => true
            ]
        ];
    }

    protected function getRegeneracaoEnergia(): int {
        return self::REGENERACAO_PROPRIA;
    }
}