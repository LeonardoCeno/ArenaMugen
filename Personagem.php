<?php

abstract class Personagem {

    protected string $nome;
    protected int $vidaMaxima;
    protected int $vidaAtual;
    protected int $ataque;
    protected int $defesa;
    protected int $energiaMaxima;
    protected int $energiaAtual;

    protected bool $defendendo = false;
    protected int $bonusDefesaTemporario = 0;

    const REGENERACAO_ENERGIA = 10;

    public function __construct(string $nome, int $vida, int $ataque, int $defesa, int $energia) {

        $this->nome = $nome;

        $this->vidaMaxima = $vida;
        $this->vidaAtual = $vida;

        $this->ataque = $ataque;
        $this->defesa = $defesa;

        $this->energiaMaxima = $energia;
        $this->energiaAtual = $energia;
    }

    public function getNome(): string {
        return $this->nome;
    }

    public function getVidaAtual(): int {
        return $this->vidaAtual;
    }

    public function getVidaMaxima(): int {
        return $this->vidaMaxima;
    }

    public function getEnergiaAtual(): int {
        return $this->energiaAtual;
    }

    public function getEnergiaMaxima(): int {
        return $this->energiaMaxima;
    }

    public function getDefesaTotal(): int {
        return $this->defesa + ($this->defendendo ? $this->bonusDefesaTemporario : 0);
    }

    public function estaVivo(): bool {
        return $this->vidaAtual > 0;
    }

    public function receberDano(int $danoReal): void {

        $this->vidaAtual -= $danoReal;

        if ($this->vidaAtual < 0) {
            $this->vidaAtual = 0;
        }
    }

    public function regenerarEnergia(): void {

        $this->energiaAtual = min(
            $this->energiaMaxima,
            $this->energiaAtual + $this->getRegeneracaoEnergia()
        );
    }

    protected function getRegeneracaoEnergia(): int {
        return self::REGENERACAO_ENERGIA;
    }

    public function iniciarTurno(): void {

        $this->defendendo = false;
        $this->bonusDefesaTemporario = 0;

        $this->regenerarEnergia();
    }

    public function atacar(Personagem $alvo): string {
        if ($alvo->tentouDesviarAtaque()) {
            return "{$this->nome} usou Ataque em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        $vidaAntes = $alvo->getVidaAtual();
        $danoReal = max(0, $this->ataque - $alvo->getDefesaTotal());
        $foiCritico = $this->tentouCriticoAtaque();

        if ($foiCritico) {
            $danoReal = (int) ceil($danoReal * 1.5);
        }

        $alvo->receberDano($danoReal);

        $mensagem = $this->formatarMensagemAcaoComAlvo("Ataque", $alvo, $vidaAntes, $danoReal);

        if ($foiCritico && $danoReal > 0) {
            $mensagem .= " Acerto crítico!";
        }

        return $mensagem;
    }

    protected function tentouDesviarAtaque(): bool {
        return random_int(1, 100) <= 5;
    }

    protected function tentouCriticoAtaque(): bool {
        return random_int(1, 100) <= 2;
    }

    protected function formatarMensagemAcaoComAlvo(
        string $nomeAcao,
        Personagem $alvo,
        int $vidaAntes,
        int $danoReal
    ): string {
        $vidaDepois = $alvo->getVidaAtual();

        if ($vidaDepois === $vidaAntes || $danoReal <= 0) {
            return "{$this->nome} usou {$nomeAcao} em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        return "{$this->nome} usou {$nomeAcao} em {$alvo->getNome()}, causando {$danoReal} de dano.";
    }

    protected function formatarMensagemAcaoSemAlvo(string $nomeAcao): string {
        return "{$this->nome} usou {$nomeAcao}.";
    }

    public function defender(): string {

        $this->defendendo = true;

        $this->bonusDefesaTemporario = 5;

        return $this->formatarMensagemAcaoSemAlvo("Defesa");
    }

    public function getHabilidades(): array {

        return [
            [
                "nome" => "Habilidade Especial",
                "metodo" => "usarHabilidadeEspecial",
                "precisaAlvo" => true
            ]
        ];
    }

    abstract public function usarHabilidadeEspecial(Personagem $alvo): string;

    abstract public static function getDescricao(): string;
}